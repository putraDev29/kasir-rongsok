<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class DeliveryOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_number',
        'sales_transaction_id',
        'driver_id',
        'delivery_date',
        'recipient_name',
        'recipient_address',
        'recipient_phone',
        'driver_name',
        'driver_phone',
        'driver_bank_name',
        'driver_bank_account_number',
        'driver_bank_account_name',
        'vehicle_number',
        'vehicle_type',
        'total_weight',
        'shipping_cost',
        'loading_cost',
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
            'shipping_cost' => 'decimal:2',
            'loading_cost' => 'decimal:2',
            'total_packages' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function hasDriverBankDetails(): bool
    {
        return (!empty($this->driver_bank_name) && !empty($this->driver_bank_account_number))
            || ($this->driver && $this->driver->hasBankDetails());
    }

    public function driverBankDisplay(): ?string
    {
        $bankName = $this->driver_bank_name ?: $this->driver?->bank_name;
        $accNo = $this->driver_bank_account_number ?: $this->driver?->bank_account_number;
        $accName = $this->driver_bank_account_name ?: $this->driver?->bank_account_name;

        if (empty($bankName) || empty($accNo)) {
            return null;
        }

        $str = "{$bankName} - Rek: {$accNo}";
        if (!empty($accName)) {
            $str .= " (a/n {$accName})";
        }
        return $str;
    }

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    /**
     * Group delivery order items by master item.
     * Master level totals are aggregated, while variant breakdowns are preserved underneath.
     */
    public function getGroupedMasterItemsAttribute()
    {
        return $this->items->groupBy(function ($item) {
            return $item->scrap_item_id ? 'id_' . $item->scrap_item_id : 'name_' . $item->item_name;
        })->map(function ($items) {
            $first = $items->first();
            $totalWeight = (float) $items->sum('weight');
            $totalPackages = (int) $items->sum('package_qty');
            $totalQuantity = (float) $items->sum('quantity');
            $totalSubtotal = (float) $items->sum(fn($it) => $it->effective_subtotal);

            // Determine if variants exist
            $hasVariants = $items->contains(fn($it) => !empty(trim($it->item_type_name ?? ''))) || $items->count() > 1;

            return (object) [
                'scrap_item_id' => $first->scrap_item_id,
                'item_code' => $first->scrapItem?->code ?? '-',
                'item_name' => $first->item_name,
                'unit_type' => $first->unit_type ?? 'kg',
                'total_weight' => $totalWeight,
                'total_packages' => $totalPackages,
                'total_quantity' => $totalQuantity,
                'total_subtotal' => $totalSubtotal,
                'has_variants' => $hasVariants,
                'items' => $items,
            ];
        })->values();
    }

    /**
     * Record operational expenses (shipping_cost & loading_cost) into expenses table.
     */
    public function recordOperationalExpenses(): void
    {
        $date = $this->delivery_date ? Carbon::parse($this->delivery_date) : Carbon::today();
        $cashMethod = PaymentMethod::where('is_active', true)->where('code', 'cash')->first() 
            ?? PaymentMethod::where('is_active', true)->first();
        $userId = $this->user_id ?? Auth::id() ?? 1;

        if ((float) $this->shipping_cost > 0) {
            $cat = ExpenseCategory::firstOrCreate(
                ['name' => 'Biaya Transportasi & Armada'],
                ['description' => 'Biaya operasional armada, bensin, tol & ongkos supir', 'is_active' => true]
            );

            $prefix = 'PPJ-EXP-' . $date->format('ymd') . '-';
            $latest = Expense::where('expense_number', 'LIKE', $prefix . '%')->orderByDesc('id')->value('expense_number');
            $num = $latest ? ((int) substr($latest, strrpos($latest, '-') + 1) + 1) : 1;
            $expNum = $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);

            $bankSuffix = $this->hasDriverBankDetails() ? " [Rek. Sopir: {$this->driverBankDisplay()}]" : "";

            Expense::create([
                'expense_number' => $expNum,
                'expense_category_id' => $cat->id,
                'user_id' => $userId,
                'payment_method_id' => $cashMethod?->id,
                'expense_date' => $date,
                'amount' => (float) $this->shipping_cost,
                'description' => "Ongkos armada pengiriman SJ {$this->delivery_number} (Tujuan: {$this->recipient_name}, Sopir: {$this->driver_name}){$bankSuffix}",
                'recipient' => $this->driver_name,
            ]);
        }

        if ((float) $this->loading_cost > 0) {
            $cat = ExpenseCategory::firstOrCreate(
                ['name' => 'Biaya Bongkar Muat / Tenaga Angkut'],
                ['description' => 'Biaya tenaga bongkar muat di gudang atau lokasi pengiriman', 'is_active' => true]
            );

            $prefix = 'PPJ-EXP-' . $date->format('ymd') . '-';
            $latest = Expense::where('expense_number', 'LIKE', $prefix . '%')->orderByDesc('id')->value('expense_number');
            $num = $latest ? ((int) substr($latest, strrpos($latest, '-') + 1) + 1) : 1;
            $expNum = $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);

            Expense::create([
                'expense_number' => $expNum,
                'expense_category_id' => $cat->id,
                'user_id' => $userId,
                'payment_method_id' => $cashMethod?->id,
                'expense_date' => $date,
                'amount' => (float) $this->loading_cost,
                'description' => "Biaya bongkar muat pengiriman SJ {$this->delivery_number} (Tujuan: {$this->recipient_name})",
                'recipient' => $this->driver_name ?: 'Tenaga Angkut',
            ]);
        }
    }
}
