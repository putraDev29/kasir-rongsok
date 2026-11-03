<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_transaction_id',
        'scrap_item_id',
        'item_name_snapshot',
        'item_type_name',
        'item_code_snapshot',
        'unit_type',
        'quantity',
        'gross_weight',
        'tare_weight',
        'net_weight',
        'price_per_kg',
        'subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gross_weight' => 'decimal:2',
            'tare_weight' => 'decimal:2',
            'net_weight' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PurchaseTransaction::class, 'purchase_transaction_id');
    }

    public function scrapItem(): BelongsTo
    {
        return $this->belongsTo(ScrapItem::class);
    }
}

