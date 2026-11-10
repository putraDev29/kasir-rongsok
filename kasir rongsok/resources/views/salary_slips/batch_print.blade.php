<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak_Rekap_Penggajian_Karyawan_{{ $period ?: date('Y-m') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 8mm 10mm 8mm;
            }
            .no-print {
                display: none !important;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .paper-container {
                max-width: 100% !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
            }
            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .signature-box {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 flex flex-col items-center font-sans text-slate-900" x-data="{ sidebarOpen: false }">

    <!-- Top Toolbar (Screen Only) -->
    <div class="no-print w-full max-w-6xl mb-4 bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3 sticky top-4 z-40">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-800">Cetak Rekapitulasi Penggajian Karyawan</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                    KERTAS HVS A4 (LANDSCAPE)
                </span>
            </div>
            <div class="text-xs text-slate-500 mt-0.5">
                Total: <strong class="text-slate-800">{{ $slips->count() }} Slip</strong> | 
                Periode: <strong class="text-slate-800">{{ $displayPeriod }}</strong> | 
                Total Gaji Bersih: <strong class="text-emerald-700">{{ \App\Services\FormatHelper::rupiah($totalNetSalary) }}</strong>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak ke Kertas HVS ({{ $slips->count() }})</span>
            </button>

            <button type="button"
               onclick="openPdfPreview('{{ route('salary-slips.batch-pdf', request()->query()) }}', 'Rekap Penggajian Karyawan', 'Rekap_Penggajian_Karyawan_{{ $period ?: date('Ymd') }}.pdf', 'Pratinjau Dokumen Rekap Gaji')"
               class="px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
               title="Pratinjau Berkas Dokumen PDF Resmi">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Preview PDF HVS</span>
            </button>

            <a href="{{ route('salary-slips.index') }}" class="px-3 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition-colors">
                Kembali
            </a>
        </div>
    </div>

    <!-- Printable Paper Container (A4 Landscape Style) -->
    <div class="paper-container w-full max-w-6xl bg-white p-6 sm:p-8 rounded-2xl shadow-xl border border-slate-200 text-slate-800 text-xs">
        
        <!-- Header -->
        <div class="text-center border-b-2 border-slate-900 pb-3 mb-4">
            <div class="text-base font-extrabold uppercase text-slate-900 tracking-wide">
                {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
            </div>
            <div class="text-sm font-bold text-indigo-700 uppercase mt-1">
                DAFTAR REKAPITULASI PENGGAJIAN KARYAWAN &amp; OPERASIONAL
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                Periode: <strong>{{ $displayPeriod }}</strong> | Dicetak: <strong>{{ date('d/m/Y H:i') }} WIB</strong>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-slate-300 text-xs mt-3">
                <thead>
                    <tr class="bg-slate-900 text-white uppercase text-[10px] tracking-wider">
                        <th class="border border-slate-700 py-2 px-2 text-center w-8">No</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-left w-24">No. Slip</th>
                        <th class="border border-slate-700 py-2 px-2 text-center w-20">Tgl Bayar</th>
                        <th class="border border-slate-700 py-2 px-3 text-left">Nama Karyawan</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-left w-28">Jabatan</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-right w-24">Gaji Pokok</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-right w-24">Tunjangan</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-right w-24">Potongan</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-right w-28">Gaji Bersih</th>
                        <th class="border border-slate-700 py-2 px-2 text-center w-24">Metode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($slips as $idx => $s)
                        @php
                            $allowanceSum = (float)($s->transport_allowance ?? 0) + (float)($s->meal_allowance ?? 0) + (float)($s->bonus ?? 0);
                            $deductionSum = (float)($s->debt_deduction ?? 0) + (float)($s->other_deduction ?? 0);
                        @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="border border-slate-300 py-2 px-2 text-center">{{ $idx + 1 }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 font-mono font-bold text-slate-900">{{ $s->slip_number }}</td>
                            <td class="border border-slate-300 py-2 px-2 text-center text-slate-600">{{ $s->payment_date->format('d/m/Y') }}</td>
                            <td class="border border-slate-300 py-2 px-3 font-bold text-slate-900">{{ $s->employee_name }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 text-slate-600">{{ $s->employee_role ?? 'Staf Operasional' }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 text-right font-mono">{{ \App\Services\FormatHelper::rupiah($s->basic_salary) }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 text-right font-mono text-emerald-700">+{{ \App\Services\FormatHelper::rupiah($allowanceSum) }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 text-right font-mono text-rose-700">-{{ \App\Services\FormatHelper::rupiah($deductionSum) }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 text-right font-mono font-bold text-slate-900">{{ \App\Services\FormatHelper::rupiah($s->net_salary) }}</td>
                            <td class="border border-slate-300 py-2 px-2 text-center text-[10px]">
                                {{ $s->paymentMethod?->name ?? 'Tunai (Kas)' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="border border-slate-300 py-8 text-center text-slate-400">Tidak ada data penggajian pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                        <td colspan="8" class="border border-slate-300 py-2.5 px-3 text-right">TOTAL PENGGAJIAN BERSIH:</td>
                        <td class="border border-slate-300 py-2.5 px-2.5 text-right font-mono text-xs font-bold text-emerald-700">{{ \App\Services\FormatHelper::rupiah($totalNetSalary) }}</td>
                        <td class="border border-slate-300"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Footer / Signature -->
        <div class="mt-8 flex justify-end">
            <div class="signature-box w-64 text-center text-xs">
                <p class="font-medium text-slate-700 mb-2">Penanggung Jawab Payroll,</p>
                <div class="h-16 flex items-center justify-center my-1">
                    @include('components.signature-stamp', ['isPdf' => false, 'height' => 48, 'stampSize' => 56])
                </div>
                <p class="font-bold text-slate-900 mt-2">( {{ \App\Models\User::where('role', 'super_admin')->first()?->name ?? 'Owner Depo' }} )</p>
                <p class="text-[11px] text-slate-500">Pimpinan / Administrasi Depo</p>
            </div>
        </div>

    </div>

    <!-- PDF Preview Modal Component -->
    @include('components.pdf-preview-modal')

</body>
</html>

