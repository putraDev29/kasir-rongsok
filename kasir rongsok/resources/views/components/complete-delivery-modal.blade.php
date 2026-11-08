<!-- Modal Input Hasil Timbang Pabrik & Konfirmasi Selesai Pengiriman -->
<div x-cloak 
     x-show="$store.deliveryCompletion.isOpen" 
     class="fixed inset-0 z-50 overflow-y-auto no-print" 
     style="display: none;"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="$store.deliveryCompletion.isOpen = false">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="$store.deliveryCompletion.isOpen = false"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-slate-200"
             @click.away="$store.deliveryCompletion.isOpen = false">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                        <i data-lucide="scale" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900">Input Hasil Timbang &amp; Selesaikan Transaksi</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                PENGIRIMAN PABRIK
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Faktur: <strong class="text-slate-800 font-mono" x-text="$store.deliveryCompletion.invoiceNumber"></strong>
                            &bull; Pembeli: <strong class="text-slate-800" x-text="$store.deliveryCompletion.buyerName"></strong>
                        </p>
                    </div>
                </div>
                <button type="button" @click="$store.deliveryCompletion.isOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200/50 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form :action="$store.deliveryCompletion.actionUrl" method="POST" class="p-6 space-y-4">
                @csrf

                <!-- Info Box -->
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2.5">
                    <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-semibold text-amber-950">Konfirmasi Penerimaan Barang Pabrik:</p>
                        <p class="text-[11px] text-amber-800 mt-0.5 leading-relaxed">
                            Saat armada berangkat mengantar barang, berat dan harga jual masih belum pasti/kosong. 
                            Masukkan <strong>berat hasil timbang pabrik (kg)</strong> dan <strong>harga jual final (Rp/kg)</strong> di bawah ini untuk menyelesaikan transaksi penjualan dan surat jalan secara resmi.
                        </p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                <th class="py-2.5 px-3 w-8 text-center">#</th>
                                <th class="py-2.5 px-3">Nama Material Scrap</th>
                                <th class="py-2.5 px-3 w-36 text-right">Berat Final (kg) *</th>
                                <th class="py-2.5 px-3 w-44 text-right">Harga Jual/kg (Rp) *</th>
                                <th class="py-2.5 px-3 w-36 text-right">Subtotal Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in $store.deliveryCompletion.items" :key="item.id || index">
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-2.5 px-3 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                    <td class="py-2.5 px-3">
                                        <input type="hidden" :name="'items[' + index + '][id]'" :value="item.id">
                                        <input type="hidden" :name="'items[' + index + '][scrap_item_id]'" :value="item.scrap_item_id">
                                        <div class="font-bold text-slate-900" x-text="item.name"></div>
                                        <template x-if="item.type">
                                            <span class="text-[10px] text-blue-700 bg-blue-50 px-1.5 py-0.2 rounded border border-blue-200 font-medium" x-text="'• ' + item.type"></span>
                                        </template>
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <div class="relative">
                                            <input type="number" 
                                                   step="0.01" 
                                                   min="0.01" 
                                                   required
                                                   :name="'items[' + index + '][weight]'" 
                                                   x-model.number="item.weight"
                                                   placeholder="0"
                                                   class="w-full py-1.5 px-2.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono font-bold text-slate-900 bg-white">
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">Rp</span>
                                            <input type="text"
                                                   required
                                                   :name="'items[' + index + '][sell_price_per_kg]'"
                                                   x-rupiah="item.price"
                                                   placeholder="0"
                                                   class="w-full py-1.5 pl-8 pr-2.5 text-xs text-right rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono font-bold text-slate-900 bg-white">
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                        <span x-text="formatRupiah((parseFloat(item.weight) || 0) * (parseFloat(item.price) || 0))"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Live Summary Bar -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                    <div class="flex items-center gap-4">
                        <span class="text-slate-500">Total Muatan: <strong class="text-slate-900 font-mono text-sm" x-text="formatNumber($store.deliveryCompletion.totalWeight()) + ' kg'"></strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-600 font-semibold uppercase text-[11px]">Total Tagihan:</span>
                        <span class="text-base font-black font-mono text-emerald-700" x-text="formatRupiah($store.deliveryCompletion.totalAmount())"></span>
                    </div>
                </div>

                <!-- Notes Input -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Catatan Hasil Pembongkaran / Timbangan Pabrik (Opsional):</label>
                    <textarea name="notes" 
                              x-model="$store.deliveryCompletion.notes" 
                              rows="2" 
                              placeholder="Keterangan tambahan dari pabrik penerima, nomor surat timbang pabrik, dsb..."
                              class="w-full p-2.5 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                    <button type="button" 
                            @click="$store.deliveryCompletion.isOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-2 shadow-sm transition-colors cursor-pointer">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Simpan &amp; Selesaikan Transaksi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

