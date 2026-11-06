@extends('layouts.app', ['header' => 'Penjualan Rongsok Partai Besar (Wholesale)'])

@section('content')
<div class="space-y-4">

    <!-- Header Actions & KPI Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                <i data-lucide="truck" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Total Omzet Penjualan</div>
                <div class="text-lg font-bold text-slate-900">
                    Rp {{ \App\Services\FormatHelper::number($totalSales) }}
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                <i data-lucide="badge-check" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Estimasi Laba Kotor Penjualan</div>
                <div class="text-lg font-bold text-emerald-700">
                    Rp {{ \App\Services\FormatHelper::number($totalProfit) }}
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between gap-2">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 font-medium">Aksi & Ekspor Data</span>
                <span class="text-[11px] font-bold text-slate-800 uppercase">Partai / Pabrik</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('sales.batch-pdf', request()->all()) }}" target="_blank"
                   class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs flex items-center gap-1 shadow-xs transition-colors"
                   title="Preview & Unduh Berkas PDF Rekap Penjualan Partai">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-600"></i>
                    <span>Preview Rekap PDF</span>
                </a>

                <a href="{{ route('sales.excel', request()->all()) }}" 
                   class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold text-xs flex items-center gap-1 shadow-xs transition-colors"
                   title="Export Data ke Excel">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Excel</span>
                </a>

                <button type="button" 
                        @click="openWaPdfModal({
                            title: 'Kirim Rekapitulasi Penjualan Partai PDF',
                            subtitle: 'Kirim berkas dokumen PDF laporan & rekap penjualan grosir ke nomor WhatsApp',
                            filename: 'Rekap_Penjualan_Partai_' + ('{{ $startDate && $endDate ? "{$startDate}_sd_{$endDate}" : date("Ymd") }}') + '.pdf',
                            fileDescription: 'Dokumen PDF Rekap Penjualan Partai (Omzet, Muatan & Laba)',
                            pdfUrl: '{{ route('sales.batch-pdf', request()->all()) }}',
                            previewUrl: '{{ route('sales.wa-global.preview', request()->all()) }}',
                            sendUrl: '{{ route('sales.send-wa-global') }}',
                            sendPayload: {
                                search: '{{ addslashes($search) }}',
                                start_date: '{{ $startDate }}',
                                end_date: '{{ $endDate }}',
                                status: '{{ $status }}'
                            },
                            submitButtonText: 'Kirim Rekap PDF via WA'
                        })"
                        class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm transition-colors"
                        title="Kirim Rekap Penjualan PDF via WhatsApp">
                    <x-whatsapp-icon class="w-3.5 h-3.5" />
                    <span>Kirim Rekap WA (PDF)</span>
                </button>

                <a href="{{ route('sales.create') }}" 
                   class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>Baru</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('sales.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Cari No. Nota / Pembeli</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="PPJ-JL-... atau PT..."
                           class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Penjualan</label>
                <select name="status" class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                    <option value="">-- Semua Status --</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Selesai (Completed)</option>
                    <option value="pending_delivery" {{ $status === 'pending_delivery' ? 'selected' : '' }}>Menunggu Pengiriman</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

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

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs">
                    Filter
                </button>
                <a href="{{ route('sales.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3">No. Nota</th>
                        <th class="py-2.5 px-3">Tanggal</th>
                        <th class="py-2.5 px-3">Nama Pembeli / Pabrik</th>
                        <th class="py-2.5 px-3 text-right">Berat Terjual</th>
                        <th class="py-2.5 px-3 text-right">Total Nilai Jual</th>
                        <th class="py-2.5 px-3 text-right">Estimasi Laba</th>
                        <th class="py-2.5 px-3">Operator</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $sale)
                        <tr class="hover:bg-slate-50 transition-colors {{ $sale->isCancelled() ? 'bg-red-50/40' : '' }}">
                            <td class="py-3 px-3 font-semibold">
                                <a href="{{ route('sales.show', $sale) }}" class="text-blue-600 hover:underline">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td class="py-3 px-3 text-slate-500">
                                {{ $sale->transaction_date->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-900">{{ $sale->buyer_name }}</div>
                                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                    @if($sale->isPickup())
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            Ambil Sendiri
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                                            🚚 Dikirim
                                        </span>
                                        @if($sale->deliveryOrder)
                                            <a href="{{ route('delivery-orders.show', $sale->deliveryOrder) }}" class="text-[10px] text-blue-600 font-mono hover:underline">
                                                ({{ $sale->deliveryOrder->delivery_number }})
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-medium text-slate-800">
                                {{ \App\Services\FormatHelper::weight($sale->total_weight) }} kg
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                Rp {{ \App\Services\FormatHelper::number($sale->final_amount) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700">
                                Rp {{ \App\Services\FormatHelper::number($sale->estimated_profit) }}
                            </td>
                            <td class="py-3 px-3 text-slate-600">
                                {{ $sale->user?->name }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($sale->isCancelled())
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">
                                        Dibatalkan
                                    </span>
                                @elseif($sale->isPendingDelivery())
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                        Menunggu Kirim
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Selesai
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($sale->isPendingDelivery())
                                        <form action="{{ route('sales.mark-delivered', $sale) }}" method="POST" class="inline" onsubmit="return confirm('Konfirmasi pengiriman penjualan ini telah selesai diterima pembeli?')">
                                            @csrf
                                            <button type="submit" class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50" title="Konfirmasi Pengiriman Selesai">
                                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <button type="button" 
                                            @click="openWaReceiptModal('sale', {{ $sale->id }}, '{{ $sale->buyer_phone }}')"
                                            class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50" 
                                            title="Kirim Faktur PDF via WhatsApp">
                                        <x-whatsapp-icon class="w-4 h-4" />
                                    </button>
                                    <button type="button"
                                            @click="openPdfPreview('{{ route('sales.pdf', $sale) }}', 'Faktur Penjualan {{ $sale->invoice_number }}', 'Faktur_{{ str_replace(['/', ' '], '_', $sale->invoice_number) }}.pdf', 'Pembeli: {{ $sale->buyer_name }}')"
                                            class="p-1 rounded text-rose-600 hover:text-rose-800 hover:bg-rose-50 cursor-pointer" title="Preview Faktur PDF">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </button>
                                    <a href="{{ route('sales.show', $sale) }}" title="Lihat Rincian"
                                       class="p-1 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('sales.print', $sale) }}" target="_blank" title="Preview & Cetak Faktur"
                                       class="p-1 rounded text-blue-600 hover:text-blue-800 hover:bg-blue-50">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">Belum ada transaksi penjualan rongsok.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($transactions->count() > 0)
                    <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-300">
                        <tr>
                            <td colspan="3" class="py-3 px-3 uppercase text-slate-800 text-right">TOTAL HALAMAN INI:</td>
                            <td class="py-3 px-3 text-right font-mono font-black text-slate-900">
                                {{ \App\Services\FormatHelper::weight($transactions->sum('total_weight')) }} kg
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-black text-slate-900">
                                Rp {{ \App\Services\FormatHelper::number($transactions->sum('final_amount')) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-black text-emerald-700">
                                Rp {{ \App\Services\FormatHelper::number($transactions->sum('estimated_profit')) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                @endif
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

