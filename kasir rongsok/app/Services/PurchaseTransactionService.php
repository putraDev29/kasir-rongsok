<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Debt;
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
            $supplierName = $data['supplier_name'] ?? null;
            $supplierPhone = $data['supplier_phone'] ?? null;
            $supplierAddress = $data['supplier_address'] ?? null;

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
                    ]);
                }
                $supplierId = $supplier->id;
                $supplierName = $supplier->name;
            } else {
                $supplierName = !empty($supplierName) ? trim($supplierName) : null;
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

            $processedItems = [];

            foreach ($itemsData as $row) {
                $scrapItemId = $row['scrap_item_id'] ?? null;
                $newItemName = trim($row['new_item_name'] ?? $row['search_text'] ?? $row['name'] ?? '');
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

                        $categoryId = !empty($row['category_id']) && is_numeric($row['category_id'])
                            ? (int) $row['category_id']
                            : null;

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

                    $masterBuyPrice = (float) $scrapItem->buy_price_per_kg;
                    if (!$isNewlyCreated && $masterBuyPrice > 0 && $pricePerUnit > $masterBuyPrice) {
                        throw new Exception("Harga beli untuk '{$scrapItem->name}' (" . FormatHelper::rupiah($pricePerUnit) . ") melebihi harga master (" . FormatHelper::rupiah($masterBuyPrice) . ").");
                    }

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

                    $masterBuyPrice = (float) $scrapItem->buy_price_per_kg;
                    if (!$isNewlyCreated && $masterBuyPrice > 0 && $pricePerKg > $masterBuyPrice) {
                        throw new Exception("Harga beli untuk '{$scrapItem->name}' (" . FormatHelper::rupiah($pricePerKg) . ") melebihi harga master (" . FormatHelper::rupiah($masterBuyPrice) . ").");
                    }

                    $itemSubtotal = round($net * $pricePerKg, 2);
                }

                $totalGrossWeight += $gross;
                $totalTareWeight += $tare;
                $totalNetWeight += ($unitType === 'kg' ? $net : 0);
                $subtotalAmount += $itemSubtotal;

                $processedItems[] = [
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
                    ['code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $targetName), 0, 6)) ?: 'PM', 'is_active' => true]
                );
                $paymentMethodId = $pm->id;
            } elseif (is_numeric($paymentMethodId)) {
                $paymentMethodId = (int) $paymentMethodId;
            } else {
                $paymentMethodId = PaymentMethod::first()?->id ?? null;
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
                if (!$item->scrap_item_id) {
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
        $prefix = 'KB-' . $date->format('ymd') . '-';
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

