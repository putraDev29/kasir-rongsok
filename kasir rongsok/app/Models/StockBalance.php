<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'scrap_item_id',
        'current_stock',
        'total_in',
        'total_out',
        'average_buy_price',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:2',
            'total_in' => 'decimal:2',
            'total_out' => 'decimal:2',
            'average_buy_price' => 'decimal:2',
        ];
    }

    public function scrapItem(): BelongsTo
    {
        return $this->belongsTo(ScrapItem::class);
    }

    public function getEstimatedStockValueAttribute(): float
    {
        return (float) ($this->current_stock * $this->average_buy_price);
    }
}

