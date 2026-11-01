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
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-100 text-blue-800 uppercase">
                        {{ $deliveryOrder->status }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Tanggal Pengiriman: {{ $deliveryOrder->delivery_date->format('d/m/Y') }} | Dibuat oleh: <strong class="text-slate-700">{{ $deliveryOrder->user?->name ?? 'Admin' }}</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('delivery-orders.print', $deliveryOrder) }}" target="_blank"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center gap-2 shadow-sm">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak / Ekspor PDF (HVS Landscape ÷ 2)</span>
            </a>

            <form action="{{ route('delivery-orders.destroy', $deliveryOrder) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus surat jalan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg border border-red-200" title="Hapus Dokumen">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </form>
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

        <!-- Vehicle Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="truck" class="w-4 h-4 text-emerald-600"></i>
                <span>Armada Ekspedisi & Driver</span>
            </h3>

            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Nomor Polisi:</span>
                    <span class="font-mono font-black text-slate-900 text-sm bg-slate-100 px-2.5 py-0.5 rounded border border-slate-200 inline-block">
                        {{ $deliveryOrder->vehicle_number }}
                    </span>
                    <span class="text-slate-500 ml-2">({{ $deliveryOrder->vehicle_type ?? 'Truk' }})</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Nama Pengemudi:</span>
                    <span class="font-bold text-slate-800 text-sm">{{ $deliveryOrder->driver_name }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Kontak Pengemudi:</span>
                    <span class="font-mono text-slate-800">{{ $deliveryOrder->driver_phone ?: '-' }}</span>
                </div>

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
                Rincian Muatan Barang Rongsok
            </h3>
            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[11px] font-bold">
                Total Berat: {{ number_format($deliveryOrder->total_weight, 2) }} kg
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-2.5 px-4 w-12 text-center">No</th>
                        <th class="py-2.5 px-4">Nama Barang & Spesifikasi Muatan</th>
                        <th class="py-2.5 px-4 text-right">Kemasan / Koli</th>
                        <th class="py-2.5 px-4 text-right">Berat Bersih (kg)</th>
                        <th class="py-2.5 px-4">Keterangan / Segel</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($deliveryOrder->items as $idx => $item)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900">{{ $item->item_name }}</td>
                            <td class="py-3 px-4 text-right font-mono text-slate-700">
                                {{ $item->package_qty > 0 ? $item->package_qty . ' Koli/Ikat' : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                                {{ number_format($item->weight, 2) }} kg
                            </td>
                            <td class="py-3 px-4 text-slate-600 italic">
                                {{ $item->notes ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-xs border-t-2 border-slate-200">
                    <tr>
                        <td colspan="2" class="py-3 px-4 text-slate-900 uppercase">Total Muatan Resmi</td>
                        <td class="py-3 px-4 text-right font-mono">{{ $deliveryOrder->total_packages }} Koli</td>
                        <td class="py-3 px-4 text-right font-mono text-sm text-emerald-800">
                            {{ number_format($deliveryOrder->total_weight, 2) }} kg
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection

