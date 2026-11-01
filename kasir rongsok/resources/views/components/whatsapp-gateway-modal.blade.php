<!-- WhatsApp Gateway Management Modal (WPPConnect) -->
<div x-cloak 
     x-show="$store.waGateway.isOpen" 
     class="fixed inset-0 z-50 overflow-y-auto no-print" 
     style="display: none;"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="$store.waGateway.isOpen = false">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="$store.waGateway.isOpen = false"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200"
             x-data="{
                 testPhone: '',
                 testMessage: 'Halo, ini pesan uji coba dari Kasir Rongsok!'
             }"
             x-init="$watch('$store.waGateway.isOpen', val => { if (val) { $store.waGateway.fetchStatus(); } })"
             @click.away="$store.waGateway.isOpen = false">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">WhatsApp Gateway (WPPConnect)</h3>
                        <p class="text-[11px] text-slate-500">Layanan pengiriman nota otomatis & pesan WhatsApp terintegrasi</p>
                    </div>
                </div>
                <button type="button" @click="$store.waGateway.isOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-200/50 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-5">
                
                <!-- Status Card Banner -->
                <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/80">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-xs"
                             :class="{
                                 'bg-emerald-600': $store.waGateway.connected,
                                 'bg-amber-500 animate-pulse': !$store.waGateway.connected && $store.waGateway.status === 'QR_READY',
                                 'bg-rose-500': !$store.waGateway.connected && $store.waGateway.status !== 'QR_READY'
                             }">
                            <template x-if="$store.waGateway.connected">
                                <i data-lucide="radio" class="w-5 h-5"></i>
                            </template>
                            <template x-if="!$store.waGateway.connected && $store.waGateway.status === 'QR_READY'">
                                <i data-lucide="qr-code" class="w-5 h-5"></i>
                            </template>
                            <template x-if="!$store.waGateway.connected && $store.waGateway.status !== 'QR_READY'">
                                <i data-lucide="power-off" class="w-5 h-5"></i>
                            </template>
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-800">Status Gateway:</span>
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 border border-emerald-200': $store.waGateway.connected,
                                          'bg-amber-100 text-amber-800 border border-amber-300': !$store.waGateway.connected && $store.waGateway.status === 'QR_READY',
                                          'bg-rose-100 text-rose-800 border border-rose-200': !$store.waGateway.connected && $store.waGateway.status !== 'QR_READY'
                                      }">
                                    <span x-text="$store.waGateway.connected ? '🟢 Terhubung (Online)' : ($store.waGateway.status === 'QR_READY' ? '🟡 Menunggu Scan QR' : '🔴 Offline / Terputus')"></span>
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">
                                <template x-if="$store.waGateway.connected">
                                    <span>Akun Depo: <strong class="text-slate-800 font-mono" x-text="$store.waGateway.phone || 'Terhubung'"></strong> (<span x-text="$store.waGateway.name || 'Kasir Rongsok'"></span>)</span>
                                </template>
                                <template x-if="!$store.waGateway.connected && $store.waGateway.status === 'QR_READY'">
                                    <span>Silakan scan QR code menggunakan WhatsApp di ponsel kasir/depo.</span>
                                </template>
                                <template x-if="!$store.waGateway.connected && $store.waGateway.status !== 'QR_READY'">
                                    <span>Layanan gateway lokal belum berjalan di port 21465.</span>
                                </template>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <template x-if="$store.waGateway.connected">
                            <button type="button" 
                                    @click="$store.waGateway.logout()"
                                    :disabled="$store.waGateway.isLoggingOut"
                                    class="h-8 px-2.5 text-xs font-semibold rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors flex items-center gap-1">
                                <i data-lucide="log-out" class="w-3.5 h-3.5" :class="{ 'animate-spin': $store.waGateway.isLoggingOut }"></i>
                                <span x-text="$store.waGateway.isLoggingOut ? 'Logout...' : 'Logout'"></span>
                            </button>
                        </template>
                        <button type="button" 
                                @click="$store.waGateway.reconnect()"
                                :disabled="$store.waGateway.isRefreshing"
                                class="h-8 px-2.5 text-xs font-semibold rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 transition-colors flex items-center gap-1">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="{ 'animate-spin': $store.waGateway.isRefreshing }"></i>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>

                <!-- QR Code Scan Area (When Not Connected) -->
                <div x-show="!$store.waGateway.connected" class="space-y-4">
                    <div class="flex flex-col items-center justify-center p-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 text-center">
                        <template x-if="$store.waGateway.qrCode">
                            <div class="space-y-3">
                                <div class="p-3 bg-white rounded-2xl shadow-md border border-slate-200 inline-block">
                                    <img :src="$store.waGateway.qrCode" alt="WhatsApp QR Code" class="w-56 h-56 object-contain mx-auto" />
                                </div>
                                <p class="text-xs font-semibold text-slate-700">Arahkan kamera WhatsApp HP Anda ke QR Code di atas</p>
                            </div>
                        </template>

                        <template x-if="!$store.waGateway.qrCode">
                            <div class="py-6 space-y-2">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
                                    <i data-lucide="qr-code" class="w-6 h-6 animate-pulse"></i>
                                </div>
                                <p class="text-xs text-slate-600 font-medium">
                                    <span x-text="$store.waGateway.status === 'OFFLINE' ? 'WPPConnect Gateway service belum aktif di port 21465.' : 'Sedang menyiapkan QR Code WhatsApp...'"></span>
                                </p>
                                <button type="button" 
                                        @click="$store.waGateway.reconnect()"
                                        class="mt-2 text-xs font-semibold py-1.5 px-3 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">
                                    Minta QR Code Baru
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Steps on how to link phone -->
                    <div class="rounded-xl bg-emerald-50/60 p-4 border border-emerald-200 text-xs text-slate-700 space-y-2">
                        <p class="font-bold text-emerald-900 flex items-center gap-1.5">
                            <i data-lucide="smartphone" class="w-4 h-4 text-emerald-600"></i>
                            <span>Cara Menghubungkan WhatsApp Nomor Depo:</span>
                        </p>
                        <ol class="list-decimal list-inside space-y-1 text-[11px] leading-relaxed text-slate-600 pl-1">
                            <li>Buka aplikasi <strong>WhatsApp</strong> di smartphone nomor depo/kasir Anda.</li>
                            <li>Ketuk ikon <strong>Titik Tiga (⋮)</strong> di Android atau menu <strong>Pengaturan (Settings)</strong> di iPhone.</li>
                            <li>Pilih menu <strong>Perangkat Tertaut (Linked Devices)</strong>.</li>
                            <li>Ketuk <strong>Tautkan Perangkat (Link a Device)</strong>, lalu scan QR Code di atas.</li>
                        </ol>
                    </div>
                </div>

                <!-- Test Message & Quick Tool Area (When Connected) -->
                <div x-show="$store.waGateway.connected" class="space-y-4 rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
                    <div class="flex items-center justify-between pb-2 border-b border-emerald-200/60">
                        <h4 class="text-xs font-bold text-emerald-950 flex items-center gap-1.5">
                            <i data-lucide="send" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Uji Coba Pengiriman Pesan Langsung</span>
                        </h4>
                        <span class="text-[10px] text-emerald-700 font-semibold bg-emerald-100 px-2 py-0.5 rounded">Gateway Aktif</span>
                    </div>

                    <div class="space-y-2.5">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Nomor WhatsApp Tujuan:</label>
                            <input type="text" 
                                   placeholder="Contoh: 081234567890" 
                                   x-model="testPhone"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Isi Pesan:</label>
                            <textarea rows="2" 
                                      x-model="testMessage" 
                                      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <button type="button" 
                                    @click="$store.waGateway.clearChat(testPhone)"
                                    :disabled="!testPhone"
                                    class="text-[11px] text-amber-700 hover:text-amber-800 hover:underline flex items-center gap-1 disabled:opacity-50">
                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                                <span>Reset Antrian Nomor Ini</span>
                            </button>

                            <button type="button" 
                                    @click="$store.waGateway.sendTest(testPhone, testMessage)"
                                    :disabled="$store.waGateway.isSendingTest || !testPhone"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5 disabled:opacity-50">
                                <i data-lucide="send" class="w-3.5 h-3.5" :class="{ 'animate-spin': $store.waGateway.isSendingTest }"></i>
                                <span x-text="$store.waGateway.isSendingTest ? 'Mengirim...' : 'Kirim Uji Coba'"></span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex justify-between items-center bg-slate-50 border-t border-slate-200 px-6 py-3">
                <span class="text-[10px] text-slate-400 font-mono">Port Gateway: 21465</span>
                <button type="button" 
                        @click="$store.waGateway.isOpen = false" 
                        class="px-4 py-2 text-xs font-semibold rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>

