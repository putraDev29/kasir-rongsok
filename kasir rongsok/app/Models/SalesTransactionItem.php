<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_transaction_id',
        'scrap_item_id',
        'item_name_snapshot',
        'item_code_snapshot',
        'weight',
        'sell_price_per_kg',
        'buy_price_snapshot',
        'subtotal',
        'cogs_subtotal',
        'profit_subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'sell_price_per_kg' => 'decimal:2',
            'buy_price_snapshot' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'cogs_subtotal' => 'decimal:2',
            'profit_subtotal' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }

    public function scrapItem(): BelongsTo
    {
        return $this->belongsTo(ScrapItem::class);
    }
}

