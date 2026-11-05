<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_number',
        'sales_transaction_id',
        'delivery_date',
        'recipient_name',
        'recipient_address',
        'recipient_phone',
        'driver_name',
        'driver_phone',
        'vehicle_number',
        'vehicle_type',
        'total_weight',
        'total_packages',
        'status',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'datetime',
            'total_weight' => 'decimal:2',
            'total_packages' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    /**
     * Group delivery order items by master item.
     * Master level totals are aggregated, while variant breakdowns are preserved underneath.
     */
    public function getGroupedMasterItemsAttribute()
    {
        return $this->items->groupBy(function ($item) {
            return $item->scrap_item_id ? 'id_' . $item->scrap_item_id : 'name_' . $item->item_name;
        })->map(function ($items) {
            $first = $items->first();
            $totalWeight = (float) $items->sum('weight');
            $totalPackages = (int) $items->sum('package_qty');
            $totalQuantity = (float) $items->sum('quantity');
            $totalSubtotal = (float) $items->sum(fn($it) => $it->effective_subtotal);

            // Determine if variants exist
            $hasVariants = $items->contains(fn($it) => !empty(trim($it->item_type_name ?? ''))) || $items->count() > 1;

            return (object) [
                'scrap_item_id' => $first->scrap_item_id,
                'item_code' => $first->scrapItem?->code ?? '-',
                'item_name' => $first->item_name,
                'unit_type' => $first->unit_type ?? 'kg',
                'total_weight' => $totalWeight,
                'total_packages' => $totalPackages,
                'total_quantity' => $totalQuantity,
                'total_subtotal' => $totalSubtotal,
                'has_variants' => $hasVariants,
                'items' => $items,
            ];
        })->values();
    }
}
