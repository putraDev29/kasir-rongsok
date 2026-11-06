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
                        : (float) $scrapItem->sell_price_per_kg;

                    if ($sellPrice <= 0) {
                        throw new Exception("Harga jual satuan untuk '{$scrapItem->name}' harus lebih dari Rp 0.");
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
                        : (float) $scrapItem->sell_price_per_kg;

                    if ($sellPrice <= 0) {
                        throw new Exception("Harga jual per kg untuk '{$scrapItem->name}' harus lebih dari Rp 0.");
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
            $estimatedProfit = round($finalAmount - $totalEstimatedCogs, 2);

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

            // Sync bank account to current Cashier/Operator User profile if requested (Task 10)
            if (!empty($data['save_to_user_bank']) && (!empty($customBank) || !empty($customAccountNo) || !empty($customAccountName))) {
                $user->update(array_filter([
                    'bank_name' => $customBank ?: $user->bank_name,
                    'bank_account_number' => $customAccountNo ?: $user->bank_account_number,
                    'bank_account_name' => $customAccountName ?: $user->bank_account_name,
                ], fn($val) => !is_null($val) && $val !== ''));
            }

            // Auto-fallback bankInfo if empty and payment method is bank/transfer
            if (empty($bankInfo) && !empty($data['payment_method_id'])) {
                $pm = PaymentMethod::find($data['payment_method_id']);
                $isBank = $pm && (
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

            $deliveryType = $data['delivery_type'] ?? (isset($data['delivery_order_id']) ? SalesTransaction::DELIVERY_TYPE_DELIVERY : SalesTransaction::DELIVERY_TYPE_PICKUP);
            $deliveryOrderId = $data['delivery_order_id'] ?? null;

            // Determine status:
            // - Diambil Sendiri: status langsung selesai (completed). Tanpa surat jalan.
            // - Perlu Pengiriman: status pending_delivery sampai pengiriman selesai.
            if ($deliveryType === SalesTransaction::DELIVERY_TYPE_PICKUP) {
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
                'payment_status' => $data['payment_status'] ?? 'paid',
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

