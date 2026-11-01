<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseTransaction extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_PENDING = 'pending';

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'supplier_name',
        'supplier_phone',
        'supplier_address',
        'user_id',
        'payment_method_id',
        'transaction_date',
        'total_gross_weight',
        'total_tare_weight',
        'total_net_weight',
        'subtotal_amount',
        'discount_amount',
        'rounding_amount',
        'final_amount',
        'debt_deduction_amount',
        'net_cash_paid',
        'remaining_debt_after',
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
            'total_gross_weight' => 'decimal:2',
            'total_tare_weight' => 'decimal:2',
            'total_net_weight' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'rounding_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'debt_deduction_amount' => 'decimal:2',
            'net_cash_paid' => 'decimal:2',
            'remaining_debt_after' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(PurchaseTransactionItem::class);
    }

    public function debtPayments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}

