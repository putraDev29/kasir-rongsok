<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip_Gaji_{{ $salarySlip->slip_number }}_{{ Str::slug($salarySlip->employee_name) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        @media print {
            .no-print { display: none !important; }
            html, body { 
                margin: 0 !important; 
                padding: 0 !important; 
                background: #fff !important;
                font-size: 11px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .slip-card {
                box-shadow: none !important;
                border: 1.5px solid #0f172a !important;
                width: 100% !important;
                max-width: 180mm !important;
                margin: 0 auto !important;
                padding: 8mm 10mm !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 flex flex-col items-center">

    <!-- Print Control Bar -->
    <div class="no-print w-full max-w-[200mm] mb-3 bg-white p-2.5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-700">Format:</span>
            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[11px] font-bold">
                Standar A4 1 Lembar Pas (Tanpa Terpotong)
            </span>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak / Simpan PDF</span>
            </button>
            <a href="{{ route('salary-slips.show', $salarySlip) }}" class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold">
                Kembali
            </a>
        </div>
    </div>

    @php
        if (!function_exists('penyebutGaji')) {
            function penyebutGaji($nilai) {
                $nilai = abs($nilai);
                $huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
                if ($nilai < 12) {
                    return " " . $huruf[$nilai];
                } elseif ($nilai < 20) {
                    return penyebutGaji($nilai - 10) . " belas";
                } elseif ($nilai < 100) {
                    return penyebutGaji(floor($nilai / 10)) . " puluh" . penyebutGaji($nilai % 10);
                } elseif ($nilai < 200) {
                    return " seratus" . penyebutGaji($nilai - 100);
                } elseif ($nilai < 1000) {
                    return penyebutGaji(floor($nilai / 100)) . " ratus" . penyebutGaji($nilai % 100);
                } elseif ($nilai < 2000) {
                    return " seribu" . penyebutGaji($nilai - 1000);
                } elseif ($nilai < 1000000) {
                    return penyebutGaji(floor($nilai / 1000)) . " ribu" . penyebutGaji($nilai % 1000);
                } elseif ($nilai < 1000000000) {
                    return penyebutGaji(floor($nilai / 1000000)) . " juta" . penyebutGaji($nilai % 1000000);
                }
                return "";
            }
        }
        $terbilang = trim(penyebutGaji($salarySlip->net_salary)) . " rupiah";
    @endphp

    <!-- SLIP CONTAINER (Size exact for 210mm x 148mm Landscape) -->
    <div class="slip-card bg-white text-slate-900 border border-slate-300 rounded-xl shadow-md p-5 w-full max-w-[200mm] text-xs">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-2 border-b-2 border-slate-900">
            <div>
                <h1 class="text-base font-black tracking-tight uppercase text-slate-900">
                    {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
                </h1>
                <p class="text-[9px] text-slate-600 leading-tight">
                    {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}
                </p>
                <p class="text-[9px] text-slate-600 font-mono">
                    Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                </p>
            </div>

            <div class="text-right">
                <div class="inline-block px-2.5 py-0.5 bg-slate-900 text-white font-black text-xs uppercase tracking-wider rounded">
                    SLIP GAJI KARYAWAN
                </div>
                <div class="text-[10px] font-mono mt-1 text-slate-700">
                    <div>No: <strong>{{ $salarySlip->slip_number }}</strong></div>
                    <div>Periode: <strong>{{ $salarySlip->period_month }}</strong></div>
                </div>
            </div>
        </div>

        <!-- Employee Info Bar -->
        <div class="grid grid-cols-2 gap-4 py-2 border-b border-dashed border-slate-300 text-[10px]">
            <div>
                <span class="text-slate-500">Nama Karyawan:</span>
                <strong class="text-slate-900 uppercase ml-1">{{ $salarySlip->employee_name }}</strong>
                @if($salarySlip->employeeUser)
                    <span class="text-slate-500 ml-1">({{ ucfirst($salarySlip->employeeUser->role) }})</span>
                @endif
            </div>
            <div class="text-right">
                <span class="text-slate-500">Tanggal Bayar:</span>
                <span class="font-mono text-slate-800 ml-1">{{ $salarySlip->payment_date->format('d/m/Y') }}</span>
                <span class="text-slate-400 mx-1">|</span>
                <span class="text-slate-500">Metode:</span>
                <span class="font-medium text-slate-800 ml-1">{{ $salarySlip->paymentMethod?->name ?? 'Tunai' }}</span>
                @if($salarySlip->hasBankDetails())
                    <span class="text-slate-400 mx-1">|</span>
                    <span class="font-medium text-slate-700">({{ $salarySlip->bankDisplay() }})</span>
                @endif
            </div>
        </div>

        <!-- Earnings & Deductions 2 Columns -->
        <div class="grid grid-cols-2 gap-4 py-2 border-b-2 border-slate-900">
            
            <!-- Penerimaan -->
            <div class="pr-2 border-r border-dashed border-slate-300">
                <div class="font-bold text-[10px] uppercase text-emerald-800 pb-1 border-b border-slate-200">
                    Penghasilan / Penerimaan
                </div>
                <table class="w-full text-[10px] mt-1 font-mono">
                    <tr>
                        <td class="py-0.5 text-slate-700 align-top">
                            <div>Gaji Pokok</div>
                            @if($salarySlip->work_days && $salarySlip->daily_rate)
                                <div class="text-[9px] text-emerald-700 font-semibold font-sans">
                                    {{ $salarySlip->work_days }} hari &times; Rp {{ number_format($salarySlip->daily_rate, 0, ',', '.') }}
                                </div>
                            @endif
                        </td>
                        <td class="py-0.5 text-right font-semibold align-top whitespace-nowrap">Rp {{ number_format($salarySlip->basic_salary, 0, ',', '.') }}</td>
                    </tr>
                    @if($salarySlip->allowance_amount > 0)
                        <tr>
                            <td class="py-0.5 text-slate-700">Tunjangan Operasional</td>
                            <td class="py-0.5 text-right">Rp {{ number_format($salarySlip->allowance_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if($salarySlip->bonus_amount > 0)
                        <tr>
                            <td class="py-0.5 text-slate-700">Bonus / Lembur</td>
                            <td class="py-0.5 text-right">Rp {{ number_format($salarySlip->bonus_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="border-t border-slate-200 font-bold text-slate-900">
                        <td class="pt-1">Total Penerimaan</td>
                        <td class="pt-1 text-right text-emerald-700">Rp {{ number_format($salarySlip->total_income, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <!-- Potongan -->
            <div class="pl-2">
                <div class="font-bold text-[10px] uppercase text-red-800 pb-1 border-b border-slate-200">
                    Potongan Gaji
                </div>
                <table class="w-full text-[10px] mt-1 font-mono">
                    <tr>
                        <td class="py-0.5 text-slate-700">Potongan Kasbon / Bon Modal</td>
                        <td class="py-0.5 text-right font-semibold text-red-600">Rp {{ number_format($salarySlip->debt_deduction, 0, ',', '.') }}</td>
                    </tr>
                    @if($salarySlip->other_deduction > 0)
                        <tr>
                            <td class="py-0.5 text-slate-700">Potongan Lainnya / BPJS</td>
                            <td class="py-0.5 text-right text-red-600">Rp {{ number_format($salarySlip->other_deduction, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="border-t border-slate-200 font-bold text-slate-900">
                        <td class="pt-1">Total Potongan</td>
                        <td class="pt-1 text-right text-red-700">- Rp {{ number_format($salarySlip->total_deduction, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- Take Home Pay & Terbilang -->
        <div class="py-2 flex items-center justify-between bg-slate-50 px-3 rounded mt-2 border border-slate-200">
            <div>
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">Gaji Bersih Diterima</span>
                <span class="text-sm font-black text-slate-900 font-mono">Rp {{ number_format($salarySlip->net_salary, 0, ',', '.') }}</span>
                <div class="text-[9px] text-slate-600 italic capitalize mt-0.5">
                    Terbilang: # {{ $terbilang }} #
                </div>
            </div>

            <div class="text-right text-[10px] text-slate-500 font-sans">
                Status: <strong class="text-emerald-700 uppercase">Lunas Dibayarkan</strong>
            </div>
        </div>

        <!-- Signatures (4 Boxes / 2 Parties) -->
        <div class="mt-3 grid grid-cols-2 gap-8 text-center text-[10px] text-slate-700">
            <div>
                <p>Penerima (Karyawan),</p>
                <div class="h-14 flex items-center justify-center my-1" style="height: 56px;"></div>
                <p class="font-bold underline uppercase">{{ $salarySlip->employee_name }}</p>
            </div>
            <div>
                <p>Pimpinan / Kasir Penggajian,</p>
                <div class="h-14 flex items-center justify-center my-1" style="height: 56px;">
                    @include('components.signature-stamp', ['isPdf' => false, 'height' => 48, 'stampSize' => 56])
                </div>
                <p class="font-bold underline uppercase">{{ $salarySlip->creator?->name ?? 'Pimpinan' }}</p>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(() => window.print(), 300);
            }
        });
    </script>
</body>
</html>

