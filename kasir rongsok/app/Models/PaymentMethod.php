<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'bank_name',
        'account_number',
        'account_holder',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function purchaseTransactions(): HasMany
    {
        return $this->hasMany(PurchaseTransaction::class);
    }

    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }
}

