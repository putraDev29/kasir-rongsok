@extends('layouts.app', ['header' => 'Pengeluaran Operasional Usaha'])

@section('content')
<div class="space-y-4" x-data="{ expenseModal: false, categoryModal: false, isManualCategory: false, categoryId: '' }">

    <!-- Header Actions & KPI -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                Total Beban @if($startDate && $startDate === $endDate && $startDate === date('Y-m-d')) Hari Ini ({{ date('d/m/Y') }}) @elseif($startDate && $endDate) Periode ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}) @else Keseluruhan Waktu @endif
            </span>
            <h2 class="text-2xl font-black text-slate-900 mt-0.5 font-mono">
                Rp {{ number_format($totalAmount, 0, ',', '.') }}
            </h2>
            <p class="text-xs text-slate-500">Biaya armada, upah kuli timbang, konsumsi, dan operasional depo</p>
        </div>
    </div>

    <!-- Quick Date Filter Pills & Search -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5 text-xs">
            <div class="flex items-center gap-1.5 font-medium">
                <span class="text-slate-400">Pilihan Cepat:</span>
                <a href="{{ route('expenses.index') }}" 
                   class="px-2.5 py-1 rounded-md transition-colors {{ (!$allDates && $startDate === date('Y-m-d') && $endDate === date('Y-m-d')) ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Hari Ini (Saat Ini)
                </a>
                <a href="{{ route('expenses.index', ['start_date' => date('Y-m-d', strtotime('-1 day')), 'end_date' => date('Y-m-d', strtotime('-1 day'))]) }}" 
                   class="px-2.5 py-1 rounded-md transition-colors {{ ($startDate === date('Y-m-d', strtotime('-1 day')) && $endDate === date('Y-m-d', strtotime('-1 day'))) ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Kemarin
                </a>
                <a href="{{ route('expenses.index', ['start_date' => date('Y-m-01'), 'end_date' => date('Y-m-d')]) }}" 
                   class="px-2.5 py-1 rounded-md transition-colors {{ ($startDate === date('Y-m-01') && $endDate === date('Y-m-d')) ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Bulan Ini
                </a>
                <a href="{{ route('expenses.index', ['all_dates' => 1]) }}" 
                   class="px-2.5 py-1 rounded-md transition-colors {{ $allDates ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Semua Waktu
                </a>
            </div>
            <span class="text-[11px] text-slate-400">Menampilkan data sesuai tanggal saat itu</span>
        </div>

        <form method="GET" action="{{ route('expenses.index') }}" class="space-y-3">
            <!-- Row 1: Nomor Nota/Cari, Bulan, Tahun -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cari Keterangan / Penerima</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="BBM, makan, solar..."
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

                <x-month-year-filter :selected-month="$selectedMonth ?? request('month')" :selected-year="$selectedYear ?? request('year')" />
            </div>

            <!-- Row 2: Dari Tanggal, Sampai Tanggal, Status/Kategori, Terapkan Filter -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                           class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                           class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Pengeluaran</label>
                    <select name="category_id" class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                        <option value="">-- Semua Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Terapkan Filter</span>
                    </button>
                    <a href="{{ route('expenses.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Daftar Pengeluaran Operasional</span>
                <span class="text-[11px] text-slate-500">Total ditampilkan: {{ $expenses->total() }} transaksi pengeluaran</span>
            </div>

            <!-- Action Buttons on Table Card Header -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Manage Categories (Task 12) -->
                <button type="button" @click="categoryModal = true"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs border border-slate-300 flex items-center gap-1">
                    <i data-lucide="tag" class="w-3.5 h-3.5 text-slate-600"></i>
                    <span>Kelola Kategori</span>
                </button>

                <!-- Print & Preview Laporan -->
                <a href="{{ route('expenses.print', request()->query()) }}" target="_blank"
                   class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm"
                   title="Cetak Laporan Biaya Operasional (Kertas HVS A4)">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print (HVS)</span>
                </a>

                <button type="button"
                   onclick="openPdfPreview('{{ route('expenses.pdf', request()->query()) }}', 'Laporan Biaya Operasional', 'Laporan_Biaya_Operasional_{{ ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd') }}.pdf', 'Pratinjau Dokumen Rekap Biaya')"
                   class="px-2.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm transition-colors cursor-pointer"
                   title="Pratinjau Dokumen PDF Rekap Biaya">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                    <span>Preview PDF</span>
                </button>

                <!-- Send WA (Task 13) -->
                <button type="button" 
                        @click="openWaPdfModal({
                            title: 'Kirim Rekap Biaya Operasional PDF via WhatsApp',
                            subtitle: 'Kirim berkas dokumen PDF rincian beban pengeluaran ke nomor WhatsApp',
                            filename: 'Laporan_Biaya_Operasional_' + ('{{ $startDate && $endDate ? "{$startDate}_sd_{$endDate}" : date("Ymd") }}') + '.pdf',
                            fileDescription: 'Dokumen PDF Resmi Laporan Biaya Operasional Depo',
                            printUrl: '{{ route('expenses.print', request()->query()) }}',
                            pdfUrl: '{{ route('expenses.pdf', request()->query()) }}',
                            previewUrl: '{{ route('expenses.wa-preview', request()->query()) }}',
                            sendUrl: '{{ route('expenses.send-wa') }}',
                            sendPayload: {
                                search: '{{ addslashes($search) }}',
                                start_date: '{{ $startDate }}',
                                end_date: '{{ $endDate }}',
                                category_id: '{{ $categoryId }}',
                                all_dates: '{{ $allDates ? 1 : 0 }}'
                            },
                            submitButtonText: 'Kirim PDF Biaya via WA'
                        })"
                        class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1 shadow-sm"
                        title="Kirim Rekap Biaya Operasional PDF via WhatsApp">
                    <x-whatsapp-icon class="w-3.5 h-3.5" />
                    <span>Kirim WA (PDF)</span>
                </button>

                <!-- Export Excel -->
                <a href="{{ route('expenses.excel', request()->query()) }}"
                   class="px-2.5 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                    <i data-lucide="sheet" class="w-3.5 h-3.5"></i>
                    <span>Export Excel</span>
                </a>

                <!-- Add Expense -->
                <button type="button" @click="expenseModal = true"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>Catat Pengeluaran</span>
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3">No. Biaya</th>
                        <th class="py-2.5 px-3">Tanggal</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3">Deskripsi Pengeluaran</th>
                        <th class="py-2.5 px-3">Penerima Dana</th>
                        <th class="py-2.5 px-3 text-right">Nominal (Rp)</th>
                        <th class="py-2.5 px-3">Dicatat Oleh</th>
                        <th class="py-2.5 px-3 text-center">Hapus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $exp)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-3 font-mono font-semibold text-slate-700">{{ $exp->expense_number }}</td>
                            <td class="py-3 px-3 text-slate-500">{{ $exp->expense_date->format('d/m/Y') }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-800">
                                    {{ $exp->category?->name }}
                                </span>
                            </td>
                            <td class="py-3 px-3 font-medium text-slate-900">{{ $exp->description }}</td>
                            <td class="py-3 px-3 text-slate-600">{{ $exp->recipient ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-mono font-black text-slate-900 text-sm">
                                Rp {{ number_format($exp->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-slate-500">{{ $exp->user?->name }}</td>
                            <td class="py-3 px-3 text-center">
                                <form action="{{ route('expenses.destroy', $exp) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus catatan pengeluaran ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">Belum ada pengeluaran yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Catat Pengeluaran -->
    <div x-show="expenseModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="expenseModal = false">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Catat Pengeluaran Operasional</h3>
                <button type="button" @click="expenseModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('expenses.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Kategori Pengeluaran *</label>
                        <button type="button" @click="isManualCategory = !isManualCategory; if(isManualCategory) categoryId = '';" 
                                class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline">
                            <span x-text="isManualCategory ? '← Pilih dari Daftar Kategori' : '+ Ketik Kategori Baru'"></span>
                        </button>
                    </div>

                    <!-- Dropdown Select Category -->
                    <div x-show="!isManualCategory">
                        <select name="expense_category_id" x-model="categoryId"
                                @change="if($event.target.value === '__new__') { isManualCategory = true; categoryId = ''; }"
                                :required="!isManualCategory"
                                class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                            <option value="__new__">+ Ketik Kategori Baru Lainnya...</option>
                        </select>
                    </div>

                    <!-- Manual Input Category -->
                    <div x-show="isManualCategory" style="display: none;" class="space-y-1">
                        <input type="text" name="new_category_name" placeholder="Ketik nama kategori baru (contoh: Servis Timbangan)..."
                               :required="isManualCategory"
                               class="w-full text-xs p-2 rounded-lg border border-emerald-500 focus:ring-emerald-500 font-medium bg-emerald-50/40 text-slate-900">
                        <p class="text-[10px] text-slate-400">Kategori baru akan otomatis tersimpan ke master kategori.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tanggal *</label>
                        <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nominal (Rp) *</label>
                        <input type="text" inputmode="numeric" name="amount" required placeholder="Contoh: 150.000"
                               class="w-full text-xs font-mono font-bold p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 text-slate-900 format-rupiah">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Deskripsi / Peruntukan *</label>
                    <textarea name="description" required rows="2" placeholder="Contoh: Beli BBM solar truk pick up L300 untuk angkut kardus..."
                              class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Penerima Dana</label>
                        <input type="text" name="recipient" placeholder="SPBU / Nama Kuli"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Metode Bayar</label>
                        <select name="payment_method_id" class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="expenseModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm">
                        Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Kelola Kategori Pengeluaran (Task 12: Tambah & Hapus Kategori) -->
    <div x-show="categoryModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="categoryModal = false">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <i data-lucide="tag" class="w-5 h-5 text-emerald-600"></i>
                    <h3 class="text-sm font-bold text-slate-900">Kelola Kategori Pengeluaran</h3>
                </div>
                <button type="button" @click="categoryModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form Tambah Kategori -->
            <form action="{{ route('expenses.categories.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="name" required placeholder="Nama kategori baru..."
                       class="flex-1 text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                <button type="submit" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm shrink-0 flex items-center gap-1">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Tambah</span>
                </button>
            </form>

            <!-- List Kategori dengan Tombol Hapus -->
            <div class="border border-slate-200 rounded-xl overflow-hidden max-h-60 overflow-y-auto">
                <table class="w-full text-left text-xs divide-y divide-slate-100">
                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500">
                        <tr>
                            <th class="p-2.5">Nama Kategori</th>
                            <th class="p-2.5 text-center w-24">Jml Biaya</th>
                            <th class="p-2.5 text-center w-16">Hapus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($categories as $cat)
                            <tr class="hover:bg-slate-50">
                                <td class="p-2.5 font-semibold text-slate-800">{{ $cat->name }}</td>
                                <td class="p-2.5 text-center text-slate-500">{{ $cat->expenses()->count() }} data</td>
                                <td class="p-2.5 text-center">
                                    <form action="{{ route('expenses.categories.destroy', $cat) }}" method="POST" onsubmit="return confirm('Hapus kategori {{ $cat->name }} dari daftar dropdown?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50" title="Hapus Kategori">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-2">
                <button type="button" @click="categoryModal = false" class="px-3.5 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

