<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Driver;
use App\Models\ItemPriceStock;
use App\Models\PaymentMethod;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\FormatHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class SalesTransactionService
{
    /**
     * Create a new scrap sales transaction with negative stock prevention and COGS calculation.
     *
     * @throws Exception
     */
    public function createSale(array $data, User $user): SalesTransaction
    {
        return DB::transaction(function () use ($data, $user) {
            $isKasir = $user->isKasir() || !empty($data['is_draft']);
            $date = isset($data['transaction_date']) ? Carbon::parse($data['transaction_date']) : Carbon::now();
            $invoiceNumber = FormatHelper::generateSalesInvoiceNumber($date);

            $buyerName = trim($data['buyer_name'] ?? '');
            if (empty($buyerName)) {
                throw new Exception('Nama pembeli / pabrik rongsok wajib diisi.');
            }

            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new Exception('Penjualan harus memiliki minimal 1 material scrap & daur ulang.');
            }

            $totalWeight = 0;
            $subtotalAmount = 0;
            $totalEstimatedCogs = 0;

            $processedItems = [];

            foreach ($itemsData as $row) {
                $scrapItemId = $row['scrap_item_id'];
                $scrapItem = ScrapItem::with('stockBalance')->lockForUpdate()->findOrFail($scrapItemId);

                $unitType = strtolower($row['unit_type'] ?? ($scrapItem->unit_type ?? 'kg'));
                if (!in_array($unitType, ['kg', 'pcs', 'satuan', 'unit'])) {
                    $unitType = 'kg';
                }
                $itemTypeName = !empty($row['item_type_name']) ? trim($row['item_type_name']) : null;

                $stockBalance = $scrapItem->stockBalance;
                $currentStock = (float) ($stockBalance?->current_stock ?? 0);

                if (in_array($unitType, ['pcs', 'satuan', 'unit'])) {
                    $quantity = max(1, (float) ($row['quantity'] ?? 1));
                    $weight = 0;
                    $stockQtyNeeded = $quantity;

                    if ($stockQtyNeeded > $currentStock) {
                        throw new Exception(
                            "Stok barang '{$scrapItem->name}' tidak mencukupi! Tersedia: " .
                            FormatHelper::number($currentStock) . " pcs, ingin dijual: " .
                            FormatHelper::number($stockQtyNeeded) . " pcs."
                        );
                    }

                    $sellPrice = isset($row['sell_price_per_kg']) && is_numeric($row['sell_price_per_kg'])
                        ? (float) $row['sell_price_per_kg']
                        : ($isKasir ? 0 : (float) $scrapItem->sell_price_per_kg);

                    if (!$isKasir && $sellPrice <= 0) {
                        throw new Exception("Harga jual satuan untuk '{$scrapItem->name}' harus lebih dari Rp 0.");
                    }
                    if ($isKasir && $sellPrice < 0) {
                        $sellPrice = 0;
                    }

                    $selectedBuyPrice = isset($row['buy_price_snapshot']) && is_numeric($row['buy_price_snapshot']) && (float) $row['buy_price_snapshot'] > 0
                        ? (float) $row['buy_price_snapshot']
                        : (isset($row['tier_price']) && is_numeric($row['tier_price']) && (float) $row['tier_price'] > 0
                            ? (float) $row['tier_price']
                            : (float) ($stockBalance?->average_buy_price ?? $scrapItem->buy_price_per_kg));

                    $itemSubtotal = round($quantity * $sellPrice, 2);
                    $itemCogs = round($quantity * $selectedBuyPrice, 2);
                    $itemProfit = round($itemSubtotal - $itemCogs, 2);
                    $avgBuyPrice = $selectedBuyPrice;
                } else {
                    $weight = max(0, (float) ($row['weight'] ?? 0));
                    $quantity = 1;
                    $stockQtyNeeded = $weight;

                    if ($weight <= 0) {
                        throw new Exception("Berat jual untuk item '{$scrapItem->name}' harus lebih dari 0 kg.");
                    }

                    // Prevent negative stock
                    if ($weight > $currentStock) {
                        throw new Exception(
                            "Stok barang '{$scrapItem->name}' tidak mencukupi! Tersedia: " .
                            FormatHelper::weight($currentStock) . " kg, ingin dijual: " .
                            FormatHelper::weight($weight) . " kg."
                        );
                    }

                    $sellPrice = isset($row['sell_price_per_kg']) && is_numeric($row['sell_price_per_kg'])
                        ? (float) $row['sell_price_per_kg']
                        : ($isKasir ? 0 : (float) $scrapItem->sell_price_per_kg);

                    if (!$isKasir && $sellPrice <= 0) {
                        throw new Exception("Harga jual per kg untuk '{$scrapItem->name}' harus lebih dari Rp 0.");
                    }
                    if ($isKasir && $sellPrice < 0) {
                        $sellPrice = 0;
                    }

                    $selectedBuyPrice = isset($row['buy_price_snapshot']) && is_numeric($row['buy_price_snapshot']) && (float) $row['buy_price_snapshot'] > 0
                        ? (float) $row['buy_price_snapshot']
                        : (isset($row['tier_price']) && is_numeric($row['tier_price']) && (float) $row['tier_price'] > 0
                            ? (float) $row['tier_price']
                            : (float) ($stockBalance?->average_buy_price ?? $scrapItem->buy_price_per_kg));

                    $itemSubtotal = round($weight * $sellPrice, 2);
                    $itemCogs = round($weight * $selectedBuyPrice, 2);
                    $itemProfit = round($itemSubtotal - $itemCogs, 2);
                    $avgBuyPrice = $selectedBuyPrice;

                    $totalWeight += $weight;
                }

                $subtotalAmount += $itemSubtotal;
                $totalEstimatedCogs += $itemCogs;

                $processedItems[] = [
                    'scrap_item' => $scrapItem,
                    'stock_balance' => $stockBalance,
                    'item_price_stock_id' => $row['item_price_stock_id'] ?? null,
                    'unit_type' => $unitType,
                    'item_type_name' => $itemTypeName,
                    'quantity' => $quantity,
                    'weight' => $weight,
                    'stock_qty_needed' => $stockQtyNeeded,
                    'sell_price_per_kg' => $sellPrice,
                    'buy_price_snapshot' => $avgBuyPrice,
                    'subtotal' => $itemSubtotal,
                    'cogs_subtotal' => $itemCogs,
                    'profit_subtotal' => $itemProfit,
                    'notes' => $row['notes'] ?? null,
                ];
            }

            $discountAmount = max(0, (float) ($data['discount_amount'] ?? 0));
            $finalAmount = max(0, $subtotalAmount - $discountAmount);
            $estimatedProfit = $isKasir ? 0 : round($finalAmount - $totalEstimatedCogs, 2);

            $paymentMethodId = $data['payment_method_id'] ?? null;
            $pm = $paymentMethodId ? PaymentMethod::find($paymentMethodId) : null;
            $isBank = false;
            if ($pm) {
                $mName = strtolower($pm->name);
                $mCode = strtolower($pm->code ?? '');
                $isCash = $mCode === 'cash' || str_contains($mName, 'tunai') || str_contains($mName, 'cash');
                $isBank = !$isCash && (
                    str_contains($mName, 'bank') ||
                    str_contains($mName, 'transfer') ||
                    str_contains($mCode, 'transfer') ||
                    str_contains($mCode, 'bank') ||
                    !empty($pm->bank_name)
                );
            }

            $customBank = trim($data['custom_bank'] ?? '');
            $customAccountName = trim($data['custom_account_name'] ?? '');
            $customAccountNo = trim($data['custom_account_no'] ?? '');

            // Sync bank account to current Cashier/Operator User profile if requested (Task 10)
            if (!empty($data['save_to_user_bank']) && (!empty($customBank) || !empty($customAccountNo) || !empty($customAccountName))) {
                $user->update(array_filter([
                    'bank_name' => $customBank ?: $user->bank_name,
                    'bank_account_number' => $customAccountNo ?: $user->bank_account_number,
                    'bank_account_name' => $customAccountName ?: $user->bank_account_name,
                ], fn($val) => !is_null($val) && $val !== ''));
            }

            $bankInfo = null;
            if ($isBank) {
                $bankInfo = $data['bank_info'] ?? null;

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

                // Auto-fallback bankInfo if empty and payment method is bank/transfer
                if (empty($bankInfo) && $pm) {
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

            $deliveryType = $data['delivery_type'] ?? (isset($data['delivery_order_id']) ? SalesTransaction::DELIVERY_TYPE_DELIVERY : SalesTransaction::DELIVERY_TYPE_PICKUP);
            $deliveryOrderId = $data['delivery_order_id'] ?? null;

            // Determine status:
            // - Jika kasir, status disimpan sebagai DRAFT (tanpa harga jual).
            // - Diambil Sendiri: status langsung selesai (completed). Tanpa surat jalan.
            // - Perlu Pengiriman: status pending_delivery sampai pengiriman selesai.
            if ($isKasir) {
                $status = SalesTransaction::STATUS_DRAFT;
            } elseif ($deliveryType === SalesTransaction::DELIVERY_TYPE_PICKUP) {
                $status = SalesTransaction::STATUS_COMPLETED;
                $deliveryOrderId = null;
            } else {
                if ($deliveryOrderId) {
                    $existingDo = DeliveryOrder::find($deliveryOrderId);
                    $status = ($existingDo && $existingDo->status === 'delivered')
                        ? SalesTransaction::STATUS_COMPLETED
                        : SalesTransaction::STATUS_PENDING_DELIVERY;
                } else {
                    $status = SalesTransaction::STATUS_PENDING_DELIVERY;
                }
            }

            $transaction = SalesTransaction::create([
                'invoice_number' => $invoiceNumber,
                'buyer_name' => $buyerName,
                'buyer_phone' => $data['buyer_phone'] ?? null,
                'buyer_address' => $data['buyer_address'] ?? null,
                'delivery_type' => $deliveryType,
                'delivery_order_id' => $deliveryOrderId,
                'user_id' => $user->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'bank_info' => $bankInfo,
                'transaction_date' => $date,
                'total_weight' => $totalWeight,
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'estimated_cogs' => $totalEstimatedCogs,
                'estimated_profit' => $estimatedProfit,
                'payment_status' => $isKasir ? 'draft' : ($data['payment_status'] ?? 'paid'),
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            // Bila dengan pengiriman dan data armada / supir diisi langsung di form, otomatis buat Surat Jalan
            if ($deliveryType === SalesTransaction::DELIVERY_TYPE_DELIVERY && !$deliveryOrderId && (!empty($data['driver_name']) || !empty($data['vehicle_number']))) {
                $doDate = $date;
                $deliveryNumber = FormatHelper::generateDeliveryOrderNumber($doDate);
                $totalPackages = 0;
                foreach ($data['items'] as $it) {
                    $totalPackages += (int) ($it['package_qty'] ?? 0);
                }

                $driverId = $data['driver_id'] ?? null;
                $driverName = trim($data['driver_name'] ?? '');
                $driverPhone = $data['driver_phone'] ?? null;
                $vehicleNumber = strtoupper(trim($data['vehicle_number'] ?? ''));
                $vehicleType = $data['vehicle_type'] ?? 'Truk';
                $driverBankName = trim($data['driver_bank_name'] ?? '');
                $driverBankAccNo = trim($data['driver_bank_account_number'] ?? '');
                $driverBankAccName = trim($data['driver_bank_account_name'] ?? '');

                $driver = null;
                if ($driverId) {
                    $driver = Driver::find($driverId);
                } elseif (!empty($driverName)) {
                    $driver = Driver::where('name', $driverName)->first();
                    if (!$driver) {
                        $latestId = Driver::max('id') ?? 0;
                        $code = 'DRV-' . str_pad((string) ($latestId + 1), 3, '0', STR_PAD_LEFT);
                        while (Driver::where('code', $code)->exists()) {
                            $code = 'DRV-' . strtoupper(substr(uniqid(), -5));
                        }
                        $driver = Driver::create([
                            'code' => $code,
                            'name' => $driverName,
                            'phone' => $driverPhone,
                            'vehicle_number' => $vehicleNumber ?: null,
                            'vehicle_type' => $vehicleType ?: null,
                            'bank_name' => $driverBankName ?: null,
                            'bank_account_number' => $driverBankAccNo ?: null,
                            'bank_account_name' => $driverBankAccName ?: null,
                            'is_active' => true,
                        ]);
                    }
                    $driverId = $driver->id;
                }

                if ($driver) {
                    $driverId = $driver->id;
                    $driverName = $driver->name;
                    $hasBankInfo = !empty($driverBankName) || !empty($driverBankAccNo) || !empty($driverBankAccName);
                    $shouldSaveToDriver = !empty($data['save_to_driver_bank']) || empty($driver->bank_account_number);
                    if ($hasBankInfo && $shouldSaveToDriver) {
                        $driver->update(array_filter([
                            'bank_name' => $driverBankName ?: $driver->bank_name,
                            'bank_account_number' => $driverBankAccNo ?: $driver->bank_account_number,
                            'bank_account_name' => $driverBankAccName ?: $driver->bank_account_name,
                            'phone' => $driverPhone ?: $driver->phone,
                            'vehicle_number' => $vehicleNumber ?: $driver->vehicle_number,
                            'vehicle_type' => $vehicleType ?: $driver->vehicle_type,
                        ], fn($val) => !is_null($val) && $val !== ''));
                    }
                }

                $createdDo = DeliveryOrder::create([
                    'delivery_number' => $deliveryNumber,
                    'sales_transaction_id' => $transaction->id,
                    'driver_id' => $driverId,
                    'delivery_date' => $doDate,
                    'recipient_name' => $buyerName,
                    'recipient_address' => $data['buyer_address'] ?? null,
                    'recipient_phone' => $data['buyer_phone'] ?? null,
                    'driver_name' => $driverName ?: 'Sopir Pengirim',
                    'driver_phone' => $driverPhone,
                    'driver_bank_name' => $driverBankName ?: ($driver?->bank_name ?? null),
                    'driver_bank_account_number' => $driverBankAccNo ?: ($driver?->bank_account_number ?? null),
                    'driver_bank_account_name' => $driverBankAccName ?: ($driver?->bank_account_name ?? null),
                    'vehicle_number' => $vehicleNumber ?: 'KENDARAAN',
                    'vehicle_type' => $vehicleType ?: 'Truk',
                    'total_weight' => $totalWeight,
                    'shipping_cost' => max(0, (float) ($data['shipping_cost'] ?? 0)),
                    'loading_cost' => max(0, (float) ($data['loading_cost'] ?? 0)),
                    'total_packages' => $totalPackages,
                    'status' => 'sent',
                    'notes' => $data['notes'] ?? null,
                    'user_id' => $user->id,
                ]);

                foreach ($data['items'] as $it) {
                    $scItem = ScrapItem::find($it['scrap_item_id']);
                    $w = (float) ($it['weight'] ?? 0);
                    $pr = (float) ($it['sell_price_per_kg'] ?? 0);
                    DeliveryOrderItem::create([
                        'delivery_order_id' => $createdDo->id,
                        'scrap_item_id' => $it['scrap_item_id'],
                        'item_name' => $scItem?->name ?? 'Barang Rongsok',
                        'unit_type' => $it['unit_type'] ?? 'kg',
                        'item_type_name' => $it['item_type_name'] ?? null,
                        'quantity' => $it['quantity'] ?? 1,
                        'weight' => $w,
                        'package_qty' => (int) ($it['package_qty'] ?? 0),
                        'price_per_kg' => $pr,
                        'subtotal' => $pr * $w,
                        'notes' => $it['notes'] ?? null,
                    ]);
                }

                $createdDo->recordOperationalExpenses();

                $transaction->update(['delivery_order_id' => $createdDo->id]);
            } elseif ($deliveryOrderId) {
                // Sync with existing Delivery Order if linked
                $do = DeliveryOrder::find($deliveryOrderId);
                if ($do) {
                    $do->update([
                        'sales_transaction_id' => $transaction->id,
                    ]);
                }
            }

            foreach ($processedItems as $item) {
                /** @var ScrapItem $scrapItem */
                $scrapItem = $item['scrap_item'];
                /** @var StockBalance $stockBalance */
                $stockBalance = $item['stock_balance'];

                SalesTransactionItem::create([
                    'sales_transaction_id' => $transaction->id,
                    'scrap_item_id' => $scrapItem->id,
                    'item_name_snapshot' => $scrapItem->name,
                    'item_code_snapshot' => $scrapItem->code,
                    'unit_type' => $item['unit_type'],
                    'item_type_name' => $item['item_type_name'],
                    'quantity' => $item['quantity'],
                    'weight' => $item['weight'],
                    'sell_price_per_kg' => $item['sell_price_per_kg'],
                    'buy_price_snapshot' => $item['buy_price_snapshot'],
                    'subtotal' => $item['subtotal'],
                    'cogs_subtotal' => $item['cogs_subtotal'],
                    'profit_subtotal' => $item['profit_subtotal'],
                    'notes' => $item['notes'],
                ]);

                // Reduce stock
                $reductionQty = $item['stock_qty_needed'];
                $newStock = max(0, (float) $stockBalance->current_stock - $reductionQty);
                $newTotalOut = (float) $stockBalance->total_out + $reductionQty;

                $stockBalance->update([
                    'current_stock' => $newStock,
                    'total_out' => $newTotalOut,
                ]);

                // Reduce price-tiered inventory
                $priceStock = null;
                if (!empty($item['item_price_stock_id'])) {
                    $priceStock = ItemPriceStock::where('id', $item['item_price_stock_id'])
                        ->where('scrap_item_id', $scrapItem->id)
                        ->first();
                }
                if (!$priceStock) {
                    $priceStock = ItemPriceStock::where('scrap_item_id', $scrapItem->id)
                        ->where(function ($q) use ($item) {
                            $q->where('price_per_kg', $item['buy_price_snapshot'])
                              ->orWhereRaw('ABS(price_per_kg - ?) < 0.01', [$item['buy_price_snapshot']]);
                        })
                        ->first();
                }
                if (!$priceStock) {
                    $priceStock = ItemPriceStock::where('scrap_item_id', $scrapItem->id)
                        ->orderByDesc('current_stock')
                        ->first();
                }
                if ($priceStock) {
                    $priceStock->update([
                        'current_stock' => max(0, (float) $priceStock->current_stock - $reductionQty),
                        'total_out' => (float) $priceStock->total_out + $reductionQty,
                    ]);
                }

                // Log movement
                StockMovement::create([
                    'scrap_item_id' => $scrapItem->id,
                    'user_id' => $user->id,
                    'type' => StockMovement::TYPE_SALE,
                    'reference_type' => SalesTransaction::class,
                    'reference_id' => $transaction->id,
                    'weight_qty' => -$reductionQty,
                    'unit_price' => $item['sell_price_per_kg'],
                    'balance_after' => $newStock,
                    'notes' => "Penjualan rongsok kepada {$buyerName} (Nota {$transaction->invoice_number})",
                ]);
            }

            ActivityLog::record(
                action: 'sale_created',
                subject: $transaction,
                description: "Penjualan rongsok {$transaction->invoice_number} senilai Rp " . number_format($finalAmount, 0, ',', '.') . " kepada {$buyerName}",
                payload: ['final_amount' => $finalAmount, 'total_weight' => $totalWeight, 'profit' => $estimatedProfit]
            );

            return $transaction->load(['items.scrapItem', 'user', 'paymentMethod']);
        });
    }

    /**
     * Cancel sales transaction and restore stock.
     *
     * @throws Exception
     */
    public function cancelSale(SalesTransaction $transaction, User $user, string $reason): bool
    {
        return DB::transaction(function () use ($transaction, $user, $reason) {
            if ($transaction->isCancelled()) {
                throw new Exception('Transaksi penjualan ini sudah dibatalkan.');
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
                    $restoreQty = in_array(strtolower($item->unit_type ?? 'kg'), ['pcs', 'satuan', 'unit'])
                        ? (float) ($item->quantity > 0 ? $item->quantity : $item->weight)
                        : (float) $item->weight;

                    $newStock = (float) $stockBalance->current_stock + $restoreQty;
                    $newTotalOut = max(0, (float) $stockBalance->total_out - $restoreQty);

                    $stockBalance->update([
                        'current_stock' => $newStock,
                        'total_out' => $newTotalOut,
                    ]);

                    // Restore price-tiered inventory
                    $priceStock = ItemPriceStock::where('scrap_item_id', $item->scrap_item_id)
                        ->where(function ($q) use ($item) {
                            $q->where('price_per_kg', $item->buy_price_snapshot)
                              ->orWhereRaw('ABS(price_per_kg - ?) < 0.01', [$item->buy_price_snapshot]);
                        })
                        ->first();
                    if (!$priceStock) {
                        $priceStock = ItemPriceStock::where('scrap_item_id', $item->scrap_item_id)
                            ->first();
                    }
                    if ($priceStock) {
                        $priceStock->update([
                            'current_stock' => (float) $priceStock->current_stock + $restoreQty,
                            'total_out' => max(0, (float) $priceStock->total_out - $restoreQty),
                        ]);
                    }

                    StockMovement::create([
                        'scrap_item_id' => $item->scrap_item_id,
                        'user_id' => $user->id,
                        'type' => StockMovement::TYPE_REVERSAL,
                        'reference_type' => SalesTransaction::class,
                        'reference_id' => $transaction->id,
                        'weight_qty' => $restoreQty,
                        'unit_price' => $item->sell_price_per_kg,
                        'balance_after' => $newStock,
                        'notes' => "Pembatalan penjualan nota {$transaction->invoice_number}: {$reason}",
                    ]);
                }
            }

            $transaction->update([
                'status' => SalesTransaction::STATUS_CANCELLED,
                'cancel_reason' => $reason,
                'cancelled_by' => $user->id,
                'cancelled_at' => Carbon::now(),
            ]);

            ActivityLog::record(
                action: 'sale_cancelled',
                subject: $transaction,
                description: "Penjualan {$transaction->invoice_number} dibatalkan oleh {$user->name}. Alasan: {$reason}",
                payload: ['reason' => $reason]
            );

            return true;
        });
    }

    /**
     * Update and finalize a draft sales transaction (fill in selling prices, edit/add items, and set status based on delivery type).
     *
     * @throws Exception
     */
    public function updateDraftSale(SalesTransaction $transaction, array $data, User $user): SalesTransaction
    {
        return DB::transaction(function () use ($transaction, $data, $user) {
            if (!$transaction->isDraft()) {
                throw new Exception('Transaksi penjualan ini bukan berstatus draft atau sudah difinalisasi.');
            }

            if (!$user->isAdmin()) {
                throw new Exception('Hanya Admin atau Owner yang memiliki hak akses untuk memfinalisasi harga jual.');
            }

            $transaction->load(['items.scrapItem', 'deliveryOrder']);

            // 1. Kembalikan stok lama transaksi draft agar stok kembali utuh sebelum dihitung ulang
            foreach ($transaction->items as $oldItem) {
                if (!$oldItem->scrap_item_id) {
                    continue;
                }

                $oldStockBalance = StockBalance::where('scrap_item_id', $oldItem->scrap_item_id)
                    ->lockForUpdate()
                    ->first();

                if ($oldStockBalance) {
                    $restoreQty = in_array(strtolower($oldItem->unit_type ?? 'kg'), ['pcs', 'satuan', 'unit'])
                        ? (float) ($oldItem->quantity > 0 ? $oldItem->quantity : $oldItem->weight)
                        : (float) $oldItem->weight;

                    $oldStockBalance->update([
                        'current_stock' => (float) $oldStockBalance->current_stock + $restoreQty,
                        'total_out' => max(0, (float) $oldStockBalance->total_out - $restoreQty),
                    ]);

                    // Restore price-tiered stock
                    $oldPriceStock = null;
                    if (!empty($oldItem->item_price_stock_id)) {
                        $oldPriceStock = ItemPriceStock::find($oldItem->item_price_stock_id);
                    }
                    if (!$oldPriceStock) {
                        $oldPriceStock = ItemPriceStock::where('scrap_item_id', $oldItem->scrap_item_id)
                            ->where(function ($q) use ($oldItem) {
                                $q->where('price_per_kg', $oldItem->buy_price_snapshot)
                                  ->orWhereRaw('ABS(price_per_kg - ?) < 0.01', [$oldItem->buy_price_snapshot]);
                            })
                            ->first();
                    }
                    if (!$oldPriceStock) {
                        $oldPriceStock = ItemPriceStock::where('scrap_item_id', $oldItem->scrap_item_id)->first();
                    }
                    if ($oldPriceStock) {
                        $oldPriceStock->update([
                            'current_stock' => (float) $oldPriceStock->current_stock + $restoreQty,
                            'total_out' => max(0, (float) $oldPriceStock->total_out - $restoreQty),
                        ]);
                    }
                }
            }

            // Hapus mutasi stok lama dari draft penjualan ini
            StockMovement::where('reference_type', SalesTransaction::class)
                ->where('reference_id', $transaction->id)
                ->delete();

            $oldItemsById = $transaction->items->keyBy('id');
            $itemsInput = $data['items'] ?? [];

            if (empty($itemsInput)) {
                throw new Exception('Daftar barang penjualan tidak boleh kosong.');
            }

            $totalWeight = 0;
            $subtotalAmount = 0;
            $totalEstimatedCogs = 0;
            $processedItems = [];

            foreach ($itemsInput as $idx => $row) {
                $oldItem = null;
                if (is_numeric($idx) && $oldItemsById->has($idx)) {
                    $oldItem = $oldItemsById->get($idx);
                }
                if (!empty($row['id']) && $oldItemsById->has($row['id'])) {
                    $oldItem = $oldItemsById->get($row['id']);
                }

                $scrapItemId = $row['scrap_item_id'] ?? $oldItem?->scrap_item_id;
                if (!$scrapItemId && isset($row['id']) && is_numeric($row['id']) && $oldItemsById->has($row['id'])) {
                    $scrapItemId = $oldItemsById->get($row['id'])->scrap_item_id;
                }

                if (!$scrapItemId) {
                    throw new Exception("Barang pada baris ke-" . ($idx + 1) . " belum dipilih dari master.");
                }

                /** @var ScrapItem|null $scrapItem */
                $scrapItem = ScrapItem::with('stockBalance')->find($scrapItemId);
                if (!$scrapItem) {
                    throw new Exception("Barang rongsok ID {$scrapItemId} tidak ditemukan di master database.");
                }

                $unitType = $row['unit_type'] ?? $oldItem?->unit_type ?? $scrapItem->unit_type ?? 'kg';
                $itemTypeName = $row['item_type_name'] ?? $oldItem?->item_type_name ?? null;
                $isPcs = in_array(strtolower($unitType), ['pcs', 'satuan', 'unit']);

                if ($isPcs) {
                    $quantity = max(1, (float) ($row['quantity'] ?? $oldItem?->quantity ?? 1));
                    $weight = 0;
                    $stockQtyNeeded = $quantity;
                } else {
                    $weight = (float) ($row['weight'] ?? $oldItem?->weight ?? 0);
                    $quantity = 1;
                    $stockQtyNeeded = $weight;
                    if ($weight <= 0) {
                        throw new Exception("Berat jual untuk barang '{$scrapItem->name}' harus lebih dari 0 kg.");
                    }
                }

                $sellPrice = (float) ($row['sell_price_per_kg'] ?? 0);
                if ($sellPrice <= 0) {
                    throw new Exception("Harga jual untuk '{$scrapItem->name}' harus lebih dari Rp 0.");
                }

                $stockBalance = StockBalance::where('scrap_item_id', $scrapItem->id)->lockForUpdate()->first();
                $availStock = (float) ($stockBalance?->current_stock ?? 0);
                if ($stockQtyNeeded > $availStock) {
                    throw new Exception("Stok untuk '{$scrapItem->name}' tidak mencukupi! Tersedia: " . number_format($availStock, 2, ',', '.') . " {$unitType}, diminta: " . number_format($stockQtyNeeded, 2, ',', '.'));
                }

                $selectedBuyPrice = isset($row['buy_price_snapshot']) && is_numeric($row['buy_price_snapshot']) && (float) $row['buy_price_snapshot'] > 0
                    ? (float) $row['buy_price_snapshot']
                    : ($oldItem && (float) $oldItem->buy_price_snapshot > 0
                        ? (float) $oldItem->buy_price_snapshot
                        : (float) ($stockBalance?->average_buy_price ?? $scrapItem->buy_price_per_kg));

                $qtyForCalc = $isPcs ? $quantity : $weight;
                $itemSubtotal = round($qtyForCalc * $sellPrice, 2);
                $itemCogs = round($qtyForCalc * $selectedBuyPrice, 2);
                $itemProfit = round($itemSubtotal - $itemCogs, 2);

                $totalWeight += $weight;
                $subtotalAmount += $itemSubtotal;
                $totalEstimatedCogs += $itemCogs;

                $processedItems[] = [
                    'id' => $row['id'] ?? (is_numeric($idx) && $oldItemsById->has($idx) ? $idx : null),
                    'scrap_item' => $scrapItem,
                    'stock_balance' => $stockBalance,
                    'item_price_stock_id' => $row['item_price_stock_id'] ?? $oldItem?->item_price_stock_id,
                    'unit_type' => $unitType,
                    'item_type_name' => $itemTypeName,
                    'quantity' => $quantity,
                    'weight' => $weight,
                    'stock_qty_needed' => $stockQtyNeeded,
                    'sell_price_per_kg' => $sellPrice,
                    'buy_price_snapshot' => $selectedBuyPrice,
                    'subtotal' => $itemSubtotal,
                    'cogs_subtotal' => $itemCogs,
                    'profit_subtotal' => $itemProfit,
                    'package_qty' => (int) ($row['package_qty'] ?? 0),
                    'notes' => $row['notes'] ?? $oldItem?->notes,
                ];
            }

            // 2. Perbarui item yang sudah ada, buat item baru yang ditambah, dan hapus item yang di-remove
            $keptItemIds = [];
            $buyerName = $data['buyer_name'] ?? $transaction->buyer_name;

            foreach ($processedItems as $item) {
                /** @var ScrapItem $scrapItem */
                $scrapItem = $item['scrap_item'];
                /** @var StockBalance $stockBalance */
                $stockBalance = $item['stock_balance'];

                $itemModel = null;
                if (!empty($item['id']) && $oldItemsById->has($item['id'])) {
                    $itemModel = $oldItemsById->get($item['id']);
                }

                if ($itemModel) {
                    $itemModel->update([
                        'scrap_item_id' => $scrapItem->id,
                        'item_name_snapshot' => $scrapItem->name,
                        'item_code_snapshot' => $scrapItem->code,
                        'unit_type' => $item['unit_type'],
                        'item_type_name' => $item['item_type_name'],
                        'quantity' => $item['quantity'],
                        'weight' => $item['weight'],
                        'sell_price_per_kg' => $item['sell_price_per_kg'],
                        'buy_price_snapshot' => $item['buy_price_snapshot'],
                        'subtotal' => $item['subtotal'],
                        'cogs_subtotal' => $item['cogs_subtotal'],
                        'profit_subtotal' => $item['profit_subtotal'],
                        'notes' => $item['notes'],
                    ]);
                    $keptItemIds[] = $itemModel->id;
                } else {
                    $createdItem = SalesTransactionItem::create([
                        'sales_transaction_id' => $transaction->id,
                        'scrap_item_id' => $scrapItem->id,
                        'item_name_snapshot' => $scrapItem->name,
                        'item_code_snapshot' => $scrapItem->code,
                        'unit_type' => $item['unit_type'],
                        'item_type_name' => $item['item_type_name'],
                        'quantity' => $item['quantity'],
                        'weight' => $item['weight'],
                        'sell_price_per_kg' => $item['sell_price_per_kg'],
                        'buy_price_snapshot' => $item['buy_price_snapshot'],
                        'subtotal' => $item['subtotal'],
                        'cogs_subtotal' => $item['cogs_subtotal'],
                        'profit_subtotal' => $item['profit_subtotal'],
                        'notes' => $item['notes'],
                    ]);
                    $keptItemIds[] = $createdItem->id;
                }

                // Potong stok gudang
                $reductionQty = $item['stock_qty_needed'];
                $newStock = max(0, (float) $stockBalance->current_stock - $reductionQty);
                $newTotalOut = (float) $stockBalance->total_out + $reductionQty;

                $stockBalance->update([
                    'current_stock' => $newStock,
                    'total_out' => $newTotalOut,
                ]);

                // Potong stok bertingkat
                $priceStock = null;
                if (!empty($item['item_price_stock_id'])) {
                    $priceStock = ItemPriceStock::where('id', $item['item_price_stock_id'])
                        ->where('scrap_item_id', $scrapItem->id)
                        ->first();
                }
                if (!$priceStock) {
                    $priceStock = ItemPriceStock::where('scrap_item_id', $scrapItem->id)
                        ->where(function ($q) use ($item) {
                            $q->where('price_per_kg', $item['buy_price_snapshot'])
                              ->orWhereRaw('ABS(price_per_kg - ?) < 0.01', [$item['buy_price_snapshot']]);
                        })
                        ->first();
                }
                if (!$priceStock) {
                    $priceStock = ItemPriceStock::where('scrap_item_id', $scrapItem->id)
                        ->orderByDesc('current_stock')
                        ->first();
                }
                if ($priceStock) {
                    $priceStock->update([
                        'current_stock' => max(0, (float) $priceStock->current_stock - $reductionQty),
                        'total_out' => (float) $priceStock->total_out + $reductionQty,
                    ]);
                }

                // Catat mutasi stok
                StockMovement::create([
                    'scrap_item_id' => $scrapItem->id,
                    'user_id' => $user->id,
                    'type' => StockMovement::TYPE_SALE,
                    'reference_type' => SalesTransaction::class,
                    'reference_id' => $transaction->id,
                    'weight_qty' => -$reductionQty,
                    'unit_price' => $item['sell_price_per_kg'],
                    'balance_after' => $newStock,
                    'notes' => "Finalisasi penjualan kepada {$buyerName} (Nota {$transaction->invoice_number})",
                ]);
            }

            // Hapus item lama yang tidak lagi ada di input (dihapus oleh admin)
            $transaction->items()->whereNotIn('id', $keptItemIds)->delete();

            $discountAmount = max(0, (float) ($data['discount_amount'] ?? $transaction->discount_amount ?? 0));
            $finalAmount = max(0, $subtotalAmount - $discountAmount);
            $estimatedProfit = round($finalAmount - $totalEstimatedCogs, 2);

            $deliveryType = $data['delivery_type'] ?? $transaction->delivery_type;
            $deliveryOrderId = $transaction->delivery_order_id;

            if ($deliveryType === SalesTransaction::DELIVERY_TYPE_PICKUP) {
                $status = SalesTransaction::STATUS_COMPLETED;
            } else {
                if ($deliveryOrderId) {
                    $existingDo = DeliveryOrder::find($deliveryOrderId);
                    $status = ($existingDo && $existingDo->status === 'delivered')
                        ? SalesTransaction::STATUS_COMPLETED
                        : SalesTransaction::STATUS_PENDING_DELIVERY;

                    // Sync DeliveryOrderItem with updated items
                    $existingDo->items()->delete();
                    $totalPackages = 0;
                    foreach ($processedItems as $it) {
                        $pQty = (int) ($it['package_qty'] ?? 0);
                        $totalPackages += $pQty;
                        DeliveryOrderItem::create([
                            'delivery_order_id' => $existingDo->id,
                            'scrap_item_id' => $it['scrap_item']->id,
                            'item_name' => $it['scrap_item']->name,
                            'unit_type' => $it['unit_type'],
                            'item_type_name' => $it['item_type_name'],
                            'quantity' => $it['quantity'],
                            'weight' => $it['weight'],
                            'package_qty' => $pQty,
                            'price_per_kg' => $it['sell_price_per_kg'],
                            'subtotal' => $it['subtotal'],
                            'notes' => $it['notes'],
                        ]);
                    }
                    $existingDo->update([
                        'recipient_name' => $buyerName,
                        'recipient_phone' => $data['buyer_phone'] ?? $transaction->buyer_phone,
                        'recipient_address' => $data['buyer_address'] ?? $transaction->buyer_address,
                        'total_weight' => $totalWeight,
                        'total_packages' => $totalPackages,
                        'driver_name' => $data['driver_name'] ?? $existingDo->driver_name,
                        'driver_phone' => $data['driver_phone'] ?? $existingDo->driver_phone,
                        'vehicle_number' => $data['vehicle_number'] ?? $existingDo->vehicle_number,
                        'vehicle_type' => $data['vehicle_type'] ?? $existingDo->vehicle_type,
                    ]);
                } else {
                    $status = SalesTransaction::STATUS_PENDING_DELIVERY;

                    // Create DO if driver details provided
                    if (!empty($data['driver_name']) || !empty($data['vehicle_number'])) {
                        $doDate = $transaction->transaction_date;
                        $deliveryNumber = FormatHelper::generateDeliveryOrderNumber($doDate);
                        $totalPackages = 0;
                        foreach ($processedItems as $it) {
                            $totalPackages += (int) ($it['package_qty'] ?? 0);
                        }

                        $driverId = $data['driver_id'] ?? null;
                        $driverName = trim($data['driver_name'] ?? '');
                        $driver = $driverId ? Driver::find($driverId) : ($driverName ? Driver::where('name', $driverName)->first() : null);

                        $createdDo = DeliveryOrder::create([
                            'delivery_number' => $deliveryNumber,
                            'sales_transaction_id' => $transaction->id,
                            'delivery_date' => $doDate,
                            'recipient_name' => $buyerName,
                            'recipient_phone' => $data['buyer_phone'] ?? null,
                            'recipient_address' => $data['buyer_address'] ?? null,
                            'driver_id' => $driver?->id,
                            'driver_name' => $driver?->name ?? $driverName,
                            'driver_phone' => $driver?->phone ?? ($data['driver_phone'] ?? null),
                            'vehicle_number' => $data['vehicle_number'] ?? ($driver?->vehicle_number ?? null),
                            'vehicle_type' => $data['vehicle_type'] ?? ($driver?->vehicle_type ?? 'Truk'),
                            'total_weight' => $totalWeight,
                            'total_packages' => $totalPackages,
                            'status' => 'sent',
                            'user_id' => $user->id,
                        ]);

                        foreach ($processedItems as $it) {
                            DeliveryOrderItem::create([
                                'delivery_order_id' => $createdDo->id,
                                'scrap_item_id' => $it['scrap_item']->id,
                                'item_name' => $it['scrap_item']->name,
                                'unit_type' => $it['unit_type'],
                                'item_type_name' => $it['item_type_name'],
                                'quantity' => $it['quantity'],
                                'weight' => $it['weight'],
                                'package_qty' => (int) ($it['package_qty'] ?? 0),
                                'price_per_kg' => $it['sell_price_per_kg'],
                                'subtotal' => $it['subtotal'],
                                'notes' => $it['notes'],
                            ]);
                        }

                        $deliveryOrderId = $createdDo->id;
                    }
                }
            }

            $targetPaymentMethodId = $data['payment_method_id'] ?? $transaction->payment_method_id;
            $pm = $targetPaymentMethodId ? PaymentMethod::find($targetPaymentMethodId) : null;
            $isBank = false;
            if ($pm) {
                $mName = strtolower($pm->name);
                $mCode = strtolower($pm->code ?? '');
                $isCash = $mCode === 'cash' || str_contains($mName, 'tunai') || str_contains($mName, 'cash');
                $isBank = !$isCash && (
                    str_contains($mName, 'bank') ||
                    str_contains($mName, 'transfer') ||
                    str_contains($mCode, 'transfer') ||
                    str_contains($mCode, 'bank') ||
                    !empty($pm->bank_name)
                );
            }

            $bankInfo = null;
            if ($isBank) {
                $bankInfo = $transaction->bank_info;
                $customBank = trim($data['custom_bank'] ?? '');
                $customAccountName = trim($data['custom_account_name'] ?? '');
                $customAccountNo = trim($data['custom_account_no'] ?? '');

                if (!empty($customAccountNo) || !empty($customAccountName) || !empty($customBank)) {
                    $parts = [];
                    if (!empty($customBank)) $parts[] = $customBank;
                    if (!empty($customAccountNo)) $parts[] = "Rek: {$customAccountNo}";
                    if (!empty($customAccountName)) $parts[] = "a/n {$customAccountName}";
                    $bankInfo = implode(' - ', $parts);
                } elseif (!empty($data['bank_info'])) {
                    $bankInfo = $data['bank_info'];
                }
            }

            $transaction->update([
                'buyer_name' => $buyerName,
                'buyer_phone' => $data['buyer_phone'] ?? $transaction->buyer_phone,
                'buyer_address' => $data['buyer_address'] ?? $transaction->buyer_address,
                'delivery_type' => $deliveryType,
                'delivery_order_id' => $deliveryOrderId,
                'payment_method_id' => $data['payment_method_id'] ?? $transaction->payment_method_id,
                'bank_info' => $bankInfo,
                'total_weight' => $totalWeight,
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'estimated_cogs' => $totalEstimatedCogs,
                'estimated_profit' => $estimatedProfit,
                'payment_status' => $data['payment_status'] ?? 'paid',
                'status' => $status,
                'notes' => $data['notes'] ?? $transaction->notes,
            ]);

            ActivityLog::record(
                action: 'sale_finalized',
                subject: $transaction,
                description: "Penjualan draft {$transaction->invoice_number} difinalisasi oleh {$user->name}. Harga total: Rp " . number_format($finalAmount, 0, ',', '.'),
                payload: ['final_amount' => $finalAmount, 'total_weight' => $totalWeight, 'status' => $status]
            );

            return $transaction->fresh(['items.scrapItem', 'user', 'paymentMethod']);
        });
    }

    /**
     * Generate unique auto-incrementing invoice number per day.
     */
    protected function generateInvoiceNumber(Carbon $date): string
    {
        $prefix = 'PPJ-JL-' . $date->format('ymd') . '-';
        $latest = SalesTransaction::where('invoice_number', 'LIKE', $prefix . '%')
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

