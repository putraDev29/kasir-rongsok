<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_transaction_id',
        'scrap_item_id',
        'item_name_snapshot',
        'item_type_name',
        'item_code_snapshot',
        'unit_type',
        'quantity',
        'gross_weight',
        'tare_weight',
        'net_weight',
        'price_per_kg',
        'subtotal',
        'is_cancelled',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gross_weight' => 'decimal:2',
            'tare_weight' => 'decimal:2',
            'net_weight' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PurchaseTransaction::class, 'purchase_transaction_id');
    }

    public function scrapItem(): BelongsTo
    {
        return $this->belongsTo(ScrapItem::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return (bool) $this->is_cancelled;
    }

    public function getEffectiveUnitAttribute(): string
    {
        $u = strtolower(trim($this->unit_type ?: ($this->scrapItem?->unit ?: 'kg')));
        return in_array($u, ['pcs', 'satuan', 'unit', 'buah']) ? 'pcs' : 'kg';
    }

    public function getQuantityOrWeightAttribute(): float
    {
        return $this->effective_unit === 'pcs'
            ? (float) ($this->quantity > 0 ? $this->quantity : $this->net_weight)
            : (float) $this->net_weight;
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

