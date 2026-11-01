<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_number',
        'sales_transaction_id',
        'delivery_date',
        'recipient_name',
        'recipient_address',
        'recipient_phone',
        'driver_name',
        'driver_phone',
        'vehicle_number',
        'vehicle_type',
        'total_weight',
        'total_packages',
        'status',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'datetime',
            'total_weight' => 'decimal:2',
            'total_packages' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }
}

