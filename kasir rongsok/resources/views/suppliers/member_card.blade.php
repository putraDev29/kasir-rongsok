<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu_Member_{{ $supplier->member_number }}_{{ Str::slug($supplier->name) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            .card-container { box-shadow: none !important; border: 1px solid #cbd5e1 !important; page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center justify-start text-slate-800">

    <!-- Top Action Bar -->
    <div class="no-print w-full max-w-2xl mb-6 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <a href="{{ Auth::user()->isKasir() ? route('purchases.create') : route('suppliers.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>{{ Auth::user()->isKasir() ? 'Kembali ke Penerimaan Barang' : 'Kembali ke Data Pemasok' }}</span>
        </a>

        <div class="flex items-center gap-2">
            <form action="{{ route('suppliers.member-card-wa', $supplier) }}" method="POST" class="inline" onsubmit="return confirm('Kirim Kartu Member ke WhatsApp {{ addslashes($supplier->name) }}?')">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                    <x-whatsapp-icon class="w-3.5 h-3.5" />
                    <span>Kirim WA</span>
                </button>
            </form>

            <a href="{{ route('suppliers.member-card-pdf', $supplier) }}" target="_blank"
               class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm"
               title="Preview & Unduh Berkas PDF Kartu Member">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-400"></i>
                <span>Preview / Unduh PDF</span>
            </a>

            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak Kartu</span>
            </button>
        </div>
    </div>

    <!-- MAIN MEMBER CARD (CR80 Standard ID Card Ratio: ~85.6mm x 54mm) -->
    <div class="w-full max-w-lg space-y-6">
        
        <!-- SISI DEPAN (FRONT CARD) -->
        <div class="card-container relative overflow-hidden rounded-2xl p-6 sm:p-7 shadow-xl border text-white transition-all
            {{ $supplier->tier === 'Platinum' ? 'bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950 border-amber-500/40' : 
              ($supplier->tier === 'Gold' ? 'bg-gradient-to-br from-amber-700 via-amber-800 to-yellow-900 border-yellow-400/50' : 
              ($supplier->tier === 'Silver' ? 'bg-gradient-to-br from-slate-700 via-slate-800 to-zinc-900 border-slate-400/50' : 
               'bg-gradient-to-br from-amber-900 via-stone-800 to-amber-950 border-amber-700/50')) }}">
            
            <!-- Metallic Decorative Glow -->
            <div class="absolute -right-16 -top-16 w-52 h-52 rounded-full opacity-25 blur-2xl 
                {{ $supplier->tier === 'Platinum' ? 'bg-cyan-400' : ($supplier->tier === 'Gold' ? 'bg-yellow-300' : 'bg-emerald-400') }}"></div>

            <!-- Card Header: Logo & Depo Name -->
            <div class="flex items-start justify-between relative z-10">
                <div class="flex items-center gap-3">
                    @if(\App\Models\BusinessSetting::hasLogo())
                        <img src="{{ \App\Models\BusinessSetting::logoUrl() }}" alt="Logo" class="h-10 w-auto object-contain bg-white/10 p-1 rounded-lg backdrop-blur-xs">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-xs border border-white/20 flex items-center justify-center text-white font-black text-sm">
                            KR
                        </div>
                    @endif
                    <div>
                        <h2 class="text-sm font-black tracking-wider uppercase text-white/95 leading-tight">
                            {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
                        </h2>
                        <span class="text-[10px] tracking-widest uppercase text-white/70 block">
                            VIP SUPPLIER MEMBERSHIP CARD
                        </span>
                    </div>
                </div>

                <!-- Tier Badge -->
                <div class="text-right">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-sm
                        {{ $supplier->tier === 'Platinum' ? 'bg-gradient-to-r from-amber-300 to-yellow-500 text-slate-950 border border-yellow-200' : 
                          ($supplier->tier === 'Gold' ? 'bg-gradient-to-r from-yellow-300 to-amber-400 text-amber-950 border border-yellow-200' : 
                          ($supplier->tier === 'Silver' ? 'bg-gradient-to-r from-slate-100 to-slate-300 text-slate-900' : 
                           'bg-gradient-to-r from-amber-200 to-amber-400 text-amber-950')) }}">
                        ★ {{ strtoupper($supplier->tier) }}
                    </span>
                    <div class="text-[11px] text-yellow-300 tracking-wider mt-1 font-mono">
                        {{ str_repeat('⭐', $supplier->rating_stars) }}
                    </div>
                </div>
            </div>

            <!-- Smart Chip Icon & Member Number -->
            <div class="my-6 relative z-10 flex items-center justify-between">
                <!-- Sim Chip Visual -->
                <div class="w-11 h-8 rounded-md bg-gradient-to-br from-amber-200 via-yellow-400 to-amber-500 border border-yellow-200/80 shadow-xs flex items-center justify-center">
                    <div class="w-8 h-5 border border-amber-700/40 rounded-xs flex flex-col justify-between p-0.5">
                        <div class="h-px bg-amber-700/50"></div>
                        <div class="h-px bg-amber-700/50"></div>
                    </div>
                </div>

                <!-- Member Number / Code -->
                <div class="text-right">
                    <div class="text-[9px] uppercase tracking-widest text-white/60">ID Member Pemasok</div>
                    <div class="font-mono text-lg sm:text-xl font-black tracking-widest text-white drop-shadow-sm">
                        {{ $supplier->member_number }}
                    </div>
                </div>
            </div>

            <!-- Supplier Name & Accumulation Stats -->
            <div class="relative z-10 pt-3 border-t border-white/15 flex items-end justify-between">
                <div>
                    <span class="text-[9px] uppercase tracking-wider text-white/60 block">Nama Pemasok / Pengepul:</span>
                    <div class="text-base sm:text-lg font-black uppercase tracking-wide text-white">
                        {{ $supplier->name }}
                    </div>
                    <div class="text-[11px] text-white/80 font-mono mt-0.5">
                        {{ $supplier->phone ?: 'Pemasok Terdaftar' }}
                    </div>
                </div>

                <!-- Points & Weight Badges -->
                <div class="text-right font-mono">
                    <div class="text-[10px] text-white/70">Total Setor: <strong class="text-white">{{ \App\Services\FormatHelper::weight($supplier->total_weight) }} kg</strong></div>
                    <div class="text-xs font-black text-yellow-300 mt-0.5">
                        🎁 {{ number_format($supplier->points, 0, ',', '.') }} Poin Reward
                    </div>
                </div>
            </div>

        </div>

        <!-- SISI BELAKANG (DETAIL & HAK REWARD DARI OWNER) -->
        <div class="bg-white rounded-2xl p-6 shadow-md border border-slate-200 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i data-lucide="award" class="w-5 h-5 text-amber-500"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                        Status Loyalitas & Hak Reward dari Owner
                    </h3>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase
                    {{ $supplier->tier === 'Platinum' ? 'bg-purple-100 text-purple-900' : 
                      ($supplier->tier === 'Gold' ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-800') }}">
                    Tingkat {{ $supplier->tier }}
                </span>
            </div>

            <!-- Progress & Stats Grid -->
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Muatan</span>
                    <span class="text-sm font-black text-slate-900 font-mono mt-0.5 block">
                        {{ \App\Services\FormatHelper::weight($supplier->total_weight) }} kg
                    </span>
                    <span class="text-[9px] text-slate-500">{{ $supplier->total_transactions_count }}x Penimbangan</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Nilai Transaksi</span>
                    <span class="text-sm font-black text-emerald-700 font-mono mt-0.5 block">
                        {{ \App\Services\FormatHelper::rupiah($supplier->total_amount) }}
                    </span>
                    <span class="text-[9px] text-slate-500">Akumulasi uang cair</span>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/70">
                    <span class="text-[10px] uppercase font-bold text-amber-800 block">Poin Loyalitas</span>
                    <span class="text-sm font-black text-amber-900 font-mono mt-0.5 block">
                        {{ number_format($supplier->points, 0, ',', '.') }} Poin
                    </span>
                    <span class="text-[9px] text-amber-700">Dapat ditukar reward</span>
                </div>
            </div>

            <!-- Current Reward Entitlement -->
            <div class="p-3.5 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 space-y-1">
                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-900">
                    <i data-lucide="gift" class="w-4 h-4 text-emerald-600"></i>
                    <span>Reward yang Berhak Didapatkan Pemasok:</span>
                </div>
                <p class="text-xs text-emerald-800 font-medium leading-relaxed">
                    {{ $supplier->eligible_reward }}
                </p>
            </div>

            <!-- Owner Grant Reward Form (Task & Feature) -->
            <div class="pt-3 border-t border-slate-100" x-data="{ openRewardForm: false }">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800">Pemberian Reward oleh Owner</span>
                    <button type="button" @click="openRewardForm = !openRewardForm"
                            class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Catat Reward Baru</span>
                    </button>
                </div>

                <form x-show="openRewardForm" action="{{ route('suppliers.store-reward', $supplier) }}" method="POST" class="mt-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Reward / Bonus *</label>
                        <input type="text" name="reward_title" required placeholder="Contoh: Bonus Tunai Akhir Bulan Rp 500.000 / Timbangan Digital"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Jenis Reward</label>
                            <select name="reward_type" class="w-full text-xs p-2 rounded-lg border border-slate-300 bg-white">
                                <option value="cash">Uang Tunai / Cashback</option>
                                <option value="souvenir">Souvenir / Timbangan / Alat</option>
                                <option value="voucher">Voucher / Sembako</option>
                                <option value="price_bonus">Tambahan Harga Khusus (VIP)</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nilai Hadiah (Rp)</label>
                            <input type="number" step="1000" min="0" name="reward_value" placeholder="0"
                                   class="w-full text-xs p-2 rounded-lg border border-slate-300 font-mono bg-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Tambahan</label>
                        <input type="text" name="notes" placeholder="Keterangan penyerahan oleh owner..."
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 bg-white">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" @click="openRewardForm = false" class="px-3 py-1.5 text-xs text-slate-600 bg-slate-200 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Simpan & Berikan Reward</button>
                    </div>
                </form>

                <!-- Riwayat Reward yang Pernah Diberikan -->
                @if(isset($rewards) && $rewards->count() > 0)
                    <div class="mt-3 space-y-1.5">
                        <div class="text-[11px] font-semibold text-slate-500 uppercase">Riwayat Reward yang Telah Diterima:</div>
                        @foreach($rewards as $rw)
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-200 text-xs flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-900">{{ $rw->reward_title }}</div>
                                    <div class="text-[10px] text-slate-400">Diberikan pada {{ $rw->awarded_date?->format('d/m/Y') }}</div>
                                </div>
                                @if($rw->reward_value > 0)
                                    <span class="font-mono font-bold text-emerald-700 text-xs">
                                        {{ \App\Services\FormatHelper::rupiah($rw->reward_value) }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Terms & Depo Address -->
            <div class="pt-3 border-t border-slate-100 text-[10px] text-slate-500 space-y-1">
                <p>• Kartu ini adalah tanda keanggotaan resmi pemasok/pengepul di <strong>{{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong>.</p>
                <p>• Tunjukkan kartu ini saat menimbang untuk mendapatkan harga prioritas, poin reward, dan fasilitas kasbon modal.</p>
                <p class="font-mono text-slate-400 pt-1">Alamat Depo: {{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam' }} | Telp: {{ $settings['company_phone'] ?? '-' }}</p>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
        });
    </script>
</body>
</html>

