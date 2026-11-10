@extends('layouts.app', ['header' => 'Penjualan Rongsok Partai Besar (Wholesale)'])

@section('content')
<div class="space-y-4">

    <!-- KPI Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('sales.index') }}" class="space-y-3">
            <!-- Row 1: Nomor Nota/Cari, Bulan, Tahun -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
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
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status Penjualan</label>
                    <select name="status" class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                        <option value="">-- Semua Status --</option>
                        <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft (Menunggu Harga)</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Selesai (Completed)</option>
                        <option value="pending_delivery" {{ $status === 'pending_delivery' ? 'selected' : '' }}>Menunggu Pengiriman</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Terapkan Filter</span>
                    </button>
                    <a href="{{ route('sales.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Daftar Transaksi Penjualan Partai</span>
                <span class="text-[11px] text-slate-500">Total ditampilkan: {{ $transactions->total() }} faktur</span>
            </div>

            <!-- Action Buttons on Table Card Header -->
            <div class="flex flex-wrap items-center gap-2">

                <a href="{{ route('sales.batch-print', request()->all()) }}" target="_blank"
                   class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm"
                   title="Cetak Rekapitulasi Penjualan Partai (Format Kertas HVS A4)">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print Rekap (HVS)</span>
                </a>

                <button type="button"
                   @click="openPdfPreview('{{ route('sales.batch-pdf', request()->all()) }}', 'Rekapitulasi Penjualan Partai', 'Rekap_Penjualan_Partai_' + ('{{ $startDate && $endDate ? "{$startDate}_sd_{$endDate}" : date("Ymd") }}') + '.pdf', 'Pratinjau Dokumen Rekapitulasi PDF')"
                   class="px-2.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm transition-colors cursor-pointer"
                   title="Pratinjau Dokumen PDF Rekap Penjualan Partai">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                    <span>Preview PDF</span>
                </button>

                <a href="{{ route('sales.excel', request()->all()) }}" 
                   class="px-2.5 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm transition-colors"
                   title="Export Data ke Excel">
                    <i data-lucide="sheet" class="w-3.5 h-3.5"></i>
                    <span>Export Excel</span>
                </a>

                <button type="button" 
                        @click="openWaPdfModal({
                            title: 'Kirim Rekapitulasi Penjualan Partai PDF',
                            subtitle: 'Kirim berkas dokumen PDF laporan & rekap penjualan grosir ke nomor WhatsApp',
                            filename: 'Rekap_Penjualan_Partai_' + ('{{ $startDate && $endDate ? "{$startDate}_sd_{$endDate}" : date("Ymd") }}') + '.pdf',
                            fileDescription: 'Dokumen PDF Rekap Penjualan Partai (Omzet, Muatan & Laba)',
                            printUrl: '{{ route('sales.batch-print', request()->all()) }}',
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
                        class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1 shadow-sm transition-colors"
                        title="Kirim Rekap Penjualan PDF via WhatsApp">
                    <x-whatsapp-icon class="w-3.5 h-3.5" />
                    <span>Kirim Rekap WA (PDF)</span>
                </button>

                <a href="{{ route('sales.create') }}" 
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>Tambah Penjualan Baru</span>
                </a>
            </div>
        </div>
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
                                {{ \App\Services\FormatHelper::weightOrDash($sale->total_weight) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                {{ \App\Services\FormatHelper::rupiahOrDash($sale->final_amount) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700">
                                {{ $sale->estimated_profit != 0 ? 'Rp ' . \App\Services\FormatHelper::number($sale->estimated_profit) : '-' }}
                            </td>
                            <td class="py-3 px-3 text-slate-600">
                                {{ $sale->user?->name }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($sale->isCancelled())
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">
                                        Dibatalkan
                                    </span>
                                @elseif($sale->isDraft())
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                        Draft Kasir
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
                                    @if(($sale->isDraft() || $sale->isPendingDelivery()) && (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin()))
                                        <a href="{{ route('sales.edit', $sale) }}" class="p-1 rounded text-amber-600 hover:text-amber-800 hover:bg-amber-50" title="Edit Rincian Berat & Harga Jual">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>
                                    @endif
                                    @if($sale->isPendingDelivery())
                                        <button type="button" 
                                                @click="openCompleteDeliveryModal({
                                                    invoiceNumber: '{{ $sale->invoice_number }}',
                                                    buyerName: '{{ addslashes($sale->buyer_name) }}',
                                                    actionUrl: '{{ route('sales.mark-delivered', $sale) }}',
                                                    notes: '{{ addslashes($sale->notes ?? '') }}',
                                                    items: {{ json_encode($sale->items->map(fn($it) => [
                                                        'id' => $it->id,
                                                        'scrap_item_id' => $it->scrap_item_id,
                                                        'name' => preg_replace('/\s*-\s*Rp[\d.,\s]+.*/i', '', $it->item_name_snapshot),
                                                        'type' => $it->item_type_name,
                                                        'unit' => $it->unit_type ?: ($it->scrapItem?->unit_type ?: 'kg'),
                                                        'weight' => (float)$it->weight,
                                                        'price' => (float)$it->sell_price_per_kg,
                                                    ])) }}
                                                })"
                                                class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 cursor-pointer" 
                                                title="Input Hasil Timbang Pabrik & Selesaikan Pengiriman">
                                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                                        </button>
                                    @endif
                                    <button type="button" 
                                            @click="openWaReceiptModal('sale', {{ $sale->id }}, '{{ $sale->buyer_phone }}')"
                                            class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50" 
                                            title="Kirim Faktur PDF via WhatsApp">
                                        <x-whatsapp-icon class="w-4 h-4" />
                                    </button>
                                    <a href="{{ route('sales.show', $sale) }}" title="Lihat Rincian"
                                       class="p-1 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('sales.print', $sale) }}" target="_blank" title="Preview & Cetak Invoice"
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

