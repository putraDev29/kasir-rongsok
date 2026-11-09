@extends('layouts.app', ['header' => 'Surat Jalan Pengiriman Rongsok'])

@section('content')
<div class="space-y-6">

    <!-- Top KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Surat Jalan</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1 font-mono">
                    {{ $orders->total() }} Dokumen
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Pengiriman rongsok ke pabrik/mitra</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="clipboard-list" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Standar Format Cetak</p>
                <h3 class="text-lg font-black text-slate-800 mt-1">
                    HVS Landscape ÷ 2
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">210mm × 148mm (Setengah Folio/A4)</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="printer" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Legalitas Pengiriman</p>
                <h3 class="text-lg font-black text-slate-800 mt-1">
                    4 Kolom Tanda Tangan
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Penerima, Sopir, Timbangan, Pengirim</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <i data-lucide="pen-tool" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    @if(isset($pendingSalesCount) && $pendingSalesCount > 0)
        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                    <i data-lucide="package-search" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-blue-900">
                        Terdapat {{ $pendingSalesCount }} Transaksi Penjualan Pengiriman yang Belum Terbit Surat Jalan
                    </h4>
                    <p class="text-[11px] text-blue-700 mt-0.5">
                        Surat jalan dibuat mandiri dengan mengambil data dari penjualan tipe pengiriman.
                    </p>
                </div>
            </div>
            <a href="{{ route('delivery-orders.create') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-xs transition-colors shrink-0">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                <span>Buat Surat Jalan dari Penjualan</span>
            </a>
        </div>
    @endif

    <!-- Filter & Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <form method="GET" action="{{ route('delivery-orders.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari No. SJ / Penerima / Plat No / Sopir..." 
                       class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="flex items-center gap-1.5 text-xs">
                <span class="text-slate-500">Tgl:</span>
                <input type="date" name="start_date" value="{{ $startDate }}" class="py-1 px-2 text-xs rounded-lg border border-slate-300">
                <span class="text-slate-400">-</span>
                <input type="date" name="end_date" value="{{ $endDate }}" class="py-1 px-2 text-xs rounded-lg border border-slate-300">
            </div>

            @if($search || $startDate || $endDate)
                <a href="{{ route('delivery-orders.index') }}" class="px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 bg-slate-100 rounded-lg">
                    Reset
                </a>
            @endif
        </form>

        <a href="{{ route('delivery-orders.create') }}" 
           class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center justify-center gap-2 shadow-sm shrink-0">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Buat Surat Jalan Baru</span>
        </a>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-3 px-4">No. Surat Jalan & Tanggal</th>
                        <th class="py-3 px-4">Pabrik / Penerima Tujuan</th>
                        <th class="py-3 px-4">Armada & Pengemudi</th>
                        <th class="py-3 px-4 text-center">Jml Item</th>
                        <th class="py-3 px-4 text-right">Total Muatan</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4">
                                <a href="{{ route('delivery-orders.show', $order) }}" class="font-bold text-emerald-600 hover:underline font-mono">
                                    {{ $order->delivery_number }}
                                </a>
                                <div class="text-[11px] text-slate-400">
                                    {{ $order->delivery_date->format('d/m/Y') }}
                                </div>
                                @if($order->salesTransaction)
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        Ref: {{ $order->salesTransaction->invoice_number }}
                                    </div>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $order->recipient_name }}</div>
                                @if($order->recipient_address)
                                    <div class="text-[11px] text-slate-500 truncate max-w-xs">{{ $order->recipient_address }}</div>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded w-max border border-slate-200">
                                    {{ $order->vehicle_number }}
                                </div>
                                <div class="text-[11px] text-slate-600 mt-1">
                                    Sopir: <strong>{{ $order->driver_name }}</strong>
                                    @if($order->vehicle_type)
                                        <span class="text-slate-400">({{ $order->vehicle_type }})</span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3 px-4 text-center font-bold text-slate-700">
                                {{ $order->items->count() }} Jenis
                            </td>

                            <td class="py-3 px-4 text-right">
                                <div class="font-mono font-black text-slate-900 text-sm">
                                    {{ \App\Services\FormatHelper::weightOrDash($order->total_weight) }}
                                </div>
                                @if($order->total_packages > 0)
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $order->total_packages }} Koli / Karung</div>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center">
                                @if($order->status === 'delivered')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        SAMPAI DI PABRIK
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                        DIKIRIM / JALAN
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($order->status !== 'delivered')
                                        @if($order->salesTransaction)
                                            <button type="button" 
                                                    @click="openCompleteDeliveryModal({
                                                        invoiceNumber: '{{ $order->salesTransaction->invoice_number }}',
                                                        buyerName: '{{ addslashes($order->recipient_name) }}',
                                                        actionUrl: '{{ route('delivery-orders.mark-delivered', $order) }}',
                                                        notes: '{{ addslashes($order->notes ?? '') }}',
                                                        items: {{ json_encode($order->salesTransaction->items->map(fn($it) => [
                                                            'id' => $it->id,
                                                            'scrap_item_id' => $it->scrap_item_id,
                                                            'name' => $it->item_name_snapshot,
                                                            'type' => $it->item_type_name,
                                                            'unit' => $it->unit_type ?: 'kg',
                                                            'weight' => (float)$it->weight,
                                                            'price' => (float)$it->sell_price_per_kg,
                                                        ])) }}
                                                    })"
                                                    class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 cursor-pointer" 
                                                    title="Input Hasil Timbang Pabrik & Selesaikan Pengiriman">
                                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                            </button>
                                        @else
                                            <form action="{{ route('delivery-orders.mark-delivered', $order) }}" method="POST" class="inline" onsubmit="return confirm('Konfirmasi bahwa surat jalan ini telah sampai di tujuan / selesai?')">
                                                @csrf
                                                <button type="submit" class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50" title="Tandai Sampai di Tujuan (Selesai)">
                                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                    <button type="button" 
                                            @click="openWaPdfModal({
                                                title: 'Kirim Surat Jalan Pengiriman PDF via WhatsApp',
                                                subtitle: 'Kirim berkas dokumen PDF resmi surat jalan lengkap ke nomor WhatsApp',
                                                phone: '{{ $order->recipient_phone ?: $order->driver_phone }}',
                                                filename: 'Surat_Jalan_{{ $order->delivery_number }}.pdf',
                                                fileDescription: 'Dokumen PDF Resmi Surat Jalan (Lengkap Supir, Armada & Muatan)',
                                                pdfUrl: '{{ route('delivery-orders.pdf', $order) }}',
                                                previewUrl: '{{ route('delivery-orders.wa-preview', $order) }}',
                                                sendUrl: '{{ route('delivery-orders.send-wa', $order) }}',
                                                submitButtonText: 'Kirim Surat Jalan via WA'
                                            })"
                                            class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50" 
                                            title="Kirim Surat Jalan PDF via WhatsApp">
                                        <x-whatsapp-icon class="w-4 h-4" />
                                    </button>

                                    <button type="button"
                                            @click="openPdfPreview('{{ route('delivery-orders.pdf', $order) }}', 'Surat Jalan {{ $order->delivery_number }}', 'Surat_Jalan_{{ str_replace(['/', ' '], '_', $order->delivery_number) }}.pdf', 'Surat Jalan Pengiriman 2 Rangkap')"
                                            class="p-1 rounded text-rose-600 hover:text-rose-800 hover:bg-rose-50 cursor-pointer" title="Preview PDF Surat Jalan (2 Rangkap)">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </button>

                                    @if($order->salesTransaction)
                                        <a href="{{ route('sales.show', $order->salesTransaction) }}" 
                                           class="px-2 py-1 rounded bg-emerald-50 text-emerald-800 font-bold text-[10px] border border-emerald-200" title="Sudah Masuk Faktur Penjualan">
                                            ✓ Terfaktur
                                        </a>
                                    @else
                                        <a href="{{ route('sales.create', ['delivery_order_id' => $order->id]) }}" 
                                           class="px-2 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[10px] border border-blue-200" title="Sinkron Buat Faktur Penjualan">
                                            + Faktur
                                        </a>
                                    @endif

                                    <a href="{{ route('delivery-orders.print', $order) }}" target="_blank"
                                       class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] flex items-center gap-1"
                                       title="Preview & Cetak HVS Landscape Bagi 2 (2 Rangkap)">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        <span>Print</span>
                                    </a>

                                    <a href="{{ route('delivery-orders.show', $order) }}" 
                                       class="p-1 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded" title="Lihat Detail">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i data-lucide="truck" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                <p class="text-sm font-medium">Belum ada dokumen surat jalan pengiriman.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

