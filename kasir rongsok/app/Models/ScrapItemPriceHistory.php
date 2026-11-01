<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapItemPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'scrap_item_id',
        'user_id',
        'old_buy_price',
        'new_buy_price',
        'old_sell_price',
        'new_sell_price',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'old_buy_price' => 'decimal:2',
            'new_buy_price' => 'decimal:2',
            'old_sell_price' => 'decimal:2',
            'new_sell_price' => 'decimal:2',
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
}

