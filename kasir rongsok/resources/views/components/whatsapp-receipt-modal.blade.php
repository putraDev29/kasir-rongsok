<!-- WhatsApp Transaction Receipt / Invoice Sender Modal -->
<div x-cloak 
     x-show="$store.waGateway.receiptModalOpen" 
     class="fixed inset-0 z-50 overflow-y-auto no-print" 
     style="display: none;"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="$store.waGateway.receiptModalOpen = false">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="$store.waGateway.receiptModalOpen = false"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200"
             @click.away="$store.waGateway.receiptModalOpen = false">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                        <x-whatsapp-icon class="w-4 h-4 text-white" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900" x-text="$store.waGateway.receipt.title || 'Kirim Nota PDF via WhatsApp'"></h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200" x-text="$store.waGateway.receipt.badgeText || 'FORMAT PDF'">
                                FORMAT PDF
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500" x-text="$store.waGateway.receipt.subtitle || 'Kirim berkas dokumen PDF resmi langsung ke nomor WhatsApp'"></p>
                    </div>
                </div>
                <button type="button" @click="$store.waGateway.receiptModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-200/50 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4">
                
                <!-- Loading State -->
                <div x-show="$store.waGateway.receipt.isLoading" class="py-8 text-center space-y-2">
                    <div class="w-8 h-8 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <p class="text-xs text-slate-500 font-medium">Sedang menyiapkan berkas PDF nota timbangan...</p>
                </div>

                <!-- Content Area -->
                <div x-show="!$store.waGateway.receipt.isLoading" class="space-y-4">
                    
                    <!-- Alert Notifications -->
                    <template x-if="$store.waGateway.receipt.sendSuccess">
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-semibold flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                            <span>Berkas Dokumen PDF Nota berhasil dikirimkan via WhatsApp Gateway!</span>
                        </div>
                    </template>

                    <template x-if="$store.waGateway.receipt.sendError">
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold flex items-center gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                            <span x-text="$store.waGateway.receipt.sendError"></span>
                        </div>
                    </template>

                    <!-- Destination Phone -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">Nomor WhatsApp Tujuan:</label>
                            <span class="text-[10px] text-slate-400">Contoh: 08123456789 atau 62812...</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                            </div>
                            <input type="text" 
                                   x-model="$store.waGateway.receipt.phone" 
                                   placeholder="08xxxxxxxxxx"
                                   class="w-full pl-9 pr-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- PDF Attachment Card -->
                    <div class="p-3.5 rounded-xl border border-rose-200 bg-rose-50/50 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-xs">
                                PDF
                            </div>
                            <div class="truncate">
                                <div class="text-xs font-bold text-slate-900 truncate">
                                    <span x-text="$store.waGateway.receipt.filename || (($store.waGateway.receipt.receiptData?.invoiceNumber || $store.waGateway.receipt.receiptData?.paymentNumber || 'Dokumen') + '.pdf')"></span>
                                </div>
                                <div class="text-[11px] text-slate-500" x-text="$store.waGateway.receipt.fileDescription || 'Dokumen PDF Resmi Kasir Rongsok (Format A5)'"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer select-none">
                                <input type="checkbox" 
                                       x-model="$store.waGateway.receipt.sendAsPdf" 
                                       class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="font-semibold text-[11px]">Kirim PDF</span>
                            </label>

                            <button type="button" 
                                    @click="$store.waGateway.downloadPdf()"
                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-1 shadow-xs"
                                    title="Tampilkan preview berkas PDF di tab baru sebelum unduh">
                                <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i>
                                <span>Preview PDF</span>
                            </button>
                        </div>
                    </div>

                    <!-- Message / Caption Preview -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                <x-whatsapp-icon class="w-3.5 h-3.5 text-emerald-600" />
                                <span>Keterangan Pesan WhatsApp (Caption):</span>
                            </label>
                            <button type="button" 
                                    @click="navigator.clipboard.writeText($store.waGateway.receipt.message); alert('Teks berhasil disalin!');" 
                                    class="text-[11px] text-emerald-700 hover:text-emerald-800 font-semibold flex items-center gap-1">
                                <i data-lucide="copy" class="w-3 h-3"></i>
                                <span>Salin Teks</span>
                            </button>
                        </div>
                        <textarea rows="6" 
                                  x-model="$store.waGateway.receipt.message" 
                                  class="w-full p-3 font-mono text-[11px] leading-relaxed rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 shadow-inner resize-y"></textarea>
                    </div>

                    <!-- Gateway Status Indicator -->
                    @if(config('services.whatsapp_gateway.enabled', false))
                        <div class="flex items-center justify-between text-[11px] px-3 py-2 bg-slate-50 rounded-lg border border-slate-200">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" :class="$store.waGateway.connected ? 'bg-emerald-500' : ($store.waGateway.status === 'QR_READY' ? 'bg-amber-500 animate-ping' : 'bg-rose-500')"></span>
                                <span class="text-slate-600" x-text="$store.waGateway.connected ? 'Gateway Terhubung: ' + ($store.waGateway.phone || 'Online') : ($store.waGateway.status === 'QR_READY' ? 'Gateway Perlu Scan QR' : 'Gateway Offline')"></span>
                            </div>
                            <button type="button" @click="$store.waGateway.isOpen = true" class="text-emerald-700 hover:text-emerald-800 font-bold hover:underline">
                                Kelola Gateway &raquo;
                            </button>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-[11px] px-3 py-2 bg-emerald-50 text-emerald-900 rounded-lg border border-emerald-200">
                            <x-whatsapp-icon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                            <span>Kirim pesan langsung ke WhatsApp penerima dengan menekan tombol <strong>Buka Chat (wa.me)</strong> di bawah.</span>
                        </div>
                    @endif

                </div>

            </div>

            <!-- Modal Footer with Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-2 bg-slate-50 border-t border-slate-200 px-6 py-3">
                <button type="button" 
                        @click="$store.waGateway.receiptModalOpen = false" 
                        class="w-full sm:w-auto px-4 py-2 text-xs font-semibold rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                    Tutup
                </button>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <!-- Manual WhatsApp Web / wa.me Button -->
                    <button type="button" 
                            @click="$store.waGateway.openWaMe()"
                            :disabled="!$store.waGateway.receipt.phone"
                            class="flex-1 sm:flex-none px-4 py-2 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50"
                            title="Buka chat WhatsApp di tab baru dengan pesan nota">
                        <x-whatsapp-icon class="w-3.5 h-3.5 text-white" />
                        <span>Buka Chat (wa.me)</span>
                    </button>

                    @if(config('services.whatsapp_gateway.enabled', false))
                        <!-- Direct Send via Background Gateway Button -->
                        <button type="button" 
                                @click="$store.waGateway.sendReceipt()"
                                :disabled="$store.waGateway.receipt.isSending || !$store.waGateway.receipt.phone"
                                class="flex-1 sm:flex-none px-4 py-2 text-xs font-bold rounded-lg bg-slate-900 hover:bg-slate-800 text-white shadow-xs transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50"
                                title="Kirim berkas PDF nota otomatis melalui nomor WhatsApp resmi depo">
                            <i data-lucide="send" class="w-3.5 h-3.5" :class="{ 'animate-spin': $store.waGateway.receipt.isSending }"></i>
                            <span x-text="$store.waGateway.receipt.isSending ? 'Mengirim PDF...' : ($store.waGateway.receipt.sendAsPdf ? ($store.waGateway.receipt.submitButtonText || 'Kirim Nota PDF via WA') : 'Kirim Pesan via WA')"></span>
                        </button>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
