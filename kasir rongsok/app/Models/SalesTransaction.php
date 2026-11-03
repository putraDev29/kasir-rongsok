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
}

