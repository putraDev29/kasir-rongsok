@extends('layouts.app', ['header' => 'Kasir Penerimaan Barang Rongsok'])

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
                            <span class="text-xs font-bold uppercase text-slate-800 tracking-wider">Pilih Cepat Barang Rongsok</span>
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
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                        <button type="button" @click="selectedCategory = 'all'"
                                :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2.5 py-1 rounded-md shrink-0 transition-colors">
                            Semua
                        </button>
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button" @click="selectedCategory = cat.id"
                                    :class="selectedCategory == cat.id ? 'bg-emerald-600 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    class="px-2.5 py-1 rounded-md shrink-0 transition-colors"
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
                        <button type="button" @click="addNewEmptyRow()" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Tambah Baris (+F2)</span>
                        </button>
                    </div>

                    <!-- Scrollable Items Container (Scroll saat barang melebihi layar) -->
                    <div class="p-3 space-y-2.5 max-h-[460px] overflow-y-auto bg-slate-100/60" style="scrollbar-width: thin;">
                        <template x-for="(row, index) in rows" :key="row.uid">
                            <div class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-emerald-400 transition-all p-3 space-y-2">
                                <!-- BARIS 1: NAMA BARANG & JENIS / VARIAN -->
                                <div class="flex items-center gap-2">
                                    <!-- Nomor Urut -->
                                    <span class="w-6 h-6 rounded-md bg-slate-800 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0 shadow-xs" x-text="index + 1">1</span>

                                    <!-- Pilihan Barang Rongsok (Searchable Combobox) -->
                                    <div class="flex-1 min-w-0 relative" @click.outside="row.dropdown_open = false">
                                        <input type="hidden" :name="'items[' + index + '][scrap_item_id]'" :value="row.scrap_item_id" required>

                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                            </span>

                                            <input type="text"
                                                   x-model="row.search_text"
                                                   @focus="row.dropdown_open = true"
                                                   @input="row.dropdown_open = true"
                                                   placeholder="Cari / pilih barang rongsok..."
                                                   required
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

                                            <template x-if="filteredMasterItems(row.search_text).length === 0">
                                                <div class="p-3 text-center text-xs text-slate-400">
                                                    Tidak ada barang dengan kata kunci "<span class="font-bold text-slate-700" x-text="row.search_text"></span>"
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Jenis / Varian Manual -->
                                    <div class="w-40 sm:w-56 shrink-0">
                                        <input type="text" 
                                               :name="'items[' + index + '][item_type_name]'" 
                                               x-model="row.item_type_name" 
                                               placeholder="Jenis/Varian (Bening, Super...)"
                                               class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800 bg-white">
                                    </div>

                                    <!-- Tombol Hapus -->
                                    <button type="button" @click="removeRow(index)" 
                                            :disabled="rows.length <= 1"
                                            class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-20 shrink-0"
                                            title="Hapus baris item">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>

                                <!-- BARIS 2: VALUE ANGKA & SATUAN (TETAP 1 BARIS PENUH) -->
                                <div class="grid grid-cols-6 gap-2 pt-1.5 border-t border-slate-100 items-end"
                                     style="display: grid; grid-template-columns: minmax(75px, 0.85fr) minmax(80px, 1fr) minmax(75px, 0.9fr) minmax(85px, 1fr) minmax(95px, 1.1fr) minmax(110px, 1.3fr); gap: 0.5rem; align-items: end;">
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
                                               x-text="isPcsUnit(row) ? 'Qty (Pcs) *' : 'Kotor (kg) *'"></label>
                                        <template x-if="isPcsUnit(row)">
                                            <div>
                                                <input type="number" step="1" min="1" 
                                                       :name="'items[' + index + '][quantity]'" 
                                                       x-model.number="row.quantity" 
                                                       placeholder="1"
                                                       required
                                                       class="w-full py-1.5 px-1.5 text-xs text-right rounded-lg border border-amber-300 bg-amber-50/60 font-mono font-bold text-amber-950">
                                                <input type="hidden" :name="'items[' + index + '][gross_weight]'" value="0">
                                            </div>
                                        </template>
                                        <template x-if="!isPcsUnit(row)">
                                            <div>
                                                <input type="number" step="0.01" min="0.01" 
                                                       :name="'items[' + index + '][gross_weight]'" 
                                                       x-model.number="row.gross_weight" 
                                                       placeholder="0.00"
                                                       required
                                                       class="w-full py-1.5 px-1.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono font-bold text-slate-900 bg-white">
                                                <input type="hidden" :name="'items[' + index + '][quantity]'" value="1">
                                            </div>
                                        </template>
                                    </div>

                                    <!-- 3. Pot./Tara (kg) -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5 whitespace-nowrap">Tara (kg)</label>
                                        <template x-if="isPcsUnit(row)">
                                            <div>
                                                <div class="py-1.5 px-1.5 text-xs text-center text-slate-300 font-mono bg-slate-100 rounded-lg border border-slate-200 font-bold">-</div>
                                                <input type="hidden" :name="'items[' + index + '][tare_weight]'" value="0">
                                            </div>
                                        </template>
                                        <template x-if="!isPcsUnit(row)">
                                            <input type="number" step="0.01" min="0" 
                                                   :name="'items[' + index + '][tare_weight]'" 
                                                   x-model.number="row.tare_weight" 
                                                   placeholder="0.00"
                                                   class="w-full py-1.5 px-1.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono text-slate-700 bg-white">
                                        </template>
                                    </div>

                                    <!-- 4. Hasil Bersih -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-emerald-800 uppercase tracking-wider mb-0.5 whitespace-nowrap">Bersih</label>
                                        <div class="py-1.5 px-1.5 text-xs text-right font-mono font-black rounded-lg bg-emerald-100/90 text-emerald-950 border border-emerald-300 truncate whitespace-nowrap">
                                            <template x-if="isPcsUnit(row)">
                                                <span x-text="formatInteger(row.quantity || 1) + ' ' + (row.unit_type || 'pcs')"></span>
                                            </template>
                                            <template x-if="!isPcsUnit(row)">
                                                <span x-text="formatNum(rowNetWeight(row)) + ' kg'"></span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- 5. Harga Beli (Rp) -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5 whitespace-nowrap">Harga Beli *</label>
                                        <input type="number" step="100" min="0" 
                                               :max="getMasterPrice(row) > 0 ? getMasterPrice(row) : null"
                                               :name="'items[' + index + '][price_per_kg]'" 
                                               x-model.number="row.price_per_kg" 
                                               @input="validatePrice(row)"
                                               @change="validatePrice(row)"
                                               required
                                               class="w-full py-1.5 px-1.5 text-xs text-right rounded-lg border font-mono font-bold transition-colors"
                                               :class="getMasterPrice(row) > 0 && row.price_per_kg > getMasterPrice(row) 
                                                   ? 'border-red-500 bg-red-50 text-red-700' 
                                                   : 'border-slate-300 focus:ring-2 focus:ring-emerald-500 text-slate-900 bg-white'">
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
                    <div class="p-3 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2">
                        <button type="button" @click="addNewEmptyRow()" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition-all">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>Tambah Item Barang Lain (+F2)</span>
                        </button>

                        <div class="text-xs text-slate-600 flex items-center gap-3 bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                            <span>Item: <strong class="text-slate-900 font-mono" x-text="rows.length"></strong></span>
                            <template x-if="totalNetWeight() > 0">
                                <span>Timbangan: <strong class="text-emerald-700 font-mono font-bold" x-text="formatNum(totalNetWeight()) + ' kg'"></strong></span>
                            </template>
                            <template x-if="totalNetWeight() <= 0 && totalPcsQty() > 0">
                                <span>Satuan: <strong class="text-amber-700 font-mono font-bold" x-text="formatInteger(totalPcsQty()) + ' pcs'"></strong></span>
                            </template>
                            <span>Subtotal: <strong class="text-slate-900 font-mono font-black" x-text="formatRp(subtotalAmount())"></strong></span>
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
                                            x-show="supplierSearchQuery && supplierSearchQuery !== 'Umum / Tunai'"
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
                                
                                <!-- Option 1: Pemasok Harian / Umum -->
                                <button type="button" 
                                        @click="selectSupplier(null)"
                                        class="w-full text-left px-3 py-2 hover:bg-emerald-50 flex items-center justify-between transition-colors"
                                        :class="{ 'bg-emerald-50/70 font-bold': !selectedSupplierId }">
                                    <div>
                                        <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                            <span>Umum / Tunai</span>
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-slate-100 text-slate-600">Pemasok Harian</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500">Transaksi reguler non-member</div>
                                    </div>
                                    <template x-if="!selectedSupplierId">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    </template>
                                </button>

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
                        <div x-show="!isManualPaymentMethod" class="relative" @click.outside="paymentMethodDropdownOpen = false">
                            <input type="hidden" name="payment_method_id" :value="paymentMethodId" :disabled="isManualPaymentMethod">

                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                </span>

                                <input type="text"
                                       x-model="paymentMethodSearchQuery"
                                       @focus="paymentMethodDropdownOpen = true"
                                       @input="paymentMethodDropdownOpen = true"
                                       placeholder="Cari metode pembayaran (Tunai, BCA, BRI, Mandiri...)"
                                       class="w-full pl-8 pr-14 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-900 bg-white shadow-2xs">

                                <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-1">
                                    <button type="button" 
                                            x-show="paymentMethodSearchQuery"
                                            @click="paymentMethodSearchQuery = ''; paymentMethodDropdownOpen = true" 
                                            class="text-slate-400 hover:text-slate-600 p-0.5 rounded"
                                            title="Reset Pencarian">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button type="button" 
                                            @click="paymentMethodDropdownOpen = !paymentMethodDropdownOpen"
                                            class="text-slate-400 hover:text-slate-600 p-0.5 rounded">
                                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': paymentMethodDropdownOpen }"></i>
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
                                            class="w-full text-left px-3 py-2 hover:bg-emerald-50 flex items-center justify-between transition-colors"
                                            :class="{ 'bg-emerald-50/70 font-bold': paymentMethodId == pm.id }">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-slate-900" x-text="pm.name"></span>
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase"
                                                  :class="isTransferMethod(pm) ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800'"
                                                  x-text="isTransferMethod(pm) ? 'Transfer' : 'Tunai'"></span>
                                        </div>
                                        <template x-if="paymentMethodId == pm.id">
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        </template>
                                    </button>
                                </template>

                                <template x-if="filteredPaymentMethods().length === 0">
                                    <div class="p-3 text-center space-y-2">
                                        <p class="text-xs text-slate-500">Tidak ada metode pembayaran "<span class="font-bold text-slate-800" x-text="paymentMethodSearchQuery"></span>"</p>
                                        <button type="button" 
                                                @click="isManualPaymentMethod = true; newPaymentMethodName = paymentMethodSearchQuery; paymentMethodDropdownOpen = false;"
                                                class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs inline-flex items-center gap-1">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
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
                            <div class="text-[11px] font-bold text-blue-900 flex items-center gap-1">
                                <i data-lucide="credit-card" class="w-3.5 h-3.5 text-blue-600"></i>
                                <span>Detail Rekening Pembayaran (Dicetak di Nota):</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-blue-900 mb-0.5">Nama Pemilik (A/N)</label>
                                    <input type="text" name="custom_account_name" x-model="customAccountName" placeholder="Nama Pemilik Rekening"
                                           class="w-full py-1.5 px-2 text-xs rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-500 bg-white font-medium text-slate-800">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-blue-900 mb-0.5">Nomor Rekening</label>
                                    <input type="text" name="custom_account_no" x-model="customAccountNo" placeholder="No. Rekening"
                                           class="w-full py-1.5 px-2 text-xs rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-500 bg-white font-mono text-slate-800">
                                </div>
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
        masterItems: @json($scrapItems),
        suppliersList: @json($suppliers),
        paymentMethodsList: @json($paymentMethods),
        categories: [
            { id: 1, name: 'Besi & Logam' },
            { id: 2, name: 'Logam Non-Besi' },
            { id: 3, name: 'Kertas & Kardus' },
            { id: 4, name: 'Plastik & Botol' },
            { id: 5, name: 'Aki Bekas' },
        ],
        selectedCategory: 'all',
        itemSearch: '',
        previewInvoice: 'PPJ-{{ \App\Services\FormatHelper::toRomanMonth(date("n")) }}{{ date("y") }}-XXXX',
        
        transactionDate: '',
        paymentMethodId: '{{ $paymentMethods->first()?->id ?? 1 }}',
        paymentMethodSearchQuery: '{{ $paymentMethods->first()?->name ?? 'Tunai' }}',
        paymentMethodDropdownOpen: false,
        isManualPaymentMethod: false,
        newPaymentMethodName: '',
        customAccountName: '',
        customAccountNo: '',

        selectedSupplierId: '',
        isManualSupplier: false,
        supplierName: 'Umum / Tunai',
        supplierSearchQuery: 'Umum / Tunai',
        supplierDropdownOpen: false,
        supplierPhone: '',
        supplierAddress: '',
        supplierDebt: 0,
        debtDeductionAmount: 0,

        discountAmount: 0,
        roundingAmount: 0,
        isSubmitting: false,

        rows: [
            { uid: 1, scrap_item_id: '', search_text: '', dropdown_open: false, unit_type: 'kg', item_type_name: '', quantity: 1, gross_weight: '', tare_weight: '', price_per_kg: 0 }
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
            return list.slice(0, 12);
        },

        filteredPaymentMethods() {
            if (!this.paymentMethodSearchQuery || this.paymentMethodSearchQuery.trim() === '') {
                return this.paymentMethodsList;
            }
            const q = this.paymentMethodSearchQuery.toLowerCase().trim();
            return this.paymentMethodsList.filter(pm => {
                const name = (pm.name || '').toLowerCase();
                const type = (pm.type || '').toLowerCase();
                return name.includes(q) || type.includes(q);
            });
        },

        selectPaymentMethod(pm) {
            this.paymentMethodId = pm.id;
            this.paymentMethodSearchQuery = pm.name;
            this.paymentMethodDropdownOpen = false;
        },

        isTransferMethod(pm) {
            if (!pm || !pm.name) return false;
            const name = pm.name.toLowerCase();
            return name.includes('transfer') || name.includes('bank');
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
            return name.includes('transfer') || name.includes('bank');
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
            if (!this.supplierSearchQuery || this.supplierSearchQuery.trim() === '' || this.supplierSearchQuery === 'Umum / Tunai') {
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
                this.supplierName = 'Umum / Tunai';
                this.supplierSearchQuery = 'Umum / Tunai';
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
            this.supplierName = 'Umum / Tunai';
            this.supplierSearchQuery = '';
            this.supplierPhone = '';
            this.supplierAddress = '';
            this.supplierDebt = 0;
            this.debtDeductionAmount = 0;
            this.supplierDropdownOpen = true;
        },

        toggleSupplierMode() {
            this.isManualSupplier = !this.isManualSupplier;
            if (this.isManualSupplier) {
                this.selectedSupplierId = '__new__';
                this.supplierName = (this.supplierSearchQuery && this.supplierSearchQuery !== 'Umum / Tunai') ? this.supplierSearchQuery : '';
                this.supplierPhone = '';
                this.supplierAddress = '';
                this.supplierDebt = 0;
                this.debtDeductionAmount = 0;
            } else {
                this.selectedSupplierId = '';
                this.supplierName = 'Umum / Tunai';
                this.supplierSearchQuery = 'Umum / Tunai';
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

        selectRowItem(row, item, index) {
            row.scrap_item_id = item.id;
            row.search_text = item.name + ' (' + item.code + ')';
            row.price_per_kg = parseFloat(item.buy_price_per_kg) || 0;
            if (item.unit_type && item.unit_type !== 'kg') {
                row.unit_type = item.unit_type;
            } else if (!row.unit_type) {
                row.unit_type = 'kg';
            }
            row.dropdown_open = false;
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },

        clearRowItem(row, index) {
            row.scrap_item_id = '';
            row.search_text = '';
            row.price_per_kg = 0;
            row.dropdown_open = true;
        },

        addNewEmptyRow() {
            this.rows.push({
                uid: Date.now() + Math.random(),
                scrap_item_id: '',
                search_text: '',
                dropdown_open: false,
                unit_type: 'kg',
                item_type_name: '',
                quantity: 1,
                gross_weight: '',
                tare_weight: '',
                price_per_kg: 0
            });
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },

        addItem(item) {
            const itemUnit = item.unit_type || 'kg';
            const itemLabel = item.name + ' (' + item.code + ')';
            const lastRow = this.rows[this.rows.length - 1];
            if (lastRow && !lastRow.scrap_item_id && !lastRow.gross_weight && (!lastRow.quantity || lastRow.quantity == 1)) {
                lastRow.scrap_item_id = item.id;
                lastRow.search_text = itemLabel;
                lastRow.unit_type = itemUnit;
                lastRow.price_per_kg = parseFloat(item.buy_price_per_kg) || 0;
            } else {
                this.rows.push({
                    uid: Date.now() + Math.random(),
                    scrap_item_id: item.id,
                    search_text: itemLabel,
                    dropdown_open: false,
                    unit_type: itemUnit,
                    item_type_name: '',
                    quantity: 1,
                    gross_weight: '',
                    tare_weight: '',
                    price_per_kg: parseFloat(item.buy_price_per_kg) || 0
                });
            }
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
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
            } else {
                row.price_per_kg = 0;
            }
        },

        removeRow(index) {
            if (this.rows.length > 1) {
                this.rows.splice(index, 1);
            }
        },

        onSupplierInput() {
            const rawVal = (this.supplierName || '').trim();
            const inputVal = rawVal.toLowerCase();
            if (inputVal === '' || inputVal === 'umum' || inputVal === 'umum / tunai') {
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
        },

        rowNetWeight(row) {
            if (this.isPcsUnit(row)) return 0;
            const gross = parseFloat(row.gross_weight) || 0;
            const tare = parseFloat(row.tare_weight) || 0;
            return Math.max(0, gross - tare);
        },

        rowSubtotal(row) {
            if (this.isPcsUnit(row)) {
                const qty = Math.max(1, parseFloat(row.quantity) || 1);
                const price = parseFloat(row.price_per_kg) || 0;
                return Math.round(qty * price);
            }
            const net = this.rowNetWeight(row);
            const price = parseFloat(row.price_per_kg) || 0;
            return Math.round(net * price);
        },

        totalNetWeight() {
            return this.rows.reduce((acc, row) => acc + this.rowNetWeight(row), 0);
        },

        totalPcsQty() {
            return this.rows.reduce((acc, row) => acc + (this.isPcsUnit(row) ? (parseFloat(row.quantity) || 1) : 0), 0);
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
            if (!row || !row.scrap_item_id) return 0;
            const found = this.masterItems.find(i => i.id == row.scrap_item_id);
            return found ? parseFloat(found.buy_price_per_kg) || 0 : 0;
        },

        validatePrice(row) {
            const max = this.getMasterPrice(row);
            if (max > 0 && parseFloat(row.price_per_kg) > max) {
                alert('Harga beli tidak boleh melebihi harga master (' + this.formatRp(max) + ')! Harga otomatis disesuaikan ke batas maksimum.');
                row.price_per_kg = max;
            }
        },

        handleSubmit(e) {
            if (this.subtotalAmount() <= 0) {
                e.preventDefault();
                alert('Silakan masukkan timbangan atau jumlah barang yang valid (subtotal > Rp 0).');
                this.isSubmitting = false;
                return;
            }

            for (let i = 0; i < this.rows.length; i++) {
                const row = this.rows[i];
                if (!row.scrap_item_id) {
                    e.preventDefault();
                    alert('Silakan pilih barang rongsok untuk baris ke-' + (i + 1) + '.');
                    this.isSubmitting = false;
                    return;
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

                const max = this.getMasterPrice(row);
                const currentPrice = parseFloat(row.price_per_kg) || 0;
                if (max > 0 && currentPrice > max) {
                    e.preventDefault();
                    const found = this.masterItems.find(it => it.id == row.scrap_item_id);
                    const itemName = found ? found.name : 'Baris ' + (i + 1);
                    alert('Harga beli untuk "' + itemName + '" (Rp ' + currentPrice.toLocaleString('id-ID') + ') tidak boleh melebihi harga master (' + this.formatRp(max) + ').\nSilakan input harga sama atau di bawahnya.');
                    row.price_per_kg = max;
                    this.isSubmitting = false;
                    return;
                }
            }

            this.isSubmitting = true;
        },

        resetForm() {
            if (confirm('Yakin ingin mengosongkan seluruh tabel timbangan kasir?')) {
                this.rows = [
                    { uid: Date.now(), scrap_item_id: '', search_text: '', dropdown_open: false, unit_type: 'kg', item_type_name: '', quantity: 1, gross_weight: '', tare_weight: '', price_per_kg: 0 }
                ];
                this.selectedSupplierId = '';
                this.supplierName = 'Umum / Tunai';
                this.supplierSearchQuery = 'Umum / Tunai';
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

