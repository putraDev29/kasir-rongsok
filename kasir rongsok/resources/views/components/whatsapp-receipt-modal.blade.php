<!-- In-App WhatsApp & Interactive PDF Document Preview Modal -->
<script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
<script>
    if (window.pdfjsLib) {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}";
    }
</script>

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
     @keydown.escape.window="$store.waGateway.receiptModalOpen = false"
     x-data="{
        activeTab: 'preview',
        isLgScreen: window.innerWidth >= 1024,
        isPdfLoading: false,
        pdfDoc: null,
        totalPages: 0,
        zoomScale: 1.0,
        pdfError: '',
        loadedUrl: '',

        init() {
            window.addEventListener('resize', () => {
                this.isLgScreen = window.innerWidth >= 1024;
            });

            this.$watch('$store.waGateway.receipt.pdfUrl', (newUrl) => {
                if (newUrl && this.$store.waGateway.receiptModalOpen) {
                    this.loadPreview(newUrl);
                }
            });

            this.$watch('$store.waGateway.receiptModalOpen', (isOpen) => {
                if (isOpen) {
                    this.activeTab = 'preview';
                    this.zoomScale = 1.0;
                    this.pdfError = '';
                    document.body.classList.add('overflow-hidden');
                    if (this.$store.waGateway.receipt.pdfUrl) {
                        this.loadPreview(this.$store.waGateway.receipt.pdfUrl);
                    }
                } else {
                    this.cleanup();
                    document.body.classList.remove('overflow-hidden');
                }
            });
        },

        cleanup() {
            this.pdfDoc = null;
            this.totalPages = 0;
            this.loadedUrl = '';
            const container = document.getElementById('waReceiptPdfPages');
            if (container) container.innerHTML = '';
        },

        async loadPreview(url) {
            if (!url) return;
            this.isPdfLoading = true;
            this.pdfError = '';
            this.loadedUrl = url;
            const container = document.getElementById('waReceiptPdfPages');
            if (container) container.innerHTML = '';

            try {
                if (!window.pdfjsLib) {
                    throw new Error('Modul pembaca PDF belum siap. Silakan muat ulang halaman.');
                }
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = '{{ asset('vendor/pdfjs/pdf.worker.min.js') }}';

                const response = await fetch(url, {
                    headers: { 'Accept': 'application/pdf, text/html, */*' },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error('Gagal memuat dokumen PDF dari server (Status ' + response.status + ').');
                }

                const contentType = (response.headers.get('content-type') || '').toLowerCase();
                if (contentType.includes('text/html')) {
                    const htmlText = await response.text();
                    if (container) {
                        container.innerHTML = `<iframe srcdoc='${htmlText.replace(/'/g, '&#39;')}' class='w-full h-[65vh] border-0 rounded-xl bg-white'></iframe>`;
                    }
                    this.isPdfLoading = false;
                    return;
                }

                const arrayBuffer = await response.arrayBuffer();
                const loadingTask = window.pdfjsLib.getDocument({
                    data: arrayBuffer,
                    disableRange: true
                });

                this.pdfDoc = await loadingTask.promise;
                this.totalPages = this.pdfDoc.numPages || 1;
                await this.renderPages();
            } catch (err) {
                console.warn('WA Receipt PDF Preview Error:', err);
                this.pdfError = err.message || 'Gagal memproses pratinjau dokumen PDF.';
            } finally {
                this.isPdfLoading = false;
                if (window.lucide && window.lucide.createIcons) {
                    window.lucide.createIcons();
                }
            }
        },

        async renderPages() {
            if (!this.pdfDoc) return;
            this.isPdfLoading = true;
            const container = document.getElementById('waReceiptPdfPages');
            if (!container) return;
            container.innerHTML = '';

            try {
                for (let pageNum = 1; pageNum <= this.totalPages; pageNum++) {
                    const page = await this.pdfDoc.getPage(pageNum);
                    const containerWidth = container.clientWidth ? Math.max(280, container.clientWidth - 40) : 560;
                    const unscaled = page.getViewport({ scale: 1.0 });
                    const baseScale = Math.min(containerWidth / unscaled.width, 1.35);
                    const scale = baseScale * this.zoomScale;
                    const viewport = page.getViewport({ scale: scale });

                    const pageCard = document.createElement('div');
                    pageCard.className = 'relative flex flex-col items-center my-3 bg-white shadow-md rounded-xl border border-slate-300 p-2 sm:p-3 max-w-full';

                    if (this.totalPages > 1) {
                        const badge = document.createElement('div');
                        badge.className = 'text-[10px] font-bold text-slate-500 mb-1.5 self-start px-2 py-0.5 rounded bg-slate-100 border border-slate-200';
                        badge.textContent = `Halaman ${pageNum} dari ${this.totalPages}`;
                        pageCard.appendChild(badge);
                    }

                    const canvas = document.createElement('canvas');
                    canvas.className = 'rounded max-w-full h-auto bg-white';
                    const outputScale = window.devicePixelRatio || 1;
                    canvas.width = Math.floor(viewport.width * outputScale);
                    canvas.height = Math.floor(viewport.height * outputScale);
                    canvas.style.width = Math.floor(viewport.width) + 'px';
                    canvas.style.height = Math.floor(viewport.height) + 'px';

                    const ctx = canvas.getContext('2d');
                    const transform = outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null;

                    pageCard.appendChild(canvas);
                    container.appendChild(pageCard);

                    await page.render({
                        canvasContext: ctx,
                        transform: transform,
                        viewport: viewport
                    }).promise;
                }
            } catch (e) {
                console.error('Error rendering WA receipt page:', e);
            } finally {
                this.isPdfLoading = false;
            }
        },

        zoomIn() {
            if (this.zoomScale >= 2.2) return;
            this.zoomScale = +(this.zoomScale + 0.2).toFixed(2);
            this.renderPages();
        },

        zoomOut() {
            if (this.zoomScale <= 0.6) return;
            this.zoomScale = +(this.zoomScale - 0.2).toFixed(2);
            this.renderPages();
        },

        zoomReset() {
            this.zoomScale = 1.0;
            this.renderPages();
        },

        printDocument() {
            const canvases = document.querySelectorAll('#waReceiptPdfPages canvas');
            if (!canvases || canvases.length === 0) {
                if (this.$store.waGateway.receipt.pdfUrl) {
                    window.open(this.$store.waGateway.receipt.pdfUrl, '_blank');
                }
                return;
            }
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                window.print();
                return;
            }
            let imgs = '';
            canvases.forEach(c => {
                imgs += `<div style='page-break-after: always; text-align: center; margin-bottom: 20px;'><img src='${c.toDataURL('image/png')}' style='max-width: 100%; height: auto;' /></div>`;
            });
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${this.$store.waGateway.receipt.title || 'Dokumen'}</title>
                    <style>
                        @page { size: auto; margin: 8mm; }
                        body { margin: 0; padding: 0; background: #fff; }
                        div:last-child { page-break-after: auto !important; }
                    </style>
                </head>
                <body>
                    ${imgs}
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                                window.close();
                            }, 300);
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
     }">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="$store.waGateway.receiptModalOpen = false"></div>

    <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-6xl h-[92vh] flex flex-col border border-slate-300"
             @click.away="$store.waGateway.receiptModalOpen = false">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-4 sm:px-6 py-3 bg-slate-50 shrink-0 gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <x-whatsapp-icon class="w-4 h-4 text-white" />
                    </div>
                    <div class="truncate">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 truncate" x-text="$store.waGateway.receipt.title || 'Kirim Dokumen via WhatsApp'"></h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200 shrink-0 hidden sm:inline-block">
                                FORMAT PDF RESMI
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 truncate" x-text="$store.waGateway.receipt.subtitle || 'Pratinjau dokumen asli dan kirimkan berkas PDF ke nomor WhatsApp'"></p>
                    </div>
                </div>

                <!-- Tabs for Mobile / Screen Size Toggle (< 1024px) -->
                <div class="flex items-center gap-1 bg-slate-200/80 p-0.5 rounded-lg text-xs font-semibold lg:hidden shrink-0">
                    <button type="button" 
                            @click="activeTab = 'preview'" 
                            :class="activeTab === 'preview' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md transition-all flex items-center gap-1 cursor-pointer">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-600"></i>
                        <span>Pratinjau PDF</span>
                    </button>
                    <button type="button" 
                            @click="activeTab = 'send'" 
                            :class="activeTab === 'send' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-md transition-all flex items-center gap-1 cursor-pointer">
                        <x-whatsapp-icon class="w-3.5 h-3.5 text-emerald-600" />
                        <span>Kirim WA</span>
                    </button>
                </div>

                <button type="button" @click="$store.waGateway.receiptModalOpen = false" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-200 transition-colors ml-1 cursor-pointer shrink-0" title="Tutup (Esc)">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Body (Dual Column Layout: Preview Canvas on Left, Dispatch Form on Right) -->
            <div class="flex-1 overflow-hidden grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-slate-200 bg-slate-100">
                
                <!-- LEFT COLUMN: In-App Document Preview (Tampilan Sama Persis dengan PDF Asli) -->
                <div x-show="activeTab === 'preview' || isLgScreen" 
                     class="lg:col-span-7 flex flex-col h-full bg-slate-200/90 overflow-hidden">
                    
                    <!-- Preview Top Bar with Zoom & Action Controls -->
                    <div class="bg-white border-b border-slate-200 px-3 py-2 flex items-center justify-between gap-2 shrink-0">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 shrink-0">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                <span>Pratinjau Asli Dokumen</span>
                            </span>
                            <span class="text-[11px] font-medium text-slate-500 truncate" x-text="$store.waGateway.receipt.filename || 'Dokumen.pdf'"></span>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <!-- Zoom Controls -->
                            <div class="flex items-center bg-slate-100 border border-slate-300 rounded-lg p-0.5 shadow-2xs">
                                <button type="button"
                                        @click="zoomOut()"
                                        :disabled="zoomScale <= 0.6"
                                        class="p-1 hover:bg-white text-slate-700 rounded disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                        title="Perkecil (-)">
                                    <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                                </button>
                                <span class="text-[10px] font-bold text-slate-600 px-1 min-w-[36px] text-center"
                                      x-text="Math.round(zoomScale * 100) + '%'">
                                </span>
                                <button type="button"
                                        @click="zoomIn()"
                                        :disabled="zoomScale >= 2.2"
                                        class="p-1 hover:bg-white text-slate-700 rounded disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                        title="Perbesar (+)">
                                    <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button"
                                        @click="zoomReset()"
                                        class="px-1.5 py-0.5 text-[9px] font-semibold text-slate-500 hover:text-slate-800 hover:bg-white rounded border-l border-slate-200 cursor-pointer"
                                        title="Pas Layar">
                                    Fit
                                </button>
                            </div>

                            <!-- Print Button -->
                            <button type="button" 
                                    @click="printDocument()"
                                    class="px-2 py-1 rounded-lg bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-semibold flex items-center gap-1 shadow-xs cursor-pointer"
                                    title="Cetak Dokumen Sekarang">
                                <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-600"></i>
                                <span class="hidden sm:inline">Cetak</span>
                            </button>

                            <!-- Download PDF -->
                            <button type="button" 
                                    @click="$store.waGateway.downloadPdf(true)"
                                    class="px-2 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold flex items-center gap-1 shadow-xs cursor-pointer"
                                    title="Unduh Berkas PDF ke Komputer">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span class="hidden sm:inline">Unduh PDF</span>
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Canvas Rendering Container -->
                    <div class="relative flex-1 overflow-y-auto p-3 sm:p-4 flex flex-col items-center">
                        
                        <!-- Loading State -->
                        <div x-show="isPdfLoading || $store.waGateway.receipt.isLoading" 
                             class="my-auto py-12 text-center space-y-2">
                            <div class="w-8 h-8 border-3 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
                            <p class="text-xs text-slate-600 font-semibold">Menyiapkan pratinjau dokumen PDF asli...</p>
                            <p class="text-[11px] text-slate-400">Tampilan dirender langsung di aplikasi tanpa membuka file eksternal</p>
                        </div>

                        <!-- Error State -->
                        <div x-show="pdfError" 
                             class="p-4 m-auto bg-amber-50 border border-amber-300 rounded-xl text-amber-900 text-xs flex flex-col items-center gap-2 max-w-md text-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                            <span x-text="pdfError"></span>
                            <div class="flex items-center gap-2 mt-2">
                                <button type="button" @click="loadPreview(loadedUrl)" class="px-3 py-1 bg-amber-600 text-white rounded font-bold hover:bg-amber-700">
                                    Coba Lagi
                                </button>
                                <button type="button" @click="$store.waGateway.downloadPdf(true)" class="px-3 py-1 bg-white border border-amber-300 rounded font-semibold text-amber-800">
                                    Unduh Langsung
                                </button>
                            </div>
                        </div>

                        <!-- Canvas Pages Container -->
                        <div id="waReceiptPdfPages" class="w-full flex flex-col items-center"></div>

                    </div>
                </div>

                <!-- RIGHT COLUMN: WhatsApp Dispatch Form -->
                <div x-show="activeTab === 'send' || isLgScreen" 
                     class="lg:col-span-5 flex flex-col h-full bg-white overflow-hidden">
                    
                    <div class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1">
                        
                        <!-- Notifications -->
                        <template x-if="$store.waGateway.receipt.sendSuccess">
                            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                <span>Dokumen PDF Nota berhasil dikirimkan via WhatsApp Gateway!</span>
                            </div>
                        </template>

                        <template x-if="$store.waGateway.receipt.sendError">
                            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
                                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                                <span x-text="$store.waGateway.receipt.sendError"></span>
                            </div>
                        </template>

                        <!-- Destination Phone Number -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-800">Nomor WhatsApp Tujuan:</label>
                                <span class="text-[10px] text-slate-400">Contoh: 08123456789</span>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                </div>
                                <input type="text" 
                                       x-model="$store.waGateway.receipt.phone" 
                                       placeholder="08xxxxxxxxxx"
                                       class="w-full pl-9 pr-3 py-2 text-xs font-mono font-bold rounded-lg border border-slate-300 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50 focus:bg-white transition-colors">
                            </div>
                        </div>

                        <!-- PDF Attachment Card -->
                        <div class="p-3.5 rounded-xl border border-rose-200 bg-rose-50/60 flex items-center justify-between gap-3 shadow-2xs">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-xs">
                                    PDF
                                </div>
                                <div class="truncate">
                                    <div class="text-xs font-bold text-slate-900 truncate">
                                        <span x-text="$store.waGateway.receipt.filename || 'Dokumen.pdf'"></span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 truncate" x-text="$store.waGateway.receipt.fileDescription || 'Dokumen Resmi Kasir Rongsok'"></div>
                                </div>
                            </div>

                            <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer select-none shrink-0">
                                <input type="checkbox" 
                                       x-model="$store.waGateway.receipt.sendAsPdf" 
                                       class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="font-bold text-[11px] text-slate-800">Kirim PDF</span>
                            </label>
                        </div>

                        <!-- Message / Caption Preview -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <x-whatsapp-icon class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>Pesan Keterangan (Caption):</span>
                                </label>
                                <button type="button" 
                                        @click="navigator.clipboard.writeText($store.waGateway.receipt.message); alert('Teks berhasil disalin!');" 
                                        class="text-[11px] text-emerald-700 hover:text-emerald-800 font-semibold flex items-center gap-1 cursor-pointer">
                                    <i data-lucide="copy" class="w-3 h-3"></i>
                                    <span>Salin Teks</span>
                                </button>
                            </div>
                            <textarea rows="7" 
                                      x-model="$store.waGateway.receipt.message" 
                                      placeholder="Pesan pengantar WhatsApp..."
                                      class="w-full p-3 font-mono text-[11px] leading-relaxed rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 shadow-inner resize-y"></textarea>
                        </div>

                        <!-- Gateway Status Indicator -->
                        @if(config('services.whatsapp_gateway.enabled', false))
                            <div class="flex items-center justify-between text-[11px] px-3 py-2 bg-slate-50 rounded-lg border border-slate-200">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="$store.waGateway.connected ? 'bg-emerald-500' : ($store.waGateway.status === 'QR_READY' ? 'bg-amber-500 animate-ping' : 'bg-rose-500')"></span>
                                    <span class="text-slate-600 truncate" x-text="$store.waGateway.connected ? 'Gateway Aktif: ' + ($store.waGateway.phone || 'Online') : ($store.waGateway.status === 'QR_READY' ? 'Gateway Perlu Scan QR' : 'Gateway Offline')"></span>
                                </div>
                                <button type="button" @click="$store.waGateway.isOpen = true" class="text-emerald-700 hover:text-emerald-800 font-bold hover:underline shrink-0 ml-1">
                                    Kelola &raquo;
                                </button>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-[11px] px-3 py-2 bg-emerald-50 text-emerald-900 rounded-lg border border-emerald-200">
                                <x-whatsapp-icon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                <span>Kirim pesan langsung ke WhatsApp penerima dengan menekan tombol <strong>Buka Chat (wa.me)</strong>.</span>
                            </div>
                        @endif

                    </div>

                    <!-- Dispatch Footer Actions inside Right Column -->
                    <div class="p-3 sm:p-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 shrink-0">
                        <button type="button" 
                                @click="$store.waGateway.receiptModalOpen = false" 
                                class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 transition-colors cursor-pointer">
                            Tutup
                        </button>

                        <div class="flex items-center gap-2">
                            <!-- Mobile Switch to Preview Tab Button -->
                            <button type="button" 
                                    x-show="!isLgScreen && activeTab === 'send'"
                                    @click="activeTab = 'preview'" 
                                    class="px-3 py-2 text-xs font-bold rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-100 flex items-center gap-1 cursor-pointer">
                                <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i>
                                <span>Lihat Pratinjau</span>
                            </button>

                            <!-- Manual WhatsApp Web / wa.me Button -->
                            <button type="button" 
                                    @click="$store.waGateway.openWaMe()"
                                    :disabled="!$store.waGateway.receipt.phone"
                                    class="px-3.5 py-2 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer"
                                    title="Buka chat WhatsApp di tab baru dengan pesan nota">
                                <x-whatsapp-icon class="w-3.5 h-3.5 text-white" />
                                <span>Buka Chat (wa.me)</span>
                            </button>

                            @if(config('services.whatsapp_gateway.enabled', false))
                                <!-- Direct Send via Background Gateway Button -->
                                <button type="button" 
                                        @click="$store.waGateway.sendReceipt()"
                                        :disabled="$store.waGateway.receipt.isSending || !$store.waGateway.receipt.phone"
                                        class="px-4 py-2 text-xs font-bold rounded-lg bg-slate-900 hover:bg-slate-800 text-white shadow-xs transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer"
                                        title="Kirim berkas PDF nota otomatis melalui nomor WhatsApp resmi depo">
                                    <i data-lucide="send" class="w-3.5 h-3.5" :class="{ 'animate-spin': $store.waGateway.receipt.isSending }"></i>
                                    <span x-text="$store.waGateway.receipt.isSending ? 'Mengirim PDF...' : ($store.waGateway.receipt.sendAsPdf ? ($store.waGateway.receipt.submitButtonText || 'Kirim PDF via WA') : 'Kirim Pesan via WA')"></span>
                                </button>
                            @endif
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>
