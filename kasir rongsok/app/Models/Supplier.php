<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'member_number',
        'join_date',
        'tier_override',
        'name',
        'phone',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'address',
        'notes',
        'is_active',
    ];

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

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'join_date' => 'date',
        ];
    }

    public function purchaseTransactions(): HasMany
    {
        return $this->hasMany(PurchaseTransaction::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function activeDebts(): HasMany
    {
        return $this->hasMany(Debt::class)->where('remaining_amount', '>', 0);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(SupplierReward::class)->latest();
    }

    public function getTotalRemainingDebtAttribute(): float
    {
        return (float) $this->debts()->where('remaining_amount', '>', 0)->sum('remaining_amount');
    }

    public function getMemberNumberAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }
        $code = $this->code ?: ('SUP-' . str_pad($this->id, 4, '0', STR_PAD_LEFT));
        return 'MBR-' . $code;
    }

    public function getTotalWeightAttribute(): float
    {
        return (float) $this->purchaseTransactions()->where('status', 'completed')->sum('total_net_weight');
    }

    public function getTotalCargoDisplayAttribute(): string
    {
        $items = PurchaseTransactionItem::whereHas('transaction', function($q) {
            $q->where('supplier_id', $this->id)->where('status', 'completed');
        })->where('is_cancelled', false)->get();

        $kgTotal = 0;
        $pcsTotal = 0;
        $otherTotals = [];

        foreach ($items as $it) {
            $u = $it->effective_unit;
            $val = $it->quantity_or_weight;
            if ($u === 'pcs') {
                $pcsTotal += $val;
            } elseif ($u === 'kg') {
                $kgTotal += $val;
            } else {
                $otherTotals[$u] = ($otherTotals[$u] ?? 0) + $val;
            }
        }

        $parts = [];
        if ($kgTotal > 0) $parts[] = \App\Services\FormatHelper::weight($kgTotal) . ' kg';
        if ($pcsTotal > 0) $parts[] = \App\Services\FormatHelper::number($pcsTotal) . ' pcs';
        foreach ($otherTotals as $unit => $val) {
            if ($val > 0) $parts[] = \App\Services\FormatHelper::number($val) . ' ' . $unit;
        }

        return count($parts) > 0 ? implode(' + ', $parts) : ($this->total_weight > 0 ? \App\Services\FormatHelper::weight($this->total_weight) . ' kg' : '0 kg');
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->purchaseTransactions()->where('status', 'completed')->sum('final_amount');
    }

    public function getTotalTransactionsCountAttribute(): int
    {
        return (int) $this->purchaseTransactions()->where('status', 'completed')->count();
    }

    /**
     * Hitung Tier Loyalitas Pemasok berdasarkan tonase dan omzet penjualan
     */
    public function getTierAttribute(): string
    {
        if (!empty($this->tier_override)) {
            return ucfirst($this->tier_override);
        }

        $amount = $this->total_amount;
        $weight = $this->total_weight;

        if ($amount >= 50000000 || $weight >= 5000) {
            return 'Platinum';
        } elseif ($amount >= 20000000 || $weight >= 2000) {
            return 'Gold';
        } elseif ($amount >= 5000000 || $weight >= 500) {
            return 'Silver';
        }

        return 'Bronze';
    }

    /**
     * Hitung Rating Bintang Pemasok (1 s/d 5 Bintang)
     */
    public function getRatingStarsAttribute(): int
    {
        return match($this->tier) {
            'Platinum' => 5,
            'Gold' => 4,
            'Silver' => 3,
            default => ($this->total_weight >= 100 || $this->total_amount >= 1000000) ? 2 : 1,
        };
    }

    /**
     * Poin Loyalitas (1 Poin per Rp 100.000 penjualan)
     */
    public function getPointsAttribute(): int
    {
        return (int) floor($this->total_amount / 100000);
    }

    /**
     * Deskripsi Reward / Bonus yang berhak didapatkan dari Owner
     */
    public function getEligibleRewardAttribute(): string
    {
        return match($this->tier) {
            'Platinum' => 'VIP Reward Owner: Bonus Tunai Rp 500.000 / Timbangan Digital / Harga Prioritas VIP +Rp 100/kg',
            'Gold' => 'Bonus Cashback Rp 250.000 / Kaos Depo + Tambahan Harga Khusus +Rp 50/kg',
            'Silver' => 'Bonus Sembako / Kaos Depo & Fasilitas Prioritas Jemput Barang',
            default => 'Poin Pembelian & Kemudahan Akses Kasbon / Bon Modal Depo',
        };
    }

    /**
     * Ringkasan data member untuk integrasi realtime di form Kasir Alpine.js
     */
    public function toMemberSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'member_number' => $this->member_number,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'total_weight' => $this->total_weight,
            'total_amount' => $this->total_amount,
            'total_transactions' => $this->total_transactions_count,
            'tier' => $this->tier,
            'rating_stars' => $this->rating_stars,
            'points' => $this->points,
            'eligible_reward' => $this->eligible_reward,
            'remaining_debt' => $this->total_remaining_debt,
        ];
    }
}
