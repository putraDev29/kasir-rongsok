<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScrapItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'unit',
        'buy_price_per_kg',
        'sell_price_per_kg',
        'min_stock_alert',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'buy_price_per_kg' => 'decimal:2',
            'sell_price_per_kg' => 'decimal:2',
            'min_stock_alert' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function stockBalance(): HasOne
    {
        return $this->hasOne(StockBalance::class, 'scrap_item_id');
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ScrapItemPriceHistory::class, 'scrap_item_id')->latest();
    }

    public function priceStocks(): HasMany
    {
        return $this->hasMany(ItemPriceStock::class, 'scrap_item_id')->orderBy('price_per_kg');
    }

    /**
     * Get available sales/delivery order options split by price tier.
     * Misal Besi ada 3 harga berbeda, maka ada 3 opsi sesuai harga.
     */
    public function getPriceTierOptions(bool $onlyWithStock = true): array
    {
        $query = $this->priceStocks();
        if ($onlyWithStock) {
            $query->where('current_stock', '>', 0);
        }
        $tiers = $query->get();

        if ($tiers->isEmpty()) {
            $defaultPrice = (float) $this->buy_price_per_kg;
            $stock = (float) ($this->stockBalance?->current_stock ?? 0);
            return [
                [
                    'id' => $this->id,
                    'tier_id' => $this->id . '_' . (int) $defaultPrice,
                    'scrap_item_id' => $this->id,
                    'code' => $this->code,
                    'base_name' => $this->name,
                    'name' => $this->name . ' - ' . \App\Services\FormatHelper::rupiah($defaultPrice),
                    'price_per_kg' => $defaultPrice,
                    'buy_price_snapshot' => $defaultPrice,
                    'sell_price_per_kg' => (float) $this->sell_price_per_kg,
                    'current_stock' => $stock,
                    'unit_type' => $this->unit_type ?: 'kg',
                    'category_name' => $this->category?->name,
                    'category_id' => $this->category_id,
                ]
            ];
        }

        $options = [];
        foreach ($tiers as $tier) {
            $price = (float) $tier->price_per_kg;
            $options[] = [
                'id' => $this->id,
                'tier_id' => $this->id . '_' . (int) $price,
                'scrap_item_id' => $this->id,
                'code' => $this->code,
                'base_name' => $this->name,
                'name' => $this->name . ' - ' . \App\Services\FormatHelper::rupiah($price),
                'price_per_kg' => $price,
                'buy_price_snapshot' => $price,
                'sell_price_per_kg' => (float) $this->sell_price_per_kg,
                'current_stock' => (float) $tier->current_stock,
                'unit_type' => $this->unit_type ?: 'kg',
                'category_name' => $this->category?->name,
                'category_id' => $this->category_id,
            ];
        }

        return $options;
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'scrap_item_id')->latest();
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseTransactionItem::class, 'scrap_item_id');
    }

    public function salesItems(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class, 'scrap_item_id');
    }

    public function getCurrentStockAttribute(): float
    {
        return (float) ($this->stockBalance?->current_stock ?? 0);
    }

    public function isLowStock(): bool
    {
        if ($this->min_stock_alert <= 0) {
            return false;
        }
        return $this->current_stock <= (float) $this->min_stock_alert;
    }
}

