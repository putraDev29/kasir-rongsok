<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ScrapItem;
use App\Models\ScrapItemPriceHistory;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Perform physical stock opname / adjustment.
     *
     * @throws Exception
     */
    public function adjustStock(ScrapItem $item, float $newStock, string $reason, User $user): StockBalance
    {
        if ($newStock < 0) {
            throw new Exception('Stok penyesuaian tidak boleh bernilai negatif.');
        }

        return DB::transaction(function () use ($item, $newStock, $reason, $user) {
            $balance = StockBalance::firstOrCreate(
                ['scrap_item_id' => $item->id],
                ['current_stock' => 0, 'total_in' => 0, 'total_out' => 0, 'average_buy_price' => $item->buy_price_per_kg]
            );

            $currentStock = (float) $balance->current_stock;
            $diff = round($newStock - $currentStock, 2);

            if ($diff == 0) {
                return $balance;
            }

            $balance->update([
                'current_stock' => $newStock,
            ]);

            StockMovement::create([
                'scrap_item_id' => $item->id,
                'user_id' => $user->id,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'reference_type' => null,
                'reference_id' => null,
                'weight_qty' => $diff,
                'unit_price' => $balance->average_buy_price,
                'balance_after' => $newStock,
                'notes' => "Penyesuaian stok manual: {$reason} (Sebelumnya: {$currentStock} kg)",
            ]);

            ActivityLog::record(
                action: 'stock_adjusted',
                subject: $item,
                description: "Penyesuaian stok {$item->name} dari {$currentStock} kg menjadi {$newStock} kg oleh {$user->name}. Alasan: {$reason}",
                payload: ['old_stock' => $currentStock, 'new_stock' => $newStock, 'diff' => $diff]
            );

            return $balance;
        });
    }

    /**
     * Update item prices and track price history.
     */
    public function updatePrice(ScrapItem $item, float $newBuyPrice, float $newSellPrice, ?string $reason, User $user): ScrapItem
    {
        return DB::transaction(function () use ($item, $newBuyPrice, $newSellPrice, $reason, $user) {
            $oldBuy = (float) $item->buy_price_per_kg;
            $oldSell = (float) $item->sell_price_per_kg;

            if ($oldBuy != $newBuyPrice || $oldSell != $newSellPrice) {
                ScrapItemPriceHistory::create([
                    'scrap_item_id' => $item->id,
                    'user_id' => $user->id,
                    'old_buy_price' => $oldBuy,
                    'new_buy_price' => $newBuyPrice,
                    'old_sell_price' => $oldSell,
                    'new_sell_price' => $newSellPrice,
                    'reason' => $reason,
                ]);

                $item->update([
                    'buy_price_per_kg' => $newBuyPrice,
                    'sell_price_per_kg' => $newSellPrice,
                ]);

                ActivityLog::record(
                    action: 'price_updated',
                    subject: $item,
                    description: "Perubahan harga {$item->name}: Beli (Rp {$oldBuy} -> Rp {$newBuyPrice}), Jual (Rp {$oldSell} -> Rp {$newSellPrice})",
                    payload: ['old_buy' => $oldBuy, 'new_buy' => $newBuyPrice, 'reason' => $reason]
                );
            }

            return $item;
        });
    }
}

