<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak_Rekapitulasi_Penjualan_Partai_{{ ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page {
                size: A4 portrait;
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
    <div class="no-print w-full max-w-5xl mb-4 bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3 sticky top-4 z-40">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-800">Cetak Rekapitulasi Penjualan Partai (Grosir)</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    KERTAS HVS A4
                </span>
            </div>
            <div class="text-xs text-slate-500 mt-0.5">
                Total: <strong class="text-slate-800">{{ $transactions->count() }} Faktur</strong> | 
                Muatan: <strong class="text-slate-800">{{ \App\Services\FormatHelper::weight($totalWeight) }} kg</strong> | 
                Total Penjualan: <strong class="text-emerald-700">{{ \App\Services\FormatHelper::rupiah($totalSales) }}</strong> | 
                Estimasi Laba: <strong class="text-emerald-700">{{ \App\Services\FormatHelper::rupiah($totalProfit) }}</strong>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak ke Kertas HVS ({{ $transactions->count() }})</span>
            </button>

            <button type="button"
               onclick="openPdfPreview('{{ route('sales.batch-pdf', request()->query()) }}', 'Rekapitulasi Penjualan Partai', 'Rekap_Penjualan_Partai_{{ ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd') }}.pdf', 'Pratinjau Dokumen Rekap Penjualan')"
               class="px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
               title="Pratinjau Berkas Dokumen PDF Resmi">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Preview PDF HVS</span>
            </button>

            <a href="{{ route('sales.index') }}" class="px-3 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition-colors">
                Kembali
            </a>
        </div>
    </div>

    <!-- Printable Paper Container (A4 Style) -->
    <div class="paper-container w-full max-w-5xl bg-white p-6 sm:p-10 rounded-2xl shadow-xl border border-slate-200 text-slate-800 text-xs">
        
        <!-- Header -->
        <div class="text-center border-b-2 border-slate-900 pb-3 mb-4">
            <div class="text-base font-extrabold uppercase text-slate-900 tracking-wide">
                {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
            </div>
            <div class="text-sm font-bold text-emerald-700 uppercase mt-1">
                REKAPITULASI PENJUALAN RONGSOK PARTAI BESAR (PABRIK)
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                Periode: {{ $startDate ? date('d/m/Y', strtotime($startDate)) : 'Semua' }} s/d {{ $endDate ? date('d/m/Y', strtotime($endDate)) : 'Semua' }} | Dicetak: {{ date('d/m/Y H:i') }} WIB
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-slate-300 text-xs mt-3">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="border border-slate-300 py-2 px-2 text-center w-8">No</th>
                        <th class="border border-slate-300 py-2 px-2.5 text-left w-28">No. Faktur</th>
                        <th class="border border-slate-300 py-2 px-2 text-center w-24">Tanggal</th>
                        <th class="border border-slate-300 py-2 px-3 text-left">Tujuan Pembeli / Pabrik</th>
                        <th class="border border-slate-300 py-2 px-2.5 text-right w-24">Muatan (kg)</th>
                        <th class="border border-slate-300 py-2 px-3 text-right w-28">Total Tagihan</th>
                        <th class="border border-slate-300 py-2 px-2.5 text-right w-28">Estimasi Laba</th>
                        <th class="border border-slate-300 py-2 px-2 text-center w-20">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($transactions as $idx => $t)
                        <tr class="hover:bg-slate-50/50">
                            <td class="border border-slate-300 py-2 px-2 text-center">{{ $idx + 1 }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 font-mono font-bold text-slate-900">{{ $t->invoice_number }}</td>
                            <td class="border border-slate-300 py-2 px-2 text-center text-slate-600">{{ $t->transaction_date->format('d/m/Y') }}</td>
                            <td class="border border-slate-300 py-2 px-3">
                                <div class="font-bold text-slate-900">
                                    {{ $t->buyer_name }}
                                    @if($t->buyer_phone)
                                        <span class="text-slate-500 font-normal text-[10px]">({{ $t->buyer_phone }})</span>
                                    @endif
                                </div>
                                <div class="mt-1 space-y-0.5 text-[10px] text-slate-600">
                                    @foreach($t->grouped_master_items as $gm)
                                        <div>
                                            • <strong>{{ $gm->item_name }}</strong>: {{ \App\Services\FormatHelper::weight($gm->total_weight) }} kg
                                            @if($gm->has_variants)
                                                <div class="pl-3 text-blue-700">
                                                    @foreach($gm->items as $vi)
                                                        ↳ Varian: {{ $vi->item_type_name ?: 'Standar' }} ({{ \App\Services\FormatHelper::weight($vi->weight) }} kg @ Rp {{ number_format($vi->sell_price_per_kg, 0, ',', '.') }})
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="border border-slate-300 py-2 px-2.5 text-right font-mono font-semibold">{{ \App\Services\FormatHelper::weight($t->total_weight) }} kg</td>
                            <td class="border border-slate-300 py-2 px-3 text-right font-mono font-bold text-slate-900">{{ \App\Services\FormatHelper::rupiah($t->final_amount) }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 text-right font-mono text-emerald-700 font-semibold">{{ \App\Services\FormatHelper::rupiah($t->estimated_profit) }}</td>
                            <td class="border border-slate-300 py-2 px-2 text-center text-[10px] font-bold">
                                @if($t->isCancelled())
                                    <span class="text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">BATAL</span>
                                @else
                                    <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">SELESAI</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border border-slate-300 py-8 text-center text-slate-400">Tidak ada data transaksi penjualan partai.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                        <td colspan="4" class="border border-slate-300 py-2.5 px-3 text-right">TOTAL KESELURUHAN:</td>
                        <td class="border border-slate-300 py-2.5 px-2.5 text-right font-mono text-xs">{{ \App\Services\FormatHelper::weight($totalWeight) }} kg</td>
                        <td class="border border-slate-300 py-2.5 px-3 text-right font-mono text-xs font-bold">{{ \App\Services\FormatHelper::rupiah($totalSales) }}</td>
                        <td class="border border-slate-300 py-2.5 px-2.5 text-right font-mono text-xs text-emerald-700 font-bold">{{ \App\Services\FormatHelper::rupiah($totalProfit) }}</td>
                        <td class="border border-slate-300"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Footer / Signature -->
        <div class="mt-8 flex justify-end">
            <div class="signature-box w-64 text-center text-xs">
                <p class="font-medium text-slate-700 mb-2">Penanggung Jawab Penjualan,</p>
                <div class="h-16 flex items-center justify-center my-1">
                    @include('components.signature-stamp', ['isPdf' => false, 'height' => 48, 'stampSize' => 56])
                </div>
                <p class="font-bold text-slate-900 mt-2">( {{ \App\Models\User::where('role', 'super_admin')->first()?->name ?? 'Owner Depo' }} )</p>
                <p class="text-[11px] text-slate-500">Administrasi / Owner Depo</p>
            </div>
        </div>

    </div>

    <!-- PDF Preview Modal Component -->
    @include('components.pdf-preview-modal')

</body>
</html>

