<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Debt;
use App\Models\ItemCategory;
use App\Models\ItemPriceStock;
use App\Models\PaymentMethod;
use App\Models\PurchaseTransaction;
use App\Models\PurchaseTransactionItem;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FormatHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchaseTransactionService
{
    /**
     * Create a new purchase transaction with atomic stock update and ledger logging.
     *
     * @throws Exception
     */
    public function createPurchase(array $data, User $user): PurchaseTransaction
    {
        return DB::transaction(function () use ($data, $user) {
            $date = isset($data['transaction_date']) ? Carbon::parse($data['transaction_date']) : Carbon::now();
            $invoiceNumber = FormatHelper::generatePurchaseInvoiceNumber($date);

            // Supplier resolution (Task 22: auto-save if not existing)
            $supplierId = $data['supplier_id'] ?? null;
            if ($supplierId === '__new__' || $supplierId === '__cash__' || empty($supplierId)) {
                $supplierId = null;
            }
            $supplier = null;
            $supplierName = $data['supplier_name'] ?? null;
            $supplierPhone = $data['supplier_phone'] ?? null;
            $supplierAddress = $data['supplier_address'] ?? null;

            // Bank details extracted early for supplier / user bank account synchronization (Task 10)
            $customBank = trim($data['custom_bank'] ?? '');
            $customAccountName = trim($data['custom_account_name'] ?? '');
            $customAccountNo = trim($data['custom_account_no'] ?? '');

            if ($supplierId) {
                $supplier = Supplier::find($supplierId);
                if ($supplier) {
                    $supplierName = $supplier->name;
                    $supplierPhone = $supplier->phone ?? $supplierPhone;
                    $supplierAddress = $supplier->address ?? $supplierAddress;
                }
            } elseif (!empty($supplierName) && !in_array(trim(strtolower($supplierName)), ['umum', 'umum / tunai', 'tunai', ''])) {
                $trimmedName = trim($supplierName);
                $supplier = Supplier::where('name', $trimmedName)->first();
                if (!$supplier) {
                    $supplier = Supplier::create([
                        'code' => 'SUP-' . strtoupper(substr(uniqid(), -5)),
                        'name' => $trimmedName,
                        'phone' => $supplierPhone,
                        'address' => $supplierAddress,
                        'bank_name' => $customBank ?: null,
                        'bank_account_number' => $customAccountNo ?: null,
                        'bank_account_name' => $customAccountName ?: null,
                    ]);
                }
                $supplierId = $supplier->id;
                $supplierName = $supplier->name;
            } else {
                $supplierName = !empty($supplierName) ? trim($supplierName) : null;
            }

            // Sync bank account to Supplier if provided and requested
            if ($supplier) {
                $hasBankInput = !empty($customBank) || !empty($customAccountNo) || !empty($customAccountName);
                $shouldSaveToSupplier = !empty($data['save_to_supplier_bank']) || (empty($supplier->bank_account_number) && !empty($customAccountNo));
                if ($hasBankInput && $shouldSaveToSupplier) {
                    $supplier->update(array_filter([
                        'bank_name' => $customBank ?: $supplier->bank_name,
                        'bank_account_number' => $customAccountNo ?: $supplier->bank_account_number,
                        'bank_account_name' => $customAccountName ?: $supplier->bank_account_name,
                    ], fn($val) => !is_null($val) && $val !== ''));
                }
            }

            // Sync bank account to current Cashier/Operator User profile if requested
            if (!empty($data['save_to_user_bank']) && (!empty($customBank) || !empty($customAccountNo) || !empty($customAccountName))) {
                $user->update(array_filter([
                    'bank_name' => $customBank ?: $user->bank_name,
                    'bank_account_number' => $customAccountNo ?: $user->bank_account_number,
                    'bank_account_name' => $customAccountName ?: $user->bank_account_name,
                ], fn($val) => !is_null($val) && $val !== ''));
            }

            // Calculate items & totals safely on backend
            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new Exception('Transaksi harus memiliki minimal 1 material scrap & daur ulang.');
            }

            $totalGrossWeight = 0;
            $totalTareWeight = 0;
            $totalNetWeight = 0;
            $subtotalAmount = 0;

            $consolidatedItems = [];

            foreach ($itemsData as $row) {
                $scrapItemId = $row['scrap_item_id'] ?? null;
                $rawNewName = trim($row['new_item_name'] ?? $row['search_text'] ?? $row['name'] ?? '');
                $cleanNewName = preg_replace('/\s*-\s*Rp[\d.,\s]+.*/i', '', $rawNewName);
                $cleanNewName = preg_replace('/\s*\([A-Z0-9-]+\)\s*$/i', '', $cleanNewName);
                $newItemName = trim($cleanNewName);
                $isNewlyCreated = false;

                if ($scrapItemId === '__new__' || (empty($scrapItemId) && !empty($newItemName)) || (!is_numeric($scrapItemId) && !empty($newItemName))) {
                    // Check if an item with this name already exists in ScrapItem master
                    $scrapItem = ScrapItem::withTrashed()->where('name', $newItemName)->first();

                    if ($scrapItem) {
                        if ($scrapItem->trashed()) {
                            $scrapItem->restore();
                        }
                        if (!$scrapItem->is_active) {
                            $scrapItem->update(['is_active' => true]);
                        }
                    } else {
                        $enteredPrice = isset($row['price_per_kg']) && is_numeric($row['price_per_kg'])
                            ? (float) $row['price_per_kg']
                            : 0;

                        $unitType = strtolower($row['unit_type'] ?? 'kg');
                        if (!in_array($unitType, ['kg', 'pcs', 'satuan', 'unit'])) {
                            $unitType = 'kg';
                        }

                        $categoryId = null;
                        $newCategoryName = trim($row['new_category_name'] ?? '');

                        if (($row['category_id'] ?? null) === '__new__' || !empty($newCategoryName)) {
                            $targetCatName = !empty($newCategoryName) ? $newCategoryName : 'Kategori Baru';
                            $category = ItemCategory::where('name', $targetCatName)->first();
                            if (!$category) {
                                $slug = \Illuminate\Support\Str::slug($targetCatName);
                                $baseSlug = $slug ?: 'kategori';
                                $slug = $baseSlug;
                                $counter = 1;
                                while (ItemCategory::where('slug', $slug)->exists()) {
                                    $slug = "{$baseSlug}-{$counter}";
                                    $counter++;
                                }

                                $category = ItemCategory::create([
                                    'name' => $targetCatName,
                                    'slug' => $slug,
                                    'description' => 'Kategori otomatis dibuat dari kasir timbang',
                                    'is_active' => true,
                                ]);
                            }
                            $categoryId = $category->id;
                        } elseif (!empty($row['category_id']) && is_numeric($row['category_id'])) {
                            $categoryId = (int) $row['category_id'];
                        }

                        $code = 'BRG-' . strtoupper(substr(uniqid(), -5));
                        while (ScrapItem::where('code', $code)->exists()) {
                            $code = 'BRG-' . strtoupper(substr(uniqid(), -5));
                        }

                        $scrapItem = ScrapItem::create([
                            'code' => $code,
                            'name' => $newItemName,
                            'category_id' => $categoryId,
                            'unit' => $unitType,
                            'buy_price_per_kg' => $enteredPrice,
                            'sell_price_per_kg' => 0,
                            'min_stock_alert' => 0,
                            'is_active' => true,
                        ]);

                        StockBalance::firstOrCreate(
                            ['scrap_item_id' => $scrapItem->id],
                            [
                                'current_stock' => 0,
                                'total_in' => 0,
                                'total_out' => 0,
                                'average_buy_price' => $enteredPrice,
                            ]
                        );

                        ActivityLog::record(
                            action: 'scrap_item_created_from_purchase',
                            subject: $scrapItem,
                            description: "Menambahkan jenis barang baru '{$scrapItem->name}' ({$scrapItem->code}) langsung saat transaksi pembelian"
                        );

                        $isNewlyCreated = true;
                    }
                } else {
                    $scrapItem = ScrapItem::lockForUpdate()->findOrFail($scrapItemId);
                }

                $unitType = strtolower($row['unit_type'] ?? ($scrapItem->unit ?? 'kg'));
                if (!in_array($unitType, ['kg', 'pcs', 'satuan', 'unit'])) {
                    $unitType = 'kg';
                }
                $itemTypeName = !empty($row['item_type_name']) ? trim($row['item_type_name']) : null;

                if (in_array($unitType, ['pcs', 'satuan', 'unit'])) {
                    $rawQty = max(1, (float) ($row['quantity'] ?? 1));
                    $tare = max(0, (float) ($row['tare_weight'] ?? 0));
                    $net = max(0, $rawQty - $tare);
                    if ($net <= 0) {
                        $net = $rawQty;
                    }
                    $quantity = $net;
                    $gross = $rawQty;
                    $pricePerUnit = isset($row['price_per_kg']) && is_numeric($row['price_per_kg'])
                        ? (float) $row['price_per_kg']
                        : (float) $scrapItem->buy_price_per_kg;

                    $itemSubtotal = round($quantity * $pricePerUnit, 2);
                    $pricePerKg = $pricePerUnit;
                } else {
                    $gross = max(0, (float) ($row['gross_weight'] ?? 0));
                    $tare = max(0, (float) ($row['tare_weight'] ?? 0));
                    $net = max(0, $gross - $tare);
                    $quantity = 1;

                    if ($net <= 0) {
                        throw new Exception("Berat bersih untuk item '{$scrapItem->name}' harus lebih dari 0 kg.");
                    }

                    $pricePerKg = isset($row['price_per_kg']) && is_numeric($row['price_per_kg'])
                        ? (float) $row['price_per_kg']
                        : (float) $scrapItem->buy_price_per_kg;

                    $itemSubtotal = round($net * $pricePerKg, 2);
                }

                if ($user->isOwner() && !empty($row['update_master_price']) && $scrapItem && !$isNewlyCreated) {
                    $scrapItem->update(['buy_price_per_kg' => $pricePerKg]);
                    ActivityLog::record(
                        action: 'scrap_item_price_updated_from_purchase',
                        subject: $scrapItem,
                        description: "Memperbarui harga master beli '{$scrapItem->name}' menjadi " . FormatHelper::rupiah($pricePerKg) . " saat penerimaan barang"
                    );
                }

                $totalGrossWeight += $gross;
                $totalTareWeight += $tare;
                $totalNetWeight += ($unitType === 'kg' ? $net : 0);
                $subtotalAmount += $itemSubtotal;

                // Group items by item ID and price tier (Task: simpan berdasarkan harga)
                $groupKey = $scrapItem->id . '_' . number_format($pricePerKg, 2, '.', '') . '_' . $unitType . '_' . trim($itemTypeName ?? '');

                if (isset($consolidatedItems[$groupKey])) {
                    // Harga sama: akumulasi ke baris tier harga tersebut
                    $consolidatedItems[$groupKey]['gross_weight'] += $gross;
                    $consolidatedItems[$groupKey]['tare_weight'] += $tare;
                    $consolidatedItems[$groupKey]['net_weight'] += $net;
                    $consolidatedItems[$groupKey]['quantity'] += $quantity;
                    $consolidatedItems[$groupKey]['subtotal'] += $itemSubtotal;
                    if (!empty($row['notes'])) {
                        $existingNotes = $consolidatedItems[$groupKey]['notes'];
                        $consolidatedItems[$groupKey]['notes'] = $existingNotes ? ($existingNotes . '; ' . $row['notes']) : $row['notes'];
                    }
                } else {
                    // Harga beda: buat entri daftar harga yang berbeda untuk barang ini
                    $consolidatedItems[$groupKey] = [
                        'scrap_item' => $scrapItem,
                        'unit_type' => $unitType,
                        'item_type_name' => $itemTypeName,
                        'quantity' => $quantity,
                        'gross_weight' => $gross,
                        'tare_weight' => $tare,
                        'net_weight' => $net,
                        'price_per_kg' => $pricePerKg,
                        'subtotal' => $itemSubtotal,
                        'notes' => $row['notes'] ?? null,
                    ];
                }
            }

            $processedItems = array_values($consolidatedItems);

            $discountAmount = max(0, (float) ($data['discount_amount'] ?? 0));
            $roundingAmount = (float) ($data['rounding_amount'] ?? 0);
            $finalAmount = max(0, $subtotalAmount - $discountAmount + $roundingAmount);

            // Debt deduction calculation
            $debtDeductionAmount = max(0, (float) ($data['debt_deduction_amount'] ?? 0));
            if ($debtDeductionAmount > $finalAmount) {
                $debtDeductionAmount = $finalAmount;
            }
            $netCashPaid = max(0, $finalAmount - $debtDeductionAmount);

            // Bank / Transfer information formatting (Task 11 & Update: Nama Pemilik & No. Rekening)
            $bankInfo = $data['bank_info'] ?? null;
            $customBank = trim($data['custom_bank'] ?? '');
            $customAccountName = trim($data['custom_account_name'] ?? '');
            $customAccountNo = trim($data['custom_account_no'] ?? '');

            if (!empty($customAccountNo) || !empty($customAccountName) || !empty($customBank)) {
                $parts = [];
                if (!empty($customBank)) {
                    $parts[] = $customBank;
                }
                if (!empty($customAccountNo)) {
                    $parts[] = "Rek: {$customAccountNo}";
                }
                if (!empty($customAccountName)) {
                    $parts[] = "a/n {$customAccountName}";
                }
                $bankInfo = implode(' - ', $parts);
            }

            // Payment method resolution (Auto-save new payment method to master table)
            $paymentMethodId = $data['payment_method_id'] ?? null;
            $newPaymentMethodName = trim($data['new_payment_method_name'] ?? '');

            if ($paymentMethodId === '__new__' || !empty($newPaymentMethodName)) {
                $targetName = !empty($newPaymentMethodName) ? $newPaymentMethodName : 'Metode Pembayaran Baru';
                $pm = PaymentMethod::firstOrCreate(
                    ['name' => $targetName],
                    [
                        'code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $targetName), 0, 6)) ?: 'PM',
                        'bank_name' => $customBank ?: null,
                        'account_number' => $customAccountNo ?: null,
                        'account_holder' => $customAccountName ?: null,
                        'is_active' => true,
                    ]
                );
                if (!empty($customBank) || !empty($customAccountNo) || !empty($customAccountName)) {
                    $pm->update(array_filter([
                        'bank_name' => $customBank ?: $pm->bank_name,
                        'account_number' => $customAccountNo ?: $pm->account_number,
                        'account_holder' => $customAccountName ?: $pm->account_holder,
                    ], fn($val) => !is_null($val) && $val !== ''));
                }
                $paymentMethodId = $pm->id;
            } elseif (is_numeric($paymentMethodId)) {
                $paymentMethodId = (int) $paymentMethodId;
            } else {
                $paymentMethodId = PaymentMethod::first()?->id ?? null;
            }

            // Auto-fallback bankInfo if empty and payment method is bank/transfer
            if (empty($bankInfo) && $paymentMethodId) {
                $pm = PaymentMethod::find($paymentMethodId);
                $isBank = $pm && (
                    !empty($pm->bank_name) ||
                    !empty($pm->account_number) ||
                    str_contains(strtolower($pm->name), 'bank') ||
                    str_contains(strtolower($pm->name), 'transfer') ||
                    str_contains(strtolower($pm->code ?? ''), 'transfer')
                );
                if ($isBank) {
                    $accNo = $pm->account_number ?: ($user->bank_account_number ?? null);
                    $accName = $pm->account_holder ?: ($user->bank_account_name ?? null);
                    $bName = $pm->bank_name ?: ($user->bank_name ?? null);

                    $parts = [];
                    if (!empty($bName)) $parts[] = $bName;
                    if (!empty($accNo)) $parts[] = "Rek: {$accNo}";
                    if (!empty($accName)) $parts[] = "a/n {$accName}";
                    if (!empty($parts)) {
                        $bankInfo = implode(' - ', $parts);
                    }
                }
            }

            // Create Transaction record
            $transaction = PurchaseTransaction::create([
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $supplierId,
                'supplier_name' => $supplierName,
                'supplier_phone' => $supplierPhone,
                'supplier_address' => $supplierAddress,
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'bank_info' => $bankInfo,
                'payment_notes' => $data['payment_notes'] ?? null,
                'transaction_date' => $date,
                'total_gross_weight' => $totalGrossWeight,
                'total_tare_weight' => $totalTareWeight,
                'total_net_weight' => $totalNetWeight,
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'rounding_amount' => $roundingAmount,
                'final_amount' => $finalAmount,
                'debt_deduction_amount' => $debtDeductionAmount,
                'net_cash_paid' => $netCashPaid,
                'payment_status' => $data['payment_status'] ?? PurchaseTransaction::PAYMENT_PAID,
                'status' => PurchaseTransaction::STATUS_COMPLETED,
                'notes' => $data['notes'] ?? null,
            ]);

            // Execute debt deduction if applicable
            $debtService = app(DebtService::class);
            if ($debtDeductionAmount > 0 && $supplierId) {
                $debtService->deductDebtsForPurchase($supplierId, $debtDeductionAmount, $user, $transaction->id);
            }

            // Snapshot remaining debt after transaction
            if ($supplierId) {
                $supplierRemainingDebt = (float) Debt::where('supplier_id', $supplierId)
                    ->where('remaining_amount', '>', 0)
                    ->sum('remaining_amount');
                $transaction->update(['remaining_debt_after' => $supplierRemainingDebt]);
            }

            // Save items & update stock
            foreach ($processedItems as $item) {
                /** @var ScrapItem $scrapItem */
                $scrapItem = $item['scrap_item'];

                PurchaseTransactionItem::create([
                    'purchase_transaction_id' => $transaction->id,
                    'scrap_item_id' => $scrapItem->id,
                    'item_name_snapshot' => $scrapItem->name,
                    'item_code_snapshot' => $scrapItem->code,
                    'unit_type' => $item['unit_type'] ?? 'kg',
                    'item_type_name' => $item['item_type_name'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'gross_weight' => $item['gross_weight'],
                    'tare_weight' => $item['tare_weight'],
                    'net_weight' => $item['net_weight'],
                    'price_per_kg' => $item['price_per_kg'],
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['notes'],
                ]);

                // Update stock balance
                $stockBalance = StockBalance::firstOrCreate(
                    ['scrap_item_id' => $scrapItem->id],
                    ['current_stock' => 0, 'total_in' => 0, 'total_out' => 0, 'average_buy_price' => 0]
                );

                $currentQty = (float) $stockBalance->current_stock;
                $currentAvg = (float) $stockBalance->average_buy_price;
                $newQty = $currentQty + $item['net_weight'];

                // Weighted moving average price calculation
                $newAvg = $newQty > 0
                    ? (($currentQty * $currentAvg) + ($item['net_weight'] * $item['price_per_kg'])) / $newQty
                    : $item['price_per_kg'];

                $stockBalance->update([
                    'current_stock' => $newQty,
                    'total_in' => (float) $stockBalance->total_in + $item['net_weight'],
                    'average_buy_price' => round($newAvg, 2),
                ]);

                // Update price-tiered inventory (item_price_stocks)
                $incomingQty = ($item['unit_type'] === 'pcs' || $item['unit_type'] === 'satuan' || $item['unit_type'] === 'unit')
                    ? (float) ($item['quantity'] > 0 ? $item['quantity'] : $item['net_weight'])
                    : (float) $item['net_weight'];

                $priceStock = ItemPriceStock::firstOrCreate(
                    ['scrap_item_id' => $scrapItem->id, 'price_per_kg' => $item['price_per_kg']],
                    ['current_stock' => 0, 'total_in' => 0, 'total_out' => 0]
                );
                $priceStock->update([
                    'current_stock' => (float) $priceStock->current_stock + $incomingQty,
                    'total_in' => (float) $priceStock->total_in + $incomingQty,
                ]);

                // Record stock ledger movement
                StockMovement::create([
                    'scrap_item_id' => $scrapItem->id,
                    'user_id' => $user->id,
                    'type' => StockMovement::TYPE_PURCHASE,
                    'reference_type' => PurchaseTransaction::class,
                    'reference_id' => $transaction->id,
                    'weight_qty' => $item['net_weight'],
                    'unit_price' => $item['price_per_kg'],
                    'balance_after' => $newQty,
                    'notes' => "Penerimaan barang dari nota {$transaction->invoice_number}",
                ]);
            }

            ActivityLog::record(
                action: 'purchase_created',
                subject: $transaction,
                description: "Transaksi penerimaan barang {$transaction->invoice_number} berhasil dicatat oleh {$user->name}",
                payload: ['final_amount' => $finalAmount, 'items_count' => count($processedItems)]
            );

            return $transaction->load(['items.scrapItem', 'user', 'paymentMethod', 'supplier']);
        });
    }

    /**
     * Cancel a single item in a purchase transaction, reverse its stock, and recalculate totals.
     *
     * @throws Exception
     */
    public function cancelPurchaseItem(PurchaseTransaction $transaction, PurchaseTransactionItem $item, User $user, string $reason): bool
    {
        return DB::transaction(function () use ($transaction, $item, $user, $reason) {
            if ($item->purchase_transaction_id !== $transaction->id) {
                throw new Exception('Item tidak sesuai dengan transaksi ini.');
            }

            if ($item->is_cancelled) {
                throw new Exception('Barang ini sudah dibatalkan sebelumnya.');
            }

            if ($transaction->isCancelled()) {
                throw new Exception('Transaksi ini sudah dibatalkan secara keseluruhan.');
            }

            if (!$user->canCancelTransactions()) {
                throw new Exception('Anda tidak memiliki hak akses untuk membatalkan item transaksi.');
            }

            // 1. Reverse stock for this specific item
            if ($item->scrap_item_id) {
                $stockBalance = StockBalance::where('scrap_item_id', $item->scrap_item_id)
                    ->lockForUpdate()
                    ->first();

                if ($stockBalance) {
                    $newStock = max(0, (float) $stockBalance->current_stock - (float) $item->net_weight);
                    $newTotalIn = max(0, (float) $stockBalance->total_in - (float) $item->net_weight);

                    $stockBalance->update([
                        'current_stock' => $newStock,
                        'total_in' => $newTotalIn,
                    ]);

                    StockMovement::create([
                        'scrap_item_id' => $item->scrap_item_id,
                        'user_id' => $user->id,
                        'type' => StockMovement::TYPE_REVERSAL,
                        'reference_type' => PurchaseTransaction::class,
                        'reference_id' => $transaction->id,
                        'weight_qty' => -$item->net_weight,
                        'unit_price' => $item->price_per_kg,
                        'balance_after' => $newStock,
                        'notes' => "Batal item {$item->item_name_snapshot} pada nota {$transaction->invoice_number}: {$reason}",
                    ]);
                }

                // Reverse price-tiered inventory
                $revQty = ($item->unit_type === 'pcs' || $item->unit_type === 'satuan' || $item->unit_type === 'unit')
                    ? (float) ($item->quantity > 0 ? $item->quantity : $item->net_weight)
                    : (float) $item->net_weight;

                $priceStock = ItemPriceStock::where('scrap_item_id', $item->scrap_item_id)
                    ->where(function ($q) use ($item) {
                        $q->where('price_per_kg', $item->price_per_kg)
                          ->orWhereRaw('ABS(price_per_kg - ?) < 0.01', [$item->price_per_kg]);
                    })
                    ->first();
                if ($priceStock) {
                    $priceStock->update([
                        'current_stock' => max(0, (float) $priceStock->current_stock - $revQty),
                        'total_in' => max(0, (float) $priceStock->total_in - $revQty),
                    ]);
                }
            }

            // 2. Mark item as cancelled
            $item->update([
                'is_cancelled' => true,
                'cancel_reason' => $reason,
                'cancelled_by' => $user->id,
                'cancelled_at' => Carbon::now(),
            ]);

            // 3. Recalculate transaction totals from remaining active items
            $activeItems = $transaction->items()->where('is_cancelled', false)->get();

            if ($activeItems->isEmpty()) {
                // If all items are now cancelled, cancel the whole transaction
                if ($transaction->debt_deduction_amount > 0) {
                    app(DebtService::class)->reversePurchaseDebtDeductions($transaction->id);
                }

                $transaction->update([
                    'status' => PurchaseTransaction::STATUS_CANCELLED,
                    'cancel_reason' => 'Semua barang telah dibatalkan. Alasan terakhir: ' . $reason,
                    'cancelled_by' => $user->id,
                    'cancelled_at' => Carbon::now(),
                    'total_gross_weight' => 0,
                    'total_tare_weight' => 0,
                    'total_net_weight' => 0,
                    'subtotal_amount' => 0,
                    'final_amount' => 0,
                    'net_cash_paid' => 0,
                    'debt_deduction_amount' => 0,
                ]);
            } else {
                $newTotalGross = 0;
                $newTotalTare = 0;
                $newTotalNet = 0;
                $newSubtotal = 0;

                foreach ($activeItems as $actItem) {
                    $isPcs = ($actItem->unit_type === 'pcs' || $actItem->unit_type === 'satuan');
                    $grossVal = $isPcs ? ($actItem->gross_weight > 0 ? $actItem->gross_weight : ($actItem->quantity + $actItem->tare_weight)) : $actItem->gross_weight;
                    $netVal = $isPcs ? ($actItem->net_weight > 0 ? $actItem->net_weight : $actItem->quantity) : $actItem->net_weight;

                    $newTotalGross += (float) $grossVal;
                    $newTotalTare += (float) $actItem->tare_weight;
                    $newTotalNet += (float) $netVal;
                    $newSubtotal += (float) $actItem->subtotal;
                }

                $discount = min((float) $transaction->discount_amount, $newSubtotal);
                $rounding = (float) $transaction->rounding_amount;
                $newFinalAmount = max(0, $newSubtotal - $discount + $rounding);

                // Handle debt deduction if applied
                $debtDeduction = (float) $transaction->debt_deduction_amount;
                if ($debtDeduction > $newFinalAmount) {
                    $debtDeduction = $newFinalAmount;
                }
                $newNetCashPaid = max(0, $newFinalAmount - $debtDeduction);

                $transaction->update([
                    'total_gross_weight' => $newTotalGross,
                    'total_tare_weight' => $newTotalTare,
                    'total_net_weight' => $newTotalNet,
                    'subtotal_amount' => $newSubtotal,
                    'final_amount' => $newFinalAmount,
                    'debt_deduction_amount' => $debtDeduction,
                    'net_cash_paid' => $newNetCashPaid,
                ]);
            }

            ActivityLog::record(
                action: 'purchase_item_cancelled',
                subject: $transaction,
                description: "Item '{$item->item_name_snapshot}' pada transaksi {$transaction->invoice_number} dibatalkan oleh {$user->name}. Alasan: {$reason}",
                payload: [
                    'item_id' => $item->id,
                    'item_name' => $item->item_name_snapshot,
                    'reason' => $reason
                ]
            );

            return true;
        });
    }

    /**
     * Cancel / void an existing purchase transaction and reverse stock.
     *
     * @throws Exception
     */
    public function cancelPurchase(PurchaseTransaction $transaction, User $user, string $reason): bool
    {
        return DB::transaction(function () use ($transaction, $user, $reason) {
            if ($transaction->isCancelled()) {
                throw new Exception('Transaksi ini sudah dibatalkan sebelumnya.');
            }

            if (!$user->canCancelTransactions()) {
                throw new Exception('Anda tidak memiliki hak akses untuk membatalkan transaksi.');
            }

            $transaction->load('items.scrapItem');

            foreach ($transaction->items as $item) {
                if (!$item->scrap_item_id || $item->is_cancelled) {
                    continue;
                }

                $stockBalance = StockBalance::where('scrap_item_id', $item->scrap_item_id)
                    ->lockForUpdate()
                    ->first();

                if ($stockBalance) {
                    $newStock = max(0, (float) $stockBalance->current_stock - (float) $item->net_weight);
                    $newTotalIn = max(0, (float) $stockBalance->total_in - (float) $item->net_weight);

                    $stockBalance->update([
                        'current_stock' => $newStock,
                        'total_in' => $newTotalIn,
                    ]);

                    StockMovement::create([
                        'scrap_item_id' => $item->scrap_item_id,
                        'user_id' => $user->id,
                        'type' => StockMovement::TYPE_REVERSAL,
                        'reference_type' => PurchaseTransaction::class,
                        'reference_id' => $transaction->id,
                        'weight_qty' => -$item->net_weight,
                        'unit_price' => $item->price_per_kg,
                        'balance_after' => $newStock,
                        'notes' => "Pembatalan nota penerimaan {$transaction->invoice_number}: {$reason}",
                    ]);
                }

                // Reverse price-tiered inventory
                $revQty = ($item->unit_type === 'pcs' || $item->unit_type === 'satuan' || $item->unit_type === 'unit')
                    ? (float) ($item->quantity > 0 ? $item->quantity : $item->net_weight)
                    : (float) $item->net_weight;

                $priceStock = ItemPriceStock::where('scrap_item_id', $item->scrap_item_id)
                    ->where(function ($q) use ($item) {
                        $q->where('price_per_kg', $item->price_per_kg)
                          ->orWhereRaw('ABS(price_per_kg - ?) < 0.01', [$item->price_per_kg]);
                    })
                    ->first();
                if ($priceStock) {
                    $priceStock->update([
                        'current_stock' => max(0, (float) $priceStock->current_stock - $revQty),
                        'total_in' => max(0, (float) $priceStock->total_in - $revQty),
                    ]);
                }
            }

            // Reverse any debt deductions made on this transaction
            if ($transaction->debt_deduction_amount > 0) {
                app(DebtService::class)->reversePurchaseDebtDeductions($transaction->id);
            }

            $transaction->update([
                'status' => PurchaseTransaction::STATUS_CANCELLED,
                'cancel_reason' => $reason,
                'cancelled_by' => $user->id,
                'cancelled_at' => Carbon::now(),
            ]);

            ActivityLog::record(
                action: 'purchase_cancelled',
                subject: $transaction,
                description: "Transaksi {$transaction->invoice_number} dibatalkan oleh {$user->name}. Alasan: {$reason}",
                payload: ['reason' => $reason]
            );

            return true;
        });
    }

    /**
     * Generate unique auto-incrementing invoice number per day.
     */
    protected function generateInvoiceNumber(Carbon $date): string
    {
        $prefix = 'PPJ-' . $date->format('ymd') . '-';
        $latest = PurchaseTransaction::where('invoice_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->value('invoice_number');

        if ($latest) {
            $num = (int) substr($latest, strlen($prefix)) + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }
}

