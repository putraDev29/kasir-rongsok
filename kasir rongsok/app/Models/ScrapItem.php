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

