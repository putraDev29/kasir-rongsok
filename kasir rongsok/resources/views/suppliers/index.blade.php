@extends('layouts.app', ['header' => 'Master Data Pemasok & Kartu Member'])

@section('content')
<div class="space-y-4" x-data="{ 
    supplierModal: false, 
    isEdit: false, 
    form: { id: '', name: '', phone: '', address: '', notes: '', member_number: '', join_date: '', tier_override: '', is_active: true },
    rewardModal: false,
    rewardSupplier: { id: '', name: '', member_number: '', tier: '', eligible_reward: '' },
    rewardForm: { reward_title: '', reward_type: 'cash', reward_value: '', notes: '' },
    openRewardModal(sup) {
        this.rewardSupplier = sup;
        this.rewardForm = { reward_title: '', reward_type: 'cash', reward_value: '', notes: '' };
        this.rewardModal = true;
    }
}">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-lg bg-emerald-100 text-emerald-700">
                    <i data-lucide="award" class="w-5 h-5"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Keanggotaan Pemasok & Kartu Member Loyalitas</h2>
                    <p class="text-xs text-slate-500">Hitung akumulasi penjualan, rating bintang loyalitas, dan reward resmi dari owner</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="isEdit = false; form = { id: '', name: '', phone: '', address: '', notes: '', member_number: '', join_date: '{{ date('Y-m-d') }}', tier_override: '', is_active: true }; supplierModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-all">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Tambah Pemasok Baru</span>
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('suppliers.index') }}" class="flex items-center gap-3">
            <div class="flex-1 relative">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama pemasok, no. member (MBR-...), telepon, atau kode..."
                       class="w-full pl-8 pr-2.5 py-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
            </div>
            <button type="submit" class="py-2 px-4 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-sm">
                Cari Pemasok
            </button>
            <a href="{{ route('suppliers.index') }}" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600" title="Reset Pencarian">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
            </a>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-left text-xs divide-y divide-slate-200">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10px] tracking-wider font-bold">
                    <tr>
                        <th class="py-3 px-3 w-10 text-center">#</th>
                        <th class="py-3 px-3 min-w-[200px]">Pemasok & No. Member</th>
                        <th class="py-3 px-3 min-w-[130px] text-center">Tier & Rating</th>
                        <th class="py-3 px-3 min-w-[140px]">Kontak & Alamat</th>
                        <th class="py-3 px-3 min-w-[120px] text-right">Total Setoran</th>
                        <th class="py-3 px-3 min-w-[130px] text-right">Total Omzet</th>
                        <th class="py-3 px-3 min-w-[110px] text-center">Poin Loyalitas</th>
                        <th class="py-3 px-3 min-w-[160px] text-center">Aksi & Kartu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $idx => $sup)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-3 text-center text-slate-400 font-mono">{{ $suppliers->firstItem() + $idx }}</td>
                            
                            <!-- Pemasok & No Member -->
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0 text-xs">
                                        {{ strtoupper(substr($sup->name, 0, 2)) }}
                                    </span>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $sup->name }}</div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="font-mono text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                                {{ $sup->member_number }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $sup->code }}</span>
                                        </div>
                                        @if($sup->notes)
                                            <div class="text-[10px] text-slate-400 font-normal truncate max-w-xs mt-0.5">{{ $sup->notes }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Tier & Star Rating -->
                            <td class="py-3 px-3 text-center">
                                <div>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-2xs
                                        {{ $sup->tier === 'Platinum' ? 'bg-purple-100 text-purple-900 border border-purple-200' : 
                                          ($sup->tier === 'Gold' ? 'bg-amber-100 text-amber-900 border border-amber-300' : 
                                          ($sup->tier === 'Silver' ? 'bg-slate-200 text-slate-800 border border-slate-300' : 
                                           'bg-amber-50 text-amber-800 border border-amber-200')) }}">
                                        {{ $sup->tier === 'Platinum' ? '💎' : ($sup->tier === 'Gold' ? '🥇' : ($sup->tier === 'Silver' ? '🥈' : '🥉')) }}
                                        {{ $sup->tier }}
                                    </span>
                                    <div class="text-amber-500 text-xs mt-1 font-mono">
                                        {{ str_repeat('⭐', $sup->rating_stars) }}
                                        <span class="text-[10px] text-slate-500 font-bold">({{ $sup->rating_stars }}.0)</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kontak & Alamat -->
                            <td class="py-3 px-3">
                                <div class="font-mono font-medium text-slate-700 text-xs flex items-center gap-1">
                                    <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                                    <span>{{ $sup->phone ?: '-' }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 truncate max-w-xs mt-0.5">
                                    {{ $sup->address ?: 'Alamat belum diisi' }}
                                </div>
                            </td>

                            <!-- Total Setoran (kg & Frekuensi) -->
                            <td class="py-3 px-3 text-right">
                                <div class="font-mono font-bold text-slate-900 text-xs">
                                    {{ \App\Services\FormatHelper::weight($sup->total_weight) }} kg
                                </div>
                                <div class="text-[10px] text-emerald-700 font-medium">
                                    {{ $sup->total_transactions_count }}x Penimbangan
                                </div>
                            </td>

                            <!-- Total Omzet Penjualan -->
                            <td class="py-3 px-3 text-right">
                                <div class="font-mono font-black text-slate-900 text-xs">
                                    {{ \App\Services\FormatHelper::rupiah($sup->total_amount) }}
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    Uang Cair Masuk
                                </div>
                            </td>

                            <!-- Poin Loyalitas & Hak Reward -->
                            <td class="py-3 px-3 text-center">
                                <span class="px-2 py-1 rounded-lg bg-amber-50 border border-amber-200 font-mono font-bold text-amber-900 text-xs">
                                    🎁 {{ number_format($sup->points, 0, ',', '.') }} Poin
                                </span>
                                <div class="text-[9px] text-slate-500 mt-1 truncate max-w-[130px] mx-auto" title="{{ $sup->eligible_reward }}">
                                    {{ Str::limit($sup->eligible_reward, 22) }}
                                </div>
                            </td>

                            <!-- Aksi & Kartu Member -->
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <!-- Kartu Member Link -->
                                    <a href="{{ route('suppliers.member-card', $sup) }}" target="_blank"
                                       class="px-2 py-1 rounded bg-slate-900 hover:bg-slate-800 text-white font-bold text-[11px] flex items-center gap-1 shadow-2xs"
                                       title="Buka / Cetak Kartu Member">
                                        <i data-lucide="credit-card" class="w-3.5 h-3.5 text-emerald-400"></i>
                                        <span>Kartu</span>
                                    </a>

                                    <!-- Kirim WA Kartu Member -->
                                    <button type="button"
                                            @click="openWaPdfModal({
                                                title: 'Kirim Kartu Member Pemasok PDF',
                                                subtitle: 'Kirim berkas dokumen PDF resmi kartu member & loyalitas ke nomor WhatsApp pemasok',
                                                phone: '{{ $sup->phone }}',
                                                filename: 'Kartu_Member_{{ $sup->member_number }}.pdf',
                                                fileDescription: 'Dokumen PDF Resmi Kartu Member & Hak Reward Pemasok',
                                                pdfUrl: '{{ route('suppliers.member-card-pdf', $sup) }}',
                                                previewUrl: '{{ route('suppliers.member-card-wa-preview', $sup) }}',
                                                sendUrl: '{{ route('suppliers.member-card-wa', $sup) }}',
                                                submitButtonText: 'Kirim Kartu Member via WA'
                                            })"
                                            title="Kirim Kartu Member PDF ke WhatsApp"
                                            class="p-1 rounded text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50">
                                        <x-whatsapp-icon class="w-4 h-4" />
                                    </button>

                                    <!-- Beri Reward Owner Button -->
                                    <button type="button" 
                                            @click="openRewardModal({ id: {{ $sup->id }}, name: '{{ addslashes($sup->name) }}', member_number: '{{ $sup->member_number }}', tier: '{{ $sup->tier }}', eligible_reward: '{{ addslashes($sup->eligible_reward) }}' })"
                                            title="Beri Reward dari Owner"
                                            class="p-1 rounded text-amber-600 hover:text-amber-700 hover:bg-amber-50">
                                        <i data-lucide="gift" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Edit Pemasok -->
                                    <button type="button" 
                                            @click="isEdit = true; form = { id: {{ $sup->id }}, name: '{{ addslashes($sup->name) }}', phone: '{{ addslashes($sup->phone ?? '') }}', address: '{{ addslashes($sup->address ?? '') }}', notes: '{{ addslashes($sup->notes ?? '') }}', member_number: '{{ addslashes($sup->member_number) }}', join_date: '{{ $sup->join_date ? $sup->join_date->format('Y-m-d') : '' }}', tier_override: '{{ strtolower($sup->tier_override ?? '') }}', is_active: {{ $sup->is_active ? 'true' : 'false' }} }; supplierModal = true"
                                            title="Edit Data Pemasok"
                                            class="p-1 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Hapus Pemasok -->
                                    <form action="{{ route('suppliers.destroy', $sup) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus pemasok {{ addslashes($sup->name) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">
                                <i data-lucide="users" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                Belum ada data pemasok rongsok terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form (Tambah / Edit Pemasok) -->
    <div x-show="supplierModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="supplierModal = false">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                    <h3 class="text-sm font-bold text-slate-900" x-text="isEdit ? 'Edit Data Pemasok' : 'Tambah Pemasok Baru'"></h3>
                </div>
                <button type="button" @click="supplierModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="isEdit ? '{{ url('suppliers') }}/' + form.id : '{{ route('suppliers.store') }}'" method="POST" class="space-y-3">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Pemasok / Penjual *</label>
                    <input type="text" name="name" x-model="form.name" required placeholder="Contoh: Pak Slamet Pengepul"
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">No. Member (Opsional)</label>
                        <input type="text" name="member_number" x-model="form.member_number" placeholder="Otomatis jika kosong"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tier Loyalitas</label>
                        <select name="tier_override" x-model="form.tier_override" class="w-full text-xs p-2 rounded-lg border border-slate-300">
                            <option value="">Otomatis (Berdasarkan Omzet/Tonase)</option>
                            <option value="bronze">Bronze (Standar)</option>
                            <option value="silver">Silver (&ge; 500 kg / 5jt)</option>
                            <option value="gold">Gold (&ge; 2 ton / 20jt)</option>
                            <option value="platinum">Platinum VIP (&ge; 5 ton / 50jt)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="phone" x-model="form.phone" placeholder="0812..."
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tanggal Bergabung</label>
                        <input type="date" name="join_date" x-model="form.join_date"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alamat / Wilayah Operasi</label>
                    <input type="text" name="address" x-model="form.address" placeholder="Kampung / Kelurahan / Desa"
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Tambahan</label>
                    <textarea name="notes" x-model="form.notes" rows="2" placeholder="Pengepul gerobak / tossa, bawa jenis tembaga, dsb."
                              class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="supplierModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Pemasok'">
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form: Beri Reward Owner ke Pemasok -->
    <div x-show="rewardModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="rewardModal = false">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-amber-100 text-amber-700">
                        <i data-lucide="gift" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Pemberian Reward oleh Owner</h3>
                        <div class="text-[11px] text-slate-500" x-text="rewardSupplier.name + ' (' + rewardSupplier.member_number + ')'"></div>
                    </div>
                </div>
                <button type="button" @click="rewardModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Info Hak Reward -->
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs space-y-1">
                <div class="font-bold text-amber-900 flex items-center gap-1">
                    <span>👑</span>
                    <span>Hak Reward Sesuai Tier (<span x-text="rewardSupplier.tier"></span>):</span>
                </div>
                <div class="text-amber-800" x-text="rewardSupplier.eligible_reward"></div>
            </div>

            <form :action="'{{ url('suppliers') }}/' + rewardSupplier.id + '/rewards'" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Reward / Bonus *</label>
                    <input type="text" name="reward_title" required x-model="rewardForm.reward_title"
                           placeholder="Contoh: Bonus Tunai Akhir Bulan Rp 500.000 / Hadiah Kaos Depo"
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Jenis Reward</label>
                        <select name="reward_type" x-model="rewardForm.reward_type" class="w-full text-xs p-2 rounded-lg border border-slate-300">
                            <option value="cash">Uang Tunai / Cashback</option>
                            <option value="souvenir">Souvenir / Kaos / Timbangan</option>
                            <option value="voucher">Voucher Sembako / Belanja</option>
                            <option value="price_bonus">Tambahan Harga Khusus (VIP)</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nilai Hadiah (Rp)</label>
                        <input type="number" step="1000" min="0" name="reward_value" x-model="rewardForm.reward_value" placeholder="0"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Tambahan</label>
                    <textarea name="notes" x-model="rewardForm.notes" rows="2" placeholder="Keterangan penyerahan hadiah oleh owner..."
                              class="w-full text-xs p-2 rounded-lg border border-slate-300"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="rewardModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm">
                        Simpan & Serahkan Reward
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
