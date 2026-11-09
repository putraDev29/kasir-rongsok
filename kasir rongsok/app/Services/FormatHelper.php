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
     * Format currency as integer, but return '-' if 0 or empty.
     */
    public static function rupiahOrDash(mixed $val): string
    {
        $num = (float) $val;
        if ($num <= 0) {
            return '-';
        }
        return 'Rp ' . number_format($num, 0, ',', '.');
    }

    /**
     * Format clean number (no decimals if whole number, e.g. 4 instead of 4,0).
     */
    public static function number(mixed $val): string
    {
        $num = round((float) $val, 4);
        if (floor($num) == $num) {
            return number_format($num, 0, ',', '.');
        }
        $formatted = number_format($num, 2, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    }

    /**
     * Format number, but return '-' if 0 or empty.
     */
    public static function numberOrDash(mixed $val): string
    {
        $num = (float) $val;
        if ($num <= 0) {
            return '-';
        }
        return self::number($val);
    }

    /**
     * Format weight: allow decimals only when fraction exists, otherwise pure whole number.
     * e.g. 5 -> "5", 5.5 -> "5,5", 12.75 -> "12,75"
     */
    public static function weight(mixed $val): string
    {
        $num = round((float) $val, 4);
        if (floor($num) == $num) {
            return number_format($num, 0, ',', '.');
        }
        $formatted = number_format($num, 2, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    }

    /**
     * Format weight with unit, but return '-' if 0 or empty.
     * e.g. 5 -> "5 kg", 0 -> "-"
     */
    public static function weightOrDash(mixed $val, string $unit = 'kg'): string
    {
        $num = (float) $val;
        if ($num <= 0) {
            return '-';
        }
        return self::weight($val) . ($unit ? ' ' . $unit : '');
    }

    /**
     * Calculate tare percentage string, e.g. "5%" or "2,5%" if tare > 0 and gross > 0.
     */
    public static function tarePercent(mixed $gross, mixed $tare): ?string
    {
        $g = (float) $gross;
        $t = (float) $tare;
        if ($g <= 0 || $t <= 0) {
            return null;
        }
        $pct = ($t / $g) * 100;
        $formatted = self::number(round($pct, 2));
        return $formatted . '%';
    }

    /**
     * Clean WhatsApp caption numbers: strip unnecessary ,00 and .00 decimals from whole numbers.
     * e.g. "10,00 kg" -> "10 kg", "Rp 50.000,00" -> "Rp 50.000", "Pot: 0,00 kg" -> "Pot: 0 kg"
     */
    public static function cleanCaption(mixed $text): string
    {
        if (empty($text) || !is_string($text)) {
            return (string) $text;
        }

        // 1. Remove ,00 when preceded by digits and not followed by a digit
        $cleaned = preg_replace('/(\d+),00(?!\d)/', '$1', $text);

        // 2. Remove .00 when preceded by digits and not followed by a digit
        $cleaned = preg_replace('/(\d+)\.00(?!\d)/', '$1', $cleaned);

        // 3. Remove ,0 and .0 when preceded by digits and not followed by a digit
        $cleaned = preg_replace('/(\d+),0(?!\d)/', '$1', $cleaned);
        $cleaned = preg_replace('/(\d+)\.0(?!\d)/', '$1', $cleaned);

        return $cleaned;
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
     * Generate Sales Invoice Number: PPJ-JL-[bulan_romawi]-[tahun_2_digit]-[0001]
     * Format: PPJ-JL-XI-26-0001
     * Nomor urut reset setiap tahun.
     */
    public static function generateSalesInvoiceNumber(mixed $date = null): string
    {
        $d = $date ? Carbon::parse($date) : Carbon::now();
        $roman = self::toRomanMonth($d->month);
        $year2 = $d->format('y');
        $prefix = "PPJ-JL-{$roman}-{$year2}-";

        // Query max sequence for this year (reset setiap tahun)
        $records = SalesTransaction::withTrashed()
            ->where(function ($q) use ($d, $year2) {
                $q->whereYear('transaction_date', $d->year)
                  ->orWhere('invoice_number', 'LIKE', "PPJ-JL-%-{$year2}-%")
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
     * Generate Delivery Order Number (Nomor Surat Jalan): PPJ-SJ-[bulan_romawi]-[tahun_2_digit]-[0001]
     * Format: PPJ-SJ-XI-26-0001
     * Nomor urut reset setiap tahun.
     */
    public static function generateDeliveryOrderNumber(mixed $date = null): string
    {
        $d = $date ? Carbon::parse($date) : Carbon::now();
        $roman = self::toRomanMonth($d->month);
        $year2 = $d->format('y');
        $prefix = "PPJ-SJ-{$roman}-{$year2}-";

        // Query max sequence for this year (reset setiap tahun)
        $records = DeliveryOrder::withTrashed()
            ->where(function ($q) use ($d, $year2) {
                $q->whereYear('delivery_date', $d->year)
                  ->orWhere('delivery_number', 'LIKE', "PPJ-SJ-%-{$year2}-%")
                  ->orWhere('delivery_number', 'LIKE', "SPJ-SBL-%-{$year2}-%")
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
     * Generate Debt Agreement Number (Nomor SPH): PPJ-SPH-[bulan_romawi]-[tahun_2_digit]-[0001]
     * Format: PPJ-SPH-XI-26-0001
     * Nomor urut reset setiap tahun.
     */
    public static function generateDebtAgreementNumber(mixed $date = null): string
    {
        $d = $date ? Carbon::parse($date) : Carbon::now();
        $roman = self::toRomanMonth($d->month);
        $year2 = $d->format('y');
        $prefix = "PPJ-SPH-{$roman}-{$year2}-";

        // Query max sequence for this year (reset setiap tahun)
        $records = Debt::withTrashed()
            ->where(function ($q) use ($d, $year2) {
                $q->whereYear('date', $d->year)
                  ->orWhere('agreement_number', 'LIKE', "PPJ-SPH-%-{$year2}-%")
                  ->orWhere('agreement_number', 'LIKE', "SPH-SBL-%-{$year2}-%")
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

