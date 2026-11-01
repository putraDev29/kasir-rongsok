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

                <!-- Active Weighing Table (Barang Ditimbang) -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-emerald-600 text-white text-xs font-bold flex items-center justify-center" x-text="rows.length">0</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Daftar Timbangan Masuk</span>
                        </div>
                        <button type="button" @click="addNewEmptyRow()" 
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 shadow-2xs">
                            <i data-lucide="plus" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Tambah Baris (+F2)</span>
                        </button>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100/80 text-slate-600 border-b border-slate-200 text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="py-2.5 px-3 w-10 text-center">#</th>
                                    <th class="py-2.5 px-3 min-w-[160px]">Jenis Barang Rongsok</th>
                                    <th class="py-2.5 px-3 w-28 text-right">Berat Kotor (kg)</th>
                                    <th class="py-2.5 px-3 w-24 text-right">Pot./Tara (kg)</th>
                                    <th class="py-2.5 px-3 w-28 text-right bg-emerald-50/60 text-emerald-950 font-bold">Berat Bersih (kg)</th>
                                    <th class="py-2.5 px-3 w-28 text-right">Harga Beli/kg</th>
                                    <th class="py-2.5 px-3 w-32 text-right font-bold">Subtotal</th>
                                    <th class="py-2.5 px-2 w-10 text-center">Hapus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(row, index) in rows" :key="row.uid">
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <!-- Row Index -->
                                        <td class="py-2 px-3 text-center text-slate-400 font-mono" x-text="index + 1"></td>

                                        <!-- Item Select -->
                                        <td class="py-2 px-3">
                                            <select :name="'items[' + index + '][scrap_item_id]'" 
                                                    x-model="row.scrap_item_id" 
                                                    @change="onItemChanged(index)"
                                                    required
                                                    class="w-full py-1.5 px-2 text-xs rounded border border-slate-300 focus:ring-1 focus:ring-emerald-500 font-medium">
                                                <option value="">-- Pilih Barang --</option>
                                                <template x-for="item in masterItems" :key="item.id">
                                                    <option :value="item.id" x-text="item.name + ' (' + item.code + ')'"></option>
                                                </template>
                                            </select>
                                        </td>

                                        <!-- Gross Weight -->
                                        <td class="py-2 px-3">
                                            <input type="number" step="0.01" min="0.01" 
                                                   :name="'items[' + index + '][gross_weight]'" 
                                                   x-model.number="row.gross_weight" 
                                                   placeholder="0.00"
                                                   required
                                                   class="w-full py-1.5 px-2 text-xs text-right rounded border border-slate-300 focus:ring-1 focus:ring-emerald-500 font-mono font-semibold">
                                        </td>

                                        <!-- Tare / Potongan Weight -->
                                        <td class="py-2 px-3">
                                            <input type="number" step="0.01" min="0" 
                                                   :name="'items[' + index + '][tare_weight]'" 
                                                   x-model.number="row.tare_weight" 
                                                   placeholder="0.00"
                                                   class="w-full py-1.5 px-2 text-xs text-right rounded border border-slate-300 focus:ring-1 focus:ring-emerald-500 font-mono text-slate-600">
                                        </td>

                                        <!-- Net Weight (Calculated) -->
                                        <td class="py-2 px-3 text-right font-mono font-bold bg-emerald-50/50 text-emerald-700">
                                            <span x-text="formatNum(rowNetWeight(row))">0.00</span>
                                        </td>

                                        <!-- Price per Kg -->
                                        <td class="py-2 px-3">
                                            <div class="space-y-0.5 min-w-[110px]">
                                                <input type="number" step="100" min="0" 
                                                       :max="getMasterPrice(row) > 0 ? getMasterPrice(row) : null"
                                                       :name="'items[' + index + '][price_per_kg]'" 
                                                       x-model.number="row.price_per_kg" 
                                                       @input="validatePrice(row)"
                                                       @change="validatePrice(row)"
                                                       required
                                                       class="w-full py-1.5 px-2 text-xs text-right rounded border font-mono transition-colors"
                                                       :class="getMasterPrice(row) > 0 && row.price_per_kg > getMasterPrice(row) 
                                                           ? 'border-red-500 bg-red-50 text-red-700 focus:ring-red-500' 
                                                           : 'border-slate-300 focus:ring-1 focus:ring-emerald-500'">
                                                
                                                <template x-if="getMasterPrice(row) > 0">
                                                    <div class="flex items-center justify-between text-[9px] px-0.5 leading-none"
                                                         :class="row.price_per_kg > getMasterPrice(row) ? 'text-red-600 font-bold' : 'text-slate-400'">
                                                        <span>Maks:</span>
                                                        <span class="font-mono font-medium" x-text="formatRp(getMasterPrice(row))"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>

                                        <!-- Subtotal (Calculated) -->
                                        <td class="py-2 px-3 text-right font-mono font-black text-slate-900">
                                            <span x-text="formatRp(rowSubtotal(row))">Rp 0</span>
                                        </td>

                                        <!-- Delete Row -->
                                        <td class="py-2 px-2 text-center">
                                            <button type="button" @click="removeRow(index)" 
                                                    :disabled="rows.length <= 1"
                                                    class="p-1 text-slate-400 hover:text-red-600 disabled:opacity-30 disabled:hover:text-slate-400 rounded">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Foot / Quick Add Row -->
                    <div class="p-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" @click="addNewEmptyRow()" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-600"></i>
                            <span>Tambah Item Barang Lain</span>
                        </button>

                        <div class="text-xs text-slate-600 flex items-center gap-4">
                            <span>Total Baris: <strong x-text="rows.length"></strong></span>
                            <span>Total Timbangan: <strong class="text-slate-900" x-text="formatNum(totalNetWeight()) + ' kg'"></strong></span>
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
                
                <!-- Supplier Info Card -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Informasi Pemasok / Penjual</span>
                        </div>
                    </div>

                    <!-- Supplier Selection Mode -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Pilih Pemasok Terdaftar</label>
                        <select name="supplier_id" x-model="selectedSupplierId" @change="onSupplierSelected()"
                                class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                            <option value="">-- Pemasok Umum / Tunai Langsung --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" 
                                        data-name="{{ $sup->name }}" 
                                        data-phone="{{ $sup->phone }}" 
                                        data-address="{{ $sup->address }}"
                                        data-debt="{{ (float) ($sup->total_debt ?? 0) }}">
                                    {{ $sup->name }} ({{ $sup->code }}) @if(($sup->total_debt ?? 0) > 0) [Bon: Rp {{ number_format($sup->total_debt, 0, ',', '.') }}] @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Direct Supplier Name -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nama Penjual / Pemasok</label>
                        <input type="text" name="supplier_name" x-model="supplierName" required
                               placeholder="Nama penjual (contoh: Pak Joko)"
                               class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Nomor HP (Opsional)</label>
                            <input type="text" name="supplier_phone" x-model="supplierPhone" placeholder="08..."
                                   class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Alamat (Opsional)</label>
                            <input type="text" name="supplier_address" x-model="supplierAddress" placeholder="Kelurahan/Kec."
                                   class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                        </div>
                    </div>

                    <!-- Auto save supplier checkbox -->
                    <div x-show="!selectedSupplierId && supplierName !== 'Umum / Tunai'" class="pt-1">
                        <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                            <input type="checkbox" name="save_as_supplier" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>Simpan otomatis ke Master Pemasok</span>
                        </label>
                    </div>
                </div>

                <!-- Payment & Checkout Card -->
                <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Pembayaran & Selesai</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">Lunas Langsung</span>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($paymentMethods as $pm)
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 hover:border-emerald-500 cursor-pointer transition-colors text-xs font-medium">
                                    <input type="radio" name="payment_method_id" value="{{ $pm->id }}" 
                                           {{ $loop->first ? 'checked' : '' }}
                                           class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="truncate">{{ $pm->name }}</span>
                                </label>
                            @endforeach
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
                                :disabled="isSubmitting || rows.length === 0 || totalNetWeight() <= 0"
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
        categories: [
            { id: 1, name: 'Besi & Logam' },
            { id: 2, name: 'Logam Non-Besi' },
            { id: 3, name: 'Kertas & Kardus' },
            { id: 4, name: 'Plastik & Botol' },
            { id: 5, name: 'Aki Bekas' },
        ],
        selectedCategory: 'all',
        itemSearch: '',
        previewInvoice: 'KB-{{ date("ymd") }}-XXXX',
        
        selectedSupplierId: '',
        supplierName: 'Umum / Tunai',
        supplierPhone: '',
        supplierAddress: '',
        supplierDebt: 0,
        debtDeductionAmount: 0,

        discountAmount: 0,
        roundingAmount: 0,
        isSubmitting: false,

        rows: [
            { uid: 1, scrap_item_id: '', gross_weight: '', tare_weight: '', price_per_kg: 0 }
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

        addNewEmptyRow() {
            this.rows.push({
                uid: Date.now() + Math.random(),
                scrap_item_id: '',
                gross_weight: '',
                tare_weight: '',
                price_per_kg: 0
            });
            if (window.refreshIcons) window.refreshIcons();
        },

        addItem(item) {
            // If the last row is empty, fill it; otherwise append new row
            const lastRow = this.rows[this.rows.length - 1];
            if (lastRow && !lastRow.scrap_item_id && !lastRow.gross_weight) {
                lastRow.scrap_item_id = item.id;
                lastRow.price_per_kg = parseFloat(item.buy_price_per_kg) || 0;
            } else {
                this.rows.push({
                    uid: Date.now() + Math.random(),
                    scrap_item_id: item.id,
                    gross_weight: '',
                    tare_weight: '',
                    price_per_kg: parseFloat(item.buy_price_per_kg) || 0
                });
            }
            if (window.refreshIcons) window.refreshIcons();
        },

        onItemChanged(index) {
            const row = this.rows[index];
            const found = this.masterItems.find(i => i.id == row.scrap_item_id);
            if (found) {
                row.price_per_kg = parseFloat(found.buy_price_per_kg) || 0;
            } else {
                row.price_per_kg = 0;
            }
        },

        removeRow(index) {
            if (this.rows.length > 1) {
                this.rows.splice(index, 1);
            }
        },

        onSupplierSelected() {
            const selectEl = document.querySelector('select[name="supplier_id"]');
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            if (this.selectedSupplierId) {
                this.supplierName = selectedOpt.getAttribute('data-name') || '';
                this.supplierPhone = selectedOpt.getAttribute('data-phone') || '';
                this.supplierAddress = selectedOpt.getAttribute('data-address') || '';
                this.supplierDebt = parseFloat(selectedOpt.getAttribute('data-debt')) || 0;
                this.debtDeductionAmount = 0;
            } else {
                this.supplierName = 'Umum / Tunai';
                this.supplierPhone = '';
                this.supplierAddress = '';
                this.supplierDebt = 0;
                this.debtDeductionAmount = 0;
            }
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

        rowNetWeight(row) {
            const gross = parseFloat(row.gross_weight) || 0;
            const tare = parseFloat(row.tare_weight) || 0;
            return Math.max(0, gross - tare);
        },

        rowSubtotal(row) {
            const net = this.rowNetWeight(row);
            const price = parseFloat(row.price_per_kg) || 0;
            return Math.round(net * price);
        },

        totalNetWeight() {
            return this.rows.reduce((acc, row) => acc + this.rowNetWeight(row), 0);
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
            if (this.totalNetWeight() <= 0) {
                e.preventDefault();
                alert('Silakan masukkan berat timbangan yang valid (berat bersih > 0).');
                return;
            }

            for (let i = 0; i < this.rows.length; i++) {
                const row = this.rows[i];
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
                    { uid: Date.now(), scrap_item_id: '', gross_weight: '', tare_weight: '', price_per_kg: 0 }
                ];
                this.selectedSupplierId = '';
                this.supplierName = 'Umum / Tunai';
                this.supplierPhone = '';
                this.supplierAddress = '';
                this.supplierDebt = 0;
                this.debtDeductionAmount = 0;
                this.discountAmount = 0;
                this.roundingAmount = 0;
            }
        },

        formatRp(val) {
            return window.formatRupiah ? window.formatRupiah(val) : 'Rp ' + (val || 0).toLocaleString('id-ID');
        },

        formatNum(val) {
            return window.formatNumber ? window.formatNumber(val, 2) : (val || 0).toFixed(2);
        }
    };
}
</script>
@endpush

