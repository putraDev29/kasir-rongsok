<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';

    public const TYPE_SUPPLIER = 'supplier';
    public const TYPE_KARYAWAN = 'karyawan';
    public const TYPE_LAINNYA = 'lainnya';

    protected $fillable = [
        'debt_number',
        'supplier_id',
        'borrower_name',
        'borrower_phone',
        'borrower_type',
        'user_id',
        'date',
        'due_date',
        'initial_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'initial_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
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

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class)->latest('payment_date');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->remaining_amount <= 0;
    }
}

