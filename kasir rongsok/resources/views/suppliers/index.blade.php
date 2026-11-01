@extends('layouts.app', ['header' => 'Master Data Pemasok / Pengepul'])

@section('content')
<div class="space-y-4" x-data="{ supplierModal: false, isEdit: false, form: { id: '', name: '', phone: '', address: '', notes: '', is_active: true } }">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900">Daftar Pemasok & Pengepul Keliling</h2>
            <p class="text-xs text-slate-500">Kelola kontak pemasok langganan rongsok untuk mempercepat kasir</p>
        </div>

        <button type="button" @click="isEdit = false; form = { id: '', name: '', phone: '', address: '', notes: '', is_active: true }; supplierModal = true"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Tambah Pemasok Baru</span>
        </button>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('suppliers.index') }}" class="flex items-center gap-3">
            <div class="flex-1 relative">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama pemasok, telepon, kode..."
                       class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
            </div>
            <button type="submit" class="py-1.5 px-4 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs">
                Cari
            </button>
            <a href="{{ route('suppliers.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
            </a>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 w-10 text-center">#</th>
                        <th class="py-2.5 px-3">Kode</th>
                        <th class="py-2.5 px-3">Nama Pemasok</th>
                        <th class="py-2.5 px-3">Nomor Telepon</th>
                        <th class="py-2.5 px-3">Alamat</th>
                        <th class="py-2.5 px-3 text-right">Total Transaksi</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $idx => $sup)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-3 text-center text-slate-400">{{ $suppliers->firstItem() + $idx }}</td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-700">{{ $sup->code ?? '-' }}</td>
                            <td class="py-3 px-3 font-bold text-slate-900">
                                <div>{{ $sup->name }}</div>
                                @if($sup->notes)
                                    <div class="text-[10px] text-slate-400 font-normal truncate max-w-xs">{{ $sup->notes }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-700">{{ $sup->phone ?? '-' }}</td>
                            <td class="py-3 px-3 text-slate-600 truncate max-w-xs">{{ $sup->address ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-medium text-slate-800">
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-semibold">{{ $sup->purchase_transactions_count }} Nota</span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" 
                                            @click="isEdit = true; form = { id: {{ $sup->id }}, name: '{{ addslashes($sup->name) }}', phone: '{{ addslashes($sup->phone ?? '') }}', address: '{{ addslashes($sup->address ?? '') }}', notes: '{{ addslashes($sup->notes ?? '') }}', is_active: {{ $sup->is_active ? 'true' : 'false' }} }; supplierModal = true"
                                            title="Edit Pemasok"
                                            class="p-1 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
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
                            <td colspan="7" class="py-8 text-center text-slate-400">Belum ada pemasok rongsok terdaftar.</td>
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
                <h3 class="text-sm font-bold text-slate-900" x-text="isEdit ? 'Edit Data Pemasok' : 'Tambah Pemasok Baru'"></h3>
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

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" x-model="form.phone" placeholder="0812..."
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
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

</div>
@endsection

