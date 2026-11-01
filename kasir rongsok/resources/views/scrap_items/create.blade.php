@extends('layouts.app', ['header' => 'Tambah Jenis Barang Rongsok'])

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    
    <div class="flex items-center justify-between">
        <a href="{{ route('scrap-items.index') }}" class="text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1 font-medium">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke Daftar Barang</span>
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="border-b border-slate-100 pb-3 mb-5">
            <h2 class="text-base font-bold text-slate-900">Formulir Barang Rongsok Baru</h2>
            <p class="text-xs text-slate-500">Tambahkan jenis logam, kertas, atau plastik baru ke sistem kasir</p>
        </div>

        <form action="{{ route('scrap-items.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Barang Rongsok *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Tembaga Super Kupas"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Kode Barang (Unik) *</label>
                    <input type="text" name="code" value="{{ old('code', 'RSK-' . rand(10, 99)) }}" required placeholder="Contoh: TBG-03"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Kategori Barang *</label>
                    <select name="category_id" required class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Satuan Timbangan *</label>
                    <input type="text" name="unit" value="{{ old('unit', 'kg') }}" required
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Harga Beli Standar / kg (Rp) *</label>
                    <input type="number" step="50" min="0" name="buy_price_per_kg" value="{{ old('buy_price_per_kg', 0) }}" required
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold text-emerald-700">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Harga Jual / kg (Rp)</label>
                    <input type="number" step="50" min="0" name="sell_price_per_kg" value="{{ old('sell_price_per_kg', 0) }}"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Batas Minimum Alert (kg)</label>
                    <input type="number" step="1" min="0" name="min_stock_alert" value="{{ old('min_stock_alert', 100) }}"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Deskripsi & Syarat Kualitas</label>
                <textarea name="description" rows="3" placeholder="Kondisi barang: bersih, tidak boleh campur air, dsb."
                          class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Status Aktif (Dapat dipilih di kasir timbangan)</span>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('scrap-items.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm">
                    Simpan Barang Baru
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

