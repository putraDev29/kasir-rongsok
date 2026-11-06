@extends('layouts.app', ['header' => 'Kasir Penerimaan Material Scrap & Daur Ulang'])

@section('content')
<div x-data="cashierPos()" x-init="init()" class="space-y-4">

    <!-- POS Top Summary Bar -->
    <div class="bg-slate-900 text-white rounded-xl p-4 sm:p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center text-white shrink-0 shadow-md">
                <i data-lucide="scale" class="w-7 h-7"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">POS TIMBANGAN & PEMBELIAN</span>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                    Nota Baru: <span class="text-emerald-400 font-mono" x-text="previewInvoice">KB-......-....</span>
                </h2>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 bg-slate-800/80 px-4 py-2.5 rounded-lg border border-slate-700">
            <div>
                <div class="text-[10px] uppercase font-semibold text-slate-400">Total Berat Bersih</div>
                <div class="text-lg font-black text-amber-400">
                    <span x-text="formatNum(totalNetWeight())">0</span> kg
                </div>
            </div>
            <div class="w-px h-8 bg-slate-700 hidden sm:block"></div>
            <div>
                <div class="text-[10px] uppercase font-semibold text-slate-400">Total Pembayaran</div>
                <div class="text-xl sm:text-2xl font-black text-emerald-400">
                    <span x-text="formatRp(finalAmount())">Rp 0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main POS Form -->
    <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm" @submit="handleSubmit($event)">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            
            <!-- LEFT COLUMN: Item Weighing Grid (Multi-Item) -->
            <div class="lg:col-span-8 space-y-4">
                
                <!-- Quick Scrap Category Filter Pills & Search -->
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2">
                            <i data-lucide="sparkles" class="w-4 h-4 text-emerald-600"></i>
                            <span class="text-xs font-bold uppercase text-slate-800 tracking-wider">Pilih Cepat Material Scrap & Daur Ulang</span>
                        </div>
                        <div class="w-full sm:w-64 relative">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </span>
                            <input type="text" x-model="itemSearch" placeholder="Cari nama / kode rongsok..." 
                                   class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- Category filter tags -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs" style="scrollbar-width: thin;">
                        <button type="button" @click="selectedCategory = 'all'"
                                :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2.5 py-1 rounded-md shrink-0 transition-colors whitespace-nowrap">
                            Semua
                        </button>
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button" @click="selectedCategory = cat.id"
                                    :class="selectedCategory == cat.id ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="px-2.5 py-1 rounded-md shrink-0 transition-colors whitespace-nowrap"
                                    x-text="cat.name">
                            </button>
                        </template>
                    </div>

                    <!-- Fast Click Item Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 max-h-48 overflow-y-auto pr-1">
                        <template x-for="item in filteredItems()" :key="item.id">
                            <button type="button" @click="addItem(item)"
                                    class="text-left p-2 rounded-lg border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition-all group flex flex-col justify-between">
                                <div>
                                    <div class="text-[11px] font-bold text-slate-900 group-hover:text-emerald-700 truncate" x-text="item.name"></div>
                                    <div class="text-[10px] text-slate-400" x-text="item.code"></div>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-[11px] font-bold text-emerald-600">
                                    <span x-text="formatRp(item.buy_price_per_kg) + '/kg'"></span>
                                    <span class="text-[10px] text-slate-400 font-normal" x-text="'Stok: ' + (item.current_stock || 0) + 'kg'"></span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Active Weighing Cards (Barang Ditimbang - 2 Baris Ringkas & Scrollable) -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-xs" x-text="rows.length">1</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Daftar Timbangan Masuk</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="addNewItemRow()" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 transition-all shadow-2xs">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-emerald-600"></i>
                                <span>+ Input Barang Baru</span>
                            </button>
                            <button type="button" @click="addNewEmptyRow()" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>Tambah Baris (+F2)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Items Container (Scroll saat barang melebihi layar) -->
                    <div class="p-3 space-y-2.5 max-h-[460px] overflow-y-auto bg-slate-100/60" style="scrollbar-width: thin;">
                        <template x-for="(row, index) in rows" :key="row.uid">
                            <div class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-emerald-400 transition-all p-3 space-y-2">
                                <!-- HEADER ROW: Row # & Mode Toggle (Sama seperti Tambah Pemasok Baru) -->
                                <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-md bg-slate-800 text-white font-mono font-bold text-[10px] flex items-center justify-center shadow-xs" x-text="index + 1">1</span>
                                        <span x-show="!row.is_new" class="text-[11px] font-bold text-slate-700">Material Scrap & Daur Ulang</span>
                                        <span x-show="row.is_new" style="display: none;" class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            Input Barang Baru (Auto Simpan ke Master)
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <!-- Indicator for multiple price tiers / accumulated weight -->
                                        <template x-if="hasMultiplePrices(row)">
                                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full border border-emerald-200">
                                                📦 Akumulasi <span x-text="getRowBaseName(row)"></span>: <strong x-text="formatNum(getItemAccumulatedWeight(row)) + ' ' + (row.unit_type || 'kg')"></strong>
                                            </span>
                                        </template>
                                        <button type="button" @click="toggleRowItemMode(row)" 
                                                class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline inline-flex items-center gap-1">
                                            <span x-text="row.is_new ? '← Pilih dari Master Barang' : '+ Tambah Barang Baru'"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- BARIS 1: NAMA BARANG / MODE SELEKSI & JENIS / VARIAN -->
                                <div class="flex items-center gap-2">
                                    <!-- Mode 1: Searchable Combobox Master Barang -->
                                    <div x-show="!row.is_new" class="flex-1 min-w-0 relative" @click.outside="row.dropdown_open = false">
                                        <input type="hidden" :name="'items[' + index + '][scrap_item_id]'" :value="row.scrap_item_id" :disabled="row.is_new">

                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                            </span>

                                            <input type="text"
                                                   x-model="row.search_text"
                                                   @focus="row.dropdown_open = true"
                                                   @input="row.dropdown_open = true"
                                                   placeholder="Cari / pilih material scrap & daur ulang..."
                                                   :required="!row.is_new"
                                                   class="w-full pl-8 pr-14 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-bold text-slate-900 bg-white shadow-2xs">

                                            <!-- Quick Clear & Caret Icons -->
                                            <div class="absolute inset-y-0 right-0 pr-1.5 flex items-center gap-0.5">
                                                <button type="button" 
                                                        x-show="row.search_text"
                                                        @click="clearRowItem(row, index)" 
                                                        class="text-slate-400 hover:text-slate-600 p-0.5 rounded"
                                                        title="Hapus pilihan barang">
                                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                </button>
                                                <button type="button" 
                                                        @click="row.dropdown_open = !row.dropdown_open"
                                                        class="text-slate-400 hover:text-slate-600 p-0.5 rounded">
                                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': row.dropdown_open }"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Floating Filtered List -->
                                        <div x-show="row.dropdown_open"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             class="absolute z-50 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white rounded-xl border border-slate-200 shadow-xl divide-y divide-slate-100"
                                             style="display: none;">
                                            
                                            <template x-for="item in filteredMasterItems(row.search_text)" :key="item.id">
                                                <button type="button" 
                                                        @click="selectRowItem(row, item, index)"
                                                        class="w-full text-left px-3 py-2 hover:bg-emerald-50 flex items-center justify-between transition-colors"
                                                        :class="{ 'bg-emerald-50/70': row.scrap_item_id == item.id }">
                                                    <div class="min-w-0 pr-2">
                                                        <div class="text-xs font-bold text-slate-900 truncate" x-text="item.name"></div>
                                                        <div class="text-[10px] text-slate-500 font-mono" x-text="item.code + (item.category ? ' • ' + item.category.name : '')"></div>
                                                    </div>
                                                    <div class="text-right shrink-0">
                                                        <div class="text-[11px] font-mono font-bold text-emerald-700" x-text="formatRp(item.buy_price_per_kg) + '/' + (item.unit_type || 'kg')"></div>
                                                    </div>
                                                </button>
                                            </template>

                                            <!-- Quick Add Button if not found -->
                                            <template x-if="filteredMasterItems(row.search_text).length === 0">
                                                <div class="p-3 text-center space-y-2">
                                                    <p class="text-xs text-slate-500">Tidak ada barang dengan kata kunci "<span class="font-bold text-slate-700" x-text="row.search_text"></span>"</p>
                                                    <button type="button" 
                                                            @click="addNewItemToRow(row, row.search_text)"
                                                            class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs inline-flex items-center gap-1">
                                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                                        <span x-text="'+ Tambah &quot;' + row.search_text + '&quot; sebagai Barang Baru'"></span>
                                                    </button>
                                                </div>
                                            </template>

                                            <!-- Option to add as new even when matching results exist -->
                                            <template x-if="filteredMasterItems(row.search_text).length > 0 && row.search_text && !isExactMatchMaster(row.search_text)">
                                                <div class="p-2 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 truncate max-w-[200px]">Barang tidak ada di daftar?</span>
                                                    <button type="button"
                                                            @click="addNewItemToRow(row, row.search_text)"
                                                            class="px-2.5 py-1 text-[11px] font-bold rounded-md bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs inline-flex items-center gap-1 shrink-0">
                                                        <i data-lucide="plus" class="w-3 h-3"></i>
                                                        <span>+ Input Barang Baru</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Mode 2: Input Manual Barang Baru (Sama seperti Tambah Pemasok Baru) -->
                                    <div x-show="row.is_new" style="display: none;" class="flex-1 min-w-0 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                        <input type="hidden" :name="'items[' + index + '][scrap_item_id]'" value="__new__" :disabled="!row.is_new">
                                        
                                        <div class="flex-1 relative">
                                            <input type="text" 
                                                   :name="'items[' + index + '][new_item_name]'" 
                                                   x-model="row.new_item_name" 
                                                   placeholder="Ketik nama material / barang baru..."
                                                   :required="row.is_new"
                                                   class="w-full py-1.5 px-3 text-xs rounded-lg border border-emerald-500 focus:ring-2 focus:ring-emerald-500 font-bold text-slate-900 bg-emerald-50/30">
                                        </div>

                                        <div class="w-56 sm:w-64 shrink-0">
                                            <!-- Mode Pilih Kategori dari Master -->
                                            <div x-show="!row.is_new_category" class="flex items-center gap-1">
                                                <select :name="'items[' + index + '][category_id]'" 
                                                        x-model="row.category_id" 
                                                        @change="if (row.category_id === '__new__') { row.is_new_category = true; row.new_category_name = ''; }"
                                                        class="w-full py-1.5 px-2 text-xs rounded-lg border border-emerald-400 bg-white font-medium text-slate-800 focus:ring-1 focus:ring-emerald-500">
                                                    <option value="">-- Kategori (Opsional) --</option>
                                                    <template x-for="cat in categories" :key="cat.id">
                                                        <option :value="cat.id" x-text="cat.name" :selected="row.category_id == cat.id"></option>
                                                    </template>
                                                    <option value="__new__">✨ + Kategori Baru...</option>
                                                </select>
                                                <button type="button" 
                                                        @click="row.is_new_category = true; row.category_id = '__new__'; row.new_category_name = '';" 
                                                        class="px-2 py-1.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 rounded-md whitespace-nowrap shrink-0 transition-colors" 
                                                        title="Ketik nama kategori baru">
                                                    + Baru
                                                </button>
                                            </div>

                                            <!-- Mode Input Kategori Baru -->
                                            <div x-show="row.is_new_category" style="display: none;" class="flex items-center gap-1">
                                                <input type="hidden" :name="'items[' + index + '][category_id]'" value="__new__" :disabled="!row.is_new_category">
                                                <input type="text" 
                                                       :name="'items[' + index + '][new_category_name]'" 
                                                       x-model="row.new_category_name" 
                                                       placeholder="Ketik kategori baru..."
                                                       class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-emerald-500 bg-emerald-50/50 font-bold text-slate-900 focus:ring-1 focus:ring-emerald-500">
                                                <button type="button" 
                                                        @click="row.is_new_category = false; row.category_id = ''; row.new_category_name = '';" 
                                                        class="px-2 py-1.5 text-[10px] font-semibold text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded border border-slate-300 shrink-0 transition-colors"
                                                        title="Batal / Pilih dari daftar kategori">
                                                    ✕ Batal
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Jenis / Varian Manual -->
                                    <div class="w-36 sm:w-48 shrink-0">
                                        <input type="text" 
                                               :name="'items[' + index + '][item_type_name]'" 
                                               x-model="row.item_type_name" 
                                               placeholder="Jenis/Varian (Bening, Super...)"
                                               class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800 bg-white">
                                    </div>

                                    <!-- Tombol Hapus: Sangat jelas dan muncul dengan SVG murni ketika baris > 1 -->
                                    <button type="button" @click="removeRow(index)" 
                                            :disabled="rows.length <= 1"
                                            :class="rows.length > 1 
                                                ? 'text-red-600 bg-red-50 hover:bg-red-200 hover:text-red-800 hover:border-red-400 active:bg-red-300 active:text-red-950 active:border-red-500 border-red-300 shadow-2xs cursor-pointer' 
                                                : 'text-slate-300 bg-slate-100 border-slate-200 cursor-not-allowed opacity-30'"
                                            class="p-1.5 rounded-lg border transition-all shrink-0 flex items-center justify-center focus:outline-hidden focus:ring-2 focus:ring-red-400"
                                            title="Hapus baris timbangan ini">
                                        <svg class="w-4 h-4 transition-colors" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Helper Text for New Item Mode -->
                                <div x-show="row.is_new" style="display: none;" class="px-1 text-[10px] text-emerald-600 font-medium flex items-center gap-1">
                                    <i data-lucide="sparkles" class="w-3 h-3 text-emerald-500"></i>
                                    <span>Barang baru otomatis tersimpan ke Master Data saat transaksi disimpan. Masukkan harga beli pada kolom harga di bawah.</span>
                                </div>

                                <!-- BARIS 2: VALUE ANGKA & SATUAN (TETAP 1 BARIS PENUH) -->
                                <div class="grid grid-cols-6 gap-2 pt-1.5 border-t border-slate-100 items-end"
                                     style="display: grid; grid-template-columns: minmax(70px, 0.8fr) minmax(80px, 1fr) minmax(105px, 1.25fr) minmax(85px, 1fr) minmax(95px, 1.1fr) minmax(110px, 1.25fr); gap: 0.5rem; align-items: end;">
                                    <!-- 1. Tipe Satuan -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5 whitespace-nowrap">Satuan</label>
                                        <select :name="'items[' + index + '][unit_type]'" 
                                                x-model="row.unit_type"
                                                @change="onUnitTypeChanged(row)"
                                                class="w-full py-1.5 px-1.5 text-xs font-bold rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 uppercase bg-slate-50 text-slate-800 text-center cursor-pointer">
                                            <option value="kg">⚖️ KG</option>
                                            <option value="pcs">📦 Pcs</option>
                                            <option value="satuan">🔢 Satuan</option>
                                        </select>
                                    </div>

                                    <!-- 2. Berat Kotor / Qty -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5 whitespace-nowrap"
                                               x-text="isPcsUnit(row) ? 'Qty (' + (row.unit_type || 'pcs') + ') *' : 'Kotor (kg) *'"></label>
                                        <template x-if="isPcsUnit(row)">
                                            <div>
                                                <input type="number" step="1" min="1" 
                                                       :name="'items[' + index + '][quantity]'" 
                                                       x-model.number="row.quantity" 
                                                       @input="onGrossOrQtyChanged(row)"
                                                       placeholder="1"
                                                       required
                                                       class="w-full py-1.5 px-1.5 text-xs text-right rounded-lg border border-amber-300 bg-amber-50/60 font-mono font-bold text-amber-950">
                                                <input type="hidden" :name="'items[' + index + '][gross_weight]'" :value="row.quantity || 1">
                                            </div>
                                        </template>
                                        <template x-if="!isPcsUnit(row)">
                                            <div>
                                                <input type="number" step="0.01" min="0.01" 
                                                       :name="'items[' + index + '][gross_weight]'" 
                                                       x-model.number="row.gross_weight" 
                                                       @input="onGrossOrQtyChanged(row)"
                                                       placeholder="0.00"
                                                       required
                                                       class="w-full py-1.5 px-1.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono font-bold text-slate-900 bg-white">
                                                <input type="hidden" :name="'items[' + index + '][quantity]'" value="1">
                                            </div>
                                        </template>
                                    </div>

                                    <!-- 3. Pot./Tara (Bisa Persen % atau Sesuai Satuan) -->
                                    <div>
                                        <div class="flex items-center justify-between mb-0.5">
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Tara</label>
                                            <!-- Toggle Mode: Satuan (kg/pcs/satuan) vs Persen (%) -->
                                            <div class="inline-flex items-center text-[9px] font-bold rounded bg-slate-200/90 p-0.5 leading-none shadow-2xs">
                                                <button type="button" 
                                                        @click="setTareMode(row, 'unit')"
                                                        :class="row.tare_mode !== 'percent' ? 'bg-white text-emerald-800 shadow-2xs font-black' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                                                        class="px-1.5 py-0.5 rounded transition-all uppercase text-[9px]"
                                                        x-text="row.unit_type || 'kg'"></button>
                                                <button type="button" 
                                                        @click="setTareMode(row, 'percent')"
                                                        :class="row.tare_mode === 'percent' ? 'bg-emerald-600 text-white shadow-2xs font-black' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                                                        class="px-1.5 py-0.5 rounded transition-all text-[9px]">%</button>
                                            </div>
                                        </div>

                                        <!-- Mode Persen (%) -->
                                        <template x-if="row.tare_mode === 'percent'">
                                            <div>
                                                <div class="relative">
                                                    <input type="number" step="0.1" min="0" max="100"
                                                           x-model.number="row.tare_percent"
                                                           @input="onTarePercentChanged(row)"
                                                           placeholder="0"
                                                           class="w-full py-1.5 pl-1.5 pr-4 text-xs text-right rounded-lg border border-emerald-400 focus:ring-2 focus:ring-emerald-500 font-mono font-bold text-emerald-950 bg-emerald-50/40">
                                                    <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-emerald-700 pointer-events-none select-none">%</span>
                                                </div>
                                                <div class="text-[9px] text-right font-mono text-emerald-800 font-bold mt-0.5 truncate"
                                                     x-show="rowTareWeight(row) > 0"
                                                     x-text="'= ' + formatNum(rowTareWeight(row)) + ' ' + (row.unit_type || 'kg')"></div>
                                            </div>
                                        </template>

                                        <!-- Mode Nominal Satuan (kg / pcs / satuan) -->
                                        <template x-if="row.tare_mode !== 'percent'">
                                            <div>
                                                <div class="relative">
                                                    <input type="number" :step="isPcsUnit(row) ? '1' : '0.01'" min="0" 
                                                           x-model.number="row.tare_weight" 
                                                           @input="onTareWeightChanged(row)"
                                                           placeholder="0.00"
                                                           class="w-full py-1.5 px-1.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono text-slate-700 bg-white">
                                                </div>
                                                <div class="text-[9px] text-right font-mono text-slate-400 mt-0.5 truncate"
                                                     x-show="calcTarePercent(row) > 0"
                                                     x-text="'(' + calcTarePercent(row) + '%)'"></div>
                                            </div>
                                        </template>

                                        <!-- Hidden inputs for backend submission -->
                                        <input type="hidden" :name="'items[' + index + '][tare_weight]'" :value="rowTareWeight(row)">
                                        <input type="hidden" :name="'items[' + index + '][tare_percent]'" :value="row.tare_mode === 'percent' ? (row.tare_percent || 0) : 0">
                                        <input type="hidden" :name="'items[' + index + '][tare_mode]'" :value="row.tare_mode || 'kg'">
                                    </div>

                                    <!-- 4. Hasil Bersih -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-emerald-800 uppercase tracking-wider mb-0.5 whitespace-nowrap">Bersih</label>
                                        <div class="py-1.5 px-1.5 text-xs text-right font-mono font-black rounded-lg bg-emerald-100/90 text-emerald-950 border border-emerald-300 truncate whitespace-nowrap">
                                            <template x-if="isPcsUnit(row)">
                                                <span x-text="formatInteger(rowNetWeight(row)) + ' ' + (row.unit_type || 'pcs')"></span>
                                            </template>
                                            <template x-if="!isPcsUnit(row)">
                                                <span x-text="formatNum(rowNetWeight(row)) + ' kg'"></span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- 5. Harga Beli (Rp) -->
                                    <div>
                                        <div class="flex items-center justify-between mb-0.5">
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Harga Beli *</label>
                                            <template x-if="getMasterPrice(row) > 0">
                                                <span class="text-[9px] text-slate-400 font-mono" :title="'Harga master: ' + formatRp(getMasterPrice(row))">
                                                    Mstr: <span x-text="formatRp(getMasterPrice(row))"></span>
                                                </span>
                                            </template>
                                        </div>
                                        <input type="number" step="100" min="0" 
                                               :max="!isOwner && getMasterPrice(row) > 0 ? getMasterPrice(row) : null"
                                               :name="'items[' + index + '][price_per_kg]'" 
                                               x-model.number="row.price_per_kg" 
                                               @input="validatePrice(row)"
                                               @change="validatePrice(row)"
                                               required
                                               class="w-full py-1.5 px-1.5 text-xs text-right rounded-lg border font-mono font-bold transition-colors"
                                               :class="!isOwner && getMasterPrice(row) > 0 && row.price_per_kg > getMasterPrice(row) 
                                                   ? 'border-red-500 bg-red-50 text-red-700' 
                                                   : 'border-slate-300 focus:ring-2 focus:ring-emerald-500 text-slate-900 bg-white'">
                                        <template x-if="!isOwner && getMasterPrice(row) > 0">
                                            <p class="text-[9px] text-amber-600 mt-0.5 leading-tight font-medium">Maks: <span x-text="formatRp(getMasterPrice(row))"></span></p>
                                        </template>
                                        <template x-if="isOwner">
                                            <p class="text-[9px] text-emerald-600 mt-0.5 leading-tight font-medium">Bebas edit (Owner)</p>
                                        </template>
                                    </div>

                                    <!-- 6. Subtotal (Rp) -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-700 uppercase tracking-wider mb-0.5 text-right whitespace-nowrap">Subtotal</label>
                                        <div class="py-1.5 px-1.5 text-xs text-right font-mono font-black rounded-lg bg-slate-900 text-white shadow-2xs border border-slate-800 truncate whitespace-nowrap"
                                             x-text="formatRp(rowSubtotal(row))">
                                            Rp 0
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Bottom Bar: Tambah Baris & Rekap Total -->
                    <div class="p-3 bg-slate-50 border-t border-slate-200 space-y-2">
                        <!-- Commodities accumulation summary if any commodity has multiple prices -->
                        <template x-if="multiPriceCommodities().length > 0">
                            <div class="p-2 bg-emerald-50 rounded-lg border border-emerald-200 text-xs">
                                <div class="text-[10px] font-bold uppercase text-emerald-900 tracking-wider mb-1 flex items-center gap-1">
                                    <i data-lucide="layers" class="w-3.5 h-3.5 text-emerald-700"></i>
                                    <span>Akumulasi Komoditas Barang (Tercatat Dalam Beberapa Harga):</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <template x-for="c in multiPriceCommodities()" :key="c.name">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-white border border-emerald-300 font-mono text-[11px] text-slate-800 shadow-2xs">
                                            <strong x-text="c.name"></strong>: 
                                            <span class="font-bold text-emerald-700" x-text="formatNum(c.totalWeight) + ' ' + c.unit"></span>
                                            <span class="text-slate-400" x-text="'(' + formatRp(c.totalSubtotal) + ')'"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <button type="button" @click="addNewEmptyRow()" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition-all">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                <span>Tambah Item Barang Lain (+F2)</span>
                            </button>

                            <div class="text-xs text-slate-600 flex items-center gap-3 bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                                <span>Item: <strong class="text-slate-900 font-mono" x-text="rows.length"></strong></span>
                                <template x-if="totalGrossWeight() > 0 && totalTareWeight() > 0">
                                    <span>Kotor: <strong class="text-slate-700 font-mono" x-text="formatNum(totalGrossWeight()) + ' kg'"></strong></span>
                                </template>
                                <template x-if="totalTareWeight() > 0">
                                    <span>Tara: <strong class="text-red-600 font-mono font-bold" x-text="'-' + formatNum(totalTareWeight()) + ' kg'"></strong></span>
                                </template>
                                <template x-if="totalNetWeight() > 0">
                                    <span>Bersih: <strong class="text-emerald-700 font-mono font-bold" x-text="formatNum(totalNetWeight()) + ' kg'"></strong></span>
                                </template>
                                <template x-if="totalNetWeight() <= 0 && totalPcsQty() > 0">
                                    <span>Satuan: <strong class="text-amber-700 font-mono font-bold" x-text="formatInteger(totalPcsQty()) + ' pcs'"></strong></span>
                                </template>
                                <span>Subtotal: <strong class="text-slate-900 font-mono font-black" x-text="formatRp(subtotalAmount())"></strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Input -->
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Catatan Transaksi / Keterangan Khusus</label>
                    <input type="text" name="notes" placeholder="Contoh: Dari bengkel motor, kardus bersih tanpa air, dsb."
                           class="w-full py-1.5 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>

            <!-- RIGHT COLUMN: Supplier Info, Payment, and Final Checkout -->
            <div class="lg:col-span-4 space-y-4">
                
                <!-- Supplier Info Card (Dropdown + Tambah Baru sama seperti Kategori Pengeluaran) -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Informasi Pemasok / Penjual</span>
                    </div>

                    <!-- Backdate / Tanggal Transaksi (Task 3) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 inline text-emerald-600 mr-1"></i>
                            Tanggal Transaksi (Bisa Backdate)
                        </label>
                        <input type="datetime-local" name="transaction_date" x-model="transactionDate"
                               class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono text-slate-800">
                    </div>

                    <!-- Supplier Input Section: Label on left, "+ Tambah Pemasok Baru" on right -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Pilih Nama Pemasok *</label>
                            <button type="button" @click="toggleSupplierMode()" 
                                    class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline">
                                <span x-text="isManualSupplier ? '← Pilih dari Daftar Pemasok' : '+ Tambah Pemasok Baru'"></span>
                            </button>
                        </div>

                        <!-- Mode 1: Searchable Dropdown Pemasok Tersimpan -->
                        <div x-show="!isManualSupplier" class="relative" @click.outside="supplierDropdownOpen = false">
                            <input type="hidden" name="supplier_id" :value="selectedSupplierId" :disabled="isManualSupplier">
                            <input type="hidden" name="supplier_name" :value="supplierName" :disabled="isManualSupplier">

                            <!-- Search Input Box -->
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                </span>

                                <input type="text"
                                       x-model="supplierSearchQuery"
                                       @focus="supplierDropdownOpen = true"
                                       @input="supplierDropdownOpen = true"
                                       placeholder="Cari nama, no. member, atau telepon pemasok..."
                                       class="w-full pl-8 pr-16 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-900 bg-white shadow-2xs">

                                <!-- Quick Clear & Caret Icons -->
                                <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-1">
                                    <button type="button" 
                                            x-show="supplierSearchQuery"
                                            @click="clearSupplierSelection()" 
                                            class="text-slate-400 hover:text-slate-600 p-0.5 rounded"
                                            title="Reset Pencarian">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button type="button" 
                                            @click="supplierDropdownOpen = !supplierDropdownOpen"
                                            class="text-slate-400 hover:text-slate-600 p-0.5 rounded">
                                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': supplierDropdownOpen }"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Floating Filtered List -->
                            <div x-show="supplierDropdownOpen"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute z-50 left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-white rounded-xl border border-slate-200 shadow-xl divide-y divide-slate-100"
                                 style="display: none;">
                                
                                <!-- Opsi: Kosongkan Pilihan Pemasok -->
                                <template x-if="selectedSupplierId || supplierName">
                                    <button type="button" 
                                            @click="clearSupplierSelection()"
                                            class="w-full text-left px-3 py-2 hover:bg-rose-50 text-rose-600 text-xs font-semibold flex items-center gap-1.5 transition-colors border-b border-slate-100">
                                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                        <span>Kosongkan Nama Pemasok</span>
                                    </button>
                                </template>

                                <!-- Option List: Filtered Master Suppliers -->
                                <template x-for="sup in filteredSuppliers()" :key="sup.id">
                                    <button type="button" 
                                            @click="selectSupplier(sup)"
                                            class="w-full text-left px-3 py-2 hover:bg-slate-50 transition-colors"
                                            :class="{ 'bg-emerald-50/70': selectedSupplierId == sup.id }">
                                        <div class="flex items-center justify-between">
                                            <div class="text-xs font-bold text-slate-900" x-text="sup.name"></div>
                                            <div class="flex items-center gap-1">
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold"
                                                      :class="{
                                                          'bg-purple-100 text-purple-900': sup.tier === 'Platinum',
                                                          'bg-amber-100 text-amber-900': sup.tier === 'Gold',
                                                          'bg-slate-100 text-slate-800': sup.tier === 'Silver',
                                                          'bg-orange-100 text-orange-900': sup.tier === 'Bronze' || !sup.tier
                                                      }"
                                                      x-text="(sup.tier || 'BRONZE') + ' ⭐' + (sup.rating_stars || 1)">
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-[10px] text-slate-500 flex items-center justify-between mt-0.5 font-mono">
                                            <span x-text="(sup.member_number || '') + (sup.phone ? ' • ' + sup.phone : '')"></span>
                                            <template x-if="parseFloat(sup.total_debt || sup.remaining_debt) > 0">
                                                <span class="text-rose-600 font-bold" x-text="'⚠️ Kasbon: ' + formatRp(sup.total_debt || sup.remaining_debt)"></span>
                                            </template>
                                        </div>
                                    </button>
                                </template>

                                <!-- Empty / No Match State with Quick Add Button -->
                                <template x-if="filteredSuppliers().length === 0">
                                    <div class="p-3 text-center space-y-2">
                                        <p class="text-xs text-slate-500">Tidak ada pemasok yang cocok dengan "<span class="font-bold text-slate-800" x-text="supplierSearchQuery"></span>"</p>
                                        <button type="button" 
                                                @click="isManualSupplier = true; supplierName = supplierSearchQuery; supplierDropdownOpen = false;"
                                                class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs inline-flex items-center gap-1">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                            <span x-text="'+ Tambah &quot;' + supplierSearchQuery + '&quot; sebagai Pemasok Baru'"></span>
                                        </button>
                                    </div>
                                </template>

                            </div>
                        </div>

                        <!-- Mode 2: Input Manual Pemasok Baru (sama seperti di Pengeluaran) -->
                        <div x-show="isManualSupplier" style="display: none;" class="space-y-1">
                            <input type="text" name="supplier_name" x-model="supplierName" 
                                   placeholder="Ketik nama pemasok baru..."
                                   :required="isManualSupplier"
                                   class="w-full py-2 px-3 text-xs rounded-lg border border-emerald-500 focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-900 bg-emerald-50/30">
                            <p class="text-[10px] text-emerald-600 font-medium">✨ Pemasok baru otomatis tersimpan ke database saat transaksi disimpan.</p>
                            <input type="hidden" name="supplier_id" value="" :disabled="!isManualSupplier">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Nomor HP (WhatsApp)</label>
                            <input type="text" name="supplier_phone" x-model="supplierPhone" placeholder="08xxx"
                                   class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Alamat (Opsional)</label>
                            <input type="text" name="supplier_address" x-model="supplierAddress" placeholder="Kelurahan/Kec."
                                   class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <!-- KARTU MEMBER & RATING PEMASOK WIDGET -->
                <div x-show="activeSupplier()" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="rounded-xl overflow-hidden border shadow-sm relative text-white"
                     :class="{
                         'bg-gradient-to-br from-purple-950 via-slate-900 to-indigo-950 border-purple-500/40': activeSupplier()?.tier === 'Platinum',
                         'bg-gradient-to-br from-amber-950 via-slate-900 to-yellow-950 border-amber-500/40': activeSupplier()?.tier === 'Gold',
                         'bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border-slate-500/40': activeSupplier()?.tier === 'Silver',
                         'bg-gradient-to-br from-stone-900 via-stone-800 to-amber-950 border-amber-700/40': activeSupplier()?.tier === 'Bronze' || !activeSupplier()?.tier
                     }">
                    
                    <!-- Top Bar: Member Status & Star Rating -->
                    <div class="p-3.5 pb-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider"
                                      :class="{
                                          'bg-purple-400 text-purple-950': activeSupplier()?.tier === 'Platinum',
                                          'bg-amber-400 text-amber-950 font-bold': activeSupplier()?.tier === 'Gold',
                                          'bg-slate-300 text-slate-900': activeSupplier()?.tier === 'Silver',
                                          'bg-amber-700 text-amber-100': activeSupplier()?.tier === 'Bronze' || !activeSupplier()?.tier
                                      }">
                                    <span x-text="activeSupplier()?.tier === 'Platinum' ? '💎 PLATINUM' : (activeSupplier()?.tier === 'Gold' ? '🥇 GOLD' : (activeSupplier()?.tier === 'Silver' ? '🥈 SILVER' : '🥉 BRONZE'))"></span>
                                </span>
                                <span class="text-xs font-mono font-bold text-slate-200" x-text="activeSupplier()?.member_number"></span>
                            </div>
                            <!-- Star Rating (1 to 5 Stars) -->
                            <div class="flex items-center gap-0.5">
                                <template x-for="i in 5" :key="i">
                                    <span class="text-xs" :class="i <= (activeSupplier()?.rating_stars || 1) ? 'text-amber-400' : 'text-slate-600'">★</span>
                                </template>
                                <span class="ml-1 text-[11px] font-bold text-amber-300 font-mono" x-text="(activeSupplier()?.rating_stars || 1) + '.0'"></span>
                            </div>
                        </div>

                        <div class="mt-2">
                            <div class="text-sm font-bold text-white tracking-wide" x-text="activeSupplier()?.name"></div>
                            <div class="text-[11px] text-slate-300 flex items-center gap-2 mt-0.5 font-mono">
                                <span x-text="activeSupplier()?.phone || 'Tanpa No. HP'"></span>
                                <span>•</span>
                                <span class="text-emerald-400 font-semibold" x-text="(activeSupplier()?.points || 0) + ' Poin Reward'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Penjualan Pemasok (Akumulasi Tonase, Omzet, Transaksi) -->
                    <div class="px-3.5 py-2 bg-black/30 border-y border-white/10">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1 flex items-center justify-between">
                            <span>Akumulasi Penjualan Pemasok</span>
                            <span class="text-emerald-400 font-semibold" x-text="(activeSupplier()?.total_transactions_count || activeSupplier()?.total_transactions || 0) + 'x Kirim'"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-center">
                            <div class="p-1.5 rounded bg-white/5 border border-white/5">
                                <div class="text-[9px] uppercase tracking-wider text-slate-400">Total Berat Setor</div>
                                <div class="text-xs font-black font-mono text-emerald-400" x-text="formatNum(activeSupplier()?.total_weight || 0) + ' kg'"></div>
                            </div>
                            <div class="p-1.5 rounded bg-white/5 border border-white/5">
                                <div class="text-[9px] uppercase tracking-wider text-slate-400">Total Omzet Depo</div>
                                <div class="text-xs font-black font-mono text-amber-300" x-text="formatRp(activeSupplier()?.total_amount || 0)"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Hak Reward dari Owner -->
                    <div class="p-3.5 pt-2 space-y-2.5">
                        <div class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs">
                            <div class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-emerald-300">
                                <span>🎁</span>
                                <span>Hak Reward / Fasilitas dari Owner:</span>
                            </div>
                            <div class="text-[11px] text-emerald-100 font-medium mt-1 leading-snug" x-text="activeSupplier()?.eligible_reward"></div>
                        </div>

                        <!-- Action Links: View Card & WhatsApp -->
                        <div class="grid grid-cols-2 gap-2">
                            <a :href="'/suppliers/' + activeSupplier()?.id + '/member-card'" target="_blank"
                               class="inline-flex items-center justify-center gap-1.5 py-1.5 px-2 bg-white/10 hover:bg-white/20 text-white rounded-lg text-[11px] font-semibold border border-white/15 transition-colors text-center">
                                <i data-lucide="credit-card" class="w-3.5 h-3.5 text-emerald-400"></i>
                                <span>Cetak Kartu</span>
                            </a>
                            <a :href="'/suppliers/' + activeSupplier()?.id + '/member-card-wa'"
                               class="inline-flex items-center justify-center gap-1.5 py-1.5 px-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[11px] font-semibold shadow-sm transition-colors text-center">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>Kirim WA</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Payment & Checkout Card (Dropdown + Tambah Baru sama seperti Kategori Pengeluaran) -->
                <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Pembayaran & Selesai</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">Lunas Langsung</span>
                    </div>

                    <!-- Payment Method (Dropdown + Manual Input Baru) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Metode Pembayaran *</label>
                            <button type="button" @click="togglePaymentMethodMode()" 
                                    class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline">
                                <span x-text="isManualPaymentMethod ? '← Pilih dari Daftar Metode' : '+ Tambah Metode Baru'"></span>
                            </button>
                        </div>

                        <!-- Mode 1: Searchable Dropdown Metode Pembayaran Tersimpan -->
                        <div x-show="!isManualPaymentMethod" class="relative" @click.outside="closePaymentMethodDropdown()">
                            <input type="hidden" name="payment_method_id" :value="paymentMethodId" :disabled="isManualPaymentMethod">

                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </span>

                                <input type="text"
                                       x-ref="paymentMethodInput"
                                       x-model="paymentMethodSearchQuery"
                                       @focus="paymentMethodDropdownOpen = true; $event.target.select()"
                                       @click="paymentMethodDropdownOpen = true"
                                       @input="paymentMethodDropdownOpen = true"
                                       placeholder="Cari metode pembayaran (Tunai, BCA, BRI, Mandiri...)"
                                       class="w-full pl-8 pr-14 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-900 bg-white shadow-2xs">

                                <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-1">
                                    <button type="button" 
                                            x-show="paymentMethodSearchQuery"
                                            @click="paymentMethodSearchQuery = ''; paymentMethodDropdownOpen = true; $refs.paymentMethodInput?.focus()" 
                                            class="text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer"
                                            title="Reset Pencarian">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                                    <button type="button" 
                                            @click="paymentMethodDropdownOpen = !paymentMethodDropdownOpen"
                                            class="text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer"
                                            title="Buka / Tutup Daftar Metode">
                                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': paymentMethodDropdownOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Floating Filtered List -->
                            <div x-show="paymentMethodDropdownOpen"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute z-50 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white rounded-xl border border-slate-200 shadow-xl divide-y divide-slate-100"
                                 style="display: none;">
                                
                                <template x-for="pm in filteredPaymentMethods()" :key="pm.id">
                                    <button type="button" 
                                            @click="selectPaymentMethod(pm)"
                                            class="w-full text-left px-3 py-2.5 hover:bg-emerald-50 flex items-center justify-between transition-colors cursor-pointer group"
                                            :class="{ 'bg-emerald-50/80 font-bold text-emerald-950': paymentMethodId == pm.id }">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-slate-900 group-hover:text-emerald-900" x-text="pm.name"></span>
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase border"
                                                  :class="getMethodBadgeClass(pm)"
                                                  x-text="getMethodBadge(pm)"></span>
                                        </div>
                                        <template x-if="paymentMethodId == pm.id">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </template>
                                    </button>
                                </template>

                                <template x-if="filteredPaymentMethods().length === 0">
                                    <div class="p-3 text-center space-y-2">
                                        <p class="text-xs text-slate-500">Tidak ada metode pembayaran "<span class="font-bold text-slate-800" x-text="paymentMethodSearchQuery"></span>"</p>
                                        <button type="button" 
                                                @click="isManualPaymentMethod = true; newPaymentMethodName = paymentMethodSearchQuery; paymentMethodDropdownOpen = false;"
                                                class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs inline-flex items-center gap-1 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                            </svg>
                                            <span x-text="'+ Tambah &quot;' + paymentMethodSearchQuery + '&quot; sebagai Metode Baru'"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Mode 2: Input Manual Metode Pembayaran Baru -->
                        <div x-show="isManualPaymentMethod" style="display: none;" class="space-y-1">
                            <input type="text" name="new_payment_method_name" x-model="newPaymentMethodName"
                                   placeholder="Ketik metode pembayaran baru (contoh: QRIS BCA, Transfer Mandiri, Dana)..."
                                   :required="isManualPaymentMethod"
                                   class="w-full py-2 px-3 text-xs rounded-lg border border-emerald-500 focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-900 bg-emerald-50/30">
                            <p class="text-[10px] text-emerald-600 font-medium">✨ Metode pembayaran baru otomatis tersimpan ke master database saat transaksi disimpan.</p>
                            <input type="hidden" name="payment_method_id" value="__new__" :disabled="!isManualPaymentMethod">
                        </div>

                        <!-- Bank Transfer Input (Task 11 & Update: Nama Pemilik Rekening & No Rekening) -->
                        <div x-show="isTransferPayment()" class="mt-2.5 p-2.5 bg-blue-50 border border-blue-200 rounded-lg space-y-2">
                            <div class="text-[11px] font-bold text-blue-900 flex items-center justify-between">
                                <div class="flex items-center gap-1">
                                    <i data-lucide="credit-card" class="w-3.5 h-3.5 text-blue-600"></i>
                                    <span>Detail Rekening Bank (Dicetak di Nota):</span>
                                </div>
                                <span class="text-[9px] text-blue-700 font-medium">Bisa transfer ke pemasok / kasir</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-blue-900 mb-0.5">Nama Bank</label>
                                    <input type="text" name="custom_bank" x-model="customBank" placeholder="BCA / BRI / Mandiri"
                                           class="w-full py-1.5 px-2 text-xs rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-500 bg-white font-medium text-slate-800">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-blue-900 mb-0.5">Nomor Rekening</label>
                                    <input type="text" name="custom_account_no" x-model="customAccountNo" placeholder="No. Rekening"
                                           class="w-full py-1.5 px-2 text-xs rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-500 bg-white font-mono text-slate-800">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-blue-900 mb-0.5">Nama Pemilik (A/N)</label>
                                    <input type="text" name="custom_account_name" x-model="customAccountName" placeholder="Nama Pemilik Rekening"
                                           class="w-full py-1.5 px-2 text-xs rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-500 bg-white font-medium text-slate-800">
                                </div>
                            </div>
                            <!-- Opsi Simpan ke Data Pemasok & User -->
                            <div class="pt-1 border-t border-blue-200 flex flex-wrap items-center gap-3 text-[10.5px]">
                                <label class="inline-flex items-center gap-1.5 text-blue-900 font-medium cursor-pointer">
                                    <input type="checkbox" name="save_to_supplier_bank" value="1" checked class="rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                                    <span>Simpan / update rekening ke data pemasok</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 text-blue-900 font-medium cursor-pointer">
                                    <input type="checkbox" name="save_to_user_bank" value="1" class="rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                                    <span>Simpan ke profil user kasir saya</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Calculation Summary -->
                    <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200 space-y-2 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal Barang:</span>
                            <span class="font-mono font-semibold text-slate-800" x-text="formatRp(subtotalAmount())">Rp 0</span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>Potongan Khusus:</span>
                            <div class="w-32">
                                <input type="number" step="100" min="0" name="discount_amount" x-model.number="discountAmount"
                                       placeholder="Rp 0"
                                       class="w-full py-1 px-2 text-xs text-right rounded border border-slate-300 focus:ring-emerald-500 font-mono">
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>Pembulatan:</span>
                            <div class="w-32">
                                <input type="number" step="100" name="rounding_amount" x-model.number="roundingAmount"
                                       placeholder="Rp 0"
                                       class="w-full py-1 px-2 text-xs text-right rounded border border-slate-300 focus:ring-emerald-500 font-mono">
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-xs font-bold text-slate-800">
                            <span>Subtotal Timbangan:</span>
                            <span class="font-mono text-sm font-bold text-slate-900" x-text="formatRp(finalAmount())">Rp 0</span>
                        </div>

                        <!-- Debt Deduction Box (Appears when supplier has debt) -->
                        <div x-show="supplierDebt > 0" class="p-3 bg-amber-50 rounded-lg border border-amber-200 space-y-2 mt-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-amber-900 flex items-center gap-1">
                                    <i data-lucide="handshake" class="w-3.5 h-3.5 text-amber-600"></i>
                                    Saldo Hutang / Bon:
                                </span>
                                <span class="font-mono font-bold text-amber-700" x-text="formatRp(supplierDebt)">Rp 0</span>
                            </div>

                            <div class="flex items-center justify-between gap-2 text-xs">
                                <label class="text-amber-800 font-medium">Potong Hutang:</label>
                                <div class="flex items-center gap-1.5 w-36">
                                    <input type="number" step="500" min="0" :max="Math.min(supplierDebt, finalAmount())" 
                                           name="debt_deduction_amount" x-model.number="debtDeductionAmount"
                                           placeholder="0"
                                           class="w-full py-1 px-2 text-xs text-right rounded border border-amber-300 focus:ring-amber-500 font-mono font-bold bg-white text-amber-900">
                                    <button type="button" @click="autoDeductMaxDebt()" title="Potong Maksimal"
                                            class="px-2 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-[10px] font-bold shrink-0">
                                        Max
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-between text-[11px] text-amber-700 pt-1 border-t border-amber-200/60">
                                <span>Sisa Hutang Nanti:</span>
                                <span class="font-mono font-semibold" x-text="formatRp(remainingDebtAfter())">Rp 0</span>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-sm font-bold text-slate-900">
                            <div>
                                <span class="block text-slate-900">UANG TUNAI DITERIMA:</span>
                                <span class="text-[10px] font-normal text-slate-500" x-show="debtDeductionAmount > 0">(Setelah potong hutang)</span>
                            </div>
                            <span class="font-mono text-xl font-black text-emerald-600" x-text="formatRp(netCashPaid())">Rp 0</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2 pt-2">
                        <button type="submit" 
                                :disabled="isSubmitting || rows.length === 0 || subtotalAmount() <= 0"
                                class="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-base shadow-lg shadow-emerald-700/20 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="printer" class="w-5 h-5"></i>
                            <span x-text="isSubmitting ? 'Menyimpan & Mencetak...' : 'SIMPAN & CETAK NOTA'"></span>
                        </button>

                        <button type="button" @click="resetForm()" 
                                class="w-full py-2 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                            Batal / Bersihkan Form Timbangan
                        </button>
                    </div>

                    <!-- Shortcut Help -->
                    <div class="text-[11px] text-slate-400 text-center leading-relaxed">
                        Shortcut: Tekan <strong>[F2]</strong> Tambah Baris, <strong>[Ctrl+Enter]</strong> Simpan & Cetak
                    </div>
                </div>

            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function cashierPos() {
    return {
        isOwner: {{ (auth()->user()->isOwner() || auth()->user()->isSuperAdmin()) ? 'true' : 'false' }},
        isSuperAdmin: {{ (auth()->user()->isOwner() || auth()->user()->isSuperAdmin()) ? 'true' : 'false' }},
        userRole: '{{ auth()->user()->role }}',
        masterItems: @json($scrapItems),
        suppliersList: @json($suppliers),
        paymentMethodsList: @json($paymentMethods),
        categories: (@json($categories ?? []).length > 0)
            ? @json($categories ?? [])
            : [...new Map((@json($scrapItems) || []).map(i => [i.category?.id || i.category_id, { id: i.category?.id || i.category_id, name: i.category?.name || 'Kategori ' + (i.category_id || '') }])).values()].filter(c => c.id),
        selectedCategory: 'all',
        itemSearch: '',
        previewInvoice: 'PPJ-{{ \App\Services\FormatHelper::toRomanMonth(date("n")) }}{{ date("y") }}-XXXX',
        
        transactionDate: '',
        paymentMethodId: '{{ $paymentMethods->first()?->id ?? 1 }}',
        paymentMethodSearchQuery: '{{ $paymentMethods->first()?->name ?? 'Tunai' }}',
        paymentMethodDropdownOpen: false,
        isManualPaymentMethod: false,
        newPaymentMethodName: '',
        customBank: '{{ addslashes(auth()->user()->bank_name ?? '') }}',
        customAccountName: '{{ addslashes(auth()->user()->bank_account_name ?? '') }}',
        customAccountNo: '{{ addslashes(auth()->user()->bank_account_number ?? '') }}',
        currentUserBankName: '{{ addslashes(auth()->user()->bank_name ?? '') }}',
        currentUserBankHolder: '{{ addslashes(auth()->user()->bank_account_name ?? '') }}',
        currentUserBankNo: '{{ addslashes(auth()->user()->bank_account_number ?? '') }}',

        selectedSupplierId: '',
        isManualSupplier: false,
        supplierName: '',
        supplierSearchQuery: '',
        supplierDropdownOpen: false,
        supplierPhone: '',
        supplierAddress: '',
        supplierDebt: 0,
        debtDeductionAmount: 0,

        discountAmount: 0,
        roundingAmount: 0,
        isSubmitting: false,

        rows: [
            { uid: 1, scrap_item_id: '', is_new: false, new_item_name: '', category_id: '', is_new_category: false, new_category_name: '', search_text: '', dropdown_open: false, unit_type: 'kg', item_type_name: '', quantity: 1, gross_weight: '', tare_weight: '', tare_percent: '', tare_mode: 'kg', price_per_kg: 0 }
        ],

        init() {
            // Keyboard shortcut listener
            window.addEventListener('keydown', (e) => {
                if (e.key === 'F2') {
                    e.preventDefault();
                    this.addNewEmptyRow();
                } else if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('purchaseForm').requestSubmit();
                }
            });

            if (window.refreshIcons) window.refreshIcons();
        },

        filteredItems() {
            let list = this.masterItems;
            if (this.selectedCategory !== 'all') {
                list = list.filter(item => item.category_id == this.selectedCategory);
            }
            if (this.itemSearch.trim() !== '') {
                const s = this.itemSearch.toLowerCase();
                list = list.filter(item => item.name.toLowerCase().includes(s) || item.code.toLowerCase().includes(s));
            }
            return list.slice(0, 36);
        },

        filteredPaymentMethods() {
            if (!this.paymentMethodSearchQuery || this.paymentMethodSearchQuery.trim() === '') {
                return this.paymentMethodsList;
            }
            // If query matches the currently active/selected payment method name, show ALL existing options
            const currentSelected = this.paymentMethodsList.find(pm => pm.id == this.paymentMethodId);
            if (currentSelected && this.paymentMethodSearchQuery.trim().toLowerCase() === (currentSelected.name || '').trim().toLowerCase()) {
                return this.paymentMethodsList;
            }
            const q = this.paymentMethodSearchQuery.toLowerCase().trim();
            return this.paymentMethodsList.filter(pm => {
                const name = (pm.name || '').toLowerCase();
                const code = (pm.code || '').toLowerCase();
                const bank = (pm.bank_name || '').toLowerCase();
                return name.includes(q) || code.includes(q) || bank.includes(q);
            });
        },

        selectPaymentMethod(pm) {
            this.paymentMethodId = pm.id;
            this.paymentMethodSearchQuery = pm.name;
            this.paymentMethodDropdownOpen = false;
            if (pm.account_number) {
                this.customAccountNo = pm.account_number;
            } else if (!this.customAccountNo && this.currentUserBankNo) {
                this.customAccountNo = this.currentUserBankNo;
            }
            if (pm.account_holder) {
                this.customAccountName = pm.account_holder;
            } else if (!this.customAccountName && this.currentUserBankHolder) {
                this.customAccountName = this.currentUserBankHolder;
            }
            this.refreshIcons();
        },

        closePaymentMethodDropdown() {
            this.paymentMethodDropdownOpen = false;
            if (!this.paymentMethodSearchQuery || this.paymentMethodSearchQuery.trim() === '') {
                const current = this.paymentMethodsList.find(m => m.id == this.paymentMethodId);
                if (current) {
                    this.paymentMethodSearchQuery = current.name;
                }
            }
        },

        getMethodBadge(pm) {
            if (!pm || !pm.name) return 'Tunai';
            const name = pm.name.toLowerCase();
            const code = (pm.code || '').toLowerCase();
            if (name.includes('transfer') || code.includes('transfer') || name.includes('bank')) return 'Transfer';
            if (name.includes('qris') || code.includes('qris')) return 'QRIS';
            return 'Tunai';
        },

        getMethodBadgeClass(pm) {
            const b = this.getMethodBadge(pm);
            if (b === 'Transfer') return 'bg-blue-100 text-blue-800 border-blue-200';
            if (b === 'QRIS') return 'bg-purple-100 text-purple-800 border-purple-200';
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        },

        isTransferMethod(pm) {
            if (!pm || !pm.name) return false;
            const name = pm.name.toLowerCase();
            const code = (pm.code || '').toLowerCase();
            return name.includes('transfer') || name.includes('bank') || code.includes('transfer');
        },

        isTransferPayment() {
            if (this.isManualPaymentMethod) {
                const name = (this.newPaymentMethodName || '').toLowerCase();
                return name.includes('transfer') || name.includes('bank');
            }
            const method = this.paymentMethodsList.find(m => m.id == this.paymentMethodId);
            if (!method) {
                const query = (this.paymentMethodSearchQuery || '').toLowerCase();
                return query.includes('transfer') || query.includes('bank');
            }
            const name = (method.name || '').toLowerCase();
            const code = (method.code || '').toLowerCase();
            return name.includes('transfer') || name.includes('bank') || code.includes('transfer');
        },

        togglePaymentMethodMode() {
            this.isManualPaymentMethod = !this.isManualPaymentMethod;
            if (this.isManualPaymentMethod) {
                this.paymentMethodId = '__new__';
                this.newPaymentMethodName = (this.paymentMethodSearchQuery && !this.paymentMethodsList.find(m => m.name === this.paymentMethodSearchQuery)) ? this.paymentMethodSearchQuery : '';
            } else {
                const first = this.paymentMethodsList[0];
                this.paymentMethodId = first?.id || 1;
                this.paymentMethodSearchQuery = first?.name || 'Tunai';
                this.newPaymentMethodName = '';
            }
            this.paymentMethodDropdownOpen = false;
        },

        filteredSuppliers() {
            if (!this.supplierSearchQuery || this.supplierSearchQuery.trim() === '') {
                return this.suppliersList;
            }
            const q = this.supplierSearchQuery.toLowerCase().trim();
            return this.suppliersList.filter(s => {
                const name = (s.name || '').toLowerCase();
                const memberNum = (s.member_number || '').toLowerCase();
                const phone = (s.phone || '').toLowerCase();
                const code = (s.code || '').toLowerCase();
                return name.includes(q) || memberNum.includes(q) || phone.includes(q) || code.includes(q);
            });
        },

        selectSupplier(sup) {
            if (!sup) {
                this.selectedSupplierId = '';
                this.supplierName = '';
                this.supplierSearchQuery = '';
                this.supplierPhone = '';
                this.supplierAddress = '';
                this.supplierDebt = 0;
                this.debtDeductionAmount = 0;
            } else {
                this.selectedSupplierId = sup.id;
                this.supplierName = sup.name;
                this.supplierSearchQuery = sup.name;
                this.supplierPhone = sup.phone || '';
                this.supplierAddress = sup.address || '';
                this.supplierDebt = parseFloat(sup.total_debt || sup.remaining_debt) || 0;
                this.debtDeductionAmount = 0;
                if (sup.bank_name || sup.bank_account_number || sup.bank_account_name) {
                    if (sup.bank_name) this.customBank = sup.bank_name;
                    if (sup.bank_account_number) this.customAccountNo = sup.bank_account_number;
                    if (sup.bank_account_name) this.customAccountName = sup.bank_account_name;
                }
            }
            this.supplierDropdownOpen = false;
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },

        clearSupplierSelection() {
            this.selectedSupplierId = '';
            this.supplierName = '';
            this.supplierSearchQuery = '';
            this.supplierPhone = '';
            this.supplierAddress = '';
            this.supplierDebt = 0;
            this.debtDeductionAmount = 0;
            this.supplierDropdownOpen = false;
        },

        toggleSupplierMode() {
            this.isManualSupplier = !this.isManualSupplier;
            if (this.isManualSupplier) {
                this.selectedSupplierId = '__new__';
                this.supplierName = this.supplierSearchQuery ? this.supplierSearchQuery : '';
                this.supplierPhone = '';
                this.supplierAddress = '';
                this.supplierDebt = 0;
                this.debtDeductionAmount = 0;
            } else {
                this.selectedSupplierId = '';
                this.supplierName = '';
                this.supplierSearchQuery = '';
                this.supplierPhone = '';
                this.supplierAddress = '';
                this.supplierDebt = 0;
                this.debtDeductionAmount = 0;
            }
            this.supplierDropdownOpen = false;
        },

        filteredMasterItems(searchText) {
            if (!searchText || searchText.trim() === '') {
                return this.masterItems;
            }
            const q = searchText.toLowerCase().trim();
            return this.masterItems.filter(item => {
                const name = (item.name || '').toLowerCase();
                const code = (item.code || '').toLowerCase();
                const cat = (item.category?.name || '').toLowerCase();
                return name.includes(q) || code.includes(q) || cat.includes(q);
            });
        },

        isExactMatchMaster(text) {
            if (!text) return false;
            const q = text.toLowerCase().trim();
            return this.masterItems.some(i => i.name.toLowerCase() === q);
        },

        refreshIcons() {
            this.$nextTick(() => {
                if (typeof window.refreshIcons === 'function') {
                    window.refreshIcons();
                } else if (window.createIcons && window.lucideIcons) {
                    window.createIcons({ icons: window.lucideIcons });
                }
            });
        },

        toggleRowItemMode(row) {
            row.is_new = !row.is_new;
            if (row.is_new) {
                row.scrap_item_id = '__new__';
                row.new_item_name = row.search_text ? row.search_text.split(' (')[0].trim() : '';
                row.is_new_category = false;
                row.new_category_name = '';
                if (!row.category_id && this.selectedCategory !== 'all') {
                    row.category_id = this.selectedCategory;
                }
                row.dropdown_open = false;
            } else {
                row.scrap_item_id = '';
                row.new_item_name = '';
                row.search_text = '';
                row.category_id = '';
                row.is_new_category = false;
                row.new_category_name = '';
                row.dropdown_open = true;
            }
            this.refreshIcons();
        },

        addNewItemToRow(row, customName) {
            row.is_new = true;
            row.scrap_item_id = '__new__';
            row.new_item_name = (customName || '').trim();
            row.search_text = row.new_item_name;
            row.is_new_category = false;
            row.new_category_name = '';
            if (!row.category_id && this.selectedCategory !== 'all') {
                row.category_id = this.selectedCategory;
            }
            row.dropdown_open = false;
            this.refreshIcons();
        },

        addNewItemRow() {
            const lastRow = this.rows[this.rows.length - 1];
            if (lastRow && !lastRow.scrap_item_id && !lastRow.search_text && !lastRow.gross_weight && (!lastRow.quantity || lastRow.quantity == 1)) {
                this.addNewItemToRow(lastRow, '');
            } else {
                this.rows.push({
                    uid: Date.now() + Math.random(),
                    scrap_item_id: '__new__',
                    is_new: true,
                    new_item_name: '',
                    category_id: this.selectedCategory !== 'all' ? this.selectedCategory : '',
                    is_new_category: false,
                    new_category_name: '',
                    search_text: '',
                    dropdown_open: false,
                    unit_type: 'kg',
                    item_type_name: '',
                    quantity: 1,
                    gross_weight: '',
                    tare_weight: '',
                    tare_percent: '',
                    tare_mode: 'kg',
                    price_per_kg: 0
                });
            }
            this.refreshIcons();
        },

        selectRowItem(row, item, index) {
            row.is_new = false;
            row.new_item_name = '';
            row.category_id = item.category_id || '';
            row.is_new_category = false;
            row.new_category_name = '';
            row.scrap_item_id = item.id;
            row.search_text = item.name + ' (' + item.code + ')';
            row.price_per_kg = parseFloat(item.buy_price_per_kg) || 0;
            if (item.unit_type && item.unit_type !== 'kg') {
                row.unit_type = item.unit_type;
            } else if (!row.unit_type) {
                row.unit_type = 'kg';
            }
            if (row.tare_mode !== 'percent') {
                row.tare_mode = row.unit_type || 'kg';
            }
            row.dropdown_open = false;
            this.refreshIcons();
        },

        clearRowItem(row, index) {
            row.is_new = false;
            row.new_item_name = '';
            row.category_id = '';
            row.is_new_category = false;
            row.new_category_name = '';
            row.scrap_item_id = '';
            row.search_text = '';
            row.price_per_kg = 0;
            row.dropdown_open = true;
        },

        addNewEmptyRow() {
            this.rows.push({
                uid: Date.now() + Math.random(),
                scrap_item_id: '',
                is_new: false,
                new_item_name: '',
                category_id: '',
                is_new_category: false,
                new_category_name: '',
                search_text: '',
                dropdown_open: false,
                unit_type: 'kg',
                item_type_name: '',
                quantity: 1,
                gross_weight: '',
                tare_weight: '',
                tare_percent: '',
                tare_mode: 'kg',
                price_per_kg: 0
            });
            this.refreshIcons();
        },

        addItem(item) {
            const itemUnit = item.unit_type || 'kg';
            const itemLabel = item.name + ' (' + item.code + ')';
            const lastRow = this.rows[this.rows.length - 1];
            if (lastRow && !lastRow.scrap_item_id && !lastRow.is_new && !lastRow.gross_weight && (!lastRow.quantity || lastRow.quantity == 1)) {
                lastRow.scrap_item_id = item.id;
                lastRow.is_new = false;
                lastRow.new_item_name = '';
                lastRow.category_id = item.category_id || '';
                lastRow.is_new_category = false;
                lastRow.new_category_name = '';
                lastRow.search_text = itemLabel;
                lastRow.unit_type = itemUnit;
                lastRow.tare_mode = itemUnit;
                lastRow.price_per_kg = parseFloat(item.buy_price_per_kg) || 0;
            } else {
                this.rows.push({
                    uid: Date.now() + Math.random(),
                    scrap_item_id: item.id,
                    is_new: false,
                    new_item_name: '',
                    category_id: item.category_id || '',
                    is_new_category: false,
                    new_category_name: '',
                    search_text: itemLabel,
                    dropdown_open: false,
                    unit_type: itemUnit,
                    item_type_name: '',
                    quantity: 1,
                    gross_weight: '',
                    tare_weight: '',
                    tare_percent: '',
                    tare_mode: itemUnit,
                    price_per_kg: parseFloat(item.buy_price_per_kg) || 0
                });
            }
            this.refreshIcons();
        },

        onItemChanged(index) {
            const row = this.rows[index];
            const found = this.masterItems.find(i => i.id == row.scrap_item_id);
            if (found) {
                row.search_text = found.name + ' (' + found.code + ')';
                row.price_per_kg = parseFloat(found.buy_price_per_kg) || 0;
                if (found.unit_type && found.unit_type !== 'kg') {
                    row.unit_type = found.unit_type;
                } else if (!row.unit_type) {
                    row.unit_type = 'kg';
                }
                if (row.tare_mode !== 'percent') {
                    row.tare_mode = row.unit_type || 'kg';
                }
            } else {
                row.price_per_kg = 0;
            }
        },

        removeRow(index) {
            if (this.rows.length > 1) {
                this.rows.splice(index, 1);
                this.refreshIcons();
            }
        },

        onSupplierInput() {
            const rawVal = (this.supplierName || '').trim();
            const inputVal = rawVal.toLowerCase();
            if (inputVal === '') {
                this.selectedSupplierId = '';
                this.supplierDebt = 0;
                this.debtDeductionAmount = 0;
                return;
            }

            const match = this.suppliersList.find(s => {
                if (s.name && s.name.toLowerCase() === inputVal) return true;
                if (s.member_number && s.member_number.toLowerCase() === inputVal) return true;
                if (s.code && s.code.toLowerCase() === inputVal) return true;
                if (s.name && rawVal.startsWith(s.name + ' [')) return true;
                return false;
            });

            if (match) {
                this.selectedSupplierId = match.id;
                this.supplierName = match.name;
                this.supplierPhone = match.phone || this.supplierPhone;
                this.supplierAddress = match.address || this.supplierAddress;
                this.supplierDebt = parseFloat(match.total_debt || match.remaining_debt) || 0;
                this.debtDeductionAmount = 0;
            } else {
                // New supplier entered manually
                this.selectedSupplierId = '';
                this.supplierDebt = 0;
                this.debtDeductionAmount = 0;
            }
        },

        activeSupplier() {
            if (this.isManualSupplier || !this.selectedSupplierId || this.selectedSupplierId === '__cash__' || this.selectedSupplierId === '__new__') {
                return null;
            }
            return this.suppliersList.find(s => s.id == this.selectedSupplierId) || null;
        },

        getItemName(scrapItemId) {
            if (!scrapItemId) return '';
            const found = this.masterItems.find(i => i.id == scrapItemId);
            return found ? (found.name + ' (' + found.code + ')') : '';
        },

        autoDeductMaxDebt() {
            const maxPossible = Math.min(this.supplierDebt, this.finalAmount());
            this.debtDeductionAmount = maxPossible;
        },

        netCashPaid() {
            const final = this.finalAmount();
            const debt = Math.min(final, Math.max(0, parseFloat(this.debtDeductionAmount) || 0));
            return Math.max(0, final - debt);
        },

        remainingDebtAfter() {
            const debt = Math.min(this.finalAmount(), Math.max(0, parseFloat(this.debtDeductionAmount) || 0));
            return Math.max(0, this.supplierDebt - debt);
        },

        isPcsUnit(row) {
            if (!row || !row.unit_type) return false;
            const u = String(row.unit_type).toLowerCase().trim();
            return u === 'pcs' || u === 'satuan' || u === 'unit';
        },

        onUnitTypeChanged(row) {
            if (this.isPcsUnit(row)) {
                if (!row.quantity || parseFloat(row.quantity) < 1) {
                    row.quantity = 1;
                }
            }
            if (row.tare_mode !== 'percent') {
                row.tare_mode = row.unit_type || 'kg';
            }
        },

        rowGrossAmount(row) {
            if (!row) return 0;
            if (this.isPcsUnit(row)) {
                return parseFloat(row.quantity) || 0;
            }
            return parseFloat(row.gross_weight) || 0;
        },

        setTareMode(row, mode) {
            const gross = this.rowGrossAmount(row);
            if (mode === 'percent') {
                row.tare_mode = 'percent';
                const tareNominal = parseFloat(row.tare_weight) || 0;
                if (gross > 0 && tareNominal > 0 && (!row.tare_percent || row.tare_percent == 0)) {
                    row.tare_percent = Math.round((tareNominal / gross) * 1000) / 10;
                }
            } else {
                row.tare_mode = row.unit_type || 'kg';
                const pct = parseFloat(row.tare_percent) || 0;
                if (gross > 0 && pct > 0 && (!row.tare_weight || row.tare_weight == 0)) {
                    row.tare_weight = Math.round(((gross * pct) / 100) * 100) / 100;
                }
            }
        },

        rowTareWeight(row) {
            if (!row) return 0;
            const gross = this.rowGrossAmount(row);
            if (row.tare_mode === 'percent') {
                const pct = parseFloat(row.tare_percent) || 0;
                if (pct <= 0 || gross <= 0) return 0;
                return Math.round(((gross * pct) / 100) * 100) / 100;
            }
            return Math.max(0, parseFloat(row.tare_weight) || 0);
        },

        calcTarePercent(row) {
            const gross = this.rowGrossAmount(row);
            if (gross <= 0) return 0;
            const tare = parseFloat(row.tare_weight) || 0;
            if (tare <= 0) return 0;
            const pct = (tare / gross) * 100;
            return Math.round(pct * 10) / 10;
        },

        onTarePercentChanged(row) {
            if (parseFloat(row.tare_percent) > 100) row.tare_percent = 100;
            if (parseFloat(row.tare_percent) < 0) row.tare_percent = 0;
        },

        onTareWeightChanged(row) {
            if (parseFloat(row.tare_weight) < 0) row.tare_weight = 0;
        },

        onGrossOrQtyChanged(row) {
            // Percent tare dynamically updates when gross changes
        },

        rowNetWeight(row) {
            const gross = this.rowGrossAmount(row);
            const tare = this.rowTareWeight(row);
            return Math.max(0, gross - tare);
        },

        rowSubtotal(row) {
            const net = this.rowNetWeight(row);
            const price = parseFloat(row.price_per_kg) || 0;
            return Math.round(net * price);
        },

        totalGrossWeight() {
            return this.rows.reduce((acc, row) => acc + (!this.isPcsUnit(row) ? (parseFloat(row.gross_weight) || 0) : 0), 0);
        },

        totalTareWeight() {
            return this.rows.reduce((acc, row) => acc + (!this.isPcsUnit(row) ? this.rowTareWeight(row) : 0), 0);
        },

        totalNetWeight() {
            return this.rows.reduce((acc, row) => acc + (!this.isPcsUnit(row) ? this.rowNetWeight(row) : 0), 0);
        },

        totalPcsQty() {
            return this.rows.reduce((acc, row) => acc + (this.isPcsUnit(row) ? this.rowNetWeight(row) : 0), 0);
        },

        subtotalAmount() {
            return this.rows.reduce((acc, row) => acc + this.rowSubtotal(row), 0);
        },

        finalAmount() {
            const sub = this.subtotalAmount();
            const disc = parseFloat(this.discountAmount) || 0;
            const round = parseFloat(this.roundingAmount) || 0;
            return Math.max(0, sub - disc + round);
        },

        getMasterPrice(row) {
            if (!row || !row.scrap_item_id || row.scrap_item_id === '__new__' || row.is_new) return 0;
            const found = this.masterItems.find(i => i.id == row.scrap_item_id);
            return found ? parseFloat(found.buy_price_per_kg) || 0 : 0;
        },

        validatePrice(row) {
            if (this.isOwner) return;
            if (row.is_new || row.scrap_item_id === '__new__') return;
            const max = this.getMasterPrice(row);
            if (max > 0 && parseFloat(row.price_per_kg) > max) {
                const roleName = this.userRole === 'admin' ? 'Admin' : 'Kasir';
                alert(roleName + ' tidak boleh memasukkan harga beli melebihi harga master (' + this.formatRp(max) + ')! Harga otomatis disesuaikan ke batas maksimum. Hanya Owner yang dapat memasukkan harga di atas harga master.');
                row.price_per_kg = max;
            }
        },

        getRowBaseName(row) {
            if (row.is_new) return row.new_item_name || row.search_text || 'Barang Baru';
            const found = this.masterItems.find(i => i.id == row.scrap_item_id);
            return found ? found.name : (row.search_text ? row.search_text.split(' (')[0] : '');
        },

        getItemRows(row) {
            const id = row.is_new ? ('new_' + (row.new_item_name || row.search_text)) : row.scrap_item_id;
            if (!id || id === '__new__') return [row];
            return this.rows.filter(r => {
                const rId = r.is_new ? ('new_' + (r.new_item_name || r.search_text)) : r.scrap_item_id;
                return rId === id;
            });
        },

        hasMultiplePrices(row) {
            const matching = this.getItemRows(row);
            return matching.length > 1;
        },

        getItemAccumulatedWeight(row) {
            const matching = this.getItemRows(row);
            return matching.reduce((acc, r) => acc + this.rowNetWeight(r), 0);
        },

        getItemAccumulatedSubtotal(row) {
            const matching = this.getItemRows(row);
            return matching.reduce((acc, r) => acc + this.rowSubtotal(r), 0);
        },

        multiPriceCommodities() {
            const map = {};
            for (const r of this.rows) {
                const name = this.getRowBaseName(r);
                if (!name) continue;
                if (!map[name]) {
                    map[name] = { name: name, rows: [], unit: r.unit_type || 'kg' };
                }
                map[name].rows.push(r);
            }
            const res = [];
            for (const k in map) {
                if (map[k].rows.length > 1) {
                    const totalWeight = map[k].rows.reduce((acc, r) => acc + this.rowNetWeight(r), 0);
                    const totalSubtotal = map[k].rows.reduce((acc, r) => acc + this.rowSubtotal(r), 0);
                    res.push({
                        name: map[k].name,
                        totalWeight: totalWeight,
                        totalSubtotal: totalSubtotal,
                        unit: map[k].unit
                    });
                }
            }
            return res;
        },

        consolidateRowsByPrice() {
            const map = {};
            const consolidated = [];
            for (const row of this.rows) {
                const id = row.is_new ? ('new_' + (row.new_item_name || row.search_text)) : (row.scrap_item_id || 'empty');
                const price = parseFloat(row.price_per_kg) || 0;
                const unit = row.unit_type || 'kg';
                const typeName = (row.item_type_name || '').trim();
                const key = id + '_' + price + '_' + unit + '_' + typeName;

                if (id !== 'empty' && map[key] !== undefined) {
                    // Harga sama: otomatis akumulasi berat / kuantitas ke baris tersebut
                    const existing = consolidated[map[key]];
                    if (this.isPcsUnit(row)) {
                        existing.quantity = (parseFloat(existing.quantity) || 1) + (parseFloat(row.quantity) || 1);
                    } else {
                        existing.gross_weight = (parseFloat(existing.gross_weight) || 0) + (parseFloat(row.gross_weight) || 0);
                        existing.tare_weight = (parseFloat(existing.tare_weight) || 0) + (parseFloat(row.tare_weight) || 0);
                    }
                } else {
                    map[key] = consolidated.length;
                    consolidated.push(row);
                }
            }
            if (consolidated.length < this.rows.length) {
                this.rows = consolidated;
            }
        },

        handleSubmit(e) {
            this.consolidateRowsByPrice();

            if (this.subtotalAmount() <= 0) {
                e.preventDefault();
                alert('Silakan masukkan timbangan atau jumlah barang yang valid (subtotal > Rp 0).');
                this.isSubmitting = false;
                return;
            }

            for (let i = 0; i < this.rows.length; i++) {
                const row = this.rows[i];

                // Auto-detect typed new item if not in master list
                if (!row.scrap_item_id && !row.is_new) {
                    const query = (row.search_text || '').trim();
                    if (query) {
                        const match = this.masterItems.find(item => item.name.toLowerCase() === query.toLowerCase());
                        if (match) {
                            row.scrap_item_id = match.id;
                            row.price_per_kg = row.price_per_kg || parseFloat(match.buy_price_per_kg) || 0;
                        } else {
                            row.is_new = true;
                            row.scrap_item_id = '__new__';
                            row.new_item_name = query;
                        }
                    } else {
                        e.preventDefault();
                        alert('Silakan pilih material scrap atau ketik nama barang baru untuk baris ke-' + (i + 1) + '.');
                        this.isSubmitting = false;
                        return;
                    }
                } else if (row.is_new) {
                    const name = (row.new_item_name || row.search_text || '').trim();
                    if (!name) {
                        e.preventDefault();
                        alert('Silakan ketik nama barang baru untuk baris ke-' + (i + 1) + '.');
                        this.isSubmitting = false;
                        return;
                    }
                    row.new_item_name = name;
                    row.scrap_item_id = '__new__';
                }

                if (this.isPcsUnit(row)) {
                    const qty = parseFloat(row.quantity) || 0;
                    if (qty <= 0) {
                        e.preventDefault();
                        alert('Jumlah barang (pcs/satuan) baris ke-' + (i + 1) + ' harus lebih dari 0.');
                        this.isSubmitting = false;
                        return;
                    }
                } else {
                    const net = this.rowNetWeight(row);
                    if (net <= 0) {
                        e.preventDefault();
                        alert('Berat bersih timbangan baris ke-' + (i + 1) + ' harus lebih dari 0 kg.');
                        this.isSubmitting = false;
                        return;
                    }
                }

                const currentPrice = parseFloat(row.price_per_kg) || 0;
                if (currentPrice <= 0) {
                    e.preventDefault();
                    alert('Harga beli per satuan pada baris ke-' + (i + 1) + ' harus lebih dari Rp 0.');
                    this.isSubmitting = false;
                    return;
                }

                if (!this.isOwner && !row.is_new && row.scrap_item_id !== '__new__') {
                    const max = this.getMasterPrice(row);
                    if (max > 0 && currentPrice > max) {
                        e.preventDefault();
                        const found = this.masterItems.find(it => it.id == row.scrap_item_id);
                        const itemName = found ? found.name : 'Baris ' + (i + 1);
                        const roleName = this.userRole === 'admin' ? 'Admin' : 'Kasir';
                        alert(roleName + ' tidak diizinkan memasukkan harga beli untuk "' + itemName + '" (Rp ' + currentPrice.toLocaleString('id-ID') + ') melebihi harga master (' + this.formatRp(max) + ').\nSilakan input harga sama atau di bawahnya. Hanya Owner yang dapat memasukkan harga di atas harga master.');
                        row.price_per_kg = max;
                        this.isSubmitting = false;
                        return;
                    }
                }
            }

            this.isSubmitting = true;
        },

        resetForm() {
            if (confirm('Yakin ingin mengosongkan seluruh tabel timbangan kasir?')) {
                this.rows = [
                    { uid: Date.now(), scrap_item_id: '', is_new: false, new_item_name: '', category_id: '', is_new_category: false, new_category_name: '', search_text: '', dropdown_open: false, unit_type: 'kg', item_type_name: '', quantity: 1, gross_weight: '', tare_weight: '', tare_percent: '', tare_mode: 'kg', price_per_kg: 0 }
                ];
                this.selectedSupplierId = '';
                this.supplierName = '';
                this.supplierSearchQuery = '';
                this.supplierDropdownOpen = false;
                this.paymentMethodId = this.paymentMethodsList[0]?.id || 1;
                this.paymentMethodSearchQuery = this.paymentMethodsList[0]?.name || 'Tunai';
                this.paymentMethodDropdownOpen = false;
                this.supplierPhone = '';
                this.supplierAddress = '';
                this.supplierDebt = 0;
                this.debtDeductionAmount = 0;
                this.discountAmount = 0;
                this.roundingAmount = 0;
                this.customAccountName = '';
                this.customAccountNo = '';
                this.refreshIcons();
            }
        },

        formatRp(val) {
            return 'Rp ' + Math.round(val || 0).toLocaleString('id-ID');
        },

        formatNum(val) {
            const num = parseFloat(val) || 0;
            if (Math.floor(num) === num) {
                return num.toLocaleString('id-ID');
            }
            return num.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
        },

        formatInteger(val) {
            return Math.round(val || 0).toLocaleString('id-ID');
        }
    };
}
</script>
@endpush

