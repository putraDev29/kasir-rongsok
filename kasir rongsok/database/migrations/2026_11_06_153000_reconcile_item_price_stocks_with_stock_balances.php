<?php

use App\Models\ItemPriceStock;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reconcile item_price_stocks with stock_balances so that
     * sum of current_stock in item_price_stocks equals stock_balances.current_stock.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $scrapItems = ScrapItem::with(['stockBalance', 'priceStocks'])->get();

            foreach ($scrapItems as $item) {
                $bal = $item->stockBalance;
                $currentStock = (float) ($bal?->current_stock ?? 0);
                $tierSum = (float) $item->priceStocks->sum('current_stock');

                $diff = round($currentStock - $tierSum, 2);

                if (abs($diff) < 0.01) {
                    continue;
                }

                if ($item->priceStocks->isEmpty()) {
                    // Create primary tier if none exists
                    ItemPriceStock::create([
                        'scrap_item_id' => $item->id,
                        'price_per_kg' => (float) $item->buy_price_per_kg,
                        'current_stock' => $currentStock,
                        'total_in' => (float) ($bal?->total_in ?? $currentStock),
                        'total_out' => (float) ($bal?->total_out ?? 0),
                    ]);
                    continue;
                }

                if ($diff > 0) {
                    // StockBalance has more than tiers, add difference to the primary/first tier
                    $primaryPrice = (float) $item->buy_price_per_kg;
                    $targetTier = $item->priceStocks->firstWhere('price_per_kg', $primaryPrice)
                        ?? $item->priceStocks->first();

                    if ($targetTier) {
                        $targetTier->update([
                            'current_stock' => (float) $targetTier->current_stock + $diff,
                        ]);
                    }
                } else {
                    // StockBalance has less than tiers (diff is negative, meaning sales weren't deducted from tiers)
                    $neededDeduction = abs($diff);

                    // Deduct from primary tier first, then others if needed
                    $primaryPrice = (float) $item->buy_price_per_kg;
                    $primaryTier = $item->priceStocks->firstWhere('price_per_kg', $primaryPrice);

                    if ($primaryTier && (float) $primaryTier->current_stock > 0) {
                        $deductFromPrimary = min((float) $primaryTier->current_stock, $neededDeduction);
                        $primaryTier->update([
                            'current_stock' => (float) $primaryTier->current_stock - $deductFromPrimary,
                            'total_out' => (float) $primaryTier->total_out + $deductFromPrimary,
                        ]);
                        $neededDeduction -= $deductFromPrimary;
                    }

                    if ($neededDeduction > 0) {
                        foreach ($item->priceStocks->sortByDesc('current_stock') as $otherTier) {
                            if ($primaryTier && $otherTier->id === $primaryTier->id) {
                                continue;
                            }
                            $deduct = min((float) $otherTier->current_stock, $neededDeduction);
                            $otherTier->update([
                                'current_stock' => (float) $otherTier->current_stock - $deduct,
                                'total_out' => (float) $otherTier->total_out + $deduct,
                            ]);
                            $neededDeduction -= $deduct;
                            if ($neededDeduction <= 0) {
                                break;
                            }
                        }
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // No-op rollback as this is a data synchronization migration
    }
};
