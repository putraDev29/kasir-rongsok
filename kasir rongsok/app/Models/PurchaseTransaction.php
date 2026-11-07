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
        'bank_info',
        'payment_notes',
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

    public function activeItems(): HasMany
    {
        return $this->hasMany(PurchaseTransactionItem::class)->where('is_cancelled', false);
    }

    public function debtPayments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isBankPayment(): bool
    {
        $pm = $this->paymentMethod;
        if ($pm) {
            $methodName = strtolower($pm->name ?? '');
            $methodCode = strtolower($pm->code ?? '');

            if ($methodCode === 'cash' || str_contains($methodName, 'tunai') || str_contains($methodName, 'cash')) {
                return false;
            }

            return str_contains($methodName, 'bank') ||
                   str_contains($methodName, 'transfer') ||
                   str_contains($methodName, 'bca') ||
                   str_contains($methodName, 'bri') ||
                   str_contains($methodName, 'mandiri') ||
                   str_contains($methodName, 'bni') ||
                   str_contains($methodCode, 'transfer') ||
                   str_contains($methodCode, 'bank') ||
                   !empty($pm->bank_name);
        }

        if (!empty($this->bank_info)) {
            $info = strtolower($this->bank_info);
            if (str_contains($info, 'tunai') || str_contains($info, 'cash')) {
                return false;
            }
            return true;
        }

        return false;
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

        if (!empty($this->supplier?->bank_account_number)) {
            return $this->supplier->bank_account_number;
        }

        if (!empty($this->user?->bank_account_number)) {
            return $this->user->bank_account_number;
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

        if (!empty($this->supplier?->bank_account_name)) {
            return $this->supplier->bank_account_name;
        }

        if (!empty($this->user?->bank_account_name)) {
            return $this->user->bank_account_name;
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

        if (!empty($this->supplier?->bank_name)) {
            return $this->supplier->bank_name;
        }

        if (!empty($this->user?->bank_name)) {
            return $this->user->bank_name;
        }

        if (!empty($this->paymentMethod?->bank_name)) {
            return $this->paymentMethod->bank_name;
        }

        $methodName = $this->paymentMethod?->name ?? '';
        if (preg_match('/(BCA|BRI|Mandiri|BNI|BSI|CIMB|Permata|Danamon)/i', $methodName, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }
}

