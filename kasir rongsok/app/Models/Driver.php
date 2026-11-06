<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'vehicle_number',
        'vehicle_type',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function hasBankDetails(): bool
    {
        return !empty($this->bank_name) && !empty($this->bank_account_number);
    }

    public function bankDisplay(): ?string
    {
        if (!$this->hasBankDetails()) {
            return null;
        }

        $str = "{$this->bank_name} - Rek: {$this->bank_account_number}";
        if (!empty($this->bank_account_name)) {
            $str .= " (a/n {$this->bank_account_name})";
        }
        return $str;
    }
}

