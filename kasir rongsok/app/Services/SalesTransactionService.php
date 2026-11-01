<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
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
            $invoiceNumber = $this->generateInvoiceNumber($date);

            $buyerName = trim($data['buyer_name'] ?? '');
            if (empty($buyerName)) {
                throw new Exception('Nama pembeli / pabrik rongsok wajib diisi.');
            }

            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new Exception('Penjualan harus memiliki minimal 1 barang rongsok.');
            }

            $totalWeight = 0;
            $subtotalAmount = 0;
            $totalEstimatedCogs = 0;

            $processedItems = [];

            foreach ($itemsData as $row) {
                $scrapItemId = $row['scrap_item_id'];
                $scrapItem = ScrapItem::with('stockBalance')->lockForUpdate()->findOrFail($scrapItemId);

                $weight = max(0, (float) ($row['weight'] ?? 0));
                if ($weight <= 0) {
                    throw new Exception("Berat jual untuk item '{$scrapItem->name}' harus lebih dari 0 kg.");
                }

                $stockBalance = $scrapItem->stockBalance;
                $currentStock = (float) ($stockBalance?->current_stock ?? 0);

                // Prevent negative stock
                if ($weight > $currentStock) {
                    throw new Exception(
                        "Stok barang '{$scrapItem->name}' tidak mencukupi! Tersedia: " .
                        number_format($currentStock, 2) . " kg, ingin dijual: " .
                        number_format($weight, 2) . " kg."
                    );
                }

                $sellPrice = isset($row['sell_price_per_kg']) && is_numeric($row['sell_price_per_kg'])
                    ? (float) $row['sell_price_per_kg']
                    : (float) $scrapItem->sell_price_per_kg;

                if ($sellPrice <= 0) {
                    throw new Exception("Harga jual per kg untuk '{$scrapItem->name}' harus lebih dari Rp 0.");
                }

                $avgBuyPrice = (float) ($stockBalance?->average_buy_price ?? $scrapItem->buy_price_per_kg);

                $itemSubtotal = round($weight * $sellPrice, 2);
                $itemCogs = round($weight * $avgBuyPrice, 2);
                $itemProfit = round($itemSubtotal - $itemCogs, 2);

                $totalWeight += $weight;
                $subtotalAmount += $itemSubtotal;
                $totalEstimatedCogs += $itemCogs;

                $processedItems[] = [
                    'scrap_item' => $scrapItem,
                    'stock_balance' => $stockBalance,
                    'weight' => $weight,
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

            $transaction = SalesTransaction::create([
                'invoice_number' => $invoiceNumber,
                'buyer_name' => $buyerName,
                'buyer_phone' => $data['buyer_phone'] ?? null,
                'buyer_address' => $data['buyer_address'] ?? null,
                'user_id' => $user->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'transaction_date' => $date,
                'total_weight' => $totalWeight,
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'estimated_cogs' => $totalEstimatedCogs,
                'estimated_profit' => $estimatedProfit,
                'payment_status' => $data['payment_status'] ?? 'paid',
                'status' => SalesTransaction::STATUS_COMPLETED,
                'notes' => $data['notes'] ?? null,
            ]);

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
                    'weight' => $item['weight'],
                    'sell_price_per_kg' => $item['sell_price_per_kg'],
                    'buy_price_snapshot' => $item['buy_price_snapshot'],
                    'subtotal' => $item['subtotal'],
                    'cogs_subtotal' => $item['cogs_subtotal'],
                    'profit_subtotal' => $item['profit_subtotal'],
                    'notes' => $item['notes'],
                ]);

                // Reduce stock
                $newStock = max(0, (float) $stockBalance->current_stock - $item['weight']);
                $newTotalOut = (float) $stockBalance->total_out + $item['weight'];

                $stockBalance->update([
                    'current_stock' => $newStock,
                    'total_out' => $newTotalOut,
                ]);

                // Log movement
                StockMovement::create([
                    'scrap_item_id' => $scrapItem->id,
                    'user_id' => $user->id,
                    'type' => StockMovement::TYPE_SALE,
                    'reference_type' => SalesTransaction::class,
                    'reference_id' => $transaction->id,
                    'weight_qty' => -$item['weight'],
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
                    $newStock = (float) $stockBalance->current_stock + (float) $item->weight;
                    $newTotalOut = max(0, (float) $stockBalance->total_out - (float) $item->weight);

                    $stockBalance->update([
                        'current_stock' => $newStock,
                        'total_out' => $newTotalOut,
                    ]);

                    StockMovement::create([
                        'scrap_item_id' => $item->scrap_item_id,
                        'user_id' => $user->id,
                        'type' => StockMovement::TYPE_REVERSAL,
                        'reference_type' => SalesTransaction::class,
                        'reference_id' => $transaction->id,
                        'weight_qty' => $item->weight,
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
        $prefix = 'PJ-' . $date->format('ymd') . '-';
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

