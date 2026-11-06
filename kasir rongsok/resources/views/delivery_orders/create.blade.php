@extends('layouts.app', ['header' => 'Buat Surat Jalan Pengiriman'])

@section('content')
<div x-data="deliveryOrderForm()" class="max-w-5xl mx-auto space-y-6">

    <!-- Top Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('delivery-orders.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Form Surat Jalan Pengiriman Rongsok</h2>
                <p class="text-xs text-slate-500">Dibuat otomatis 2 rangkap (Lembar 1: Gudang & Lembar 2: Supir) ukuran HVS Landscape ÷ 2.</p>
            </div>
        </div>
    </div>

    <!-- 2 Rangkap Notice -->
    <div class="p-3 bg-blue-50/90 rounded-xl border border-blue-200 text-xs flex items-center gap-2.5 text-blue-950">
        <i data-lucide="info" class="w-4 h-4 text-blue-600 shrink-0"></i>
        <span><strong>Ketentuan Dokumen:</strong> Surat jalan ini otomatis diterbitkan <strong>2 (dua) rangkap</strong>: <strong>Lembar 1</strong> dipegang oleh Gudang (arsip depo) dan <strong>Lembar 2</strong> dipegang oleh Supir (dibawa armada ke lokasi tujuan).</span>
    </div>

    @if($recentSales->count() > 0 && !$salesTx)
        <div class="p-3 bg-blue-50 rounded-xl border border-blue-200 text-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-blue-900">
                <i data-lucide="info" class="w-4 h-4 text-blue-600 shrink-0"></i>
                <span>Tersedia transaksi penjualan partai grosir terbaru yang belum dibuatkan surat jalan.</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="text-[11px] text-blue-700">Tarik dari penjualan:</span>
                <select onchange="if(this.value) window.location.href='{{ route('delivery-orders.create') }}?sales_transaction_id=' + this.value"
                        class="text-xs py-1 px-2 rounded border border-blue-300 bg-white font-medium">
                    <option value="">-- Pilih Transaksi Penjualan --</option>
                    @foreach($recentSales as $rs)
                        <option value="{{ $rs->id }}">{{ $rs->invoice_number }} - {{ $rs->buyer_name }} ({{ \App\Services\FormatHelper::weight($rs->total_weight) }} kg)</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <form action="{{ route('delivery-orders.store') }}" method="POST" class="space-y-6">
        @csrf
        @if($salesTx)
            <input type="hidden" name="sales_transaction_id" value="{{ $salesTx->id }}">
        @endif

        <!-- Card 1: Tujuan Pabrik & Armada Transportasi -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="truck" class="w-4 h-4 text-emerald-600"></i>
                <span>1. Data Pabrik Penerima & Armada Ekspedisi</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Recipient Info -->
                <div class="space-y-3 p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                    <span class="text-xs font-bold text-slate-700 uppercase">Pabrik / Mitra Penerima</span>
                    
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Perusahaan / Pabrik Tujuan <span class="text-red-500">*</span></label>
                        <input type="text" name="recipient_name" x-model="recipientName" required placeholder="Contoh: PT. Master Baja Peleburan / Depo Cilegon"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Alamat Tujuan Pengiriman</label>
                        <input type="text" name="recipient_address" placeholder="Kawasan Industri / Alamat Depo Pembongkaran"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nomor Telepon / Kontak Penerima</label>
                        <input type="text" name="recipient_phone" x-model="recipientPhone" placeholder="08..."
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>
                </div>

                <!-- Vehicle Info -->
                <div class="space-y-3 p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                    <span class="text-xs font-bold text-slate-700 uppercase">Armada Kendaraan & Pengemudi</span>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">No. Polisi Armada <span class="text-red-500">*</span></label>
                            <input type="text" name="vehicle_number" x-model="vehicleNumber" required placeholder="Contoh: B 9123 TYZ"
                                   class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold uppercase">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Jenis Kendaraan</label>
                            <select name="vehicle_type" x-model="vehicleType" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                                <option value="Truk Colt Diesel">Truk Colt Diesel (CDD)</option>
                                <option value="Truk Engkel (CDE)">Truk Engkel (CDE)</option>
                                <option value="Truk Fuso">Truk Fuso</option>
                                <option value="Truk Tronton / Wingbox">Truk Tronton / Wingbox</option>
                                <option value="Pick Up L300 / Gran Max">Pick Up L300 / Gran Max</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Sopir / Driver <span class="text-red-500">*</span></label>
                            <input type="text" name="driver_name" x-model="driverName" required placeholder="Nama sopir"
                                   class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nomor HP Sopir</label>
                            <input type="text" name="driver_phone" x-model="driverPhone" placeholder="08..."
                                   class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tanggal Keberangkatan <span class="text-red-500">*</span></label>
                        <input type="date" name="delivery_date" value="{{ date('Y-m-d') }}" required
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

            </div>

            <!-- Biaya Operasional Pengiriman (Task 10) -->
            <div class="pt-3 border-t border-slate-100 bg-blue-50/50 p-4 rounded-xl border border-blue-200/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-blue-900 uppercase flex items-center gap-1.5">
                        <i data-lucide="banknote" class="w-4 h-4 text-blue-600"></i>
                        <span>Biaya Operasional Pengiriman (Masuk Operasional & Tampil di PDF Owner)</span>
                    </span>
                    <span class="text-[10px] text-blue-700 font-semibold bg-blue-100 px-2 py-0.5 rounded">Opsional</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Biaya Ongkos Armada / Supir (Rp)</label>
                        <input type="number" name="shipping_cost" min="0" step="1000" placeholder="0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-blue-500 font-mono font-bold text-slate-800 bg-white">
                        <span class="text-[10px] text-slate-400">Dicatat ke pengeluaran 'Biaya Transportasi & Armada' & Lembar 1 PDF Owner.</span>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Biaya Bongkar Muat / Tenaga Angkut (Rp)</label>
                        <input type="number" name="loading_cost" min="0" step="1000" placeholder="0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-blue-500 font-mono font-bold text-slate-800 bg-white">
                        <span class="text-[10px] text-slate-400">Dicatat ke pengeluaran 'Biaya Bongkar Muat' & Lembar 1 PDF Owner.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Cargo / Items Table (Tampilan sama seperti Form Barang di Penerimaan Barang) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden space-y-0">
            <!-- Header Card -->
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                        <i data-lucide="package" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            2. Rincian Muatan Material Scrap & Daur Ulang
                        </h3>
                        <p class="text-[10px] text-slate-500">Daftar muatan material scrap & daur ulang yang dimuat ke armada ekspedisi</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="addRow()" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Tambah Baris (+F2)</span>
                    </button>
                </div>
            </div>

            <!-- Scrollable Items Container (Scroll saat barang melebihi layar) -->
            <div class="p-3 space-y-2.5 max-h-[480px] overflow-y-auto bg-slate-100/60" style="scrollbar-width: thin;">
                <template x-for="(row, idx) in rows" :key="row.uid || idx">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-emerald-400 transition-all p-3 space-y-2">
                        
                        <!-- BARIS 1: PILIH MASTER, NAMA BARANG & JENIS / VARIAN -->
                        <div class="flex items-center gap-2">
                            <!-- Nomor Urut -->
                            <span class="w-6 h-6 rounded-md bg-slate-800 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0 shadow-xs" x-text="idx + 1">1</span>

                            <!-- Pilih Dari Master (Searchable Combobox) -->
                            <div class="w-48 sm:w-60 shrink-0 relative" @click.outside="row.dropdown_open = false">
                                <input type="hidden" :name="'items['+idx+'][scrap_item_id]'" :value="row.scrap_item_id">
                                <input type="hidden" :name="'items['+idx+'][price_per_kg]'" :value="row.price_per_kg || 0">
                                <input type="hidden" :name="'items['+idx+'][subtotal]'" :value="(row.price_per_kg && row.weight) ? (row.price_per_kg * row.weight) : 0">

                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                    </span>

                                    <input type="text"
                                           x-model="row.search_text"
                                           @focus="row.dropdown_open = true"
                                           @input="row.dropdown_open = true"
                                           placeholder="Pilih dari Master..."
                                           class="w-full pl-8 pr-12 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-900 bg-white shadow-2xs">

                                    <div class="absolute inset-y-0 right-0 pr-1.5 flex items-center gap-0.5">
                                        <button type="button" 
                                                x-show="row.search_text"
                                                @click="clearRowItem(row)" 
                                                class="text-slate-400 hover:text-slate-600 p-0.5 rounded"
                                                title="Reset pilihan">
                                            <i data-lucide="x" class="w-3 h-3"></i>
                                        </button>
                                        <button type="button" 
                                                @click="row.dropdown_open = !row.dropdown_open"
                                                class="text-slate-400 hover:text-slate-600 p-0.5 rounded">
                                            <i data-lucide="chevron-down" class="w-3 h-3 transition-transform" :class="{ 'rotate-180': row.dropdown_open }"></i>
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
                                                @click="selectRowItem(row, item)"
                                                class="w-full text-left px-2.5 py-1.5 hover:bg-emerald-50 flex items-center justify-between transition-colors"
                                                :class="{ 'bg-emerald-50/70': row.scrap_item_id == item.id }">
                                            <div class="min-w-0 pr-2">
                                                <div class="text-xs font-bold text-slate-900 truncate" x-text="item.name"></div>
                                                <div class="text-[10px] text-slate-500 font-mono" x-text="item.code"></div>
                                            </div>
                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1 rounded uppercase" x-text="item.unit_type || 'kg'"></span>
                                        </button>
                                    </template>

                                    <template x-if="filteredMasterItems(row.search_text).length === 0">
                                        <div class="p-2.5 text-center text-xs text-slate-400">
                                            Tidak ditemukan di master. Ketik manual di kolom nama barang.
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Nama Barang / Muatan (Required) -->
                            <div class="flex-1 min-w-0">
                                <input type="text" 
                                       :name="'items['+idx+'][item_name]'" 
                                       x-model="row.item_name" 
                                       required 
                                       placeholder="Nama Barang / Muatan *"
                                       class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-bold text-slate-900 bg-white">
                            </div>

                            <!-- Jenis / Varian -->
                            <div class="w-36 sm:w-48 shrink-0">
                                <input type="text" 
                                       :name="'items['+idx+'][item_type_name]'" 
                                       x-model="row.item_type_name" 
                                       placeholder="Jenis/Varian (Press, Bening...)"
                                       class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-medium text-slate-800 bg-white">
                            </div>

                            <!-- Tombol Hapus -->
                            <button type="button" @click="removeRow(idx)" 
                                    :disabled="rows.length <= 1"
                                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-20 shrink-0"
                                    title="Hapus baris muatan">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <!-- BARIS 2: VALUE ANGKA & SATUAN (TETAP 1 BARIS PENUH) -->
                        <div class="grid grid-cols-4 gap-2 pt-1.5 border-t border-slate-100 items-end"
                             style="display: grid; grid-template-columns: minmax(80px, 0.9fr) minmax(110px, 1.2fr) minmax(90px, 1fr) minmax(140px, 2fr); gap: 0.5rem; align-items: end;">
                            
                            <!-- 1. Tipe Satuan -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5 whitespace-nowrap">Satuan</label>
                                <select :name="'items['+idx+'][unit_type]'" 
                                        x-model="row.unit_type"
                                        class="w-full py-1.5 px-2 text-xs font-bold rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 uppercase bg-slate-50 text-slate-800 text-center cursor-pointer">
                                    <option value="kg">⚖️ KG</option>
                                    <option value="pcs">📦 Pcs / Satuan</option>
                                </select>
                            </div>

                            <!-- 2. Berat / Qty -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5 whitespace-nowrap"
                                       x-text="row.unit_type === 'pcs' ? 'Qty Satuan *' : 'Berat Muatan (kg) *'"></label>
                                <input type="number" step="0.01" min="0.01" 
                                       :name="'items['+idx+'][weight]'" 
                                       x-model.number="row.weight" 
                                       required 
                                       placeholder="0.00"
                                       class="w-full py-1.5 px-2 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono font-bold text-slate-900 bg-white">
                            </div>

                            <!-- 3. Koli / Ikat -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5 whitespace-nowrap">Koli / Ikat</label>
                                <input type="number" min="0" 
                                       :name="'items['+idx+'][package_qty]'" 
                                       x-model.number="row.package_qty" 
                                       placeholder="0"
                                       class="w-full py-1.5 px-2 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono text-slate-700 bg-white">
                            </div>

                            <!-- 4. Keterangan Muatan -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5 whitespace-nowrap">Keterangan / Instruksi</label>
                                <input type="text" 
                                       :name="'items['+idx+'][notes]'" 
                                       x-model="row.notes" 
                                       placeholder="Misal: Segel kontainer, ikat terpal rapi..."
                                       class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 text-slate-700 bg-white">
                            </div>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Bottom Bar: Tambah Baris & Rekap Total -->
            <div class="p-3 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <button type="button" @click="addRow()" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition-all">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Tambah Baris Muatan (+F2)</span>
                </button>

                <div class="text-xs text-slate-600 flex items-center gap-3 bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                    <span>Total Baris: <strong class="text-slate-900 font-mono" x-text="rows.length"></strong></span>
                    <span>Total Koli: <strong class="text-slate-900 font-mono font-bold" x-text="totalPackages()"></strong></span>
                    <span>Total Berat: <strong class="text-emerald-700 font-mono font-black text-sm" x-text="(totalWeight() || 0).toLocaleString('id-ID') + ' kg'"></strong></span>
                </div>
            </div>
        </div>

        <!-- Card 3: Notes & Submit -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Pengiriman / Instruksi Khusus</label>
                <textarea name="notes" rows="2" placeholder="Contoh: Muatan diikat terpal rapi, bongkar pagi pukul 08:00, dokumen diserahkan security..."
                          class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <a href="{{ route('delivery-orders.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200">
                    Batal
                </a>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan & Terbitkan Surat Jalan</span>
                </button>
            </div>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function deliveryOrderForm() {
    return {
        masterItems: @json($scrapItems),
        salesTx: @json($salesTx),
        recipientName: '{{ $salesTx ? $salesTx->buyer_name : '' }}',
        recipientPhone: '{{ $salesTx ? $salesTx->buyer_phone : '' }}',
        vehicleNumber: '',
        driverName: '',
        driverPhone: '',
        vehicleType: 'Truk Colt Diesel',
        rows: [
            @if($salesTx && $salesTx->items->count() > 0)
                @foreach($salesTx->items as $si)
                    {
                        uid: {{ $loop->index + 1 }},
                        scrap_item_id: '{{ $si->scrap_item_id }}',
                        search_text: '{{ addslashes($si->item_name_snapshot) }}',
                        dropdown_open: false,
                        item_name: '{{ addslashes($si->item_name_snapshot) }}',
                        item_type_name: '{{ addslashes($si->item_type_name ?? "") }}',
                        unit_type: '{{ $si->unit_type ?? "kg" }}',
                        weight: {{ (float) ($si->weight ?? $si->quantity ?? 0) }},
                        price_per_kg: {{ (float) ($si->sell_price_per_kg ?? 0) }},
                        subtotal: {{ (float) ($si->subtotal ?? 0) }},
                        package_qty: 0,
                        notes: ''
                    },
                @endforeach
            @else
                { uid: 1, scrap_item_id: '', search_text: '', dropdown_open: false, item_name: '', item_type_name: '', unit_type: 'kg', weight: '', price_per_kg: 0, subtotal: 0, package_qty: '', notes: '' }
            @endif
        ],

        filteredMasterItems(searchText) {
            if (!searchText || !searchText.trim()) {
                return this.masterItems;
            }
            const q = searchText.toLowerCase().trim();
            return this.masterItems.filter(item => {
                const nameMatch = (item.name || '').toLowerCase().includes(q);
                const codeMatch = (item.code || '').toLowerCase().includes(q);
                return nameMatch || codeMatch;
            });
        },

        selectRowItem(row, item) {
            row.scrap_item_id = item.id;
            row.search_text = item.name + (item.code ? ' (' + item.code + ')' : '');
            row.item_name = item.name;
            if (item.unit_type) {
                row.unit_type = item.unit_type;
            }
            if (item.current_price && item.current_price.sell_price_per_kg) {
                row.price_per_kg = parseFloat(item.current_price.sell_price_per_kg) || 0;
            }
            row.dropdown_open = false;
        },

        clearRowItem(row) {
            row.scrap_item_id = '';
            row.search_text = '';
            row.price_per_kg = 0;
            row.dropdown_open = true;
        },

        addRow() {
            this.rows.push({
                uid: Date.now() + Math.random(),
                scrap_item_id: '',
                search_text: '',
                dropdown_open: false,
                item_name: '',
                item_type_name: '',
                unit_type: 'kg',
                weight: '',
                price_per_kg: 0,
                subtotal: 0,
                package_qty: '',
                notes: ''
            });
            this.$nextTick(() => {
                if (window.refreshIcons) window.refreshIcons();
            });
        },

        removeRow(idx) {
            if (this.rows.length > 1) {
                this.rows.splice(idx, 1);
            }
        },

        onItemSelect(row) {
            const found = this.masterItems.find(i => i.id == row.scrap_item_id);
            if (found) {
                row.item_name = found.name;
            }
        },

        totalWeight() {
            return this.rows.reduce((acc, r) => acc + (parseFloat(r.weight) || 0), 0);
        },

        totalPackages() {
            return this.rows.reduce((acc, r) => acc + (parseInt(r.package_qty) || 0), 0);
        },

        init() {
            window.addEventListener('keydown', (e) => {
                if (e.key === 'F2') {
                    e.preventDefault();
                    this.addRow();
                }
            });
        }
    };
}
</script>
@endpush

