@extends('layouts.app', ['header' => 'Manajemen Stok & Persediaan Gudang'])

@section('content')
<div class="space-y-4" x-data="stockManager()">

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                <i data-lucide="boxes" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">
                    {{ $isFilteredByDate ? 'Total Penerimaan Barang (' . $periodLabel . ')' : 'Total Akumulasi Stok Gudang' }}
                </div>
                <div class="text-lg md:text-xl font-bold text-slate-900 font-mono">
                    {{ $totalStockDisplay }}
                </div>
                <div class="text-[11px] text-slate-400">
                    {{ $isFilteredByDate ? 'Total fisik masuk dari pembelian' : 'Total berat fisik tersedia saat ini' }}
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                <i data-lucide="calculator" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">
                    {{ $isFilteredByDate ? 'Nilai Penerimaan (' . $periodLabel . ')' : 'Estimasi Valuasi Persediaan' }}
                </div>
                <div class="text-xl font-bold text-slate-900 font-mono">
                    Rp {{ number_format($totalValuation, 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-400">
                    {{ $isFilteredByDate ? 'Total biaya pembelian barang masuk' : 'Dihitung dari moving average buy price' }}
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs text-slate-500 font-medium">Kartu Stok & Pergerakan</div>
                <div class="text-sm font-bold text-slate-800 mt-1">Audit Fisik & Mutasi</div>
                <div class="text-[11px] text-slate-400">Opname fisik atau audit buku mutasi</div>
            </div>
            <div class="flex items-center gap-2">
                @if(Auth::user()->isOwner())
                    <button type="button" 
                            @click.stop="openOpnameModal(null)"
                            class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs flex items-center gap-1.5 shadow-sm transition-colors">
                        <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                        <span>Stock Opname</span>
                    </button>
                @endif
                <a href="{{ route('stock.movements') }}" 
                   class="px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center gap-1.5 shadow-sm transition-colors">
                    <i data-lucide="history" class="w-3.5 h-3.5"></i>
                    <span>Lihat Mutasi</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm space-y-3">
        <form method="GET" action="{{ route('stock.index') }}" class="space-y-3">
            <!-- Row 1: Search + Month/Year Filter -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cari Barang</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Nama barang atau kode..."
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

                <x-month-year-filter :selected-month="$selectedMonth ?? request('month')" :selected-year="$selectedYear ?? request('year')" :default-all="true" />
            </div>

            <!-- Row 2: Kategori, Dari Tanggal, Sampai Tanggal, Actions -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                    <select name="category_id" class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                        <option value="">-- Semua Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

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
            </div>
        </form>
    </div>

    <!-- Stock Balances Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Persediaan Barang & Penilaian Fisik</h3>
                @if($isFilteredByDate)
                    <p class="text-[11px] text-emerald-700 font-semibold">Stock berdasarkan penerimaan barang (pembelian) periode: <span class="underline font-bold">{{ $periodLabel }}</span> &bull; Total: {{ $items->total() }} jenis material</p>
                @else
                    <p class="text-[11px] text-slate-500">Total: {{ $items->total() }} jenis material terdata</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if(Auth::user()->isOwner())
                    <button type="button" 
                            @click.stop="openOpnameModal(null)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors">
                        <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                        <span>+ Input Stock Opname</span>
                    </button>
                @endif
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
                        <th class="py-2.5 px-3 text-right">{{ $isFilteredByDate ? 'Penerimaan Periode' : 'Total Masuk' }}</th>
                        <th class="py-2.5 px-3 text-right">Total Keluar</th>
                        <th class="py-2.5 px-3 text-right font-bold text-slate-900">{{ $isFilteredByDate ? 'Stok Penerimaan' : 'Stok Tersedia' }}</th>
                        <th class="py-2.5 px-3 text-right">Rata-Rata Beli</th>
                        <th class="py-2.5 px-3 text-right font-bold">Estimasi Nilai</th>
                        <th class="py-2.5 px-3 text-center">Status Stok</th>
                        @if(Auth::user()->isOwner())
                            <th class="py-2.5 px-3 text-center">Opname / Adjust</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        @php
                            $bal = $item->stockBalance;
                            $effectiveUnit = $item->unit_type ?: ($item->unit ?: 'kg');
                            $isPcs = in_array(strtolower($effectiveUnit), ['pcs', 'satuan', 'unit', 'buah', 'lembar']);
                            $formatStock = fn($val) => $isPcs ? \App\Services\FormatHelper::number($val) : \App\Services\FormatHelper::weight($val);

                            $curStock = $isFilteredByDate ? (float) ($periodIncomingStocks[$item->id] ?? 0) : (float) ($bal?->current_stock ?? 0);
                            $avgBuy = $isFilteredByDate ? (float) ($periodIncomingAvgPrices[$item->id] ?? ($bal?->average_buy_price ?? 0)) : (float) ($bal?->average_buy_price ?? 0);
                            $estVal = $curStock * $avgBuy;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors {{ $item->isLowStock() ? 'bg-amber-50/40' : '' }}">
                            <td class="py-3 px-3 font-mono font-bold text-slate-700">{{ $item->code }}</td>
                            <td class="py-3 px-3 font-bold text-slate-900">
                                <div>{{ $item->name }}</div>
                                @if(!$isFilteredByDate && $item->priceStocks && $item->priceStocks->count() > 1)
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($item->priceStocks as $ps)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono bg-blue-50 text-blue-700 border border-blue-200">
                                                Rp {{ \App\Services\FormatHelper::number($ps->price_per_kg) }}: <strong class="ml-1">{{ $formatStock($ps->current_stock) }}</strong> {{ $effectiveUnit }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">Min. Alert: {{ $formatStock($item->min_stock_alert) }} {{ $effectiveUnit }}</div>
                            </td>
                            <td class="py-3 px-3 text-slate-600">{{ $item->category?->name ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-mono text-emerald-700">
                                +{{ $formatStock($isFilteredByDate ? $curStock : ($bal?->total_in ?? 0)) }} {{ $effectiveUnit }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-red-600">
                                @if($isFilteredByDate)
                                    <span class="text-slate-300">-</span>
                                @else
                                    -{{ $formatStock($bal?->total_out ?? 0) }} {{ $effectiveUnit }}
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-black text-sm {{ $item->isLowStock() ? 'text-amber-600' : 'text-slate-900' }}">
                                {{ $formatStock($curStock) }} {{ $effectiveUnit }}
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
                            @if(Auth::user()->isOwner())
                                <td class="py-3 px-3 text-center">
                                    <button type="button" 
                                            @click.stop="openOpnameModal({ id: {{ $item->id }}, name: '{{ addslashes($item->name) }}', code: '{{ $item->code }}', stock: {{ (float)$curStock }}, unit: '{{ $effectiveUnit }}' })"
                                            title="Penyesuaian Fisik / Stock Opname"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-700 font-semibold text-[11px] border border-slate-200 transition-colors">
                                        <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                                        <span>Opname</span>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ Auth::user()->isOwner() ? 10 : 9 }}" class="py-8 text-center text-slate-400">Belum ada data stok.</td>
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
         style="display: none;"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.outside="closeOpnameModal()">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="sliders" class="w-4 h-4 text-emerald-600"></i>
                        <span>Stock Opname Fisik Gudang</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Penyesuaian hasil audit timbangan & stok fisik</p>
                </div>
                <button type="button" @click="closeOpnameModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="'{{ url('stock') }}/' + (activeItem.id || '') + '/adjust'" method="POST" class="space-y-4">
                @csrf

                <!-- Pilihan Barang jika dibuka dari tombol atas / menu opname -->
                <template x-if="!activeItem.id || isChangingItem">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Pilih Barang Rongsok <span class="text-red-500">*</span>
                        </label>
                        <select x-model="selectedItemId" @change="onSelectModalItem($event.target.value)" required
                                class="w-full text-xs font-semibold p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 bg-white shadow-2xs">
                            <option value="">-- Cari / Pilih Barang Master --</option>
                            <template x-for="item in allMasterItems" :key="item.id">
                                <option :value="item.id" x-text="item.name + ' (' + item.code + ') - Stok: ' + formatNumber(item.stock) + ' ' + item.unit"></option>
                            </template>
                        </select>
                    </div>
                </template>

                <template x-if="activeItem.id && !isChangingItem">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Barang yang Diopname:</span>
                            <div class="text-sm font-bold text-slate-900" x-text="activeItem.name"></div>
                            <div class="text-xs text-slate-500 font-mono" x-text="'Kode: ' + (activeItem.code || '-')"></div>
                        </div>
                        <button type="button" @click="isChangingItem = true" class="text-xs text-blue-600 hover:underline font-semibold">
                            Ganti Barang
                        </button>
                    </div>
                </template>

                <!-- Informasi Stok & Satuan -->
                <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Stok Tercatat Sistem</span>
                        <div class="text-sm font-mono font-bold text-slate-800">
                            <span x-text="formatNumber(activeItem.stock || 0)"></span>
                            <span class="text-xs font-normal text-slate-500" x-text="activeItem.unit || 'kg'"></span>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Satuan Barang</span>
                        <div class="text-sm font-bold text-emerald-700 uppercase font-mono" x-text="activeItem.unit || 'kg'"></div>
                    </div>
                </div>

                <!-- Input Hasil Fisik Dinamis -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        <span x-text="isPcsUnit(activeItem.unit) ? 'Hasil Fisik Sebenarnya (Pcs / Satuan) *' : 'Hasil Timbangan Fisik Sebenarnya (kg) *'"></span>
                    </label>
                    <div class="relative">
                        <input type="number" 
                               :step="isPcsUnit(activeItem.unit) ? '1' : '0.01'" 
                               min="0" 
                               name="new_stock" 
                               x-model.number="activeItem.new_stock" 
                               :placeholder="isPcsUnit(activeItem.unit) ? '0' : '0.00'" 
                               required
                               class="w-full text-sm font-mono font-bold py-2.5 pl-3 pr-16 rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 bg-white shadow-2xs">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs font-bold uppercase text-slate-400 font-mono"
                              x-text="activeItem.unit || 'kg'"></span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-[11px]" x-show="activeItem.new_stock !== '' && activeItem.new_stock !== null">
                        <span class="text-slate-500">Selisih Fisik vs Sistem:</span>
                        <span class="font-mono font-bold" 
                              :class="stockDiff >= 0 ? 'text-emerald-700' : 'text-rose-600'" 
                              x-text="(stockDiff > 0 ? '+' : '') + formatNumber(stockDiff) + ' ' + (activeItem.unit || 'kg')"></span>
                    </div>
                </div>

                <!-- Alasan Penyesuaian -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Alasan Penyesuaian / Hasil Audit <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reason" required rows="2" 
                              placeholder="Contoh: Selisih timbangan susut air / kotoran, salah catat nota sebelumnya, opname berkala..."
                              class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="closeOpnameModal()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            :disabled="!activeItem.id"
                            class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm disabled:opacity-40 transition-all">
                        Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function stockManager() {
    return {
        adjustModal: false,
        activeItem: {},
        selectedItemId: '',
        isChangingItem: false,
        allMasterItems: @json($allItemsForOpname ?? []),
        autoOpenOpname: {{ request()->query('opname') ? 'true' : 'false' }},

        init() {
            if (this.autoOpenOpname) {
                this.openOpnameModal(null);
            }
        },

        isPcsUnit(u) {
            if (!u) return false;
            return ['pcs', 'satuan', 'unit', 'buah', 'lembar'].includes(String(u).toLowerCase().trim());
        },

        formatNumber(val) {
            const num = parseFloat(val) || 0;
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(num);
        },

        openOpnameModal(item) {
            if (item && item.id) {
                this.activeItem = {
                    id: item.id,
                    name: item.name,
                    code: item.code || '',
                    stock: parseFloat(item.stock) || 0,
                    new_stock: parseFloat(item.stock) || 0,
                    unit: item.unit || 'kg'
                };
                this.selectedItemId = item.id;
                this.isChangingItem = false;
            } else {
                this.activeItem = { id: '', name: '', code: '', stock: 0, new_stock: '', unit: 'kg' };
                this.selectedItemId = '';
                this.isChangingItem = true;
            }
            this.adjustModal = true;
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },

        closeOpnameModal() {
            this.adjustModal = false;
        },

        onSelectModalItem(id) {
            const found = this.allMasterItems.find(it => it.id == id);
            if (found) {
                this.activeItem = {
                    id: found.id,
                    name: found.name,
                    code: found.code || '',
                    stock: parseFloat(found.stock) || 0,
                    new_stock: parseFloat(found.stock) || 0,
                    unit: found.unit || 'kg'
                };
                this.isChangingItem = false;
            }
        },

        get stockDiff() {
            const cur = parseFloat(this.activeItem.stock) || 0;
            const nw = (this.activeItem.new_stock !== '' && this.activeItem.new_stock !== null) 
                ? (parseFloat(this.activeItem.new_stock) || 0) 
                : cur;
            return nw - cur;
        }
    };
}
</script>
@endpush
