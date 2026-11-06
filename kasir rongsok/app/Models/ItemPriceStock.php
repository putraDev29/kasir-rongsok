<?php

namespace App\Models;

use App\Services\FormatHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPriceStock extends Model
{
    use HasFactory;

    protected $table = 'item_price_stocks';

    protected $fillable = [
        'scrap_item_id',
        'price_per_kg',
        'current_stock',
        'total_in',
        'total_out',
    ];

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'total_in' => 'decimal:2',
            'total_out' => 'decimal:2',
        ];
    }

    public function scrapItem(): BelongsTo
    {
        return $this->belongsTo(ScrapItem::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return FormatHelper::rupiah($this->price_per_kg);
    }

    public function getFormattedStockAttribute(): string
    {
        $unit = $this->scrapItem?->unit_type ?? 'kg';
        if (in_array($unit, ['pcs', 'satuan', 'unit'])) {
            return FormatHelper::number($this->current_stock) . ' ' . $unit;
        }
        return FormatHelper::weight($this->current_stock) . ' kg';
    }
}
