@extends('layouts.app', ['header' => 'Laporan Stok & Valuasi Persediaan'])

@section('content')
<div class="space-y-4">

    <!-- KPI Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Akumulasi Stok Gudang</span>
            <div class="text-xl font-bold text-slate-900 mt-1">
                {{ \App\Services\FormatHelper::weight($totalStock) }} <span class="text-xs font-semibold text-slate-500">kg</span>
            </div>
            <div class="text-[11px] text-slate-400">Total berat fisik tersedia di depo</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Estimasi Nilai Stok</span>
            <div class="text-xl font-bold text-purple-700 mt-1">
                Rp {{ number_format($totalValue, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400">Valuasi berbasis moving average buy price</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Export Laporan</span>
                <div class="text-xs text-slate-500">Unduh atau cetak stok</div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.stock', ['format' => 'csv']) }}" 
                   class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Excel / CSV</span>
                </a>
                <a href="{{ route('reports.stock', ['format' => 'print']) }}" target="_blank"
                   class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print PDF</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3">Kode</th>
                        <th class="py-2.5 px-3">Nama Barang</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3 text-right">Stok Fisik (kg)</th>
                        <th class="py-2.5 px-3 text-right">Rata-Rata Beli/kg</th>
                        <th class="py-2.5 px-3 text-right font-bold text-purple-700">Estimasi Nilai</th>
                        <th class="py-2.5 px-3 text-right">Harga Beli Master</th>
                        <th class="py-2.5 px-3 text-right">Harga Jual Master</th>
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
                            <td class="py-3 px-3 font-bold text-slate-900">{{ $item->name }}</td>
                            <td class="py-3 px-3 text-slate-600">{{ $item->category?->name ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">{{ \App\Services\FormatHelper::weight($cur) }} {{ $item->unit_type ?: 'kg' }}</td>
                            <td class="py-3 px-3 text-right font-mono text-slate-600">Rp {{ number_format($avg, 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-purple-700">Rp {{ number_format($val, 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right font-mono text-emerald-700">Rp {{ number_format($item->buy_price_per_kg, 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right font-mono text-slate-700">Rp {{ number_format($item->sell_price_per_kg, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

