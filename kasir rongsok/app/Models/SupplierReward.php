<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'reward_title',
        'reward_type',
        'reward_value',
        'points_spent',
        'awarded_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'awarded_date' => 'date',
            'reward_value' => 'float',
            'points_spent' => 'integer',
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
}

