@extends('layouts.app', ['header' => 'Catat Penjualan Rongsok ke Pabrik / Pembeli'])

@section('content')
<div class="max-w-4xl mx-auto space-y-4" x-data="salesPos()">
    
    <div class="flex items-center justify-between">
        <a href="{{ route('sales.index') }}" class="text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1 font-medium">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke Riwayat Penjualan</span>
        </a>
    </div>

    <form action="{{ route('sales.store') }}" method="POST" @submit="handleSubmit($event)">
        @csrf

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
            
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Form Pengiriman & Penjualan Rongsok (Grosir)</h2>
                    <p class="text-xs text-slate-500">Stok barang di gudang akan otomatis dipotong dan diverifikasi tidak minus</p>
                </div>
            </div>

            <!-- Buyer Details -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Pembeli / Pabrik Peleburan *</label>
                    <input type="text" name="buyer_name" required placeholder="Contoh: PT. Krakatau Scrap Metal / Pabrik Peleburan Cikarang"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-blue-500 font-medium">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nomor Telepon Pembeli</label>
                    <input type="text" name="buyer_phone" placeholder="021-..."
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-blue-500 font-mono">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alamat Tujuan Pengiriman</label>
                    <input type="text" name="buyer_address" placeholder="Kawasan Industri / Alamat Pabrik"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Metode Pembayaran</label>
                    <select name="payment_method_id" class="w-full py-2 px-2 text-xs rounded-lg border border-slate-300 focus:ring-blue-500 font-medium">
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Items Table -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase text-slate-800 tracking-wider">Barang yang Dijual</span>
                    <button type="button" @click="addRow()" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded bg-blue-50 text-blue-700 border border-blue-200">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Tambah Barang</span>
                    </button>
                </div>

                <div class="border border-slate-200 rounded-lg overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[10px] uppercase font-bold">
                            <tr>
                                <th class="py-2.5 px-3 min-w-[200px]">Barang Rongsok & Stok Tersedia</th>
                                <th class="py-2.5 px-3 w-32 text-right">Berat Jual (kg) *</th>
                                <th class="py-2.5 px-3 w-32 text-right">Harga Jual/kg (Rp) *</th>
                                <th class="py-2.5 px-3 w-36 text-right font-bold">Subtotal</th>
                                <th class="py-2.5 px-2 w-10 text-center">Hapus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(row, idx) in rows" :key="row.uid">
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 px-3">
                                        <select :name="'items[' + idx + '][scrap_item_id]'" 
                                                x-model="row.scrap_item_id" 
                                                @change="onItemChanged(idx)"
                                                required
                                                class="w-full py-1.5 px-2 text-xs rounded border border-slate-300 focus:ring-blue-500 font-medium">
                                            <option value="">-- Pilih Barang --</option>
                                            <template x-for="item in masterItems" :key="item.id">
                                                <option :value="item.id" 
                                                        x-text="item.name + ' [Stok: ' + (item.stock_balance ? item.stock_balance.current_stock : 0) + ' kg]'"></option>
                                            </template>
                                        </select>
                                        <!-- Warning if stock exceeded -->
                                        <div x-show="row.weight > row.maxStock" class="text-[11px] text-red-600 font-bold mt-1">
                                            ⚠️ Melebihi stok gudang! Maks: <span x-text="row.maxStock"></span> kg
                                        </div>
                                    </td>

                                    <td class="py-2.5 px-3">
                                        <input type="number" step="0.01" min="0.01" 
                                               :name="'items[' + idx + '][weight]'" 
                                               x-model.number="row.weight" 
                                               required
                                               class="w-full py-1.5 px-2 text-xs text-right rounded border border-slate-300 font-mono font-bold"
                                               :class="row.weight > row.maxStock ? 'border-red-500 bg-red-50 text-red-700' : ''">
                                    </td>

                                    <td class="py-2.5 px-3">
                                        <input type="number" step="100" min="0" 
                                               :name="'items[' + idx + '][sell_price_per_kg]'" 
                                               x-model.number="row.sell_price_per_kg" 
                                               required
                                               class="w-full py-1.5 px-2 text-xs text-right rounded border border-slate-300 font-mono">
                                    </td>

                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                        <span x-text="formatRp(rowSubtotal(row))">Rp 0</span>
                                    </td>

                                    <td class="py-2.5 px-2 text-center">
                                        <button type="button" @click="removeRow(idx)" :disabled="rows.length <= 1"
                                                class="p-1 text-slate-400 hover:text-red-600 disabled:opacity-30">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notes & Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Pengiriman / No. Pol Truk</label>
                    <textarea name="notes" rows="3" placeholder="Nomor polisi armada truk pengangkut, supir, surat jalan pabrik..."
                              class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-blue-500"></textarea>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Total Berat Dijual:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="totalWeight().toFixed(2) + ' kg'">0 kg</span>
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
                        :disabled="hasStockError() || rows.length === 0 || totalWeight() <= 0"
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
        discountAmount: 0,
        rows: [
            { uid: 1, scrap_item_id: '', weight: '', sell_price_per_kg: 0, avg_buy_price: 0, maxStock: 0 }
        ],

        addRow() {
            this.rows.push({
                uid: Date.now() + Math.random(),
                scrap_item_id: '',
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
                row.maxStock = found.stock_balance ? parseFloat(found.stock_balance.current_stock) : 0;
                row.avg_buy_price = found.stock_balance ? parseFloat(found.stock_balance.average_buy_price) : 0;
            } else {
                row.sell_price_per_kg = 0;
                row.maxStock = 0;
                row.avg_buy_price = 0;
            }
        },

        rowSubtotal(row) {
            const w = parseFloat(row.weight) || 0;
            const p = parseFloat(row.sell_price_per_kg) || 0;
            return Math.round(w * p);
        },

        totalWeight() {
            return this.rows.reduce((acc, r) => acc + (parseFloat(r.weight) || 0), 0);
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
                const w = parseFloat(r.weight) || 0;
                const buy = parseFloat(r.avg_buy_price) || 0;
                totalCogs += (w * buy);
            });
            return Math.max(0, this.finalAmount() - totalCogs);
        },

        hasStockError() {
            return this.rows.some(r => {
                const w = parseFloat(r.weight) || 0;
                return w > r.maxStock;
            });
        },

        handleSubmit(e) {
            if (this.hasStockError()) {
                e.preventDefault();
                alert('Terdapat barang dengan berat melebihi stok yang tersedia di gudang!');
                return;
            }
        },

        formatRp(val) {
            return window.formatRupiah ? window.formatRupiah(val) : 'Rp ' + (val || 0).toLocaleString('id-ID');
        }
    };
}
</script>
@endpush

