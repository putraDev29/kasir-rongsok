@extends('layouts.app', ['header' => 'Master Data Sopir & Armada Kendaraan'])

@section('content')
<div class="space-y-4" x-data="{ 
    driverModal: false, 
    isEdit: false, 
    form: { id: '', name: '', phone: '', vehicle_number: '', vehicle_type: 'Truk Colt Diesel', bank_name: '', bank_account_number: '', bank_account_name: '', address: '', notes: '', is_active: true }
}">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-lg bg-emerald-100 text-emerald-700">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Data Sopir / Driver & Rekening Bank Ongkos</h2>
                    <p class="text-xs text-slate-500">Kelola identitas armada pengiriman, sopir, plat nomor kendaraan, dan rekening bank untuk pencairan ongkos jalan</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="isEdit = false; form = { id: '', name: '', phone: '', vehicle_number: '', vehicle_type: 'Truk Colt Diesel', bank_name: '', bank_account_number: '', bank_account_name: '', address: '', notes: '', is_active: true }; driverModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-all">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Tambah Sopir Baru</span>
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('drivers.index') }}" class="flex items-center gap-3">
            <div class="flex-1 relative">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama sopir, nomor HP, plat no. polisi, atau rekening bank..."
                       class="w-full pl-8 pr-2.5 py-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
            </div>
            <button type="submit" class="py-2 px-4 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-sm">
                Cari Sopir
            </button>
            <a href="{{ route('drivers.index') }}" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600" title="Reset Pencarian">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
            </a>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Sopir / Driver</th>
                        <th class="py-3 px-4">Kontak & Alamat</th>
                        <th class="py-3 px-4">Armada Kendaraan</th>
                        <th class="py-3 px-4">Rekening Bank Ongkos</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($drivers as $drv)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $drv->name }}</div>
                                <div class="font-mono text-[10px] text-slate-400">{{ $drv->code }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-mono font-semibold text-slate-700">{{ $drv->phone ?: '-' }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs">{{ $drv->address ?: 'Alamat belum diatur' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                @if($drv->vehicle_number)
                                    <span class="inline-block px-2 py-0.5 rounded bg-slate-800 text-amber-300 font-mono font-bold text-[11px]">
                                        {{ $drv->vehicle_number }}
                                    </span>
                                    <div class="text-[10px] text-slate-500 mt-0.5">{{ $drv->vehicle_type ?: 'Truk Armada' }}</div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Tidak ditentukan</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($drv->hasBankDetails())
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-blue-50 border border-blue-200 text-blue-900">
                                        <i data-lucide="credit-card" class="w-3.5 h-3.5 text-blue-600 shrink-0"></i>
                                        <div>
                                            <div class="font-bold text-[11px] leading-tight">{{ $drv->bank_name }}</div>
                                            <div class="font-mono text-[10px] text-blue-800 font-semibold">{{ $drv->bank_account_number }}</div>
                                            <div class="text-[9.5px] text-blue-600 leading-none">a/n {{ $drv->bank_account_name ?: $drv->name }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Belum ada data bank</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($drv->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" 
                                            @click="isEdit = true; form = { id: {{ $drv->id }}, name: '{{ addslashes($drv->name) }}', phone: '{{ addslashes($drv->phone ?? '') }}', vehicle_number: '{{ addslashes($drv->vehicle_number ?? '') }}', vehicle_type: '{{ addslashes($drv->vehicle_type ?? 'Truk Colt Diesel') }}', bank_name: '{{ addslashes($drv->bank_name ?? '') }}', bank_account_number: '{{ addslashes($drv->bank_account_number ?? '') }}', bank_account_name: '{{ addslashes($drv->bank_account_name ?? '') }}', address: '{{ addslashes($drv->address ?? '') }}', notes: '{{ addslashes($drv->notes ?? '') }}', is_active: {{ $drv->is_active ? 'true' : 'false' }} }; driverModal = true"
                                            title="Edit Sopir"
                                            class="p-1.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <form action="{{ route('drivers.destroy', $drv) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data sopir ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded bg-red-50 hover:bg-red-100 text-red-600" title="Hapus Sopir">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                Belum ada data sopir / driver yang tersimpan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($drivers->hasPages())
            <div class="p-3 border-t border-slate-100">
                {{ $drivers->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL ADD / EDIT DRIVER -->
    <div x-show="driverModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4"
             @click.away="driverModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900" x-text="isEdit ? 'Edit Data Sopir / Driver' : 'Tambah Sopir / Driver Baru'"></h3>
                <button type="button" @click="driverModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="isEdit ? '{{ url('drivers') }}/' + form.id : '{{ route('drivers.store') }}'" method="POST" class="space-y-3">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Sopir / Driver *</label>
                    <input type="text" name="name" x-model="form.name" required placeholder="Nama lengkap sopir"
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nomor HP / WhatsApp</label>
                        <input type="text" name="phone" x-model="form.phone" placeholder="08..."
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">No. Polisi Armada (Plat)</label>
                        <input type="text" name="vehicle_number" x-model="form.vehicle_number" placeholder="Contoh: B 9123 KAS"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold uppercase">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Jenis Kendaraan Default</label>
                    <select name="vehicle_type" x-model="form.vehicle_type" class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                        <option value="Truk Colt Diesel">Truk Colt Diesel (CDD)</option>
                        <option value="Truk Engkel (CDE)">Truk Engkel (CDE)</option>
                        <option value="Truk Fuso">Truk Fuso</option>
                        <option value="Truk Tronton / Wingbox">Truk Tronton / Wingbox</option>
                        <option value="Pick Up L300 / Gran Max">Pick Up L300 / Gran Max</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <!-- Data Rekening Bank Sopir -->
                <div class="p-3 bg-blue-50/60 border border-blue-200 rounded-lg space-y-2">
                    <span class="block text-[11px] font-bold text-blue-900 uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="credit-card" class="w-3.5 h-3.5 text-blue-600"></i>
                        <span>Informasi Rekening Bank Sopir (Pencairan Ongkos Armada)</span>
                    </span>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Nama Bank</label>
                            <input type="text" name="bank_name" x-model="form.bank_name" placeholder="BCA / BRI / Mandiri"
                                   class="w-full text-xs p-1.5 rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Nomor Rekening</label>
                            <input type="text" name="bank_account_number" x-model="form.bank_account_number" placeholder="No. Rekening"
                                   class="w-full text-xs p-1.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Nama Pemilik Rekening (A/N)</label>
                        <input type="text" name="bank_account_name" x-model="form.bank_account_name" placeholder="Atas Nama Pemilik Rekening"
                               class="w-full text-xs p-1.5 rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alamat Domisili</label>
                    <input type="text" name="address" x-model="form.address" placeholder="Kota / Wilayah asal sopir"
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Catatan Tambahan</label>
                    <textarea name="notes" x-model="form.notes" rows="2" placeholder="Armada ekspedisi langganan, jadwal trip mingguan, dsb."
                              class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500"></textarea>
                </div>

                <template x-if="isEdit">
                    <div class="pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-semibold text-slate-700">Status Aktif (Bisa dipilih di Surat Jalan)</span>
                        </label>
                    </div>
                </template>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="driverModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Sopir'">
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

