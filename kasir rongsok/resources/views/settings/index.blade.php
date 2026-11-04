@extends('layouts.app', ['header' => 'Pengaturan Usaha, Tampilan & Nota'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        
        <div class="border-b border-slate-200 pb-4 mb-6">
            <h2 class="text-lg font-black text-slate-900 tracking-tight">Pengaturan Identitas Usaha, Tampilan & Cetak Nota</h2>
            <p class="text-xs text-slate-500 mt-1">Sesuaikan nama yang tampil di sidebar/header, logo usaha, profil depo, serta teks peringatan di nota transaksi</p>
        </div>

        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- BAGIAN 1: IDENTITAS HEADER SIDEBAR & LOGO -->
            <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
                    <div class="w-6 h-6 rounded bg-emerald-600 text-white flex items-center justify-center">
                        <i data-lucide="layout" class="w-3.5 h-3.5"></i>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">1. Identitas Tampilan Header Sidebar & Logo Aplikasi</h3>
                </div>

                <!-- Logo Section -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Logo Aplikasi / Depo</label>
                        <p class="text-[11px] text-slate-500 mb-2">Tampil di header sidebar, kop nota, dan halaman login. Format: PNG, JPG, WebP, SVG (Maks. 2MB).</p>
                        
                        @if(\App\Models\BusinessSetting::hasLogo())
                            <div class="p-3 bg-white rounded-xl border border-slate-300 inline-block shadow-sm">
                                <img src="{{ \App\Models\BusinessSetting::logoUrl() }}" alt="Logo Usaha" class="h-16 w-auto object-contain">
                            </div>
                            <div class="mt-2">
                                <label class="inline-flex items-center gap-1.5 text-xs text-red-600 font-semibold cursor-pointer">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                    <span>Hapus Logo (Gunakan Ikon Bawaan)</span>
                                </label>
                            </div>
                        @else
                            <div class="w-16 h-16 rounded-xl bg-slate-900 flex items-center justify-center text-emerald-400 border border-slate-700 shadow-sm">
                                <i data-lucide="recycle" class="w-8 h-8"></i>
                            </div>
                            <span class="text-[10px] text-slate-400 block mt-1">Menggunakan ikon bawaan</span>
                        @endif
                    </div>

                    <div class="sm:col-span-2 space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Unggah / Ganti Logo Baru</label>
                            <input type="file" name="app_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                   class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-300 rounded-lg p-1 bg-white">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama di Header Sidebar *</label>
                                <input type="text" name="app_name" value="{{ old('app_name', $settings['app_name'] ?? 'KASIR RONGSOK') }}" required
                                       placeholder="Contoh: KASIR RONGSOK"
                                       class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-bold uppercase text-slate-900 bg-white">
                                <span class="text-[10px] text-slate-400">Tampil tebal di bagian atas menu samping.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Subjudul / Keterangan Sidebar</label>
                                <input type="text" name="app_subtitle" value="{{ old('app_subtitle', $settings['app_subtitle'] ?? 'Sistem Depo & POS') }}"
                                       placeholder="Contoh: Sistem Depo & POS"
                                       class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 text-slate-800 bg-white">
                                <span class="text-[10px] text-slate-400">Teks kecil di bawah nama pada sidebar.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 2: PROFIL USAHA & DEPO (UNTUK KOP NOTA & SURAT JALAN) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                    <div class="w-6 h-6 rounded bg-blue-600 text-white flex items-center justify-center">
                        <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">2. Profil Usaha Depo (Kop Dokumen Resmi)</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Badan Usaha / Depo Rongsok *</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur') }}" required
                               class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-bold text-slate-900">
                        <span class="text-[10px] text-slate-400">Nama resmi yang dicetak di baris pertama nota transaksi & surat jalan.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Slogan / Tagline Usaha</label>
                        <input type="text" name="company_tagline" value="{{ old('company_tagline', $settings['company_tagline'] ?? 'Pusat Pembelian & Pengelolaan Daur Ulang Terpercaya') }}"
                               class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 text-slate-700">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nomor Telepon / WhatsApp *</label>
                        <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '0812-3456-7890') }}" required
                               class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Format Printer Nota Default *</label>
                        <select name="default_printer_type" required class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                            <option value="thermal_58" {{ ($settings['default_printer_type'] ?? 'thermal_58') === 'thermal_58' ? 'selected' : '' }}>
                                Thermal Mini 58mm (Kertas Struk Kasir Kecil)
                            </option>
                            <option value="thermal_80" {{ ($settings['default_printer_type'] ?? '') === 'thermal_80' ? 'selected' : '' }}>
                                Thermal Standard 80mm (Kertas Struk Kasir Lebar)
                            </option>
                            <option value="a4" {{ ($settings['default_printer_type'] ?? '') === 'a4' ? 'selected' : '' }}>
                                Kertas HVS A4 Standar (Printer Kantor / Dot Matrix)
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alamat Lengkap Depo / Gudang Timbang *</label>
                    <textarea name="company_address" rows="2" required class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">{{ old('company_address', $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok') }}</textarea>
                </div>
            </div>

            <!-- BAGIAN 3: KONFIGURASI NOTA & TEKS PERINGATAN (DISCLAIMER) -->
            <div class="bg-amber-50/60 p-5 rounded-xl border border-amber-200 space-y-4">
                <div class="flex items-center gap-2 border-b border-amber-200 pb-2">
                    <div class="w-6 h-6 rounded bg-amber-600 text-white flex items-center justify-center">
                        <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-950">3. Teks Peringatan & Disclaimer Nota Transaksi</h3>
                </div>

                <!-- Teks Tidak Menerima Barang Bukan Milik Pribadi -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-black text-slate-900 uppercase">
                            Teks Peringatan Di Nota Paling Bawah (Disclaimer Hukum) *
                        </label>
                        <span class="text-[10px] font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded">Tampil Mencolok</span>
                    </div>
                    <input type="text" name="receipt_disclaimer" 
                           value="{{ old('receipt_disclaimer', $settings['receipt_disclaimer'] ?? 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI') }}" required
                           class="w-full py-2.5 px-3 text-xs rounded-lg border border-red-300 focus:ring-red-500 font-black text-red-900 bg-white shadow-sm uppercase">
                    <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                        Teks ini dicetak otomatis dengan huruf tebal bergaris batas di paling bawah setiap nota pembelian (semua ukuran: 58mm, 80mm, A4) untuk melindungi usaha depo Anda dari barang curian.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Kaki Tambahan Nota (Receipt Footer)</label>
                    <textarea name="receipt_footer" rows="2" class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">{{ old('receipt_footer', $settings['receipt_footer'] ?? 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali. Terima kasih atas kerja samanya.') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Kop Atas Nota (Receipt Header)</label>
                    <input type="text" name="receipt_header" value="{{ old('receipt_header', $settings['receipt_header'] ?? 'Pusat Pembelian & Daur Ulang Logam / Kertas / Plastik') }}"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">
                </div>
            </div>

            <!-- BAGIAN 4: PENGATURAN TEKS DOKUMEN RESMI & PDF (TASK 9) -->
            <div class="bg-indigo-50/50 p-5 rounded-xl border border-indigo-200 space-y-4">
                <div class="flex items-center gap-2 border-b border-indigo-200 pb-2">
                    <div class="w-6 h-6 rounded bg-indigo-600 text-white flex items-center justify-center">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-950">4. Teks Khusus PDF, Surat Jalan, Layanan Jemput & Perjanjian</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teks Layanan Jemput Barang (Untuk WA & PDF) *</label>
                        <textarea name="pickup_service_text" rows="3" class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-indigo-500 bg-white">{{ old('pickup_service_text', $settings['pickup_service_text'] ?? 'Melayani armada jemput material scrap & daur ulang skala besar langsung ke lokasi gudang/pabrik Anda. Hubungi kantor kami.') }}</textarea>
                        <span class="text-[10px] text-slate-500">Tampil otomatis saat broadcast daftar harga ke WA atau pada dokumen resmi.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Ketentuan & Syarat Nota Transaksi (Receipt Terms)</label>
                        <textarea name="receipt_terms" rows="3" class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-indigo-500 bg-white">{{ old('receipt_terms', $settings['receipt_terms'] ?? 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali. Nota ini sah sebagai bukti penerimaan/penjualan.') }}</textarea>
                        <span class="text-[10px] text-slate-500">Dicetak di bagian syarat transaksi nota.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Dokumen Surat Jalan Pengiriman</label>
                        <textarea name="delivery_order_notes" rows="3" class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-indigo-500 bg-white">{{ old('delivery_order_notes', $settings['delivery_order_notes'] ?? 'Surat jalan ini dibuat 2 (dua) rangkap: Lembar 1 dipegang oleh gudang dan Lembar 2 dipegang oleh supir. Dokumen sah penyerahan material scrap & daur ulang.') }}</textarea>
                        <span class="text-[10px] text-slate-500">Dicetak di bagian bawah lembar Surat Jalan pengiriman pabrik.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Ketentuan Perjanjian Hutang Modal (Debt Agreement Terms)</label>
                        <textarea name="debt_agreement_terms" rows="3" class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-indigo-500 bg-white">{{ old('debt_agreement_terms', $settings['debt_agreement_terms'] ?? 'Apabila PIHAK KEDUA tidak menyetorkan material scrap & daur ulang atau tidak mengangsur pinjaman sesuai kesepakatan, PIHAK PERTAMA berhak menagih sisa kewajiban atau mengambil tindakan penyelesaian sesuai hukum yang berlaku di Republik Indonesia.') }}</textarea>
                        <span class="text-[10px] text-slate-500">Dicetak sebagai pasal penyelesaian pada Surat Perjanjian Hutang Modal.</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Kaki Slip Gaji Karyawan</label>
                    <input type="text" name="salary_slip_notes" value="{{ old('salary_slip_notes', $settings['salary_slip_notes'] ?? 'Slip gaji ini merupakan bukti sah pembayaran honor/upah kerja yang diterbitkan secara resmi.') }}"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-indigo-500 bg-white">
                </div>
            </div>

            <!-- BAGIAN 5: INTEGRASI WHATSAPP GATEWAY (WPPCONNECT) -->
            <div class="bg-emerald-50/50 p-5 rounded-xl border border-emerald-200 space-y-4">
                <div class="flex items-center justify-between border-b border-emerald-200 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-emerald-600 text-white flex items-center justify-center">
                            <x-whatsapp-icon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-950">5. Integrasi WhatsApp Gateway (WPPConnect Service)</h3>
                    </div>

                    @if(config('services.whatsapp_gateway.enabled', false))
                        <button type="button" 
                                @click="$store.waGateway.isOpen = true"
                                class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5">
                            <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                            <span>Buka Panel Gateway & Scan QR</span>
                        </button>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 border border-slate-300">
                            DINONAKTIFKAN DI HOSTING
                        </span>
                    @endif
                </div>

                @if(config('services.whatsapp_gateway.enabled', false))
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                        <div class="sm:col-span-2 space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-800">Status Server:</span>
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-800 border border-emerald-300': $store.waGateway.connected,
                                          'bg-amber-100 text-amber-800 border border-amber-300': !$store.waGateway.connected && $store.waGateway.status === 'QR_READY',
                                          'bg-rose-100 text-rose-800 border border-rose-300': !$store.waGateway.connected && $store.waGateway.status !== 'QR_READY'
                                      }">
                                    <span x-text="$store.waGateway.connected ? '🟢 Terhubung (Online)' : ($store.waGateway.status === 'QR_READY' ? '🟡 Menunggu Scan QR' : '🔴 Offline / Belum Jalan')"></span>
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Gateway WhatsApp berjalan di latar belakang (Port 21465). Sistem ini memungkinkan pengiriman nota timbangan pembelian, faktur penjualan partai, dan bukti bayar kasbon langsung ke nomor WhatsApp pemasok & pelanggan.
                            </p>
                        </div>

                        <div class="p-3 bg-white rounded-xl border border-emerald-200 text-xs space-y-1.5">
                            <div class="text-[11px] text-slate-500 font-medium">Nomor Terhubung:</div>
                            <div class="font-bold text-slate-900 font-mono" x-text="$store.waGateway.phone || '(Belum Terhubung)'"></div>
                            <div class="text-[10px] text-slate-400">Endpoint: http://127.0.0.1:21465</div>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-white rounded-xl border border-emerald-200 text-xs space-y-2">
                        <div class="flex items-center gap-2 text-slate-800 font-bold">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                            <span>Layanan Gateway Latar Belakang Nonaktif (Standar Hosting)</span>
                        </div>
                        <p class="text-slate-600 leading-relaxed">
                            Layanan background service WPPConnect dinonaktifkan di server hosting agar sistem berjalan ringan tanpa beban proses background node.js.
                            Pengiriman pesan dan dokumen PDF nota ke WhatsApp tetap berjalan 100% normal dan lancar melalui integrasi tautan <strong>Buka Chat (wa.me)</strong> langsung dari browser.
                        </p>
                    </div>
                @endif
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="px-8 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-2 shadow-md">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan Seluruh Pengaturan</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
