<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\PurchaseTransaction;
use App\Models\SalesTransaction;
use App\Models\Debt;
use Carbon\Carbon;

class FormatHelper
{
    /**
     * Convert month number (1-12) to Roman numerals.
     */
    public static function toRomanMonth(int|string $month): string
    {
        $m = (int) $month;
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[$m] ?? 'I';
    }

    /**
     * Format currency as integer without decimals (e.g. Rp 50.000).
     */
    public static function rupiah(mixed $val): string
    {
        $num = (float) $val;
        return 'Rp ' . number_format($num, 0, ',', '.');
    }

    /**
     * Format clean number (no decimals if whole number, e.g. 4 instead of 4,0).
     */
    public static function number(mixed $val): string
    {
        $num = (float) $val;
        if (floor($num) == $num) {
            return number_format($num, 0, ',', '.');
        }
        $formatted = number_format($num, 2, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    }

    /**
     * Format weight: allow decimals only when fraction exists, otherwise pure whole number.
     * e.g. 5 -> "5", 5.5 -> "5,5", 12.75 -> "12,75"
     */
    public static function weight(mixed $val): string
    {
        $num = (float) $val;
        if (floor($num) == $num) {
            return number_format($num, 0, ',', '.');
        }
        $formatted = number_format($num, 2, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    }

    /**
     * Generate Purchase Invoice Number: PPJ-[bulan_romawi]-[tahun_2_digit]-[0001]
     * Format: PPJ-XI-26-0001
     * Nomor urut reset setiap tahun.
     */
    public static function generatePurchaseInvoiceNumber(mixed $date = null): string
    {
        $d = $date ? Carbon::parse($date) : Carbon::now();
        $roman = self::toRomanMonth($d->month);
        $year2 = $d->format('y');
        $prefix = "PPJ-{$roman}-{$year2}-";

        // Query max sequence for this year (reset setiap tahun)
        $records = PurchaseTransaction::withTrashed()
            ->where(function ($q) use ($d, $year2) {
                $q->whereYear('transaction_date', $d->year)
                  ->orWhere('invoice_number', 'LIKE', "PPJ-%-{$year2}-%")
                  ->orWhere('invoice_number', 'LIKE', "PPJ-%{$year2}-%");
            })
            ->pluck('invoice_number');

        $maxSeq = 0;
        foreach ($records as $inv) {
            $parts = explode('-', $inv);
            $lastNum = (int) end($parts);
            if ($lastNum > $maxSeq) {
                $maxSeq = $lastNum;
            }
        }
        $nextSeq = $maxSeq + 1;

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Sales Invoice Number: PJL-[bulan_romawi]-[tahun_2_digit]-[0001]
     * Format: PJL-XI-26-0001
     * Nomor urut reset setiap tahun.
     */
    public static function generateSalesInvoiceNumber(mixed $date = null): string
    {
        $d = $date ? Carbon::parse($date) : Carbon::now();
        $roman = self::toRomanMonth($d->month);
        $year2 = $d->format('y');
        $prefix = "PJL-{$roman}-{$year2}-";

        // Query max sequence for this year (reset setiap tahun)
        $records = SalesTransaction::withTrashed()
            ->where(function ($q) use ($d, $year2) {
                $q->whereYear('transaction_date', $d->year)
                  ->orWhere('invoice_number', 'LIKE', "PJL-%-{$year2}-%")
                  ->orWhere('invoice_number', 'LIKE', "PJL-%{$year2}-%");
            })
            ->pluck('invoice_number');

        $maxSeq = 0;
        foreach ($records as $inv) {
            $parts = explode('-', $inv);
            $lastNum = (int) end($parts);
            if ($lastNum > $maxSeq) {
                $maxSeq = $lastNum;
            }
        }
        $nextSeq = $maxSeq + 1;

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Delivery Order Number (Nomor SPJ): SPJ-PPJ-[bulan_romawi]-[tahun_2_digit]-[0001]
     * Format: SPJ-PPJ-XI-26-0001
     * Nomor urut reset setiap tahun.
     */
    public static function generateDeliveryOrderNumber(mixed $date = null): string
    {
        $d = $date ? Carbon::parse($date) : Carbon::now();
        $roman = self::toRomanMonth($d->month);
        $year2 = $d->format('y');
        $prefix = "SPJ-PPJ-{$roman}-{$year2}-";

        // Query max sequence for this year (reset setiap tahun)
        $records = DeliveryOrder::withTrashed()
            ->where(function ($q) use ($d, $year2) {
                $q->whereYear('delivery_date', $d->year)
                  ->orWhere('delivery_number', 'LIKE', "SPJ-PPJ-%-{$year2}-%")
                  ->orWhere('delivery_number', 'LIKE', "SJ/PPJ-%{$year2}-%")
                  ->orWhere('delivery_number', 'LIKE', "SJ-%{$year2}-%");
            })
            ->pluck('delivery_number');

        $maxSeq = 0;
        foreach ($records as $num) {
            $parts = explode('-', $num);
            $lastNum = (int) end($parts);
            if ($lastNum > $maxSeq) {
                $maxSeq = $lastNum;
            }
        }
        $nextSeq = $maxSeq + 1;

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Debt Agreement Number (Nomor SPH): SPH-PPJ-[bulan_romawi]-[tahun_2_digit]-[0001]
     * Format: SPH-PPJ-XI-26-0001
     * Nomor urut reset setiap tahun.
     */
    public static function generateDebtAgreementNumber(mixed $date = null): string
    {
        $d = $date ? Carbon::parse($date) : Carbon::now();
        $roman = self::toRomanMonth($d->month);
        $year2 = $d->format('y');
        $prefix = "SPH-PPJ-{$roman}-{$year2}-";

        // Query max sequence for this year (reset setiap tahun)
        $records = Debt::withTrashed()
            ->where(function ($q) use ($d, $year2) {
                $q->whereYear('date', $d->year)
                  ->orWhere('agreement_number', 'LIKE', "SPH-PPJ-%-{$year2}-%")
                  ->orWhere('agreement_number', 'LIKE', "SPH/PPJ-%{$year2}-%");
            })
            ->pluck('agreement_number');

        $maxSeq = 0;
        foreach ($records as $num) {
            if (!$num) continue;
            $parts = explode('-', $num);
            $lastNum = (int) end($parts);
            if ($lastNum > $maxSeq) {
                $maxSeq = $lastNum;
            }
        }
        $nextSeq = $maxSeq + 1;

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Konversi bilangan angka ke terbilang kata bahasa Indonesia resmi.
     * Contoh: 1500000 -> "Satu Juta Lima Ratus Ribu"
     */
    public static function terbilang(float|int|string $angka): string
    {
        $angka = abs((float) $angka);
        $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $temp = '';

        if ($angka < 12) {
            $temp = ' ' . $huruf[(int) $angka];
        } elseif ($angka < 20) {
            $temp = self::terbilang($angka - 10) . ' Belas';
        } elseif ($angka < 100) {
            $temp = self::terbilang((int) ($angka / 10)) . ' Puluh ' . self::terbilang($angka % 10);
        } elseif ($angka < 200) {
            $temp = ' Seratus ' . self::terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $temp = self::terbilang((int) ($angka / 100)) . ' Ratus ' . self::terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $temp = ' Seribu ' . self::terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $temp = self::terbilang((int) ($angka / 1000)) . ' Ribu ' . self::terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $temp = self::terbilang((int) ($angka / 1000000)) . ' Juta ' . self::terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $temp = self::terbilang((int) ($angka / 1000000000)) . ' Miliar ' . self::terbilang($angka % 1000000000);
        } elseif ($angka < 1000000000000000) {
            $temp = self::terbilang((int) ($angka / 1000000000000)) . ' Triliun ' . self::terbilang($angka % 1000000000000);
        }

        return trim(preg_replace('/\s+/', ' ', $temp));
    }
}

