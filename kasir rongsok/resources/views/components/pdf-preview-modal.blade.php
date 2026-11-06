<!-- In-App Interactive PDF Preview Modal (Handle Preview on Tablet & Mobile without Auto-Download using PDF.js Canvas) -->
<script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
<script>
    if (window.pdfjsLib) {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}";
    }
</script>

<div x-data="{
        isOpen: false,
        isLoading: false,
        errorMessage: '',
        title: 'Pratinjau Dokumen PDF',
        subtitle: 'Pratinjau berkas dokumen resmi sistem kasir',
        url: '',
        filename: 'Dokumen.pdf',
        totalPages: 0,
        zoomScale: 1.0,
        pdfDoc: null,

        init() {
            window.openPdfPreview = (url, title = 'Pratinjau Dokumen PDF', filename = 'Dokumen.pdf', subtitle = '') => {
                this.title = title;
                this.filename = filename;
                this.subtitle = subtitle || 'Pratinjau berkas sebelum mencetak atau mengunduh.';
                this.url = url;
                this.zoomScale = 1.0;
                this.errorMessage = '';
                this.isOpen = true;
                document.body.classList.add('overflow-hidden');
                this.loadDocument(url);
            };
        },

        close() {
            this.isOpen = false;
            this.url = '';
            this.pdfDoc = null;
            this.totalPages = 0;
            this.errorMessage = '';
            const container = document.getElementById('pdfPagesContainer');
            if (container) container.innerHTML = '';
            document.body.classList.remove('overflow-hidden');
        },

        getDownloadUrl() {
            if (!this.url) return '#';
            return this.url + (this.url.includes('?') ? '&' : '?') + 'download=1';
        },

        async loadDocument(url) {
            this.isLoading = true;
            this.errorMessage = '';
            this.totalPages = 0;
            const container = document.getElementById('pdfPagesContainer');
            if (container) container.innerHTML = '';

            try {
                if (!window.pdfjsLib) {
                    throw new Error('Modul pembaca PDF belum siap. Silakan muat ulang halaman.');
                }
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = '{{ asset('vendor/pdfjs/pdf.worker.min.js') }}';

                // Fetch PDF bytes in-memory (prevents tablet / mobile browser from auto-downloading)
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
                    this.renderHtmlFallback(htmlText);
                    this.isLoading = false;
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
                console.error('PDF Preview Error:', err);
                this.errorMessage = err.message || 'Terjadi kesalahan saat memproses pratinjau dokumen PDF.';
            } finally {
                this.isLoading = false;
                if (window.lucide && window.lucide.createIcons) {
                    window.lucide.createIcons();
                }
            }
        },

        async renderPages() {
            if (!this.pdfDoc) return;
            this.isLoading = true;
            const container = document.getElementById('pdfPagesContainer');
            if (!container) return;
            container.innerHTML = '';

            try {
                for (let pageNum = 1; pageNum <= this.totalPages; pageNum++) {
                    const page = await this.pdfDoc.getPage(pageNum);
                    
                    const containerWidth = container.clientWidth ? Math.max(300, container.clientWidth - 48) : 750;
                    const unscaled = page.getViewport({ scale: 1.0 });
                    
                    const baseScale = Math.min(containerWidth / unscaled.width, 1.35);
                    const scale = baseScale * this.zoomScale;
                    const viewport = page.getViewport({ scale: scale });

                    const pageCard = document.createElement('div');
                    pageCard.className = 'relative flex flex-col items-center my-3 bg-white shadow-lg rounded-xl border border-slate-300 p-2 sm:p-4 max-w-full';

                    if (this.totalPages > 1) {
                        const badge = document.createElement('div');
                        badge.className = 'text-[11px] font-bold text-slate-500 mb-2 self-start px-2 py-0.5 rounded bg-slate-100 border border-slate-200';
                        badge.textContent = `Halaman ${pageNum} dari ${this.totalPages}`;
                        pageCard.appendChild(badge);
                    }

                    const canvas = document.createElement('canvas');
                    canvas.className = 'rounded shadow-xs max-w-full h-auto bg-white';
                    
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
                console.error('Error rendering page:', e);
            } finally {
                this.isLoading = false;
            }
        },

        renderHtmlFallback(htmlContent) {
            const container = document.getElementById('pdfPagesContainer');
            if (!container) return;
            container.innerHTML = `
                <div class='w-full h-full bg-white rounded-xl shadow-md p-4 overflow-auto border border-slate-300'>
                    <iframe srcdoc='${htmlContent.replace(/'/g, '&#39;')}' class='w-full h-[70vh] border-0'></iframe>
                </div>
            `;
        },

        zoomIn() {
            if (this.zoomScale >= 2.4) return;
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
            const canvases = document.querySelectorAll('#pdfPagesContainer canvas');
            if (!canvases || canvases.length === 0) {
                window.open(this.url, '_blank');
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
                    <title>${this.title || 'Dokumen'}</title>
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
     }"
     x-init="init()"
     @keydown.escape.window="if (isOpen) close()"
     x-cloak
     x-show="isOpen"
     class="fixed inset-0 z-50 overflow-y-auto no-print"
     style="display: none;">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" @click="close()"></div>

    <!-- Modal Dialog -->
    <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-5xl h-[92vh] flex flex-col border border-slate-300"
             @click.away="close()">

            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-3 sm:px-6 py-2.5 sm:py-3 bg-slate-50 shrink-0 gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-xs">
                        PDF
                    </div>
                    <div class="truncate">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 truncate" x-text="title"></h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 shrink-0 hidden sm:inline-block">
                                MODE PRATINJAU
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 truncate" x-text="subtitle"></p>
                    </div>
                </div>

                <!-- Top Action Controls -->
                <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
                    <!-- Zoom Controls -->
                    <div class="flex items-center bg-white border border-slate-300 rounded-lg p-0.5 shadow-2xs mr-1 hidden sm:flex">
                        <button type="button"
                                @click="zoomOut()"
                                :disabled="zoomScale <= 0.6"
                                class="p-1 hover:bg-slate-100 text-slate-700 rounded disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                title="Perkecil (-)">
                            <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                        </button>
                        <span class="text-[11px] font-bold text-slate-600 px-1.5 min-w-[42px] text-center"
                              x-text="Math.round(zoomScale * 100) + '%'">
                        </span>
                        <button type="button"
                                @click="zoomIn()"
                                :disabled="zoomScale >= 2.4"
                                class="p-1 hover:bg-slate-100 text-slate-700 rounded disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                title="Perbesar (+)">
                            <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i>
                        </button>
                        <button type="button"
                                @click="zoomReset()"
                                class="px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded border-l border-slate-200 cursor-pointer"
                                title="Pas Layar (Fit)">
                            Fit
                        </button>
                    </div>

                    <!-- Print Button -->
                    <button type="button"
                            @click="printDocument()"
                            class="px-2 sm:px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 text-xs font-semibold flex items-center gap-1 shadow-xs cursor-pointer"
                            title="Cetak Dokumen">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-600"></i>
                        <span class="hidden sm:inline">Cetak</span>
                    </button>

                    <!-- Explicit Download Button -->
                    <a :href="getDownloadUrl()"
                       :download="filename"
                       class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-1 shadow-xs cursor-pointer"
                       title="Unduh Berkas PDF Resmi ke Perangkat">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Unduh PDF</span>
                    </a>

                    <!-- Open in New Tab (Desktop) -->
                    <a :href="url"
                       target="_blank"
                       class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold flex items-center gap-1 shadow-xs hidden md:inline-flex"
                       title="Buka PDF di Tab Baru">
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-300"></i>
                        <span>Tab Baru</span>
                    </a>

                    <!-- Close Button -->
                    <button type="button"
                            @click="close()"
                            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-200 transition-colors ml-1 cursor-pointer"
                            title="Tutup (Esc)">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- PDF Container Body -->
            <div class="relative flex-1 w-full bg-slate-200/90 overflow-hidden flex flex-col">
                <!-- Loading Spinner Overlay -->
                <div x-show="isLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 z-20 gap-2 backdrop-blur-2xs">
                    <div class="w-8 h-8 border-3 border-rose-600 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-semibold text-slate-700">Menyiapkan pratinjau dokumen PDF...</span>
                    <span class="text-[11px] text-slate-400">Berkas hanya ditampilkan di layar tanpa auto-download</span>
                </div>

                <!-- Error Alert Box -->
                <div x-show="errorMessage" class="p-4 m-4 bg-amber-50 border border-amber-300 rounded-xl text-amber-900 text-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0"></i>
                        <span x-text="errorMessage"></span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" @click="loadDocument(url)" class="px-3 py-1 bg-amber-600 text-white rounded font-bold hover:bg-amber-700">
                            Coba Lagi
                        </button>
                        <a :href="getDownloadUrl()" :download="filename" class="px-3 py-1 bg-white border border-amber-300 rounded font-semibold text-amber-800 hover:bg-amber-100">
                            Unduh Langsung
                        </a>
                    </div>
                </div>

                <!-- Interactive Scrollable Canvas Container -->
                <div id="pdfPagesContainer"
                     class="flex-1 w-full overflow-y-auto p-3 sm:p-6 flex flex-col items-center">
                    <!-- Dynamic PDF.js Canvas Pages will be injected here -->
                </div>
            </div>

            <!-- Modal Footer (Tablet / Mobile Quick Bar) -->
            <div class="px-3 sm:px-4 py-2 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs shrink-0">
                <div class="flex items-center gap-2 truncate">
                    <span class="text-[11px] text-slate-600 font-mono font-semibold truncate" x-text="filename"></span>
                    <template x-if="totalPages > 0">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 shrink-0">
                            <span x-text="totalPages"></span> Halaman
                        </span>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-slate-400 hidden sm:inline">Tekan Esc atau klik Tutup untuk kembali</span>
                    <button type="button" @click="close()" class="px-3 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-semibold cursor-pointer">
                        Tutup Pratinjau
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
