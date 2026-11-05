<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'scrap_item_id',
        'item_name',
        'item_type_name',
        'unit_type',
        'quantity',
        'weight',
        'package_qty',
        'price_per_kg',
        'subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'quantity' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'package_qty' => 'integer',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function scrapItem(): BelongsTo
    {
        return $this->belongsTo(ScrapItem::class);
    }

    /**
     * Get effective unit price (from recorded price, linked sale, or master item).
     */
    public function getEffectivePriceAttribute(): float
    {
        if ((float) $this->price_per_kg > 0) {
            return (float) $this->price_per_kg;
        }

        if ($this->deliveryOrder && $this->deliveryOrder->relationLoaded('salesTransaction') && $this->deliveryOrder->salesTransaction) {
            $stItem = $this->deliveryOrder->salesTransaction->items
                ->first(function ($it) {
                    if ($this->scrap_item_id && $it->scrap_item_id == $this->scrap_item_id) {
                        if (!empty($this->item_type_name) && !empty($it->item_type_name)) {
                            return strcasecmp(trim($this->item_type_name), trim($it->item_type_name)) === 0;
                        }
                        return true;
                    }
                    return strcasecmp(trim($it->item_name_snapshot), trim($this->item_name)) === 0;
                });

            if ($stItem) {
                return (float) $stItem->sell_price_per_kg;
            }
        }

        return (float) ($this->scrapItem?->currentPrice?->sell_price_per_kg ?? 0);
    }

    /**
     * Get effective subtotal.
     */
    public function getEffectiveSubtotalAttribute(): float
    {
        if ((float) $this->subtotal > 0) {
            return (float) $this->subtotal;
        }

        $price = $this->effective_price;
        $qty = (float) $this->weight > 0 ? (float) $this->weight : (float) ($this->quantity > 0 ? $this->quantity : 1);
        return round($price * $qty, 2);
    }
}
