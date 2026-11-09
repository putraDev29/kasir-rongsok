@extends('layouts.app', ['header' => 'Surat Jalan #' . $deliveryOrder->delivery_number])

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('delivery-orders.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-slate-900 font-mono">{{ $deliveryOrder->delivery_number }}</h2>
                    @if($deliveryOrder->status === 'delivered')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                            ✓ SAMPAI DI PABRIK (SELESAI)
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-100 text-blue-800 border border-blue-300">
                            🚚 DALAM PENGIRIMAN
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Tanggal Pengiriman: {{ $deliveryOrder->delivery_date->format('d/m/Y') }} | Dibuat oleh: <strong class="text-slate-700">{{ $deliveryOrder->user?->name ?? 'Admin' }}</strong>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if($deliveryOrder->status !== 'delivered')
                @if($deliveryOrder->salesTransaction)
                    <button type="button" 
                            @click="openCompleteDeliveryModal({
                                invoiceNumber: '{{ $deliveryOrder->salesTransaction->invoice_number }}',
                                buyerName: '{{ addslashes($deliveryOrder->recipient_name) }}',
                                actionUrl: '{{ route('delivery-orders.mark-delivered', $deliveryOrder) }}',
                                notes: '{{ addslashes($deliveryOrder->notes ?? '') }}',
                                items: {{ json_encode($deliveryOrder->salesTransaction->items->map(fn($it) => [
                                    'id' => $it->id,
                                    'scrap_item_id' => $it->scrap_item_id,
                                    'name' => preg_replace('/\s*-\s*Rp[\d.,\s]+.*/i', '', $it->item_name_snapshot),
                                    'type' => $it->item_type_name,
                                    'unit' => $it->unit_type ?: ($it->scrapItem?->unit_type ?: 'kg'),
                                    'weight' => (float)$it->weight,
                                    'price' => (float)$it->sell_price_per_kg,
                                ])) }}
                            })"
                            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
                            title="Input Hasil Timbang Pabrik & Selesaikan Pengiriman">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Konfirmasi Sampai di Tujuan</span>
                    </button>
                @else
                    <form action="{{ route('delivery-orders.mark-delivered', $deliveryOrder) }}" method="POST" onsubmit="return confirm('Konfirmasi bahwa surat jalan ini telah sampai di pabrik tujuan dan muatan selesai dibongkar? Status transaksi penjualan terkait otomatis menjadi SELESAI.')">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm transition-colors"
                                title="Konfirmasi Pengiriman Selesai / Sampai di Tujuan">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            <span>Konfirmasi Sampai di Tujuan</span>
                        </button>
                    </form>
                @endif
            @endif

            <button type="button" 
                    @click="openWaPdfModal({
                        title: 'Kirim Surat Jalan Pengiriman PDF via WhatsApp',
                        subtitle: 'Kirim berkas dokumen PDF resmi surat jalan lengkap (2 Rangkap) ke nomor WhatsApp',
                        phone: '{{ $deliveryOrder->recipient_phone ?: $deliveryOrder->driver_phone }}',
                        filename: 'Surat_Jalan_{{ $deliveryOrder->delivery_number }}_2Rangkap.pdf',
                        fileDescription: 'Dokumen PDF Resmi Surat Jalan (2 Rangkap: Lembar 1 Gudang & Lembar 2 Supir)',
                        pdfUrl: '{{ route('delivery-orders.pdf', $deliveryOrder) }}',
                        previewUrl: '{{ route('delivery-orders.wa-preview', $deliveryOrder) }}',
                        sendUrl: '{{ route('delivery-orders.send-wa', $deliveryOrder) }}',
                        submitButtonText: 'Kirim Surat Jalan via WA'
                    })"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm transition-colors"
                    title="Kirim Surat Jalan PDF via WhatsApp">
                <x-whatsapp-icon class="w-4 h-4" />
                <span>Kirim Surat Jalan WA</span>
            </button>

            <a href="{{ route('delivery-orders.print', $deliveryOrder) }}" target="_blank"
               class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs"
               title="Preview & Cetak Surat Jalan (Tersedia Pilihan Lembar 1 & Lembar 2)">
                <i data-lucide="printer" class="w-4 h-4 text-slate-600"></i>
                <span>Preview & Cetak Print</span>
            </a>

            @if(!$deliveryOrder->salesTransaction)
                <a href="{{ route('sales.create', ['delivery_order_id' => $deliveryOrder->id]) }}"
                   class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                    <span>Buat Faktur Penjualan</span>
                </a>
            @else
                <a href="{{ route('sales.show', $deliveryOrder->salesTransaction) }}"
                   class="px-3.5 py-2 bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 rounded-lg text-xs font-bold flex items-center gap-1.5">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>Lihat Faktur Penjualan</span>
                </a>
            @endif

            <form action="{{ route('delivery-orders.destroy', $deliveryOrder) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus surat jalan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg border border-red-200" title="Hapus Dokumen">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- 2 Rangkap Information Banner -->
    <div class="bg-blue-50/90 border border-blue-200 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs text-blue-950">
        <div class="flex items-start gap-3">
            <div class="p-2 bg-blue-600 text-white rounded-lg shrink-0 mt-0.5">
                <i data-lucide="copy" class="w-4 h-4"></i>
            </div>
            <div>
                <h4 class="font-bold text-sm text-blue-950">Ketentuan 2 (Dua) Rangkap Surat Jalan</h4>
                <p class="text-blue-800 mt-0.5 leading-relaxed">
                    Surat jalan ini dibuat <strong>2 rangkap</strong>:
                    <span class="inline-flex items-center px-2 py-0.5 rounded font-black bg-blue-100 text-blue-900 border border-blue-300 mx-1">
                        Lembar 1 : Dipegang oleh Gudang
                    </span>
                    (arsip depo pengirim) dan
                    <span class="inline-flex items-center px-2 py-0.5 rounded font-black bg-amber-100 text-amber-900 border border-amber-300 mx-1">
                        Lembar 2 : Dipegang oleh Supir
                    </span>
                    (dibawa armada pengantar ke lokasi penerima).
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 self-start md:self-center shrink-0">
            <a href="{{ route('delivery-orders.print', [$deliveryOrder, 'copy' => 'all']) }}" target="_blank"
               class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak 2 Rangkap</span>
            </a>
            <a href="{{ route('delivery-orders.print', [$deliveryOrder, 'copy' => 1]) }}" target="_blank"
               class="px-2.5 py-1.5 rounded-lg bg-white border border-blue-300 hover:bg-blue-50 text-blue-900 font-bold text-xs flex items-center gap-1 shadow-xs">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-blue-600"></i>
                <span>Print Lembar 1 (Gudang)</span>
            </a>
            <a href="{{ route('delivery-orders.print', [$deliveryOrder, 'copy' => 2]) }}" target="_blank"
               class="px-2.5 py-1.5 rounded-lg bg-white border border-amber-300 hover:bg-amber-50 text-amber-900 font-bold text-xs flex items-center gap-1 shadow-xs">
                <i data-lucide="printer" class="w-3.5 h-3.5 text-amber-600"></i>
                <span>Print Lembar 2 (Supir)</span>
            </a>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Recipient Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                <span>Pabrik / Tujuan Pengiriman</span>
            </h3>

            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Nama Perusahaan / Penerima:</span>
                    <span class="font-bold text-slate-900 text-sm">{{ $deliveryOrder->recipient_name }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Alamat Tujuan:</span>
                    <span class="text-slate-700">{{ $deliveryOrder->recipient_address ?: 'Alamat pabrik / depo pembongkaran' }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Kontak / Telepon:</span>
                    <span class="font-mono text-slate-800">{{ $deliveryOrder->recipient_phone ?: '-' }}</span>
                </div>

                @if($deliveryOrder->salesTransaction)
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-slate-400 block text-[11px]">Ref Penjualan:</span>
                        <a href="{{ route('sales.show', $deliveryOrder->salesTransaction) }}" class="text-emerald-600 font-bold hover:underline font-mono">
                            {{ $deliveryOrder->salesTransaction->invoice_number }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Vehicle & Driver Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="truck" class="w-4 h-4 text-emerald-600"></i>
                <span>Armada Ekspedisi &amp; Detail Pengemudi</span>
            </h3>

            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Nama Pengemudi / Sopir:</span>
                    <span class="font-bold text-slate-900 text-sm">{{ $deliveryOrder->driver_name ?: '-' }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Kontak HP Sopir:</span>
                    <span class="font-mono text-slate-800">{{ $deliveryOrder->driver_phone ?: '-' }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Nomor Polisi Armada:</span>
                    <span class="font-mono font-black text-slate-900 text-sm bg-slate-100 px-2.5 py-0.5 rounded border border-slate-200 inline-block">
                        {{ $deliveryOrder->vehicle_number }}
                    </span>
                    <span class="text-slate-500 ml-2">({{ $deliveryOrder->vehicle_type ?? 'Truk' }})</span>
                </div>

                @if($deliveryOrder->hasDriverBankDetails())
                    <div>
                        <span class="text-slate-400 block text-[11px]">Rekening Bank Sopir (Pencairan Ongkos):</span>
                        <span class="font-mono text-blue-900 font-bold bg-blue-50 px-2 py-0.5 rounded border border-blue-200 inline-block">{{ $deliveryOrder->driverBankDisplay() }}</span>
                    </div>
                @endif

                <div>
                    <span class="text-slate-400 block text-[11px]">Catatan / Instruksi:</span>
                    <p class="text-slate-600 italic">{{ $deliveryOrder->notes ?: 'Tidak ada instruksi khusus.' }}</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Items Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                Rincian Muatan Material Scrap & Daur Ulang
            </h3>
            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[11px] font-bold">
                Total Berat: {{ \App\Services\FormatHelper::weightOrDash($deliveryOrder->total_weight) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-2.5 px-4 w-12 text-center">No</th>
                        <th class="py-2.5 px-4">Nama Barang & Spesifikasi Muatan</th>
                        <th class="py-2.5 px-4 text-right">Kemasan / Koli</th>
                        <th class="py-2.5 px-4 text-right">{{ $deliveryOrder->weight_column_header }}</th>
                        <th class="py-2.5 px-4">Keterangan / Segel</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($deliveryOrder->grouped_master_items as $idx => $master)
                        <!-- Baris Master Barang (Akumulasi Total) -->
                        <tr class="{{ $master->has_variants ? 'bg-slate-50/60 font-semibold' : 'hover:bg-slate-50/80' }}">
                            <td class="py-3 px-4 text-center font-mono text-slate-500">{{ $idx + 1 }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-slate-900">{{ $master->item_name }}</span>
                                    <span class="text-[10px] font-mono text-slate-400 font-normal">({{ $master->item_code }})</span>
                                    @if($master->has_variants)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            Total Master ({{ $master->items->count() }} Varian)
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-800 font-bold">
                                {{ $master->total_packages > 0 ? $master->total_packages . ' Koli/Ikat' : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-800 text-sm">
                                {{ \App\Services\FormatHelper::weightOrDash($master->total_weight, $master->unit_type ?? 'kg') }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                @if(!$master->has_variants && $master->items->first()->notes)
                                    <span class="italic">{{ $master->items->first()->notes }}</span>
                                @else
                                    <span class="text-slate-400 text-[11px]">-</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Rincian Jenis Varian (Bila Ada Varian) -->
                        @if($master->has_variants)
                            @foreach($master->items as $vItem)
                                <tr class="bg-blue-50/30 hover:bg-blue-50/60 border-l-4 border-l-blue-500 text-xs transition-colors">
                                    <td class="py-2.5 px-4 text-center font-mono text-blue-400 text-[11px]">↳</td>
                                    <td class="py-2.5 px-4 pl-8">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-white border border-blue-300 font-semibold text-blue-900 text-[11px] shadow-xs">
                                                Varian: {{ $vItem->item_type_name ?: 'Standar' }}
                                            </span>
                                            @if($vItem->effective_price > 0)
                                                <span class="text-[11px] text-emerald-700 font-mono font-semibold">
                                                    @ Rp {{ number_format($vItem->effective_price, 0, ',', '.') }}/{{ $vItem->unit_type ?? 'kg' }}
                                                </span>
                                            @endif
                                            @if($vItem->notes)
                                                <span class="text-[11px] text-slate-500 italic">({{ $vItem->notes }})</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-mono text-slate-700">
                                        {{ $vItem->package_qty > 0 ? $vItem->package_qty . ' Koli' : '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-800">
                                        {{ \App\Services\FormatHelper::weightOrDash($vItem->weight, $vItem->unit_type ?? 'kg') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-slate-600 text-[11px]">
                                        @if($vItem->effective_price > 0)
                                            <span class="font-mono font-bold text-slate-800">Rp {{ number_format($vItem->effective_subtotal, 0, ',', '.') }}</span>
                                        @else
                                            <span class="italic text-slate-400">{{ $vItem->notes ?: '-' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-xs border-t-2 border-slate-200">
                    <tr>
                        <td colspan="2" class="py-3 px-4 text-slate-900 uppercase">Total Muatan Resmi</td>
                        <td class="py-3 px-4 text-right font-mono">{{ $deliveryOrder->total_packages > 0 ? $deliveryOrder->total_packages . ' Koli' : '-' }}</td>
                        <td class="py-3 px-4 text-right font-mono text-sm text-emerald-800">
                            {{ $deliveryOrder->total_cargo_display }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection

