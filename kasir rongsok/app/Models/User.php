<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_KASIR = 'kasir';
    public const ROLE_DRIVER = 'driver';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isOwner(): bool
    {
        return $this->isSuperAdmin();
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true);
    }

    public function isKasir(): bool
    {
        return $this->role === self::ROLE_KASIR;
    }

    public function isDriver(): bool
    {
        return $this->role === self::ROLE_DRIVER;
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

    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageSettings(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManagePrices(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canAdjustStock(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canCancelTransactions(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canViewReports(): bool
    {
        return $this->isSuperAdmin();
    }

    public function purchaseTransactions(): HasMany
    {
        return $this->hasMany(PurchaseTransaction::class);
    }

    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
