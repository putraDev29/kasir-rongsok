@extends('layouts.app', ['header' => 'Laporan Pembelian Material Scrap & Daur Ulang'])

@section('content')
<div class="space-y-4">

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Timbangan Bersih</span>
            <div class="text-xl font-bold text-slate-900 mt-1">
                {{ \App\Services\FormatHelper::weight($totalWeight) }} <span class="text-xs font-semibold text-slate-500">kg</span>
            </div>
            <div class="text-[11px] text-slate-400">{{ \App\Services\FormatHelper::weight($totalWeight / 1000) }} Ton barang diterima</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pengeluaran Pembelian</span>
            <div class="text-xl font-bold text-emerald-700 mt-1">
                Rp {{ number_format($totalAmount, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400">Kas keluar untuk membayar rongsokan</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between gap-2.5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Export & Daftar Harga</span>
                    <div class="text-[11px] text-slate-500 mt-0.5">Unduh data atau bagikan harga ke penjual</div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('reports.purchases', array_merge(request()->query(), ['format' => 'csv'])) }}" 
                   class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm"
                   title="Unduh Data Pembelian (Excel / CSV)">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Excel / CSV</span>
                </a>
                <a href="{{ route('reports.purchases', array_merge(request()->query(), ['format' => 'print'])) }}" target="_blank"
                   class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm"
                   title="Cetak Rekap Laporan Pembelian">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print PDF</span>
                </a>
                <a href="{{ route('scrap-items.print-price-list') }}" target="_blank"
                   class="px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm"
                   title="Buka & Ekspor PDF Daftar Harga Beli Rongsok untuk Dishare ke Penjual / Pemasok">
                    <i data-lucide="file-badge" class="w-3.5 h-3.5"></i>
                    <span>Daftar Harga Beli (PDF)</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('reports.purchases') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
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

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kasir</label>
                <select name="cashier_id" class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                    <option value="">-- Semua Kasir --</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ $cashierId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs">
                    Tampilkan
                </button>
                <a href="{{ route('reports.purchases') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600">
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
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-3">No. Nota</th>
                        <th class="py-2.5 px-3">Pemasok</th>
                        <th class="py-2.5 px-3">Material Scrap & Daur Ulang</th>
                        <th class="py-2.5 px-3 text-right">Berat Bersih</th>
                        <th class="py-2.5 px-3 text-right">Harga/kg</th>
                        <th class="py-2.5 px-3 text-right font-bold">Subtotal</th>
                        <th class="py-2.5 px-3">Kasir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @forelse($items as $item)
                        @php $tx = $item->transaction; @endphp
                        <tr class="hover:bg-slate-50 transition-colors font-sans">
                            <td class="py-3 px-3 text-slate-500 font-mono">{{ $tx->transaction_date->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-3 font-semibold">
                                <a href="{{ route('purchases.show', $tx) }}" class="text-emerald-600 hover:underline">
                                    {{ $tx->invoice_number }}
                                </a>
                            </td>
                            <td class="py-3 px-3 font-medium text-slate-800">{{ $tx->supplier_name }}</td>
                            <td class="py-3 px-3 font-bold text-slate-900">
                                {{ $item->item_name_snapshot }}
                                @if($item->item_type_name)
                                    <span class="text-xs font-semibold text-emerald-700">({{ $item->item_type_name }})</span>
                                @endif
                                <span class="text-[10px] font-mono text-slate-400 font-normal">({{ $item->item_code_snapshot }})</span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">
                                @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                    {{ \App\Services\FormatHelper::number($item->quantity ?? 1) }} pcs
                                @else
                                    {{ \App\Services\FormatHelper::weight($item->net_weight) }} kg
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-slate-600">Rp {{ \App\Services\FormatHelper::number($item->price_per_kg) }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700">Rp {{ \App\Services\FormatHelper::number($item->subtotal) }}</td>
                            <td class="py-3 px-3 text-slate-600">{{ $tx->user?->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 font-sans">Belum ada transaksi pembelian pada rentang tanggal ini.</td>
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
                            <td class="py-3 px-3 text-right font-mono font-black text-emerald-700">
                                Rp {{ \App\Services\FormatHelper::number($totalAmount) }}
                            </td>
                            <td></td>
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

