@extends('layouts.app', ['header' => 'Laporan Kinerja & Transaksi Per Kasir'])

@section('content')
<div class="space-y-4">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-base font-bold text-slate-900">Rekapitulasi Kinerja Kasir & Operator Timbang</h2>
            <p class="text-xs text-slate-500">Melihat total nota yang dilayani, berat timbangan, dan perputaran kas per operator</p>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('reports.cashier') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
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
                    Tampilkan Rekap Kasir
                </button>
                <a href="{{ route('reports.cashier') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Cashiers Performance Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        @foreach($cashiers as $c)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-100 text-emerald-600 font-bold flex items-center justify-center text-sm border border-slate-200">
                        {{ strtoupper(substr($c->name, 0, 2)) }}
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">{{ $c->name }}</h3>
                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 text-slate-700">
                            {{ $c->role }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2 pt-2 border-t border-slate-100 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Total Nota Pembelian:</span>
                        <strong class="text-slate-900 font-mono">{{ $c->total_transactions ?? 0 }} Transaksi</strong>
                    </div>

                    <div class="flex justify-between text-slate-600">
                        <span>Total Berat Ditimbang:</span>
                        <strong class="text-slate-900 font-mono">{{ \App\Services\FormatHelper::weight($c->total_weight ?? 0) }} kg</strong>
                    </div>

                    <div class="flex justify-between text-slate-600 pt-1 border-t border-slate-100">
                        <span>Total Kas Dibayarkan:</span>
                        <strong class="text-emerald-700 font-mono text-sm">Rp {{ number_format($c->total_payout ?? 0, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="{{ route('purchases.index', ['cashier_id' => $c->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                       class="w-full py-1.5 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors">
                        <i data-lucide="list" class="w-3.5 h-3.5"></i>
                        <span>Lihat Riwayat Kasir Ini</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection

