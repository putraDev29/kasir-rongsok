@extends('layouts.app', ['header' => 'Manajemen Stok & Persediaan Gudang'])

@section('content')
<div class="space-y-4" x-data="{ adjustModal: false, activeItem: {} }">

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                <i data-lucide="boxes" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Total Akumulasi Stok Gudang</div>
                <div class="text-xl font-bold text-slate-900">
                    {{ \App\Services\FormatHelper::weight($totalStockKg) }} <span class="text-xs font-semibold text-slate-500">kg</span>
                </div>
                <div class="text-[11px] text-slate-400">Total berat fisik tersedia saat ini</div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                <i data-lucide="calculator" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Estimasi Valuasi Persediaan</div>
                <div class="text-xl font-bold text-slate-900">
                    Rp {{ number_format($totalValuation, 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-400">Dihitung dari moving average buy price</div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs text-slate-500 font-medium">Kartu Stok & Pergerakan</div>
                <div class="text-sm font-bold text-slate-800 mt-1">Buku Mutasi Masuk/Keluar</div>
                <div class="text-[11px] text-slate-400">Audit trail barang masuk & keluar</div>
            </div>
            <a href="{{ route('stock.movements') }}" 
               class="px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="history" class="w-3.5 h-3.5"></i>
                <span>Lihat Mutasi</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('stock.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Cari Barang</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama barang atau kode..."
                           class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                <select name="category_id" class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                    <option value="">-- Semua Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan Filter</span>
                </button>
                <a href="{{ route('stock.index', ['low_stock' => '1']) }}" 
                   title="Filter Stok Menipis"
                   class="px-2.5 py-1.5 rounded-lg border text-xs font-bold {{ $lowStockOnly ? 'bg-amber-100 border-amber-300 text-amber-900' : 'bg-slate-100 border-slate-200 text-slate-700' }}">
                    ⚠️ Menipis
                </a>
                <a href="{{ route('stock.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Stock Balances Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Persediaan Barang & Penilaian Fisik</h3>
                <p class="text-[11px] text-slate-500">Total: {{ $items->total() }} jenis material terdata</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('stock.movements') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition-colors">
                    <i data-lucide="history" class="w-3.5 h-3.5"></i>
                    <span>Lihat Mutasi Stok</span>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100/70 text-slate-600 border-b border-slate-200 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3">Kode</th>
                        <th class="py-2.5 px-3">Nama Barang</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3 text-right">Total Masuk</th>
                        <th class="py-2.5 px-3 text-right">Total Keluar</th>
                        <th class="py-2.5 px-3 text-right font-bold text-slate-900">Stok Tersedia</th>
                        <th class="py-2.5 px-3 text-right">Rata-Rata Beli</th>
                        <th class="py-2.5 px-3 text-right font-bold">Estimasi Nilai</th>
                        <th class="py-2.5 px-3 text-center">Status Stok</th>
                        @if(Auth::user()->isAdmin())
                            <th class="py-2.5 px-3 text-center">Opname / Adjust</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        @php
                            $bal = $item->stockBalance;
                            $curStock = (float) ($bal?->current_stock ?? 0);
                            $avgBuy = (float) ($bal?->average_buy_price ?? 0);
                            $estVal = $curStock * $avgBuy;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors {{ $item->isLowStock() ? 'bg-amber-50/40' : '' }}">
                            <td class="py-3 px-3 font-mono font-bold text-slate-700">{{ $item->code }}</td>
                            <td class="py-3 px-3 font-bold text-slate-900">
                                <div>{{ $item->name }}</div>
                                @if($item->priceStocks && $item->priceStocks->count() > 1)
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($item->priceStocks as $ps)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono bg-blue-50 text-blue-700 border border-blue-200">
                                                Rp {{ \App\Services\FormatHelper::number($ps->price_per_kg) }}: <strong class="ml-1">{{ \App\Services\FormatHelper::weight($ps->current_stock) }}</strong> {{ $item->unit_type ?: 'kg' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Min. Alert: {{ \App\Services\FormatHelper::weight($item->min_stock_alert) }} {{ $item->unit ?? 'kg' }}</div>
                            </td>
                            <td class="py-3 px-3 text-slate-600">{{ $item->category?->name ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-mono text-emerald-700">
                                +{{ \App\Services\FormatHelper::weight($bal?->total_in ?? 0) }} {{ $item->unit ?? 'kg' }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-red-600">
                                -{{ \App\Services\FormatHelper::weight($bal?->total_out ?? 0) }} {{ $item->unit ?? 'kg' }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-black text-sm {{ $item->isLowStock() ? 'text-amber-600' : 'text-slate-900' }}">
                                {{ \App\Services\FormatHelper::weight($curStock) }} {{ $item->unit ?? 'kg' }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-slate-700">
                                Rp {{ \App\Services\FormatHelper::number($avgBuy) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700">
                                Rp {{ \App\Services\FormatHelper::number($estVal) }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($item->isLowStock())
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                        Menipis
                                    </span>
                                @elseif($curStock > 0)
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                        Tersedia
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200 text-slate-700">
                                        Kosong
                                    </span>
                                @endif
                            </td>
                            @if(Auth::user()->isAdmin())
                                <td class="py-3 px-3 text-center">
                                    <button type="button" 
                                            @click="activeItem = { id: {{ $item->id }}, name: '{{ addslashes($item->name) }}', stock: {{ $curStock }} }; adjustModal = true"
                                            title="Penyesuaian Fisik / Stock Opname"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px]">
                                        <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                                        <span>Opname</span>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400">Belum ada data stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    <!-- Stock Adjustment / Opname Modal -->
    <div x-show="adjustModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="adjustModal = false">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Penyesuaian Stok (Stock Opname)</h3>
                    <p class="text-xs text-emerald-600 font-semibold" x-text="activeItem.name"></p>
                </div>
                <button type="button" @click="adjustModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="'{{ url('stock') }}/' + activeItem.id + '/adjust'" method="POST" class="space-y-3">
                @csrf
                <div>
                    <span class="text-xs text-slate-500 block mb-1">Stok Tercatat di Sistem:</span>
                    <div class="text-sm font-mono font-bold text-slate-800" x-text="(activeItem.stock || 0) + ' ' + (activeItem.unit || 'kg')"></div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Hasil Fisik Sebenarnya (<span x-text="activeItem.unit || 'kg'"></span>) *</label>
                    <input type="number" step="0.01" min="0" name="new_stock" x-model.number="activeItem.stock" required
                           class="w-full text-sm font-mono font-bold p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alasan Penyesuaian / Audit *</label>
                    <textarea name="reason" required rows="2" placeholder="Contoh: Selisih timbangan susut air / kotoran menguap, hasil opname akhir bulan..."
                              class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="adjustModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm">
                        Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

