@extends('layouts.app', ['header' => 'Laporan Penjualan Material Scrap & Daur Ulang'])

@section('content')
<div class="space-y-4">

    <!-- KPI Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Berat Terjual</span>
            <div class="text-xl font-bold text-slate-900 mt-1">
                {{ number_format($totalWeight, 2, ',', '.') }} <span class="text-xs font-semibold text-slate-500">kg</span>
            </div>
            <div class="text-[11px] text-slate-400">Pengiriman rongsok ke pabrik</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Omzet Penjualan</span>
            <div class="text-xl font-bold text-blue-700 mt-1">
                Rp {{ number_format($totalSales, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400">Total nilai penerimaan penjualan</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Estimasi Modal (HPP)</span>
            <div class="text-xl font-bold text-slate-700 mt-1">
                Rp {{ number_format($totalCogs, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400">Beban pokok pembelian rongsok</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Estimasi Laba Kotor</span>
            <div class="text-xl font-bold text-emerald-700 mt-1">
                Rp {{ number_format($totalProfit, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400">Omzet penjualan - HPP barang</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('reports.sales') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
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
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Barang</label>
                <select name="scrap_item_id" class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                    <option value="">-- Semua Barang --</option>
                    @foreach($scrapItems as $item)
                        <option value="{{ $item->id }}" {{ $scrapItemId == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs">
                    Tampilkan
                </button>
                <a href="{{ route('reports.sales', array_merge(request()->query(), ['format' => 'csv'])) }}" 
                   class="px-2.5 py-1.5 rounded-lg bg-emerald-700 text-white font-bold text-xs" title="Export Excel / CSV">
                    <i data-lucide="download" class="w-4 h-4"></i>
                </a>
                <a href="{{ route('reports.sales', array_merge(request()->query(), ['format' => 'print'])) }}" target="_blank"
                   class="px-2.5 py-1.5 rounded-lg bg-slate-900 text-white font-bold text-xs" title="Cetak Print / PDF">
                    <i data-lucide="printer" class="w-4 h-4"></i>
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
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-3">No. Nota</th>
                        <th class="py-2.5 px-3">Pembeli</th>
                        <th class="py-2.5 px-3">Material Scrap & Daur Ulang</th>
                        <th class="py-2.5 px-3 text-right">Berat Terjual</th>
                        <th class="py-2.5 px-3 text-right font-bold text-amber-700">Harga Beli</th>
                        <th class="py-2.5 px-3 text-right font-bold text-slate-600">Total Modal (HPP)</th>
                        <th class="py-2.5 px-3 text-right">Harga Jual</th>
                        <th class="py-2.5 px-3 text-right font-bold text-blue-700">Subtotal Jual</th>
                        <th class="py-2.5 px-3 text-right font-bold text-emerald-700">Estimasi Laba</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @forelse($items as $item)
                        @php $tx = $item->transaction; @endphp
                        <tr class="hover:bg-slate-50 transition-colors font-sans">
                            <td class="py-3 px-3 text-slate-500 font-mono">{{ $tx->transaction_date->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-3 font-semibold font-mono">
                                <a href="{{ route('sales.show', $tx) }}" class="text-blue-600 hover:underline">
                                    {{ $tx->invoice_number }}
                                </a>
                            </td>
                            <td class="py-3 px-3 font-bold text-slate-800">{{ $tx->buyer_name }}</td>
                            <td class="py-3 px-3 font-medium text-slate-900">
                                <div class="font-bold text-slate-900">{{ $item->item_name_snapshot }}</div>
                                <div class="text-[10px] font-mono text-slate-400">{{ $item->item_code_snapshot }}</div>
                                @if($item->item_type_name)
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200 text-[10px] font-bold">
                                            Varian: {{ $item->item_type_name }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 font-mono">
                                            ({{ \App\Services\FormatHelper::weight($item->weight) }} kg @ Rp {{ \App\Services\FormatHelper::number($item->sell_price_per_kg) }})
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">
                                @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                    {{ \App\Services\FormatHelper::number($item->quantity ?? 1) }} pcs
                                @else
                                    {{ \App\Services\FormatHelper::weight($item->weight) }} kg
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-semibold text-amber-700">Rp {{ \App\Services\FormatHelper::number($item->buy_price_snapshot) }}</td>
                            <td class="py-3 px-3 text-right font-mono font-semibold text-slate-700">Rp {{ \App\Services\FormatHelper::number($item->cogs_subtotal) }}</td>
                            <td class="py-3 px-3 text-right font-mono text-slate-600">Rp {{ \App\Services\FormatHelper::number($item->sell_price_per_kg) }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-blue-700">Rp {{ \App\Services\FormatHelper::number($item->subtotal) }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700">Rp {{ \App\Services\FormatHelper::number($item->profit_subtotal) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400 font-sans">Belum ada transaksi penjualan pada rentang tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($items->count() > 0)
                    <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-300">
                        <tr>
                            <td colspan="4" class="py-3 px-3 uppercase text-slate-800 text-right">TOTAL KESELURUHAN PERIODE:</td>
                            <td class="py-3 px-3 text-right font-mono font-black text-slate-900">
                                {{ \App\Services\FormatHelper::weight($totalWeight) }} kg
                            </td>
                            <td></td>
                            <td class="py-3 px-3 text-right font-mono font-black text-slate-800">
                                Rp {{ \App\Services\FormatHelper::number($totalCogs) }}
                            </td>
                            <td></td>
                            <td class="py-3 px-3 text-right font-mono font-black text-blue-700">
                                Rp {{ \App\Services\FormatHelper::number($totalSales) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-black text-emerald-700">
                                Rp {{ \App\Services\FormatHelper::number($totalProfit) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

