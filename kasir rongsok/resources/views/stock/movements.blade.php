@extends('layouts.app', ['header' => 'Mutasi Stok & Kartu Pergerakan Gudang'])

@section('content')
<div class="space-y-4">

    <!-- Header & Info -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900">Buku Mutasi Pergerakan Material Scrap & Daur Ulang</h2>
            <p class="text-xs text-slate-500">Mencatat setiap kilogram barang masuk dari pembelian, keluar ke pabrik, dan penyesuaian</p>
        </div>
        <a href="{{ route('stock.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke Stok Gudang</span>
        </a>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('stock.movements') }}" class="space-y-3">
            <!-- Row 1: Material/Barang, Bulan, Tahun -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Material Scrap & Daur Ulang</label>
                    <select name="scrap_item_id" class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">
                        <option value="">-- Semua Barang --</option>
                        @foreach($scrapItems as $item)
                            <option value="{{ $item->id }}" {{ $scrapItemId == $item->id ? 'selected' : '' }}>
                                {{ $item->name }} ({{ $item->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <x-month-year-filter :selected-month="$selectedMonth ?? request('month')" :selected-year="$selectedYear ?? request('year')" />
            </div>

            <!-- Row 2: Dari Tanggal, Sampai Tanggal, Tipe Mutasi, Terapkan Filter -->
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
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Mutasi</label>
                    <select name="type" class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                        <option value="">-- Semua Tipe --</option>
                        <option value="purchase" {{ $type === 'purchase' ? 'selected' : '' }}>Purchase (Masuk Pembelian)</option>
                        <option value="sale" {{ $type === 'sale' ? 'selected' : '' }}>Sale (Keluar Penjualan)</option>
                        <option value="adjustment" {{ $type === 'adjustment' ? 'selected' : '' }}>Adjustment (Penyesuaian Opname)</option>
                        <option value="reversal" {{ $type === 'reversal' ? 'selected' : '' }}>Reversal (Pembatalan Transaksi)</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Terapkan Filter</span>
                    </button>
                    <a href="{{ route('stock.movements') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Ledger Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Buku Mutasi Pergerakan Material Scrap</span>
                <span class="text-[11px] text-slate-500">Total ditampilkan: {{ $movements->total() }} catatan mutasi</span>
            </div>

            <!-- Action Buttons on Table Card Header -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('stock.index') }}" 
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-slate-900 hover:bg-slate-800 text-white shadow-sm">
                    <i data-lucide="boxes" class="w-3.5 h-3.5"></i>
                    <span>Lihat Stok Gudang</span>
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10px] font-sans">
                    <tr>
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-3">Material Scrap & Daur Ulang</th>
                        <th class="py-2.5 px-3 text-center">Tipe</th>
                        <th class="py-2.5 px-3 text-right">Perubahan</th>
                        <th class="py-2.5 px-3 text-right">Saldo Akhir</th>
                        <th class="py-2.5 px-3">Keterangan / Referensi</th>
                        <th class="py-2.5 px-3 font-sans">Operator</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $mov)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-3 text-slate-500">
                                {{ $mov->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-3 font-bold font-sans text-slate-800">
                                <div>{{ $mov->scrapItem?->name }}</div>
                                <div class="text-[10px] font-mono text-slate-400 font-normal">{{ $mov->scrapItem?->code }}</div>
                            </td>
                            <td class="py-3 px-3 text-center font-sans">
                                @if($mov->type === 'purchase')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Masuk
                                    </span>
                                @elseif($mov->type === 'sale')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">
                                        Keluar
                                    </span>
                                @elseif($mov->type === 'adjustment')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                        Opname
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                        Batal/Reversal
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-black text-sm {{ $mov->weight_qty > 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ $mov->weight_qty > 0 ? '+' : '' }}{{ \App\Services\FormatHelper::weight($mov->weight_qty) }} {{ $mov->scrapItem?->unit ?? 'kg' }}
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-slate-900">
                                {{ \App\Services\FormatHelper::weight($mov->balance_after) }} {{ $mov->scrapItem?->unit ?? 'kg' }}
                            </td>
                            <td class="py-3 px-3 font-sans text-xs text-slate-600 max-w-xs truncate">
                                {{ $mov->notes }}
                            </td>
                            <td class="py-3 px-3 font-sans text-slate-600">
                                {{ $mov->user?->name ?? 'Sistem' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-sans">Belum ada catatan mutasi pergerakan stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

