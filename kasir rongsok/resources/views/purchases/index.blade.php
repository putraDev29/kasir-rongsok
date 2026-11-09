@extends('layouts.app', ['header' => 'Riwayat Transaksi Pembelian Rongsok'])

@section('content')
<div class="space-y-4">

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('purchases.index') }}" class="space-y-3">
            <!-- Row 1: Nomor Nota/Cari, Bulan, Tahun -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cari No. Nota / Pemasok</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="PPJ-... atau nama..."
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

                <x-month-year-filter :selected-month="$selectedMonth ?? request('month')" :selected-year="$selectedYear ?? request('year')" />
            </div>

            <!-- Row 2: Dari Tanggal, Sampai Tanggal, Status, Terapkan Filter -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                           class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                           class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                    <select name="status" class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                        <option value="">-- Semua Status --</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Selesai / Lunas</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Terapkan Filter</span>
                    </button>
                    <a href="{{ route('purchases.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Daftar Transaksi Masuk</span>
                <span class="text-[11px] text-slate-500">Total ditampilkan: {{ $transactions->total() }} transaksi</span>
            </div>

            <!-- Global Action Bar (Point A, D, B, C) -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('purchases.batch-print', request()->query()) }}" target="_blank"
                   class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm"
                   title="Cetak Semua Nota Sekaligus (Format 58mm Kertas HVS A4)">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print Semua (HVS)</span>
                </a>

                <button type="button"
                   @click="openPdfPreview('{{ route('purchases.batch-pdf', request()->query()) }}', 'Rekapitulasi Semua Nota Pembelian (HVS)', 'Semua_Nota_Pembelian_' + ('{{ ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd') }}') + '.pdf', 'Pratinjau Dokumen Rekapitulasi Nota')"
                   class="px-2.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm cursor-pointer"
                   title="Pratinjau Berkas PDF Semua Nota di Aplikasi">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                    <span>Preview PDF HVS</span>
                </button>

                <a href="{{ route('purchases.export-excel', request()->query()) }}"
                   class="px-2.5 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm"
                   title="Unduh Data ke Excel (.xls)">
                    <i data-lucide="sheet" class="w-3.5 h-3.5"></i>
                    <span>Export Excel</span>
                </a>

                <button type="button" 
                        @click="openWaPdfModal({
                            title: 'Kirim Rekapitulasi Nota Pembelian PDF',
                            subtitle: 'Kirim berkas dokumen PDF gabungan nota timbang ke nomor WhatsApp',
                            filename: 'Semua_Nota_Pembelian_' + ('{{ ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd') }}') + '.pdf',
                            fileDescription: 'Dokumen PDF Rekapitulasi Semua Nota Pembelian (Format 58mm Kertas HVS)',
                            pdfUrl: '{{ route('purchases.batch-pdf', request()->query()) }}',
                            previewUrl: '{{ route('purchases.preview-wa-global', request()->query()) }}',
                            sendUrl: '{{ route('purchases.send-wa-global') }}',
                            sendPayload: {
                                search: '{{ addslashes($search) }}',
                                start_date: '{{ $startDate }}',
                                end_date: '{{ $endDate }}',
                                status: '{{ $status }}',
                                cashier_id: '{{ request('cashier_id') }}'
                            },
                            submitButtonText: 'Kirim Semua Nota PDF via WA'
                        })"
                        class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1 shadow-sm"
                        title="Kirim Rekapitulasi & Dokumen PDF via WhatsApp">
                    <x-whatsapp-icon class="w-3.5 h-3.5" />
                    <span>Kirim WA Global (PDF)</span>
                </button>

                <a href="{{ route('purchases.create') }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>Timbang Baru</span>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100/70 text-slate-600 border-b border-slate-200 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3">No. Nota</th>
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-3">Pemasok</th>
                        <th class="py-2.5 px-3 text-right">Jml Item</th>
                        <th class="py-2.5 px-3 text-right">Berat Bersih</th>
                        <th class="py-2.5 px-3 text-right">Total Bayar</th>
                        <th class="py-2.5 px-3">Kasir</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50 transition-colors {{ $tx->isCancelled() ? 'bg-red-50/30' : '' }}">
                            <td class="py-3 px-3 font-semibold">
                                <a href="{{ route('purchases.show', $tx) }}" class="text-emerald-600 hover:underline">
                                    {{ $tx->invoice_number }}
                                </a>
                            </td>
                            <td class="py-3 px-3 text-slate-500">
                                {{ $tx->transaction_date->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-3 font-medium text-slate-800">
                                {{ $tx->supplier_name }}
                            </td>
                            <td class="py-3 px-3 text-right text-slate-600">
                                {{ $tx->items->count() }} item
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-medium text-slate-800">
                                {{ \App\Services\FormatHelper::weight($tx->total_net_weight) }} kg
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                {{ \App\Services\FormatHelper::rupiah($tx->final_amount) }}
                            </td>
                            <td class="py-3 px-3 text-slate-600">
                                {{ $tx->user?->name }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($tx->isCancelled())
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">
                                        Batal
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Lunas
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('purchases.show', $tx) }}" title="Lihat Rincian"
                                       class="p-1 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('purchases.print', $tx) }}" target="_blank" title="Cetak Ulang Nota"
                                       class="p-1 rounded text-slate-600 hover:text-slate-800 hover:bg-slate-100">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </a>
                                    <button type="button" 
                                            @click="$store.waGateway.openReceipt('purchase', {{ $tx->id }}, '{{ $tx->supplier_phone }}')" 
                                            title="Kirim Nota via WhatsApp"
                                            class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50">
                                        <x-whatsapp-icon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                Tidak ada transaksi yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

</div>

@endsection

