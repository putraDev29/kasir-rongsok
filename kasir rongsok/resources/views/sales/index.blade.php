@extends('layouts.app', ['header' => 'Penjualan Rongsok Partai Besar (Wholesale)'])

@section('content')
<div class="space-y-4">

    <!-- Header Actions & KPI Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                <i data-lucide="truck" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Total Omzet Penjualan</div>
                <div class="text-lg font-bold text-slate-900">
                    Rp {{ number_format($totalSales, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                <i data-lucide="badge-check" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Estimasi Laba Kotor Penjualan</div>
                <div class="text-lg font-bold text-emerald-700">
                    Rp {{ number_format($totalProfit, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs text-slate-500 font-medium">Kirim ke Pabrik Peleburan</div>
                <div class="text-sm font-bold text-slate-800">Penjualan Grosir</div>
            </div>
            <a href="{{ route('sales.create') }}" 
               class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Catat Penjualan Baru</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('sales.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Cari No. Nota / Pembeli</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="PJ-... atau PT..."
                           class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>
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
                    Filter Penjualan
                </button>
                <a href="{{ route('sales.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600">
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
                        <th class="py-2.5 px-3">No. Nota</th>
                        <th class="py-2.5 px-3">Tanggal</th>
                        <th class="py-2.5 px-3">Nama Pembeli / Pabrik</th>
                        <th class="py-2.5 px-3 text-right">Berat Terjual</th>
                        <th class="py-2.5 px-3 text-right">Total Nilai Jual</th>
                        <th class="py-2.5 px-3 text-right">Estimasi Laba</th>
                        <th class="py-2.5 px-3">Operator</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $sale)
                        <tr class="hover:bg-slate-50 transition-colors {{ $sale->isCancelled() ? 'bg-red-50/40' : '' }}">
                            <td class="py-3 px-3 font-semibold">
                                <a href="{{ route('sales.show', $sale) }}" class="text-blue-600 hover:underline">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td class="py-3 px-3 text-slate-500">
                                {{ $sale->transaction_date->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-3 font-bold text-slate-900">
                                {{ $sale->buyer_name }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-medium text-slate-800">
                                {{ number_format($sale->total_weight, 2, ',', '.') }} kg
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                Rp {{ number_format($sale->final_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700">
                                Rp {{ number_format($sale->estimated_profit, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-slate-600">
                                {{ $sale->user?->name }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($sale->isCancelled())
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">
                                        Dibatalkan
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Selesai
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('sales.show', $sale) }}" title="Rincian"
                                       class="p-1 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('sales.print', $sale) }}" target="_blank" title="Cetak Surat Jalan / Invoice"
                                       class="p-1 rounded text-blue-600 hover:text-blue-800 hover:bg-blue-50">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">Belum ada transaksi penjualan rongsok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-3 border-t border-slate-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

