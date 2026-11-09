<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalarySlip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slip_number',
        'employee_name',
        'user_id',
        'created_by',
        'payment_method_id',
        'period_month',
        'payment_date',
        'work_days',
        'daily_rate',
        'basic_salary',
        'allowance_amount',
        'bonus_amount',
        'total_income',
        'debt_deduction',
        'other_deduction',
        'total_deduction',
        'net_salary',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'work_days' => 'float',
            'daily_rate' => 'decimal:2',
            'basic_salary' => 'decimal:2',
            'allowance_amount' => 'decimal:2',
            'bonus_amount' => 'decimal:2',
            'total_income' => 'decimal:2',
            'debt_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ];
    }

    public function employeeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}

