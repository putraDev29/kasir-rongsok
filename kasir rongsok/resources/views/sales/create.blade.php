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
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alamat Tujuan Pengiriman</label>
                    <input type="text" name="buyer_address" x-model="buyerAddress" placeholder="Kawasan Industri / Alamat Pabrik"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Metode Pembayaran</label>
                    <select name="payment_method_id" x-model="paymentMethodId" class="w-full py-2 px-2 text-xs rounded-lg border border-slate-300 focus:ring-blue-500 font-medium">
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}" data-name="{{ $pm->name }}">{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Bank Info for Sales (Task 11) -->
                <div class="sm:col-span-2" x-show="isTransferPayment()">
                    <label class="block text-xs font-semibold text-blue-900 uppercase mb-1">Informasi Rekening Bank Tujuan Transfer (Dicetak di Faktur)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="custom_bank" placeholder="Nama Bank (BCA / Mandiri / BRI)"
                               class="w-full py-1.5 px-3 text-xs rounded-lg border border-blue-300 bg-blue-50/50">
                        <input type="text" name="custom_account_no" placeholder="No. Rek & Atas Nama Rekening"
                               class="w-full py-1.5 px-3 text-xs rounded-lg border border-blue-300 bg-blue-50/50 font-mono">
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
                                    <div class="md:col-span-6">
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Barang & Stok Gudang <span class="text-red-500">*</span>
                                        </label>
                                        <select :name="'items[' + idx + '][scrap_item_id]'" 
                                                x-model="row.scrap_item_id" 
                                                @change="onItemChanged(idx)"
                                                required
                                                class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 font-semibold text-slate-900 bg-white">
                                            <option value="">-- Pilih Barang dari Gudang --</option>
                                            <template x-for="item in masterItems" :key="item.id">
                                                <option :value="item.id" 
                                                        x-text="item.name + ' [Stok: ' + (item.stock_balance ? formatNum(item.stock_balance.current_stock) : 0) + ' ' + (item.unit_type || 'kg') + ']'"></option>
                                            </template>
                                        </select>
                                        <!-- Warning if stock exceeded -->
                                        <div x-show="rowStockQuantity(row) > row.maxStock" class="text-[11px] text-red-600 font-bold mt-1">
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
                                               x-text="isPcsUnit(row) ? 'Jumlah Dijual (Pcs/Satuan) *' : 'Berat Dijual (kg) *'"></label>
                                        <template x-if="isPcsUnit(row)">
                                            <div>
                                                <input type="number" step="1" min="1" 
                                                       :name="'items[' + idx + '][quantity]'" 
                                                       x-model.number="row.quantity" 
                                                       placeholder="Jumlah"
                                                       required
                                                       class="w-full py-2 px-2.5 text-xs text-right rounded-lg border border-amber-300 bg-amber-50/70 focus:ring-2 focus:ring-amber-500 font-mono font-bold text-amber-950">
                                                <input type="hidden" :name="'items[' + idx + '][weight]'" value="0">
                                                <span class="text-[10px] text-amber-700 font-medium block text-right mt-0.5">Jumlah Satuan Fisik</span>
                                            </div>
                                        </template>
                                        <template x-if="!isPcsUnit(row)">
                                            <div>
                                                <input type="number" step="0.01" min="0.01" 
                                                       :name="'items[' + idx + '][weight]'" 
                                                       x-model.number="row.weight" 
                                                       required
                                                       class="w-full py-2 px-2.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 font-mono font-bold text-slate-900 bg-white"
                                                       :class="row.weight > row.maxStock ? 'border-red-500 bg-red-50 text-red-700' : ''">
                                                <input type="hidden" :name="'items[' + idx + '][quantity]'" value="1">
                                                <span class="text-[10px] text-slate-400 font-medium block text-right mt-0.5">Berat Bersih Kirim</span>
                                            </div>
                                        </template>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide mb-1">
                                            Harga Jual Pabrik (Rp) *
                                        </label>
                                        <input type="number" step="100" min="0" 
                                               :name="'items[' + idx + '][sell_price_per_kg]'" 
                                               x-model.number="row.sell_price_per_kg" 
                                               required
                                               class="w-full py-2 px-2.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 font-mono font-bold text-slate-900 bg-white">
                                        <span class="text-[10px] text-slate-400 font-medium block text-right mt-0.5">Harga per kg/pcs</span>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-800 uppercase tracking-wide mb-1 text-right">
                                            Subtotal (Rp)
                                        </label>
                                        <div class="py-2 px-3 text-xs text-right font-mono font-black rounded-lg bg-slate-900 text-white shadow-2xs border border-slate-800"
                                             x-text="formatRp(rowSubtotal(row))">
                                            Rp 0
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-medium block text-right mt-0.5">Total Baris Ini</span>
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
                        <input type="number" step="1000" min="0" name="discount_amount" x-model.number="discountAmount"
                               class="w-32 py-1 px-2 text-xs text-right rounded border border-slate-300 font-mono">
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
                <button type="submit" 
                        :disabled="hasStockError() || rows.length === 0 || subtotalAmount() <= 0"
                        class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold shadow-sm">
                    Simpan & Keluarkan Stok Rongsok
                </button>
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function salesPos() {
    return {
        masterItems: @json($scrapItems),
        paymentMethodsList: @json($paymentMethods),
        unbilledOrders: @json($unbilledDeliveryOrders ?? []),
        selectedDeliveryOrder: @json($selectedDeliveryOrder ?? null),
        
        selectedDoId: '{{ $selectedDeliveryOrder?->id ?? "" }}',
        deliveryOrderId: '{{ $selectedDeliveryOrder?->id ?? "" }}',
        transactionDate: '',
        buyerName: '{{ $selectedDeliveryOrder?->recipient_name ?? "" }}',
        buyerPhone: '{{ $selectedDeliveryOrder?->recipient_phone ?? "" }}',
        buyerAddress: '{{ $selectedDeliveryOrder?->recipient_address ?? "" }}',
        paymentMethodId: '{{ $paymentMethods->first()?->id ?? 1 }}',
        notes: '{{ $selectedDeliveryOrder ? "Sinkron Surat Jalan: " . $selectedDeliveryOrder->delivery_number : "" }}',

        discountAmount: 0,
        rows: [],

        init() {
            if (this.selectedDeliveryOrder && this.selectedDeliveryOrder.items && this.selectedDeliveryOrder.items.length > 0) {
                this.rows = this.selectedDeliveryOrder.items.map(it => {
                    const found = this.masterItems.find(m => m.id == it.scrap_item_id);
                    return {
                        uid: Math.random(),
                        scrap_item_id: it.scrap_item_id || '',
                        item_type_name: it.item_type_name || '',
                        unit_type: it.unit_type || 'kg',
                        quantity: it.quantity || 1,
                        weight: it.weight || 0,
                        sell_price_per_kg: found ? (parseFloat(found.sell_price_per_kg) || 0) : 0,
                        avg_buy_price: found && found.stock_balance ? (parseFloat(found.stock_balance.average_buy_price) || 0) : 0,
                        maxStock: found && found.stock_balance ? (parseFloat(found.stock_balance.current_stock) || 0) : 0
                    };
                });
            } else {
                this.addRow();
            }
        },

        loadFromDeliveryOrder() {
            if (!this.selectedDoId) {
                this.deliveryOrderId = '';
                return;
            }
            window.location.href = '{{ route("sales.create") }}?delivery_order_id=' + this.selectedDoId;
        },

        isTransferPayment() {
            const method = this.paymentMethodsList.find(m => m.id == this.paymentMethodId);
            if (!method) return false;
            const name = (method.name || '').toLowerCase();
            return name.includes('transfer') || name.includes('bank');
        },

        addRow() {
            this.rows.push({
                uid: Date.now() + Math.random(),
                scrap_item_id: '',
                item_type_name: '',
                unit_type: 'kg',
                quantity: 1,
                weight: '',
                sell_price_per_kg: 0,
                avg_buy_price: 0,
                maxStock: 0
            });
            if (window.refreshIcons) window.refreshIcons();
        },

        removeRow(idx) {
            if (this.rows.length > 1) {
                this.rows.splice(idx, 1);
            }
        },

        onItemChanged(idx) {
            const row = this.rows[idx];
            const found = this.masterItems.find(i => i.id == row.scrap_item_id);
            if (found) {
                row.sell_price_per_kg = parseFloat(found.sell_price_per_kg) || 0;
                row.unit_type = found.unit_type || row.unit_type || 'kg';
                row.maxStock = found.stock_balance ? parseFloat(found.stock_balance.current_stock) : 0;
                row.avg_buy_price = found.stock_balance ? parseFloat(found.stock_balance.average_buy_price) : 0;
            } else {
                row.sell_price_per_kg = 0;
                row.maxStock = 0;
                row.avg_buy_price = 0;
            }
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
            return num.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endpush

