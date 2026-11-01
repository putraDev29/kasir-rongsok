@extends('layouts.app', ['header' => 'Laporan Biaya Operasional Depo'])

@section('content')
<div class="space-y-4">

    <!-- KPI Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Beban Operasional Periode</span>
            <div class="text-xl font-bold text-red-600 mt-1">
                Rp {{ number_format($totalExpense, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400">Total kas keluar untuk biaya operasional</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Export Laporan Biaya</span>
                <div class="text-xs text-slate-500">Unduh atau cetak laporan</div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.expenses', array_merge(request()->query(), ['format' => 'csv'])) }}" 
                   class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Excel / CSV</span>
                </a>
                <a href="{{ route('reports.expenses', array_merge(request()->query(), ['format' => 'print'])) }}" target="_blank"
                   class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Print PDF</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('reports.expenses') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
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
                    Tampilkan
                </button>
                <a href="{{ route('reports.expenses') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600">
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
                        <th class="py-2.5 px-3">No. Biaya</th>
                        <th class="py-2.5 px-3">Tanggal</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3">Keterangan</th>
                        <th class="py-2.5 px-3">Penerima Dana</th>
                        <th class="py-2.5 px-3 text-right">Nominal (Rp)</th>
                        <th class="py-2.5 px-3">Dicatat Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @forelse($expenses as $exp)
                        <tr class="hover:bg-slate-50 transition-colors font-sans">
                            <td class="py-3 px-3 font-mono font-semibold text-slate-700">{{ $exp->expense_number }}</td>
                            <td class="py-3 px-3 text-slate-500 font-mono">{{ $exp->expense_date->format('d/m/Y') }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-800">
                                    {{ $exp->category?->name }}
                                </span>
                            </td>
                            <td class="py-3 px-3 font-medium text-slate-900">{{ $exp->description }}</td>
                            <td class="py-3 px-3 text-slate-600">{{ $exp->recipient ?? '-' }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-red-600">
                                Rp {{ number_format($exp->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-slate-500">{{ $exp->user?->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-sans">Belum ada data pengeluaran pada rentang tanggal ini.</td>
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

</div>
@endsection

