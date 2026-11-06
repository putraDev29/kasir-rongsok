@extends('layouts.app', ['header' => 'Edit Barang: ' . $scrapItem->name])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <div class="flex items-center justify-between">
        <a href="{{ route('scrap-items.index') }}" class="text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1 font-medium">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke Daftar Barang</span>
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Perbarui Informasi Material Scrap & Daur Ulang</h2>
                <p class="text-xs text-slate-500">Perubahan harga beli / jual akan dicatat otomatis ke riwayat harga</p>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400 block font-medium">Stok Saat Ini</span>
                <span class="text-base font-bold text-emerald-700">{{ \App\Services\FormatHelper::weight($scrapItem->current_stock) }} kg</span>
            </div>
        </div>

        <form action="{{ route('scrap-items.update', $scrapItem) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nama Material Scrap & Daur Ulang *</label>
                    <input type="text" name="name" value="{{ old('name', $scrapItem->name) }}" required
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Kode Barang *</label>
                    <input type="text" name="code" value="{{ old('code', $scrapItem->code) }}" required
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Kategori Barang *</label>
                    <select name="category_id" required class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $scrapItem->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Satuan Timbangan *</label>
                    <input type="text" name="unit" value="{{ old('unit', $scrapItem->unit) }}" required
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Harga Beli Standar / kg (Rp) *</label>
                    <input type="number" step="50" min="0" name="buy_price_per_kg" value="{{ old('buy_price_per_kg', $scrapItem->buy_price_per_kg) }}" required
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold text-emerald-700">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Harga Jual / kg (Rp)</label>
                    <input type="number" step="50" min="0" name="sell_price_per_kg" value="{{ old('sell_price_per_kg', $scrapItem->sell_price_per_kg) }}"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Batas Minimum Alert (kg)</label>
                    <input type="number" step="1" min="0" name="min_stock_alert" value="{{ old('min_stock_alert', $scrapItem->min_stock_alert) }}"
                           class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alasan Perubahan (Bila Mengubah Harga)</label>
                <input type="text" name="price_change_reason" placeholder="Contoh: Mengikuti kenaikan harga beli pabrik"
                       class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Deskripsi & Syarat Kualitas</label>
                <textarea name="description" rows="3" class="w-full p-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">{{ old('description', $scrapItem->description) }}</textarea>
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $scrapItem->is_active) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Status Aktif (Dapat dipilih kasir)</span>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('scrap-items.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Riwayat Perubahan Harga Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex items-center gap-2">
            <i data-lucide="history" class="w-4 h-4 text-emerald-600"></i>
            <h3 class="text-sm font-bold text-slate-900">Riwayat Perubahan Harga (Audit Trail)</h3>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-lg">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[10px] uppercase">
                    <tr>
                        <th class="py-2 px-3">Waktu Perubahan</th>
                        <th class="py-2 px-3 text-right">Harga Beli Lama</th>
                        <th class="py-2 px-3 text-right font-bold text-emerald-700">Harga Beli Baru</th>
                        <th class="py-2 px-3 text-right">Harga Jual Baru</th>
                        <th class="py-2 px-3">Alasan</th>
                        <th class="py-2 px-3">Oleh User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @forelse($scrapItem->priceHistories as $ph)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 text-slate-500">{{ $ph->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-2 px-3 text-right text-slate-400">Rp {{ number_format($ph->old_buy_price, 0, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right font-bold text-emerald-700">Rp {{ number_format($ph->new_buy_price, 0, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right text-slate-700">Rp {{ number_format($ph->new_sell_price, 0, ',', '.') }}</td>
                            <td class="py-2 px-3 text-slate-600 font-sans text-xs">{{ $ph->reason ?? '-' }}</td>
                            <td class="py-2 px-3 text-slate-500 font-sans text-xs">{{ $ph->user?->name ?? 'Sistem' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center text-slate-400 font-sans">Belum ada catatan riwayat perubahan harga.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

