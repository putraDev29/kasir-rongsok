<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_transaction_id',
        'scrap_item_id',
        'item_name_snapshot',
        'item_type_name',
        'item_code_snapshot',
        'unit_type',
        'quantity',
        'weight',
        'sell_price_per_kg',
        'buy_price_snapshot',
        'subtotal',
        'cogs_subtotal',
        'profit_subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'sell_price_per_kg' => 'decimal:2',
            'buy_price_snapshot' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'cogs_subtotal' => 'decimal:2',
            'profit_subtotal' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }

    public function scrapItem(): BelongsTo
    {
        return $this->belongsTo(ScrapItem::class);
    }

    public function getEffectiveUnitAttribute(): string
    {
        $u = strtolower(trim($this->unit_type ?: ($this->scrapItem?->unit ?: 'kg')));
        return in_array($u, ['pcs', 'satuan', 'unit', 'buah']) ? 'pcs' : 'kg';
    }

    public function getQuantityOrWeightAttribute(): float
    {
        return $this->effective_unit === 'pcs'
            ? (float) ($this->quantity > 0 ? $this->quantity : $this->weight)
            : (float) $this->weight;
    }

    public function getFormattedQuantityAttribute(): string
    {
        $val = $this->quantity_or_weight;
        $unit = $this->effective_unit;
        return $unit === 'pcs'
            ? \App\Services\FormatHelper::number($val) . ' pcs'
            : \App\Services\FormatHelper::weight($val) . ' kg';
    }
}

