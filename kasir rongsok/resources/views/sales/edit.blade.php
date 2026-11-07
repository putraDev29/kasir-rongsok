@extends('layouts.app', ['header' => 'Finalisasi Penjualan & Isi Harga Jual'])

@section('content')
<div class="max-w-5xl mx-auto space-y-4" x-data="editSalesPos()">
    
    <div class="flex items-center justify-between">
        <a href="{{ route('sales.show', $sale) }}" class="text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1 font-medium">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke Detail Penjualan</span>
        </a>
        <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                STATUS: DRAFT
            </span>
        </div>
    </div>

    <!-- Alert Informasi Draft Kasir -->
    <div class="p-4 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 text-amber-900 shadow-xs flex items-start gap-3">
        <div class="p-2 rounded-lg bg-amber-500 text-white shrink-0 mt-0.5">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
        </div>
        <div class="text-xs space-y-1">
            <div class="font-bold text-sm text-amber-950">Draft Penjualan dari Kasir: {{ $sale->user?->name ?? 'Kasir Depo' }}</div>
            <p class="leading-relaxed text-amber-900">
                Penjualan <strong>{{ $sale->invoice_number }}</strong> dicatat tanpa harga jual. Silakan tentukan <strong>Harga Jual Pabrik (Rp)</strong> untuk setiap barang rongsok di bawah ini. Setelah Anda menyimpan, status transaksi akan otomatis berubah menjadi resmi (<strong>{{ $sale->isPickup() ? 'Selesai' : 'Menunggu Kirim / Selesai' }}</strong>).
            </p>
        </div>
    </div>

    <form action="{{ route('sales.update', $sale) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
            
            <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Form Penetapan Harga Jual & Finalisasi Nota</h2>
                    <p class="text-xs text-slate-500">Nomor Nota: <span class="font-mono font-bold text-slate-800">{{ $sale->invoice_number }}</span> • Tanggal Input: {{ $sale->transaction_date->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <!-- Informasi Pembeli & Tipe Penyerahan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Nama Pembeli / Pabrik *
                    </label>
                    <input type="text" name="buyer_name" value="{{ old('buyer_name', $sale->buyer_name) }}" required
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 font-bold bg-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        No. Telepon / WhatsApp Pembeli
                    </label>
                    <input type="text" name="buyer_phone" value="{{ old('buyer_phone', $sale->buyer_phone) }}"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-blue-500 font-mono">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Alamat Pabrik / Tujuan Pengiriman
                    </label>
                    <input type="text" name="buyer_address" value="{{ old('buyer_address', $sale->buyer_address) }}"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Metode Penyerahan Barang *
                    </label>
                    <select name="delivery_type" x-model="deliveryType"
                            class="w-full py-2 px-3 text-xs font-bold rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-blue-500">
                        <option value="pickup">Diambil Sendiri oleh Pembeli (Langsung Selesai)</option>
                        <option value="delivery">Dengan Pengiriman Armada Depo (Perlu Surat Jalan)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Metode Pembayaran
                    </label>
                    <select name="payment_method_id" x-model="paymentMethodId"
                            class="w-full py-2 px-3 text-xs font-bold rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-blue-500">
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}" {{ $sale->payment_method_id == $pm->id ? 'selected' : '' }}>
                                {{ $pm->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tabel Barang Penjualan & Input Harga Jual -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="boxes" class="w-4 h-4 text-blue-600"></i>
                        <span>Daftar Barang Fisik yang Dijual (Silakan Tentukan Harga)</span>
                    </h3>
                    <span class="text-[11px] font-bold text-slate-500 font-mono">
                        {{ $sale->items->count() }} Jenis Barang
                    </span>
                </div>

                <div class="space-y-3 bg-slate-50/70 p-3 rounded-xl border border-slate-200">
                    <template x-for="(row, idx) in rows" :key="row.id">
                        <div class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-blue-300 transition-all overflow-hidden">
                            <!-- Card Header -->
                            <div class="px-3.5 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded bg-slate-800 text-white font-mono font-bold text-[11px] flex items-center justify-center" x-text="idx + 1">1</span>
                                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wide" x-text="row.name">Nama Barang</span>
                                    <span x-show="row.item_type_name" class="text-[11px] text-slate-500 font-normal" x-text="'(' + row.item_type_name + ')'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1 bg-white px-2.5 py-0.5 rounded border border-slate-200">
                                        <span class="text-[10px] uppercase font-bold text-slate-400">Subtotal:</span>
                                        <span class="text-xs font-black font-mono text-blue-700" x-text="formatRp(rowSubtotal(row))">Rp 0</span>
                                    </div>
                                    <div class="flex items-center gap-1 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        <span class="text-[10px] uppercase font-bold text-emerald-700">Laba:</span>
                                        <span class="text-[11px] font-bold font-mono text-emerald-800" x-text="formatRp(rowProfit(row))">Rp 0</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-3.5 grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                <input type="hidden" :name="'items[' + idx + '][id]'" :value="row.id">

                                <!-- Berat / Qty -->
                                <div class="sm:col-span-4">
                                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-0.5">
                                        Jumlah / Berat Fisik
                                    </label>
                                    <div class="py-2 px-3 bg-slate-100 rounded-lg text-xs font-mono font-bold text-slate-800 border border-slate-200">
                                        <span x-text="row.unit_type === 'pcs' || row.unit_type === 'satuan' ? formatNum(row.quantity) + ' ' + (row.unit_type || 'pcs') : formatNum(row.weight) + ' kg'"></span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-mono block mt-0.5" x-text="'Harga Beli Modal: ' + formatRp(row.buy_price_snapshot) + '/' + row.unit_type"></span>
                                </div>

                                <!-- Input Harga Jual Pabrik -->
                                <div class="sm:col-span-5">
                                    <label class="block text-[11px] font-bold text-blue-900 uppercase tracking-wide mb-0.5 flex items-center justify-between">
                                        <span>Harga Jual Pabrik (Rp) *</span>
                                        <span class="text-[10px] text-blue-600 font-normal">Wajib diisi</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">Rp</span>
                                        <input type="number" step="100" min="1" 
                                               :name="'items[' + idx + '][sell_price_per_kg]'" 
                                               x-model.number="row.sell_price_per_kg" 
                                               required
                                               placeholder="Ketik harga jual per kg/pcs..."
                                               class="w-full pl-8 pr-3 py-2 text-sm text-right rounded-lg border-2 border-blue-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-500 font-mono font-black text-slate-900 bg-blue-50/30 shadow-xs">
                                    </div>
                                    <span class="text-[10px] text-slate-500 font-medium block text-right mt-0.5" x-text="'Dihitung per ' + (row.unit_type || 'kg')"></span>
                                </div>

                                <!-- Subtotal Nilai -->
                                <div class="sm:col-span-3 text-right">
                                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-0.5">
                                        Subtotal Baris
                                    </label>
                                    <div class="py-2 px-3 text-xs text-right font-mono font-black rounded-lg bg-slate-900 text-white shadow-2xs border border-slate-800"
                                         x-text="formatRp(rowSubtotal(row))">
                                        Rp 0
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-medium block mt-0.5">Nilai Jual</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Ringkasan Keuangan & Laba -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Catatan Tambahan</label>
                    <textarea name="notes" rows="3" class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 bg-white" placeholder="Catatan transaksi bila ada...">{{ old('notes', $sale->notes) }}</textarea>
                </div>

                <div class="space-y-2 text-xs font-sans">
                    <div class="flex justify-between text-slate-600">
                        <span>Total Berat Bersih:</span>
                        <span class="font-mono font-bold text-slate-900">{{ \App\Services\FormatHelper::weight($sale->total_weight) }} kg</span>
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
                        <span>TOTAL TAGIHAN RESMI:</span>
                        <span class="font-mono text-xl font-black text-blue-700" x-text="formatRp(finalAmount())">Rp 0</span>
                    </div>

                    <div class="pt-2 border-t border-dotted border-slate-200 flex justify-between text-[11px] text-emerald-700 font-medium">
                        <span>Estimasi Laba Kotor:</span>
                        <span class="font-mono font-bold" x-text="formatRp(estimatedProfit())">Rp 0</span>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('sales.show', $sale) }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                    Batal
                </a>
                <button type="submit" 
                        :disabled="subtotalAmount() <= 0"
                        class="px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>Simpan & Finalisasi Penjualan Resmi</span>
                </button>
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function editSalesPos() {
    return {
        deliveryType: '{{ $sale->delivery_type ?? "pickup" }}',
        paymentMethodId: '{{ $sale->payment_method_id ?? 1 }}',
        discountAmount: {{ (float) ($sale->discount_amount ?? 0) }},
        rows: [
            @foreach($sale->items as $it)
            {
                id: {{ $it->id }},
                scrap_item_id: {{ $it->scrap_item_id }},
                name: '{{ addslashes($it->item_name_snapshot) }}',
                item_type_name: '{{ addslashes($it->item_type_name ?? "") }}',
                unit_type: '{{ $it->unit_type ?? "kg" }}',
                quantity: {{ (float) ($it->quantity ?? 1) }},
                weight: {{ (float) ($it->weight ?? 0) }},
                buy_price_snapshot: {{ (float) ($it->buy_price_snapshot ?? 0) }},
                sell_price_per_kg: {{ (float) ($it->sell_price_per_kg > 0 ? $it->sell_price_per_kg : ($it->scrapItem?->sell_price_per_kg ?? 0)) }}
            },
            @endforeach
        ],

        formatRp(val) {
            return 'Rp ' + (new Intl.NumberFormat('id-ID').format(Math.round(val || 0)));
        },

        formatNum(val) {
            return new Intl.NumberFormat('id-ID').format(val || 0);
        },

        isPcsUnit(row) {
            const u = String(row.unit_type).toLowerCase().trim();
            return u === 'pcs' || u === 'satuan' || u === 'unit';
        },

        rowStockQuantity(row) {
            return this.isPcsUnit(row) ? (parseFloat(row.quantity) || 1) : (parseFloat(row.weight) || 0);
        },

        rowSubtotal(row) {
            const qty = this.rowStockQuantity(row);
            const p = parseFloat(row.sell_price_per_kg) || 0;
            return Math.round(qty * p);
        },

        rowProfit(row) {
            const qty = this.rowStockQuantity(row);
            const sub = this.rowSubtotal(row);
            const modal = qty * (parseFloat(row.buy_price_snapshot) || 0);
            return Math.round(sub - modal);
        },

        subtotalAmount() {
            return this.rows.reduce((acc, r) => acc + this.rowSubtotal(r), 0);
        },

        finalAmount() {
            return Math.max(0, this.subtotalAmount() - (parseFloat(this.discountAmount) || 0));
        },

        estimatedProfit() {
            return this.rows.reduce((acc, r) => acc + this.rowProfit(r), 0) - (parseFloat(this.discountAmount) || 0);
        }
    }
}
</script>
@endpush
