<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak_Rekap_Hutang_Modal_{{ date('Ymd') }}</title>
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
                <span class="text-sm font-bold text-slate-800">Cetak Rekapitulasi Hutang Piutang & Kasbon Modal</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    KERTAS HVS A4
                </span>
            </div>
            <div class="text-xs text-slate-500 mt-0.5">
                Total: <strong class="text-slate-800">{{ $debts->count() }} Data</strong> | 
                Total Pinjaman: <strong class="text-slate-800">{{ \App\Services\FormatHelper::rupiah($totalAmount) }}</strong> | 
                Telah Diangsur: <strong class="text-emerald-700">{{ \App\Services\FormatHelper::rupiah($totalPaid) }}</strong> | 
                Sisa Tagihan: <strong class="text-rose-700">{{ \App\Services\FormatHelper::rupiah($totalRemaining) }}</strong>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak ke Kertas HVS ({{ $debts->count() }})</span>
            </button>

            <button type="button"
               onclick="openPdfPreview('{{ route('debts.batch-pdf', request()->query()) }}', 'Rekap Hutang Piutang Modal', 'Rekap_Hutang_Modal_{{ date('Ymd') }}.pdf', 'Pratinjau Dokumen Rekap Kasbon')"
               class="px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
               title="Pratinjau Berkas Dokumen PDF Resmi">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Preview PDF HVS</span>
            </button>

            <a href="{{ route('debts.index') }}" class="px-3 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition-colors">
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
            <div class="text-sm font-bold text-amber-700 uppercase mt-1">
                BUKU KASBON &amp; REKAPITULASI PINJAMAN MODAL BEREDAR
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                {{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam & Rongsok' }} | Telp: {{ $settings['company_phone'] ?? '-' }}
                <br>
                Filter: Status: <strong>{{ $status ? strtoupper($status) : 'SEMUA' }}</strong> | Tipe: <strong>{{ $type ? strtoupper($type) : 'SEMUA' }}</strong> | Dicetak: <strong>{{ date('d/m/Y H:i') }} WIB</strong>
            </div>
        </div>

        <!-- 3 KPI Summary Cards -->
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="border border-slate-200 border-t-2 border-t-slate-800 rounded-lg p-3 bg-white text-center">
                <div class="text-[10px] font-bold text-slate-500 uppercase">Total Pinjaman Modal</div>
                <div class="text-base font-bold font-mono text-slate-900 mt-0.5">{{ \App\Services\FormatHelper::rupiah($totalAmount) }}</div>
                <div class="text-[9px] text-slate-400">Akumulasi seluruh kasbon &amp; pinjaman</div>
            </div>
            <div class="border border-emerald-200 border-t-2 border-t-emerald-600 rounded-lg p-3 bg-emerald-50/50 text-center">
                <div class="text-[10px] font-bold text-emerald-800 uppercase">Telah Diangsur / Dipotong</div>
                <div class="text-base font-bold font-mono text-emerald-700 mt-0.5">{{ \App\Services\FormatHelper::rupiah($totalPaid) }}</div>
                <div class="text-[9px] text-emerald-600 font-semibold">{{ $totalAmount > 0 ? round(($totalPaid / $totalAmount) * 100) : 0 }}% terbayar</div>
            </div>
            <div class="border border-rose-200 border-t-2 border-t-rose-600 rounded-lg p-3 bg-rose-50/50 text-center">
                <div class="text-[10px] font-bold text-rose-800 uppercase">Sisa Tagihan Beredar</div>
                <div class="text-base font-bold font-mono text-rose-700 mt-0.5">{{ \App\Services\FormatHelper::rupiah($totalRemaining) }}</div>
                <div class="text-[9px] text-rose-600 font-semibold">{{ $debts->where('status', '!=', 'paid')->count() }} pinjaman aktif</div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-slate-300 text-xs">
                <thead>
                    <tr class="bg-slate-900 text-white uppercase text-[10px] tracking-wider">
                        <th class="border border-slate-700 py-2 px-2 text-center w-8">No</th>
                        <th class="border border-slate-700 py-2 px-2 text-center w-20">Tanggal</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-left w-24">No. Bukti</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-left">Peminjam / Mitra</th>
                        <th class="border border-slate-700 py-2 px-2 text-center w-24">Kategori</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-right w-24">Jumlah Pinjaman</th>
                        <th class="border border-slate-700 py-2 px-2.5 text-right w-24">Sisa Kasbon</th>
                        <th class="border border-slate-700 py-2 px-2 text-center w-20">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($debts as $idx => $d)
                        <tr class="hover:bg-slate-50/50">
                            <td class="border border-slate-300 py-2 px-2 text-center">{{ $idx + 1 }}</td>
                            <td class="border border-slate-300 py-2 px-2 text-center text-slate-600">{{ $d->date->format('d/m/Y') }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 font-mono font-bold text-slate-900">
                                {{ $d->debt_number }}
                                @if($d->agreement_number)
                                    <div class="text-[9px] text-blue-700 font-normal">SPH: {{ $d->agreement_number }}</div>
                                @endif
                            </td>
                            <td class="border border-slate-300 py-2 px-2.5">
                                <div class="font-bold text-slate-900">{{ $d->borrower_name }}</div>
                                @if($d->borrower_phone)
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $d->borrower_phone }}</div>
                                @endif
                            </td>
                            <td class="border border-slate-300 py-2 px-2 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $d->loan_category === 'modal' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ strtoupper($d->loan_category ?? 'KASBON') }}
                                </span>
                            </td>
                            <td class="border border-slate-300 py-2 px-2.5 text-right font-mono font-bold text-slate-900">{{ \App\Services\FormatHelper::rupiah($d->amount) }}</td>
                            <td class="border border-slate-300 py-2 px-2.5 text-right font-mono font-bold text-rose-700">{{ \App\Services\FormatHelper::rupiah($d->remaining_amount) }}</td>
                            <td class="border border-slate-300 py-2 px-2 text-center text-[10px] font-bold">
                                @if($d->status === 'paid')
                                    <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">LUNAS</span>
                                @elseif($d->status === 'partial')
                                    <span class="text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">SEBAGIAN</span>
                                @else
                                    <span class="text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">BELUM BAYAR</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border border-slate-300 py-8 text-center text-slate-400">Tidak ada data pinjaman modal.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                        <td colspan="5" class="border border-slate-300 py-2.5 px-3 text-right">TOTAL KESELURUHAN:</td>
                        <td class="border border-slate-300 py-2.5 px-2.5 text-right font-mono text-xs">{{ \App\Services\FormatHelper::rupiah($totalAmount) }}</td>
                        <td class="border border-slate-300 py-2.5 px-2.5 text-right font-mono text-xs text-rose-700 font-bold">{{ \App\Services\FormatHelper::rupiah($totalRemaining) }}</td>
                        <td class="border border-slate-300"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Footer / Signature -->
        <div class="mt-8 flex justify-end">
            <div class="signature-box w-64 text-center text-xs">
                <p class="font-medium text-slate-700 mb-2">Penanggung Jawab Keuangan &amp; Modal,</p>
                <div class="h-16 flex items-center justify-center my-1">
                    @include('components.signature-stamp', ['isPdf' => false, 'height' => 48, 'stampSize' => 56])
                </div>
                <p class="font-bold text-slate-900 mt-2">( {{ \App\Models\User::where('role', 'super_admin')->first()?->name ?? 'Owner Depo' }} )</p>
                <p class="text-[11px] text-slate-500">Owner / Pimpinan Depo</p>
            </div>
        </div>

    </div>

    <!-- PDF Preview Modal Component -->
    @include('components.pdf-preview-modal')

</body>
</html>

