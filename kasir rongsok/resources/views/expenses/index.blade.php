@extends('layouts.app', ['header' => 'Pengeluaran Operasional Usaha'])

@section('content')
<div class="space-y-4" x-data="{ expenseModal: false, isManualCategory: false, categoryId: '' }">

    <!-- Header Actions & KPI -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                Total Beban @if($startDate && $startDate === $endDate && $startDate === date('Y-m-d')) Hari Ini ({{ date('d/m/Y') }}) @elseif($startDate && $endDate) Periode ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}) @else Keseluruhan Waktu @endif
            </span>
            <h2 class="text-2xl font-black text-slate-900 mt-0.5 font-mono">
                Rp {{ number_format($totalAmount, 0, ',', '.') }}
            </h2>
            <p class="text-xs text-slate-500">Biaya armada, upah kuli timbang, konsumsi, dan operasional depo</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="expenseModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Catat Pengeluaran Baru</span>
            </button>
        </div>
    </div>

    <!-- Quick Date Filter Pills & Search -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm space-y-3">
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

        <form method="GET" action="{{ route('expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Cari Keterangan / Penerima</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="BBM, makan, solar..."
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

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs">
                    Filter
                </button>
                <a href="{{ route('expenses.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
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
                        <input type="number" step="1000" min="1000" name="amount" required placeholder="Contoh: 150000"
                               class="w-full text-xs font-mono font-bold p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 text-slate-900">
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

</div>
@endsection

