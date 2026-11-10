@extends('layouts.app', ['header' => 'Laporan Stok & Valuasi Persediaan'])

@section('content')
<div class="space-y-4">

    <!-- KPI Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Akumulasi Stok Gudang</span>
            <div class="text-xl font-bold text-slate-900 mt-1">
                {{ $totalStockDisplay ?? (\App\Services\FormatHelper::weight($totalStock) . ' kg') }}
            </div>
            <div class="text-[11px] text-slate-400">Total fisik tersedia di depo</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Estimasi Nilai Stok</span>
            <div class="text-xl font-bold text-purple-700 mt-1">
                Rp {{ number_format($totalValue, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400">Valuasi berbasis moving average buy price</div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Laporan Persediaan & Valuasi Stok</h3>
                <p class="text-[11px] text-slate-500">Total: {{ count($items) }} jenis material</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.stock', ['format' => 'csv']) }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                    <span>Export Excel / CSV</span>
                </a>
                <a href="{{ route('reports.stock', ['format' => 'print']) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition-colors">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print PDF</span>
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3">Kode</th>
                        <th class="py-2.5 px-3">Nama Barang</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3 text-right">Stok Fisik</th>
                        <th class="py-2.5 px-3 text-right">Rata-Rata Beli / Satuan</th>
                        <th class="py-2.5 px-3 text-right font-bold text-purple-700">Estimasi Nilai</th>
                        <th class="py-2.5 px-3 text-right">Harga Beli / Satuan</th>
                        <th class="py-2.5 px-3 text-right">Harga Jual / Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @foreach($items as $item)
                        @php
                            $bal = $item->stockBalance;
                            $cur = (float) ($bal?->current_stock ?? 0);
                            $avg = (float) ($bal?->average_buy_price ?? 0);
                            $val = $cur * $avg;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors font-sans">
                            <td class="py-3 px-3 font-mono font-bold text-slate-700">{{ $item->code }}</td>
                            <td class="py-3 px-3 font-bold text-slate-900">
                                <div>{{ $item->name }}</div>
                                @if($item->priceStocks && $item->priceStocks->count() > 1)
                                    <div class="mt-0.5 flex flex-wrap gap-1">
                                        @foreach($item->priceStocks as $ps)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-mono bg-blue-50 text-blue-700 border border-blue-100">
                                                Rp {{ \App\Services\FormatHelper::number($ps->price_per_kg) }}: <strong class="ml-1">{{ \App\Services\FormatHelper::weight($ps->current_stock) }}</strong> {{ $item->unit_type ?: 'kg' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-slate-600">{{ $item->category?->name ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">{{ \App\Services\FormatHelper::weight($cur) }} {{ $item->unit_type ?: ($item->unit ?? 'kg') }}</td>
                            <td class="py-3 px-3 text-right font-mono text-slate-600">Rp {{ number_format($avg, 0, ',', '.') }}/{{ $item->unit_type ?: ($item->unit ?? 'kg') }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-purple-700">Rp {{ number_format($val, 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right font-mono text-emerald-700">Rp {{ number_format($item->buy_price_per_kg, 0, ',', '.') }}/{{ $item->unit_type ?: ($item->unit ?? 'kg') }}</td>
                            <td class="py-3 px-3 text-right font-mono text-slate-700">Rp {{ number_format($item->sell_price_per_kg, 0, ',', '.') }}/{{ $item->unit_type ?: ($item->unit ?? 'kg') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

