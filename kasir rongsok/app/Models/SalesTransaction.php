<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTransaction extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_number',
        'buyer_name',
        'buyer_phone',
        'buyer_address',
        'user_id',
        'payment_method_id',
        'transaction_date',
        'total_weight',
        'subtotal_amount',
        'discount_amount',
        'final_amount',
        'estimated_cogs',
        'estimated_profit',
        'payment_status',
        'status',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'cancelled_at' => 'datetime',
            'total_weight' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'estimated_cogs' => 'decimal:2',
            'estimated_profit' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesTransactionItem::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getCustomerNameAttribute(): ?string
    {
        return $this->buyer_name;
    }

    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->buyer_phone;
    }

    /**
     * Group sales transaction items by master item.
     * Totals are aggregated on the master item, while variant details (jenis varian, jumlah, harga)
     * are preserved in the items sub-collection.
     */
    public function getGroupedMasterItemsAttribute()
    {
        return $this->items->groupBy(function ($item) {
            return $item->scrap_item_id ? 'id_' . $item->scrap_item_id : 'name_' . $item->item_name_snapshot;
        })->map(function ($items) {
            $first = $items->first();
            $totalWeight = (float) $items->sum('weight');
            $totalQuantity = (float) $items->sum('quantity');
            $totalSubtotal = (float) $items->sum('subtotal');
            $totalCogs = (float) $items->sum('cogs_subtotal');
            $totalProfit = (float) $items->sum('profit_subtotal');

            $hasVariants = $items->contains(fn($it) => !empty(trim($it->item_type_name ?? ''))) || $items->count() > 1;

            return (object) [
                'scrap_item_id' => $first->scrap_item_id,
                'item_code' => $first->item_code_snapshot ?: ($first->scrapItem?->code ?? '-'),
                'item_name' => $first->item_name_snapshot,
                'unit_type' => $first->unit_type ?? 'kg',
                'total_weight' => $totalWeight,
                'total_quantity' => $totalQuantity,
                'total_subtotal' => $totalSubtotal,
                'total_cogs' => $totalCogs,
                'total_profit' => $totalProfit,
                'avg_sell_price' => $totalWeight > 0 ? ($totalSubtotal / $totalWeight) : ($totalQuantity > 0 ? ($totalSubtotal / $totalQuantity) : (float) $first->sell_price_per_kg),
                'has_variants' => $hasVariants,
                'items' => $items,
            ];
        })->values();
    }
}
