@extends('layouts.app', ['header' => 'Master Data Material Scrap & Daur Ulang'])

@section('content')
<div class="space-y-4" x-data="{ quickPriceModal: false, activeItem: {} }">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900">Daftar Jenis Barang &amp; Standar Harga</h2>
            <p class="text-xs text-slate-500">Kelola harga beli &amp; jual {{ strtolower(\App\Models\BusinessSetting::commodityTerm()) }} per kilogram</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" 
                    @click="openWaPdfModal({
                        title: 'Kirim Daftar Harga Beli {{ \App\Models\BusinessSetting::commodityTerm() }} PDF',
                        subtitle: 'Kirim berkas dokumen PDF daftar harga beli {{ strtolower(\App\Models\BusinessSetting::commodityTerm()) }} ke nomor WhatsApp',
                        filename: 'Daftar_Harga_Beli_' + ('{{ date("Ymd") }}') + '.pdf',
                        fileDescription: 'Dokumen PDF Resmi Daftar Standar Harga Beli {{ \App\Models\BusinessSetting::commodityTerm() }} Depo',
                        pdfUrl: '{{ route('scrap-items.pdf-price-list') }}',
                        previewUrl: '{{ route('scrap-items.preview-wa') }}',
                        sendUrl: '{{ route('scrap-items.send-wa') }}',
                        submitButtonText: 'Kirim Daftar Harga PDF via WA'
                    })"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-colors"
                    title="Kirim Daftar Harga PDF via WhatsApp">
                <x-whatsapp-icon class="w-4 h-4" />
                <span>Kirim Daftar Harga (PDF)</span>
            </button>

            <a href="{{ route('scrap-items.export-excel') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white hover:bg-slate-50 text-emerald-700 border border-emerald-200 font-bold text-xs shadow-xs transition-colors">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                <span>Export Excel</span>
            </a>

            <a href="{{ route('scrap-items.pdf-price-list') }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200 shadow-xs transition-colors"
               title="Preview & Unduh Berkas PDF Daftar Standar Harga Beli">
                <i data-lucide="file-text" class="w-4 h-4 text-rose-600"></i>
                <span>Preview PDF Harga</span>
            </a>

            <a href="{{ route('scrap-items.print-price-list') }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs border border-slate-300 shadow-sm"
               title="Cetak / Layout Kertas Daftar Standar Harga Beli">
                <i data-lucide="printer" class="w-4 h-4 text-slate-600"></i>
                <span>Format Cetak</span>
            </a>

            <a href="{{ route('scrap-items.create') }}" 
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Barang</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('scrap-items.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Cari Nama / Kode</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Besi, tembaga, BSI-01..."
                           class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                <select name="category_id" class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                    <option value="">-- Semua Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs">
                    Cari Data
                </button>
                <a href="{{ route('scrap-items.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
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
                        <th class="py-2.5 px-3">Nama Barang</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3 text-right">Harga Beli/kg</th>
                        <th class="py-2.5 px-3 text-right">Harga Jual/kg</th>
                        <th class="py-2.5 px-3 text-right">Stok Gudang</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $idx => $item)
                        <tr class="hover:bg-slate-50 transition-colors {{ !$item->is_active ? 'opacity-60 bg-slate-50/50' : '' }}">
                            <td class="py-3 px-3 text-center text-slate-400">{{ $items->firstItem() + $idx }}</td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-700">{{ $item->code }}</td>
                            <td class="py-3 px-3 font-bold text-slate-900">
                                <div>{{ $item->name }}</div>
                                @if($item->description)
                                    <div class="text-[10px] text-slate-400 font-normal truncate max-w-xs">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-slate-600">{{ $item->category?->name ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700">
                                Rp {{ number_format($item->buy_price_per_kg, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-semibold text-slate-700">
                                Rp {{ number_format($item->sell_price_per_kg, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono">
                                <span class="font-bold {{ $item->isLowStock() ? 'text-amber-600' : 'text-slate-800' }}">
                                    {{ \App\Services\FormatHelper::weight($item->current_stock) }} kg
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($item->is_active)
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200 text-slate-700">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" 
                                            @click="activeItem = { id: {{ $item->id }}, name: '{{ addslashes($item->name) }}', buy: {{ $item->buy_price_per_kg }}, sell: {{ $item->sell_price_per_kg }} }; quickPriceModal = true"
                                            title="Ubah Harga Cepat"
                                            class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50">
                                        <i data-lucide="badge-dollar-sign" class="w-4 h-4"></i>
                                    </button>
                                    <a href="https://wa.me/?text={{ urlencode('*INFORMASI HARGA RONGSOK*' . "\n" . 'Barang: ' . $item->name . "\n" . 'Harga Beli: ' . \App\Services\FormatHelper::rupiah($item->buy_price_per_kg) . '/kg' . "\n" . 'Hubungi depo untuk informasi timbangan.') }}" 
                                       target="_blank"
                                       title="Bagikan Harga Barang via WhatsApp"
                                       class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50">
                                        <x-whatsapp-icon class="w-4 h-4" />
                                    </a>
                                    <a href="{{ route('scrap-items.edit', $item) }}" title="Edit Detail & Riwayat Harga"
                                       class="p-1 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                    <form action="{{ route('scrap-items.destroy', $item) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus barang {{ addslashes($item->name) }}? (Hanya bisa dihapus bila belum ada riwayat transaksi)');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Barang" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">Belum ada material scrap & daur ulang yang terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Price Update Modal -->
    <div x-show="quickPriceModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="quickPriceModal = false">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Perbarui Harga Beli / Jual</h3>
                    <p class="text-xs text-emerald-600 font-semibold" x-text="activeItem.name"></p>
                </div>
                <button type="button" @click="quickPriceModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="'{{ url('scrap-items') }}/' + activeItem.id + '/quick-price'" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Harga Beli Baru per kg (Rp)</label>
                    <input type="number" name="buy_price_per_kg" x-model="activeItem.buy" required step="50" min="0"
                           class="w-full text-sm font-mono p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Harga Jual Baru per kg (Rp)</label>
                    <input type="number" name="sell_price_per_kg" x-model="activeItem.sell" step="50" min="0"
                           class="w-full text-sm font-mono p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alasan Perubahan Harga</label>
                    <input type="text" name="reason" required placeholder="Contoh: Mengikuti fluktuasi harga lebur pabrik"
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="quickPriceModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm">
                        Simpan Harga Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

