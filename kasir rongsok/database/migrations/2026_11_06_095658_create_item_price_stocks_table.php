<?php

use App\Models\ScrapItem;
use App\Models\StockBalance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_price_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrap_item_id')->constrained('scrap_items')->cascadeOnDelete();
            $table->decimal('price_per_kg', 12, 2)->default(0);
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('total_in', 12, 2)->default(0);
            $table->decimal('total_out', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['scrap_item_id', 'price_per_kg']);
        });

        // Initialize existing stock and historical purchases into item_price_stocks
        $scrapItems = ScrapItem::with('stockBalance')->get();

        foreach ($scrapItems as $item) {
            $currentTotalStock = (float) ($item->stockBalance?->current_stock ?? 0);
            $masterBuyPrice = (float) $item->buy_price_per_kg;

            // Check if there are past purchase items with specific prices
            $purchasePrices = DB::table('purchase_transaction_items')
                ->where('scrap_item_id', $item->id)
                ->where('is_cancelled', false)
                ->select('price_per_kg', DB::raw('SUM(net_weight) as total_net'), DB::raw('SUM(quantity) as total_qty'))
                ->groupBy('price_per_kg')
                ->get();

            $allocatedStock = 0;

            if ($purchasePrices->isNotEmpty()) {
                $totalPurchased = $purchasePrices->sum(fn ($p) => (float) ($p->total_net > 0 ? $p->total_net : $p->total_qty));

                foreach ($purchasePrices as $p) {
                    $price = (float) $p->price_per_kg;
                    $inQty = (float) ($p->total_net > 0 ? $p->total_net : $p->total_qty);

                    // Estimate current stock proportionally if total purchases exist
                    $ratio = $totalPurchased > 0 ? ($inQty / $totalPurchased) : 1;
                    $tierStock = round($currentTotalStock * $ratio, 2);
                    $allocatedStock += $tierStock;

                    DB::table('item_price_stocks')->updateOrInsert(
                        [
                            'scrap_item_id' => $item->id,
                            'price_per_kg' => $price,
                        ],
                        [
                            'current_stock' => $tierStock,
                            'total_in' => $inQty,
                            'total_out' => max(0, $inQty - $tierStock),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            // If remaining stock not yet allocated or no purchases, allocate to master buy price tier
            $remaining = round($currentTotalStock - $allocatedStock, 2);
            if ($remaining > 0 || $purchasePrices->isEmpty()) {
                $tierStock = max(0, $remaining > 0 ? $remaining : $currentTotalStock);

                $existing = DB::table('item_price_stocks')
                    ->where('scrap_item_id', $item->id)
                    ->where('price_per_kg', $masterBuyPrice)
                    ->first();

                if ($existing) {
                    DB::table('item_price_stocks')
                        ->where('id', $existing->id)
                        ->update([
                            'current_stock' => (float) $existing->current_stock + $tierStock,
                            'total_in' => (float) $existing->total_in + $tierStock,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('item_price_stocks')->insert([
                        'scrap_item_id' => $item->id,
                        'price_per_kg' => $masterBuyPrice,
                        'current_stock' => $tierStock,
                        'total_in' => $tierStock,
                        'total_out' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_price_stocks');
    }
};
