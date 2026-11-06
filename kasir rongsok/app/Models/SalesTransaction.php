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
    public const STATUS_PENDING_DELIVERY = 'pending_delivery';
    public const STATUS_CANCELLED = 'cancelled';

    public const DELIVERY_TYPE_PICKUP = 'pickup';
    public const DELIVERY_TYPE_DELIVERY = 'delivery';

    protected $fillable = [
        'invoice_number',
        'buyer_name',
        'buyer_phone',
        'buyer_address',
        'delivery_type',
        'delivery_order_id',
        'user_id',
        'payment_method_id',
        'bank_info',
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

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPendingDelivery(): bool
    {
        return $this->status === self::STATUS_PENDING_DELIVERY;
    }

    public function isPickup(): bool
    {
        return ($this->delivery_type ?? self::DELIVERY_TYPE_PICKUP) === self::DELIVERY_TYPE_PICKUP;
    }

    public function isDelivery(): bool
    {
        return ($this->delivery_type ?? self::DELIVERY_TYPE_PICKUP) === self::DELIVERY_TYPE_DELIVERY;
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

    public function isBankPayment(): bool
    {
        if (!empty($this->bank_info)) {
            return true;
        }

        $methodName = strtolower($this->paymentMethod?->name ?? '');
        $methodCode = strtolower($this->paymentMethod?->code ?? '');

        return str_contains($methodName, 'bank') ||
               str_contains($methodName, 'transfer') ||
               str_contains($methodName, 'bca') ||
               str_contains($methodName, 'bri') ||
               str_contains($methodName, 'mandiri') ||
               str_contains($methodName, 'bni') ||
               str_contains($methodCode, 'transfer');
    }

    public function getBankAccountNumber(): ?string
    {
        if (!empty($this->bank_info)) {
            if (preg_match('/(?:Rek|No\.?\s*Rek)\s*:\s*([^-\/]+)/i', $this->bank_info, $matches)) {
                $val = trim($matches[1]);
                if (!empty($val)) {
                    return $val;
                }
            }
        }

        if (!empty($this->paymentMethod?->account_number)) {
            return $this->paymentMethod->account_number;
        }

        if (!empty($this->user?->bank_account_number)) {
            return $this->user->bank_account_number;
        }

        $owner = User::where('role', User::ROLE_SUPER_ADMIN)->whereNotNull('bank_account_number')->first();
        if ($owner && !empty($owner->bank_account_number)) {
            return $owner->bank_account_number;
        }

        $settingAcc = BusinessSetting::get('company_bank_account_number');
        if (!empty($settingAcc)) {
            return $settingAcc;
        }

        return null;
    }

    public function getBankAccountName(): ?string
    {
        if (!empty($this->bank_info)) {
            if (preg_match('/(?:a\/?n\.?)\s*:?\s*([^-\/]+)/i', $this->bank_info, $matches)) {
                $val = trim($matches[1]);
                if (!empty($val)) {
                    return $val;
                }
            }
        }

        if (!empty($this->paymentMethod?->account_holder)) {
            return $this->paymentMethod->account_holder;
        }

        if (!empty($this->user?->bank_account_name)) {
            return $this->user->bank_account_name;
        }

        $owner = User::where('role', User::ROLE_SUPER_ADMIN)->whereNotNull('bank_account_name')->first();
        if ($owner && !empty($owner->bank_account_name)) {
            return $owner->bank_account_name;
        }

        $settingName = BusinessSetting::get('company_bank_account_name');
        if (!empty($settingName)) {
            return $settingName;
        }

        return null;
    }

    public function getBankName(): ?string
    {
        if (!empty($this->bank_info)) {
            $parts = explode('-', $this->bank_info);
            $first = trim($parts[0] ?? '');
            if (!empty($first) && !preg_match('/(?:Rek|a\/n)/i', $first)) {
                return $first;
            }
        }

        if (!empty($this->paymentMethod?->bank_name)) {
            return $this->paymentMethod->bank_name;
        }

        $methodName = $this->paymentMethod?->name ?? '';
        if (preg_match('/(BCA|BRI|Mandiri|BNI|BSI|CIMB|Permata|Danamon)/i', $methodName, $matches)) {
            return strtoupper($matches[1]);
        }

        if (!empty($this->user?->bank_name)) {
            return $this->user->bank_name;
        }

        return null;
    }
}
