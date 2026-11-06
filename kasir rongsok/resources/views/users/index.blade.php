@extends('layouts.app', ['header' => 'Manajemen Pengguna & Hak Akses'])

@section('content')
<div class="space-y-4" x-data="{ userModal: false, isEdit: false, form: { id: '', name: '', email: '', role: 'kasir', phone: '', bank_name: '', bank_account_number: '', bank_account_name: '', is_active: true } }">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900">Kelola Akun Pengguna & Operator Kasir</h2>
            <p class="text-xs text-slate-500">Hak akses berbasis peran: Super Admin (Owner), Admin, dan Kasir Timbang</p>
        </div>

        <button type="button" @click="isEdit = false; form = { id: '', name: '', email: '', role: 'kasir', phone: '', bank_name: '', bank_account_number: '', bank_account_name: '', is_active: true }; userModal = true"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Tambah Pengguna Baru</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 w-10 text-center">#</th>
                        <th class="py-2.5 px-3">Nama Pengguna</th>
                        <th class="py-2.5 px-3">Email Login</th>
                        <th class="py-2.5 px-3">Peran / Role</th>
                        <th class="py-2.5 px-3">Telepon</th>
                        <th class="py-2.5 px-3">Rekening Bank</th>
                        <th class="py-2.5 px-3 text-right">Nota Pembelian</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $idx => $u)
                        <tr class="hover:bg-slate-50 transition-colors {{ !$u->is_active ? 'opacity-60 bg-slate-50' : '' }}">
                            <td class="py-3 px-3 text-center text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-3 px-3 font-bold text-slate-900">
                                <div>{{ $u->name }}</div>
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-600">{{ $u->email }}</td>
                            <td class="py-3 px-3">
                                @if($u->role === 'super_admin')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800">
                                        Super Admin (Owner)
                                    </span>
                                @elseif($u->role === 'admin')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">
                                        Admin
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Kasir Timbang
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-600">{{ $u->phone ?? '-' }}</td>
                            <td class="py-3 px-3 font-sans">
                                @if($u->hasBankDetails())
                                    <div class="font-bold text-slate-800 text-[11px]">{{ $u->bank_name }}</div>
                                    <div class="font-mono text-[10px] text-slate-600">{{ $u->bank_account_number }}</div>
                                    <div class="text-[10px] text-slate-400">a/n {{ $u->bank_account_name ?: $u->name }}</div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-medium text-slate-800">
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-semibold">{{ $u->purchase_transactions_count }} Nota</span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($u->is_active)
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-800">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" 
                                            @click="isEdit = true; form = { id: {{ $u->id }}, name: '{{ addslashes($u->name) }}', email: '{{ addslashes($u->email) }}', role: '{{ $u->role }}', phone: '{{ addslashes($u->phone ?? '') }}', bank_name: '{{ addslashes($u->bank_name ?? '') }}', bank_account_number: '{{ addslashes($u->bank_account_number ?? '') }}', bank_account_name: '{{ addslashes($u->bank_account_name ?? '') }}', is_active: {{ $u->is_active ? 'true' : 'false' }} }; userModal = true"
                                            title="Edit Pengguna"
                                            class="p-1 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    @if($u->id !== Auth::id())
                                        <form action="{{ route('users.destroy', $u) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Hapus akun {{ addslashes($u->name) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form (Tambah / Edit Pengguna) -->
    <div x-show="userModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="userModal = false">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900" x-text="isEdit ? 'Edit Pengguna' : 'Tambah Pengguna Baru'"></h3>
                <button type="button" @click="userModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="isEdit ? '{{ url('users') }}/' + form.id : '{{ route('users.store') }}'" method="POST" class="space-y-3">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Lengkap *</label>
                    <input type="text" name="name" x-model="form.name" required placeholder="Nama operator"
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alamat Email *</label>
                    <input type="email" name="email" x-model="form.email" required placeholder="email@contoh.com"
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Peran Akses *</label>
                        <select name="role" x-model="form.role" required class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                            <option value="kasir">Kasir Timbang</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin (Owner)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Telepon</label>
                        <input type="text" name="phone" x-model="form.phone" placeholder="08..."
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        <span x-text="isEdit ? 'Kata Sandi Baru (Kosongkan bila tidak diubah)' : 'Kata Sandi Awal *'"></span>
                    </label>
                    <input type="password" name="password" :required="!isEdit" placeholder="Minimal 6 karakter"
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                </div>

                <!-- Data Rekening Bank User -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2">
                    <span class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">Informasi Rekening Bank (Opsional)</span>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Nama Bank</label>
                            <input type="text" name="bank_name" x-model="form.bank_name" placeholder="BCA / BRI / Mandiri"
                                   class="w-full text-xs p-1.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Nomor Rekening</label>
                            <input type="text" name="bank_account_number" x-model="form.bank_account_number" placeholder="No. Rekening"
                                   class="w-full text-xs p-1.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Nama Pemilik Rekening (A/N)</label>
                        <input type="text" name="bank_account_name" x-model="form.bank_account_name" placeholder="Atas Nama Pemilik Rekening"
                               class="w-full text-xs p-1.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

                <template x-if="isEdit">
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>Akun Aktif (Dapat Login)</span>
                        </label>
                    </div>
                </template>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="userModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Buat Pengguna'">
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

