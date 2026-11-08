@extends('layouts.app', ['header' => 'Catat Penjualan Rongsok ke Pabrik / Pembeli'])

@section('content')
<div class="max-w-5xl mx-auto space-y-4" x-data="salesPos()">
    
    <div class="flex items-center justify-between">
        <a href="{{ route('sales.index') }}" class="text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1 font-medium">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke Riwayat Penjualan</span>
        </a>
    </div>

    <form action="{{ route('sales.store') }}" method="POST" @submit="handleSubmit($event)">
        @csrf

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
            
            <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Form Pengiriman & Penjualan Rongsok (Grosir)</h2>
                    <p class="text-xs text-slate-500">Stok barang di gudang akan otomatis dipotong dan diverifikasi tidak minus</p>
                </div>

                <!-- Sync from Surat Jalan (Task 8) -->
                @if(isset($unbilledDeliveryOrders) && $unbilledDeliveryOrders->count() > 0)
                    <div class="bg-amber-50 border border-amber-200 p-2.5 rounded-lg flex items-center gap-2">
                        <i data-lucide="clipboard-check" class="w-4 h-4 text-amber-600 shrink-0"></i>
                        <div class="text-xs">
                            <span class="font-bold text-amber-900 block">Tarik Data dari Surat Jalan:</span>
                            <select x-model="selectedDoId" @change="loadFromDeliveryOrder()" class="mt-1 py-1 px-2 text-xs rounded border border-amber-300 font-medium">
                                <option value="">-- Pilih Surat Jalan Siap Ditagih --</option>
                                @foreach($unbilledDeliveryOrders as $udo)
                                    <option value="{{ $udo->id }}" {{ (isset($selectedDeliveryOrder) && $selectedDeliveryOrder->id == $udo->id) ? 'selected' : '' }}>
                                        {{ $udo->delivery_number }} - {{ $udo->recipient_name }} ({{ $udo->total_weight }} kg)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <input type="hidden" name="delivery_order_id" x-model="deliveryOrderId">

            @if(Auth::user()->isKasir())
                <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-center gap-3 shadow-xs">
                    <div class="p-2 rounded-lg bg-amber-100 text-amber-700 shrink-0">
                        <i data-lucide="file-clock" class="w-5 h-5"></i>
                    </div>
                    <div class="space-y-0.5">
                        <div class="font-bold text-sm">Mode Kasir: Input Penjualan sebagai DRAFT</div>
                        <p class="text-[11px] text-amber-800 leading-relaxed">
                            Field harga jual dinonaktifkan. Anda hanya perlu mendata pembeli, jenis barang, dan berat/jumlah fisik yang dikeluarkan. <strong>Status penjualan akan tersimpan sebagai DRAFT</strong> dan Admin / Owner akan melengkapi harga jual resminya.
                        </p>
                    </div>
                </div>
            @endif

            <!-- Pilihan Metode Penyerahan / Pengiriman Barang -->
            <div class="p-4 rounded-xl border bg-slate-50/80 border-slate-200 space-y-3">
                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Metode Penyerahan / Pengiriman Barang *
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Opsi 1: Diambil Sendiri oleh Pembeli -->
                    <label class="relative flex items-start p-3.5 rounded-xl border-2 cursor-pointer transition-all"
                           :class="deliveryType === 'pickup' ? 'border-emerald-600 bg-emerald-50/50 shadow-xs' : 'border-slate-200 bg-white hover:border-slate-300'">
                        <input type="radio" name="delivery_type" value="pickup" x-model="deliveryType" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                        <div class="ml-3">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-slate-900">Diambil Sendiri oleh Pembeli</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Langsung Selesai
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">
                                Pelanggan datang ke depo. <strong>Tanpa surat jalan</strong>, hanya faktur penjualan (invoice) & status langsung SELESAI.
                            </p>
                        </div>
                    </label>

                    <!-- Opsi 2: Dengan Pengiriman Armada -->
                    <label class="relative flex items-start p-3.5 rounded-xl border-2 cursor-pointer transition-all"
                           :class="deliveryType === 'delivery' ? 'border-blue-600 bg-blue-50/50 shadow-xs' : 'border-slate-200 bg-white hover:border-slate-300'">
                        <input type="radio" name="delivery_type" value="delivery" x-model="deliveryType" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                        <div class="ml-3">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-slate-900">Dengan Pengiriman Armada Depo</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    Perlu Surat Jalan
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">
                                Dikirim dengan kendaraan/sopir perusahaan. <strong>Status baru selesai setelah pengiriman tuntas.</strong>
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Keterangan Alur Pengiriman Armada Gudang -->
                <div x-show="deliveryType === 'delivery'" x-transition class="pt-3 border-t border-slate-200 text-xs">
                    <div class="p-3.5 bg-blue-50/90 border border-blue-200 rounded-xl flex items-start gap-3 text-blue-950">
                        <i data-lucide="info" class="w-5 h-5 text-blue-600 shrink-0 mt-0.5"></i>
                        <div class="space-y-1">
                            <span class="font-bold text-xs text-blue-900 block">Alur Pengiriman Armada Gudang &amp; Surat Jalan:</span>
                            <p class="text-[11px] text-blue-800 leading-relaxed">
                                Transaksi penjualan ini akan disimpan dengan status <strong>Menunggu Pengiriman</strong> (tidak langsung masuk menjadi Surat Jalan).
                            </p>
                            <p class="text-[11px] text-blue-800 leading-relaxed">
                                Surat Jalan dibuat tersendiri melalui menu <strong>Surat Jalan</strong> &rarr; <em>Buat Surat Jalan Baru</em> dengan memilih data penjualan ini.
                            </p>
                            <p class="text-[11px] text-emerald-800 font-medium">
                                💡 <em>Berat dan harga jual dapat dikosongi terlebih dahulu saat pengiriman, dan wajib diisi sebelum status transaksi diselesaikan.</em>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Date & Buyer Details -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 inline text-blue-600 mr-1"></i>
                        Tanggal Transaksi (Bisa Backdate)
                    </label>
                    <input type="datetime-local" name="transaction_date" x-model="transactionDate"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-blue-500 font-mono text-slate-800">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Pembeli / Pabrik Peleburan *</label>
                    <input type="text" name="buyer_name" x-model="buyerName" required placeholder="Contoh: PT. Krakatau Scrap Metal / Pabrik Cikarang"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-blue-500 font-medium">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nomor Telepon Pembeli</label>
                    <input type="text" name="buyer_phone" x-model="buyerPhone" placeholder="08..."
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-blue-500 font-mono">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1"
                           x-text="deliveryType === 'delivery' ? 'Alamat Tujuan Pengiriman (Pabrik / Gudang Bongkar) *' : 'Alamat Pembeli (Opsional)'">
                        Alamat Pembeli
                    </label>
                    <input type="text" name="buyer_address" x-model="buyerAddress" placeholder="Kawasan Industri / Alamat Pabrik"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-blue-500">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Metode Pembayaran *</label>
                        <button type="button" @click="togglePaymentMethodMode()" 
                                class="text-[11px] font-bold text-blue-600 hover:text-blue-700 hover:underline">
                            <span x-text="isManualPaymentMethod ? '← Pilih dari Daftar' : '+ Tambah Metode Baru'"></span>
                        </button>
                    </div>

                    <!-- Mode 1: Searchable Dropdown -->
                    <div x-show="!isManualPaymentMethod" class="relative" @click.outside="closePaymentMethodDropdown()">
                        <input type="hidden" name="payment_method_id" :value="paymentMethodId" :disabled="isManualPaymentMethod">

                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </span>

                            <input type="text"
                                   x-ref="paymentMethodInput"
                                   x-model="paymentMethodSearchQuery"
                                   @focus="paymentMethodDropdownOpen = true; $event.target.select()"
                                   @click="paymentMethodDropdownOpen = true"
                                   @input="paymentMethodDropdownOpen = true"
                                   placeholder="Cari metode pembayaran..."
                                   class="w-full pl-8 pr-12 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 font-semibold text-slate-900 bg-white shadow-2xs">

                            <div class="absolute inset-y-0 right-0 pr-1.5 flex items-center gap-0.5">
                                <button type="button" 
                                        x-show="paymentMethodSearchQuery"
                                        @click="paymentMethodSearchQuery = ''; paymentMethodDropdownOpen = true; $refs.paymentMethodInput?.focus()" 
                                        class="text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer"
                                        title="Reset Pencarian">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </button>
                                <button type="button" 
                                        @click="paymentMethodDropdownOpen = !paymentMethodDropdownOpen"
                                        class="text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer">
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': paymentMethodDropdownOpen }"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Floating Filtered List -->
                        <div x-show="paymentMethodDropdownOpen"
                             x-transition
                             class="absolute z-50 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white rounded-xl border border-slate-200 shadow-xl divide-y divide-slate-100"
                             style="display: none;">
                            
                            <template x-for="pm in filteredPaymentMethods()" :key="pm.id">
                                <button type="button" 
                                        @click="selectPaymentMethod(pm)"
                                        class="w-full text-left px-3 py-2 hover:bg-blue-50 flex items-center justify-between transition-colors cursor-pointer"
                                        :class="{ 'bg-blue-50 font-bold text-blue-950': paymentMethodId == pm.id }">
                                    <span class="text-xs text-slate-900" x-text="pm.name"></span>
                                    <span x-show="paymentMethodId == pm.id" class="text-blue-600 font-bold text-xs">✓</span>
                                </button>
                            </template>

                            <template x-if="filteredPaymentMethods().length === 0">
                                <div class="p-3 text-center space-y-2">
                                    <p class="text-xs text-slate-500">Tidak ada metode "<span class="font-bold text-slate-800" x-text="paymentMethodSearchQuery"></span>"</p>
                                    <button type="button" 
                                            @click="isManualPaymentMethod = true; newPaymentMethodName = paymentMethodSearchQuery; paymentMethodDropdownOpen = false;"
                                            class="px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-xs inline-flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span x-text="'+ Tambah &quot;' + paymentMethodSearchQuery + '&quot; sebagai Metode Baru'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Mode 2: Input Manual Metode Baru -->
                    <div x-show="isManualPaymentMethod" style="display: none;" class="space-y-1">
                        <input type="text" name="new_payment_method_name" x-model="newPaymentMethodName"
                               placeholder="Ketik metode pembayaran baru (contoh: QRIS Bank BCA, Mandiri Transfer, Giro)..."
                               :required="isManualPaymentMethod"
                               class="w-full py-2 px-3 text-xs rounded-lg border border-blue-500 focus:ring-2 focus:ring-blue-500 font-semibold text-slate-900 bg-blue-50/30">
                        <p class="text-[10px] text-blue-600 font-medium">✨ Metode pembayaran baru otomatis tersimpan ke master database saat transaksi disimpan.</p>
                        <input type="hidden" name="payment_method_id" value="__new__" :disabled="!isManualPaymentMethod">
                    </div>
                </div>

                <!-- Bank Info for Sales (Task 11) -->
                <div class="sm:col-span-2" x-show="isTransferPayment()">
                    <label class="block text-xs font-semibold text-blue-900 uppercase mb-1">Informasi Rekening Bank Pemilik Gudang</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <input type="text" name="custom_bank" x-model="customBank" placeholder="Nama Bank (BCA / Mandiri / BRI)"
                               :disabled="!isTransferPayment()"
                               class="w-full py-1.5 px-3 text-xs rounded-lg border border-blue-300 bg-blue-50/50">
                        <input type="text" name="custom_account_no" x-model="customAccountNo" placeholder="Nomor Rekening"
                               :disabled="!isTransferPayment()"
                               class="w-full py-1.5 px-3 text-xs rounded-lg border border-blue-300 bg-blue-50/50 font-mono">
                        <input type="text" name="custom_account_name" x-model="customAccountName" placeholder="Nama Pemilik Rekening (A/N)"
                               :disabled="!isTransferPayment()"
                               class="w-full py-1.5 px-3 text-xs rounded-lg border border-blue-300 bg-blue-50/50">
                    </div>
                    <div class="mt-1.5 flex items-center">
                        <label class="inline-flex items-center gap-1.5 text-blue-900 text-xs font-medium cursor-pointer">
                            <input type="checkbox" name="save_to_user_bank" value="1" checked :disabled="!isTransferPayment()" class="rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                            <span>Simpan / perbarui nomor rekening ini ke data profil kasir/user saya</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Items List (Card-based Multi-Line Entry: Tidak ada kolom terpotong) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-blue-600 text-white text-xs font-bold flex items-center justify-center shadow-xs" x-text="rows.length">1</span>
                        <span class="text-xs font-bold uppercase text-slate-800 tracking-wider">Daftar Barang yang Dijual</span>
                    </div>
                    <button type="button" @click="addRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Tambah Barang (+Baris)</span>
                    </button>
                </div>

                <div class="space-y-3 bg-slate-50/70 p-3 rounded-xl border border-slate-200">
                    <template x-for="(row, idx) in rows" :key="row.uid">
                        <div class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-blue-300 transition-all overflow-hidden">
                            <!-- Card Header -->
                            <div class="px-3.5 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded bg-slate-800 text-white font-mono font-bold text-[11px] flex items-center justify-center" x-text="idx + 1">1</span>
                                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">Item Penjualan #<span x-text="idx + 1"></span></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1 bg-white px-2.5 py-0.5 rounded border border-slate-200">
                                        <span class="text-[10px] uppercase font-bold text-slate-400">Subtotal:</span>
                                        <span class="text-xs font-black font-mono text-blue-700" x-text="formatRp(rowSubtotal(row))">Rp 0</span>
                                    </div>
                                    <button type="button" @click="removeRow(idx)" :disabled="rows.length <= 1"
                                            class="p-1 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors disabled:opacity-20"
                                            title="Hapus baris">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-3.5 space-y-3">
                                <!-- Baris 1: Pilihan Barang, Jenis/Varian & Tipe Hitungan -->
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                    <div class="md:col-span-6 relative" @click.outside="row.dropdown_open = false">
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Barang Sesuai Harga & Stok <span class="text-red-500">*</span>
                                        </label>

                                        <input type="hidden" :name="'items[' + idx + '][scrap_item_id]'" :value="row.scrap_item_id" required>
                                        <input type="hidden" :name="'items[' + idx + '][item_price_stock_id]'" :value="row.item_price_stock_id || ''">
                                        <input type="hidden" :name="'items[' + idx + '][tier_id]'" :value="row.tier_id || ''">
                                        <input type="hidden" :name="'items[' + idx + '][buy_price_snapshot]'" :value="row.buy_price_snapshot || row.avg_buy_price || 0">

                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                            </span>

                                            <input type="text"
                                                   x-model="row.search_text"
                                                   @focus="row.dropdown_open = true"
                                                   @input="row.dropdown_open = true"
                                                   placeholder="Cari nama barang atau harga (cth: Besi, 4000)..."
                                                   required
                                                   class="w-full pl-8 pr-12 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 font-bold text-slate-900 bg-white shadow-2xs">

                                            <!-- Quick Clear & Caret Icons -->
                                            <div class="absolute inset-y-0 right-0 pr-1.5 flex items-center gap-0.5">
                                                <button type="button" 
                                                        x-show="row.search_text"
                                                        @click="clearRowItem(row)" 
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
                                             class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-xl border border-slate-200 shadow-xl divide-y divide-slate-100"
                                             style="display: none;">
                                            
                                            <template x-for="item in filteredMasterItems(row.search_text)" :key="item.tier_id || (item.id + '_' + item.price_per_kg)">
                                                <button type="button" 
                                                        @click="selectRowItem(row, item)"
                                                        class="w-full text-left px-3 py-2 hover:bg-blue-50 flex items-center justify-between transition-colors"
                                                        :class="{ 'bg-blue-50/70': row.tier_id === item.tier_id }">
                                                    <div class="min-w-0 pr-2">
                                                        <div class="text-xs font-bold text-slate-900 truncate" x-text="item.name"></div>
                                                        <div class="text-[10px] text-slate-500 font-mono">
                                                            <span x-text="item.code"></span>
                                                            <span x-show="item.category_name" x-text="' • ' + item.category_name"></span>
                                                            <span class="text-emerald-700 font-semibold" x-text="' • Stok: ' + formatNum(item.current_stock) + ' ' + (item.unit_type || 'kg')"></span>
                                                        </div>
                                                    </div>
                                                    <div class="text-right shrink-0">
                                                        <div class="text-[11px] font-mono font-bold text-blue-700" x-text="formatRp(item.sell_price_per_kg) + '/' + (item.unit_type || 'kg')"></div>
                                                        <div class="text-[9px] text-slate-400 font-mono" x-text="'Modal: ' + formatRp(item.buy_price_snapshot)"></div>
                                                    </div>
                                                </button>
                                            </template>

                                            <template x-if="filteredMasterItems(row.search_text).length === 0">
                                                <div class="p-3 text-center text-xs text-slate-500">
                                                    Tidak ada barang dengan kata kunci "<span class="font-bold text-slate-700" x-text="row.search_text"></span>"
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Warning if stock exceeded -->
                                        <div x-show="row.scrap_item_id && rowStockQuantity(row) > row.maxStock" class="text-[11px] text-red-600 font-bold mt-1">
                                            ⚠️ Melebihi stok gudang! Maks: <span x-text="formatNum(row.maxStock)"></span>
                                        </div>
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                                            Jenis / Varian <span class="text-[10px] text-slate-400">(Manual)</span>
                                        </label>
                                        <input type="text" 
                                               :name="'items[' + idx + '][item_type_name]'" 
                                               x-model="row.item_type_name" 
                                               placeholder="Misal: Press, Super, Karungan..."
                                               class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 bg-white">
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                                            Tipe Hitungan
                                        </label>
                                        <select :name="'items[' + idx + '][unit_type]'" 
                                                x-model="row.unit_type"
                                                class="w-full py-2 px-2.5 text-xs font-bold rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 uppercase bg-slate-50 text-slate-800 cursor-pointer">
                                            <option value="kg">⚖️ KG (Berat Timbangan)</option>
                                            <option value="pcs">📦 Pcs / Satuan</option>
                                            <option value="satuan">🔢 Satuan</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Baris 2: Qty / Berat, Harga Jual, Subtotal -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide mb-1"
                                               x-text="isPcsUnit(row) ? 'Jumlah Dijual (Pcs/Satuan)' : 'Berat Dijual (kg)'"></label>
                                        <template x-if="isPcsUnit(row)">
                                            <div>
                                                <input type="number" step="1" min="0" 
                                                       :name="'items[' + idx + '][quantity]'" 
                                                       x-model.number="row.quantity" 
                                                       placeholder="0"
                                                       class="w-full py-2 px-2.5 text-xs text-right rounded-lg border border-amber-300 bg-amber-50/70 focus:ring-2 focus:ring-amber-500 font-mono font-bold text-amber-950">
                                                <input type="hidden" :name="'items[' + idx + '][weight]'" value="0">
                                                <span class="text-[10px] text-amber-700 font-medium block text-right mt-0.5">Bisa dikosongi</span>
                                            </div>
                                        </template>
                                        <template x-if="!isPcsUnit(row)">
                                            <div>
                                                <input type="number" step="0.01" min="0" 
                                                       :name="'items[' + idx + '][weight]'" 
                                                       x-model.number="row.weight" 
                                                       placeholder="0"
                                                       class="w-full py-2 px-2.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 font-mono font-bold text-slate-900 bg-white"
                                                       :class="row.weight > row.maxStock ? 'border-red-500 bg-red-50 text-red-700' : ''">
                                                <input type="hidden" :name="'items[' + idx + '][quantity]'" value="1">
                                                <span class="text-[10px] text-slate-400 font-medium block text-right mt-0.5">Bisa dikosongi</span>
                                            </div>
                                        </template>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide mb-1">
                                            Harga Jual Pabrik (Rp)
                                        </label>
                                        @if(Auth::user()->isKasir())
                                            <div>
                                                <input type="text" value="Ditentukan Owner/Admin" disabled
                                                       class="w-full py-2 px-2.5 text-xs text-center rounded-lg border border-slate-300 font-bold text-amber-800 bg-amber-50/70 border-dashed cursor-not-allowed">
                                                <input type="hidden" :name="'items[' + idx + '][sell_price_per_kg]'" value="0">
                                                <span class="text-[10px] text-amber-700 font-semibold block text-right mt-0.5">🔒 Dinonaktifkan untuk Kasir</span>
                                            </div>
                                        @else
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">Rp</span>
                                                <input type="text" 
                                                       inputmode="numeric"
                                                       :name="'items[' + idx + '][sell_price_per_kg]'" 
                                                       x-rupiah="row.sell_price_per_kg" 
                                                       placeholder="0"
                                                       class="w-full py-2 pl-9 pr-2.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 font-mono font-bold text-slate-900 bg-white">
                                            </div>
                                            <div class="flex items-center justify-between text-[10px] mt-0.5">
                                                <span class="text-blue-600 font-mono font-bold" x-text="formatRp(row.sell_price_per_kg || 0)"></span>
                                                <span class="text-slate-400 font-medium">Bisa dikosongi</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-800 uppercase tracking-wide mb-1 text-right">
                                            Subtotal (Rp)
                                        </label>
                                        @if(Auth::user()->isKasir())
                                            <div class="py-2 px-3 text-xs text-right font-mono font-black rounded-lg bg-slate-900 text-white shadow-2xs border border-slate-800">
                                                Draft
                                            </div>
                                            <span class="text-[10px] text-amber-600 font-semibold block text-right mt-0.5">Menunggu Harga</span>
                                        @else
                                            <div class="py-2 px-3 text-xs text-right font-mono font-black rounded-lg bg-slate-900 text-white shadow-2xs border border-slate-800"
                                                 x-text="formatRp(rowSubtotal(row))">
                                                Rp 0
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium block text-right mt-0.5">Total Baris Ini</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Notes & Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Pengiriman / No. Pol Truk</label>
                    <textarea name="notes" x-model="notes" rows="3" placeholder="Nomor polisi armada truk pengangkut, supir, surat jalan pabrik..."
                              class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-blue-500"></textarea>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Total Berat Dijual:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="formatNum(totalWeight()) + ' kg'">0 kg</span>
                    </div>

                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal Penjualan:</span>
                        <span class="font-mono font-semibold" x-text="formatRp(subtotalAmount())">Rp 0</span>
                    </div>

                    <div class="flex items-center justify-between text-slate-600">
                        <span>Potongan Diskon:</span>
                        <div class="relative w-36">
                            <span class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none text-slate-400 font-bold text-xs">Rp</span>
                            <input type="text" inputmode="numeric" name="discount_amount" x-rupiah="discountAmount"
                                   placeholder="0"
                                   class="w-full py-1 pl-7 pr-2 text-xs text-right rounded border border-slate-300 font-mono">
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-sm font-bold text-slate-900">
                        <span>TOTAL TAGIHAN:</span>
                        <span class="font-mono text-xl font-black text-blue-700" x-text="formatRp(finalAmount())">Rp 0</span>
                    </div>

                    <div class="pt-2 border-t border-dotted border-slate-200 flex justify-between text-[11px] text-emerald-700 font-medium">
                        <span>Estimasi Laba Kotor:</span>
                        <span class="font-mono font-bold" x-text="formatRp(estimatedProfit())">Rp 0</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                    Batal
                </a>
                @if(Auth::user()->isKasir())
                    <button type="submit" 
                            :disabled="hasStockError() || rows.length === 0"
                            class="px-6 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white text-xs font-bold shadow-sm transition-all">
                        Simpan Penjualan (Draft Kasir)
                    </button>
                @else
                    <button type="submit" 
                            :disabled="hasStockError() || rows.length === 0"
                            class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold shadow-sm transition-all">
                        Simpan & Keluarkan Stok Rongsok
                    </button>
                @endif
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function salesPos() {
    return {
        isKasir: {{ Auth::user()->isKasir() ? 'true' : 'false' }},
        masterItems: @json($scrapItems),
        paymentMethodsList: @json($paymentMethods),
        unbilledOrders: @json($unbilledDeliveryOrders ?? []),
        selectedDeliveryOrder: @json($selectedDeliveryOrder ?? null),
        
        selectedDoId: '{{ $selectedDeliveryOrder?->id ?? "" }}',
        deliveryOrderId: '{{ $selectedDeliveryOrder?->id ?? "" }}',
        deliveryType: '{{ $selectedDeliveryOrder ? "delivery" : "pickup" }}',
        driversList: @json($drivers ?? []),
        driverId: '',
        driverName: '{{ $selectedDeliveryOrder?->driver_name ?? "" }}',
        driverPhone: '{{ $selectedDeliveryOrder?->driver_phone ?? "" }}',
        vehicleNumber: '{{ $selectedDeliveryOrder?->vehicle_number ?? "" }}',
        vehicleType: '{{ $selectedDeliveryOrder?->vehicle_type ?? "Truk" }}',
        driverBankName: '{{ $selectedDeliveryOrder?->driver_bank_name ?? "" }}',
        driverBankAccountNumber: '{{ $selectedDeliveryOrder?->driver_bank_account_number ?? "" }}',
        driverBankAccountName: '{{ $selectedDeliveryOrder?->driver_bank_account_name ?? "" }}',
        saveToDriverBank: true,

        onDriverSelect(e) {
            const opt = e.target.selectedOptions[0];
            if (opt && opt.value) {
                this.driverName = opt.dataset.name || '';
                this.driverPhone = opt.dataset.phone || '';
                if (opt.dataset.vehicleNumber) this.vehicleNumber = opt.dataset.vehicleNumber;
                if (opt.dataset.vehicleType) this.vehicleType = opt.dataset.vehicleType;
                this.driverBankName = opt.dataset.bankName || '';
                this.driverBankAccountNumber = opt.dataset.bankNumber || '';
                this.driverBankAccountName = opt.dataset.bankHolder || '';
            } else {
                this.driverId = '';
                this.driverBankName = '';
                this.driverBankAccountNumber = '';
                this.driverBankAccountName = '';
            }
        },
        transactionDate: '',
        buyerName: '{{ $selectedDeliveryOrder?->recipient_name ?? "" }}',
        buyerPhone: '{{ $selectedDeliveryOrder?->recipient_phone ?? "" }}',
        buyerAddress: '{{ $selectedDeliveryOrder?->recipient_address ?? "" }}',
        paymentMethodId: '{{ $paymentMethods->first()?->id ?? 1 }}',
        paymentMethodSearchQuery: '{{ $paymentMethods->first()?->name ?? "" }}',
        paymentMethodDropdownOpen: false,
        isManualPaymentMethod: false,
        newPaymentMethodName: '',

        togglePaymentMethodMode() {
            this.isManualPaymentMethod = !this.isManualPaymentMethod;
            if (this.isManualPaymentMethod) {
                this.paymentMethodDropdownOpen = false;
                this.newPaymentMethodName = this.paymentMethodSearchQuery || '';
            } else {
                const pm = this.paymentMethodsList.find(p => p.id == this.paymentMethodId) || this.paymentMethodsList[0];
                if (pm) {
                    this.paymentMethodId = pm.id;
                    this.paymentMethodSearchQuery = pm.name;
                }
            }
        },

        filteredPaymentMethods() {
            if (!this.paymentMethodSearchQuery) return this.paymentMethodsList;
            const q = this.paymentMethodSearchQuery.toLowerCase();
            return this.paymentMethodsList.filter(pm => (pm.name || '').toLowerCase().includes(q));
        },

        selectPaymentMethod(pm) {
            this.paymentMethodId = pm.id;
            this.paymentMethodSearchQuery = pm.name;
            this.paymentMethodDropdownOpen = false;
            this.isManualPaymentMethod = false;
            this.newPaymentMethodName = '';
            if (pm.bank_name && !this.customBank) {
                this.customBank = pm.bank_name;
            }
        },

        closePaymentMethodDropdown() {
            this.paymentMethodDropdownOpen = false;
            if (!this.isManualPaymentMethod) {
                const pm = this.paymentMethodsList.find(p => p.id == this.paymentMethodId);
                if (pm) {
                    this.paymentMethodSearchQuery = pm.name;
                }
            }
        },
        customBank: '{{ addslashes(auth()->user()->bank_name ?? '') }}',
        customAccountNo: '{{ addslashes(auth()->user()->bank_account_number ?? '') }}',
        customAccountName: '{{ addslashes(auth()->user()->bank_account_name ?? '') }}',
        notes: '{{ $selectedDeliveryOrder ? "Sinkron Surat Jalan: " . $selectedDeliveryOrder->delivery_number : "" }}',

        discountAmount: 0,
        rows: [],

        init() {
            if (this.selectedDeliveryOrder && this.selectedDeliveryOrder.items && this.selectedDeliveryOrder.items.length > 0) {
                this.rows = this.selectedDeliveryOrder.items.map(it => {
                    const found = this.masterItems.find(m => m.id == it.scrap_item_id && (parseFloat(m.price_per_kg) == parseFloat(it.price_per_kg) || !it.price_per_kg)) || this.masterItems.find(m => m.id == it.scrap_item_id);
                    const buyPrice = found ? (parseFloat(found.buy_price_snapshot) || parseFloat(found.price_per_kg) || 0) : 0;
                    const sellPrice = (it.price_per_kg && parseFloat(it.price_per_kg) > 0) ? parseFloat(it.price_per_kg) : (found ? (parseFloat(found.sell_price_per_kg) || 0) : 0);
                    const stock = found ? (parseFloat(found.current_stock) || 0) : 0;
                    return {
                        uid: Math.random(),
                        scrap_item_id: it.scrap_item_id || '',
                        tier_id: found ? found.tier_id : '',
                        item_price_stock_id: found ? (found.item_price_stock_id || '') : '',
                        search_text: found ? (found.name + ' [Stok: ' + this.formatNum(stock) + ' ' + (found.unit_type || 'kg') + ']') : (it.item_name || ''),
                        dropdown_open: false,
                        item_type_name: it.item_type_name || '',
                        unit_type: it.unit_type || 'kg',
                        quantity: it.quantity || 1,
                        weight: it.weight || 0,
                        sell_price_per_kg: sellPrice,
                        buy_price_snapshot: buyPrice,
                        avg_buy_price: buyPrice,
                        maxStock: stock
                    };
                });
            } else {
                this.addRow();
            }

            this.$watch('paymentMethodId', (val) => {
                const pm = this.paymentMethodsList.find(m => m.id == val);
                if (pm && pm.bank_name && !this.customBank) {
                    this.customBank = pm.bank_name;
                }
            });
        },

        loadFromDeliveryOrder() {
            if (!this.selectedDoId) {
                this.deliveryOrderId = '';
                return;
            }
            window.location.href = '{{ route("sales.create") }}?delivery_order_id=' + this.selectedDoId;
        },

        isTransferPayment() {
            if (this.isManualPaymentMethod) {
                const name = (this.newPaymentMethodName || '').toLowerCase();
                return name.includes('transfer') || name.includes('bank') || name.includes('bca') || name.includes('bri') || name.includes('mandiri') || name.includes('bni');
            }
            const method = this.paymentMethodsList.find(m => m.id == this.paymentMethodId);
            if (!method) return false;
            const name = (method.name || '').toLowerCase();
            const code = (method.code || '').toLowerCase();
            if (code === 'cash' || name.includes('tunai') || name.includes('cash')) return false;
            return name.includes('transfer') || name.includes('bank') || name.includes('bca') || name.includes('bri') || name.includes('mandiri') || name.includes('bni') || code.includes('transfer');
        },

        filteredMasterItems(searchText) {
            if (!searchText || searchText.trim() === '') {
                return this.masterItems;
            }
            const q = searchText.toLowerCase().trim();
            return this.masterItems.filter(item => {
                const name = (item.name || '').toLowerCase();
                const code = (item.code || '').toLowerCase();
                const base = (item.base_name || '').toLowerCase();
                const cat = (item.category_name || '').toLowerCase();
                const price = String(item.price_per_kg || '');
                return name.includes(q) || code.includes(q) || base.includes(q) || cat.includes(q) || price.includes(q);
            });
        },

        selectRowItem(row, item) {
            row.scrap_item_id = item.scrap_item_id || item.id;
            row.item_price_stock_id = item.item_price_stock_id || '';
            row.tier_id = item.tier_id || (item.id + '_' + item.price_per_kg);
            row.search_text = item.name + ' [Stok: ' + this.formatNum(item.current_stock) + ' ' + (item.unit_type || 'kg') + ']';
            row.sell_price_per_kg = this.isKasir ? 0 : (parseFloat(item.sell_price_per_kg) || 0);
            row.buy_price_snapshot = parseFloat(item.buy_price_snapshot) || parseFloat(item.price_per_kg) || 0;
            row.avg_buy_price = row.buy_price_snapshot;
            row.maxStock = parseFloat(item.current_stock) || 0;
            row.unit_type = item.unit_type || 'kg';
            row.dropdown_open = false;
            this.refreshIcons();
        },

        clearRowItem(row) {
            row.scrap_item_id = '';
            row.item_price_stock_id = '';
            row.tier_id = '';
            row.search_text = '';
            row.sell_price_per_kg = 0;
            row.buy_price_snapshot = 0;
            row.avg_buy_price = 0;
            row.maxStock = 0;
            row.dropdown_open = true;
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

        addRow() {
            this.rows.push({
                uid: Date.now() + Math.random(),
                scrap_item_id: '',
                item_price_stock_id: '',
                tier_id: '',
                search_text: '',
                dropdown_open: false,
                item_type_name: '',
                unit_type: 'kg',
                quantity: 1,
                weight: '',
                sell_price_per_kg: 0,
                buy_price_snapshot: 0,
                avg_buy_price: 0,
                maxStock: 0
            });
            this.refreshIcons();
        },

        removeRow(idx) {
            if (this.rows.length > 1) {
                this.rows.splice(idx, 1);
            }
        },

        onItemChanged(idx) {
            // Handled by selectRowItem
        },

        isPcsUnit(row) {
            if (!row || !row.unit_type) return false;
            const u = String(row.unit_type).toLowerCase().trim();
            return u === 'pcs' || u === 'satuan' || u === 'unit';
        },

        rowStockQuantity(row) {
            return this.isPcsUnit(row) ? (parseFloat(row.quantity) || 1) : (parseFloat(row.weight) || 0);
        },

        rowSubtotal(row) {
            if (this.isPcsUnit(row)) {
                const qty = Math.max(1, parseFloat(row.quantity) || 1);
                const p = parseFloat(row.sell_price_per_kg) || 0;
                return Math.round(qty * p);
            }
            const w = parseFloat(row.weight) || 0;
            const p = parseFloat(row.sell_price_per_kg) || 0;
            return Math.round(w * p);
        },

        totalWeight() {
            return this.rows.reduce((acc, r) => acc + (this.isPcsUnit(r) ? 0 : (parseFloat(r.weight) || 0)), 0);
        },

        subtotalAmount() {
            return this.rows.reduce((acc, r) => acc + this.rowSubtotal(r), 0);
        },

        finalAmount() {
            return Math.max(0, this.subtotalAmount() - (parseFloat(this.discountAmount) || 0));
        },

        estimatedProfit() {
            let totalCogs = 0;
            this.rows.forEach(r => {
                const qty = this.rowStockQuantity(r);
                const buy = parseFloat(r.avg_buy_price) || 0;
                totalCogs += (qty * buy);
            });
            return Math.max(0, this.finalAmount() - totalCogs);
        },

        hasStockError() {
            return this.rows.some(r => {
                const qty = this.rowStockQuantity(r);
                return qty > r.maxStock;
            });
        },

        handleSubmit(e) {
            if (this.hasStockError()) {
                e.preventDefault();
                alert('Terdapat barang dengan jumlah/berat melebihi stok yang tersedia di gudang!');
                return;
            }
        },

        formatRp(val) {
            return 'Rp ' + Math.round(val || 0).toLocaleString('id-ID');
        },

        formatNum(val) {
            const num = parseFloat(val) || 0;
            if (Math.floor(num) === num) return num.toLocaleString('id-ID');
            return num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endpush

