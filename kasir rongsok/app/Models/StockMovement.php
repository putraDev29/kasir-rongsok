<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SALE = 'sale';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_REVERSAL = 'reversal';

    protected $fillable = [
        'scrap_item_id',
        'user_id',
        'type',
        'reference_type',
        'reference_id',
        'weight_qty',
        'unit_price',
        'balance_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight_qty' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function scrapItem(): BelongsTo
    {
        return $this->belongsTo(ScrapItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

