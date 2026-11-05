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

    public const CATEGORY_MODAL = 'modal';
    public const CATEGORY_HUTANG = 'hutang';
    public const CATEGORY_KOMITMEN = 'komitmen';

    protected $fillable = [
        'debt_number',
        'agreement_number',
        'supplier_id',
        'borrower_name',
        'borrower_phone',
        'borrower_type',
        'loan_category',
        'user_id',
        'date',
        'due_date',
        'initial_amount',
        'paid_amount',
        'remaining_amount',
        'guarantee_info',
        'witness_name',
        'agreement_notes',
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

    public function getCategoryLabel(): string
    {
        return match ($this->loan_category) {
            self::CATEGORY_HUTANG => 'Hutang / Pinjaman',
            self::CATEGORY_KOMITMEN => 'Komitmen Pasokan',
            default => 'Modal Usaha',
        };
    }

    public function getCategoryBadgeClass(): string
    {
        return match ($this->loan_category) {
            self::CATEGORY_HUTANG => 'bg-amber-100 text-amber-800 border-amber-200',
            self::CATEGORY_KOMITMEN => 'bg-purple-100 text-purple-800 border-purple-200',
            default => 'bg-blue-100 text-blue-800 border-blue-200',
        };
    }

    public function getAgreementTitle(): string
    {
        return match ($this->loan_category) {
            self::CATEGORY_HUTANG => 'SURAT PERJANJIAN HUTANG PIUTANG',
            self::CATEGORY_KOMITMEN => 'SURAT PERJANJIAN KOMITMEN PASOKAN MATERIAL SCRAP',
            default => 'SURAT PERJANJIAN PINJAMAN MODAL USAHA',
        };
    }
}

