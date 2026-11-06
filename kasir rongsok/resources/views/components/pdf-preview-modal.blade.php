<!-- In-App Interactive PDF Preview Modal (Handle Preview on Tablet & Mobile without Auto-Download) -->
<div x-data="{
        isOpen: false,
        title: 'Pratinjau Dokumen PDF',
        subtitle: 'Pratinjau berkas dokumen resmi sistem kasir',
        url: '',
        filename: 'Dokumen.pdf',
        isLoading: true,
        init() {
            window.openPdfPreview = (url, title = 'Pratinjau Dokumen PDF', filename = 'Dokumen.pdf', subtitle = '') => {
                this.url = url;
                this.title = title;
                this.filename = filename;
                this.subtitle = subtitle || 'Pratinjau berkas sebelum mencetak atau mengunduh.';
                this.isLoading = true;
                this.isOpen = true;
                document.body.classList.add('overflow-hidden');
            };
        },
        close() {
            this.isOpen = false;
            this.url = '';
            document.body.classList.remove('overflow-hidden');
        },
        getDownloadUrl() {
            if (!this.url) return '#';
            return this.url + (this.url.includes('?') ? '&' : '?') + 'download=1';
        },
        printIframe() {
            const frame = document.getElementById('inAppPdfPreviewFrame');
            if (frame && frame.contentWindow) {
                try {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch(e) {
                    window.open(this.url, '_blank');
                }
            }
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
            <div class="flex items-center justify-between border-b border-slate-200 px-4 sm:px-6 py-3 bg-slate-50 shrink-0">
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

                <!-- Top Action Buttons -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button"
                            @click="printIframe()"
                            class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 text-xs font-semibold flex items-center gap-1 shadow-xs"
                            title="Cetak Dokumen">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-600"></i>
                        <span class="hidden sm:inline">Cetak</span>
                    </button>

                    <a :href="getDownloadUrl()"
                       :download="filename"
                       class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-1 shadow-xs"
                       title="Unduh Berkas PDF ke Perangkat">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Unduh PDF</span>
                    </a>

                    <a :href="url"
                       target="_blank"
                       class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold flex items-center gap-1 shadow-xs hidden md:inline-flex"
                       title="Buka PDF di Tab Baru">
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-300"></i>
                        <span>Tab Baru</span>
                    </a>

                    <button type="button"
                            @click="close()"
                            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-200 transition-colors ml-1"
                            title="Tutup (Esc)">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- PDF Container Body -->
            <div class="relative flex-1 w-full bg-slate-100 overflow-hidden">
                <!-- Loading Spinner -->
                <div x-show="isLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 z-10 gap-2">
                    <div class="w-8 h-8 border-3 border-rose-600 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-xs font-semibold text-slate-600">Memuat pratinjau dokumen PDF...</span>
                </div>

                <!-- Iframe Viewer -->
                <iframe id="inAppPdfPreviewFrame"
                        :src="url"
                        @load="isLoading = false"
                        class="w-full h-full border-0 bg-white"
                        title="PDF Document Viewer">
                </iframe>
            </div>

            <!-- Modal Footer (Tablet / Mobile Quick Bar) -->
            <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs shrink-0">
                <span class="text-[11px] text-slate-500 font-mono truncate" x-text="filename"></span>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-slate-400 hidden sm:inline">Tekan Esc atau klik Tutup untuk kembali</span>
                    <button type="button" @click="close()" class="px-3 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-semibold">
                        Tutup Pratinjau
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
