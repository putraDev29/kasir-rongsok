@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Period Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Ringkasan Operasional</div>
            <h2 class="text-lg font-bold text-slate-900">Dashboard Bisnis Rongsok</h2>
        </div>

        <!-- Period Filter Pills -->
        <div class="flex flex-wrap items-center gap-1.5 bg-slate-100 p-1 rounded-lg text-xs font-medium">
            <a href="{{ route('dashboard', ['period' => 'today']) }}" 
               class="px-3 py-1.5 rounded-md transition-colors {{ $period === 'today' ? 'bg-white text-emerald-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Hari Ini
            </a>
            <a href="{{ route('dashboard', ['period' => 'yesterday']) }}" 
               class="px-3 py-1.5 rounded-md transition-colors {{ $period === 'yesterday' ? 'bg-white text-emerald-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Kemarin
            </a>
            <a href="{{ route('dashboard', ['period' => 'this_week']) }}" 
               class="px-3 py-1.5 rounded-md transition-colors {{ $period === 'this_week' ? 'bg-white text-emerald-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Minggu Ini
            </a>
            <a href="{{ route('dashboard', ['period' => 'this_month']) }}" 
               class="px-3 py-1.5 rounded-md transition-colors {{ $period === 'this_month' ? 'bg-white text-emerald-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Bulan Ini
            </a>
            <a href="{{ route('dashboard', ['period' => 'this_year']) }}" 
               class="px-3 py-1.5 rounded-md transition-colors {{ $period === 'this_year' ? 'bg-white text-emerald-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Tahun Ini
            </a>
        </div>
    </div>

    <!-- PRIMARY METRIC STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Pembelian Rongsok -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Pembelian (Keluar Kas)</p>
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">
                    Rp {{ number_format($metrics['total_purchases_amount'], 0, ',', '.') }}
                </h3>
                <div class="flex items-center gap-1.5 mt-2 text-xs text-slate-600 font-medium">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] bg-emerald-100 text-emerald-800 font-semibold">
                        {{ $metrics['total_purchases_count'] }} Nota
                    </span>
                    <span>Telah dibayar kasir</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="hand-coins" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Total Berat Barang Diterima -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Timbangan Bersih</p>
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">
                    {{ number_format($metrics['total_purchases_weight'], 2, ',', '.') }} <span class="text-base font-semibold text-slate-500">kg</span>
                </h3>
                <div class="flex items-center gap-1.5 mt-2 text-xs text-slate-600 font-medium">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] bg-blue-100 text-blue-800 font-semibold">
                        {{ number_format($metrics['total_purchases_weight'] / 1000, 2) }} Ton
                    </span>
                    <span>Barang masuk gudang</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="scale" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Total Stok Gudang Saat Ini -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Stok Tersedia</p>
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">
                    {{ number_format($metrics['total_stock_kg'], 2, ',', '.') }} <span class="text-base font-semibold text-slate-500">kg</span>
                </h3>
                <div class="flex items-center gap-1.5 mt-2 text-xs text-slate-600 font-medium">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] bg-amber-100 text-amber-800 font-semibold">
                        Gudang
                    </span>
                    <span>Siap dijual / dipres</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="boxes" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Estimasi Nilai Stok -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Estimasi Nilai Persediaan</p>
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">
                    Rp {{ number_format($metrics['estimated_stock_value'], 0, ',', '.') }}
                </h3>
                <div class="flex items-center gap-1.5 mt-2 text-xs text-slate-600 font-medium">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] bg-purple-100 text-purple-800 font-semibold">
                        Valuasi HPP
                    </span>
                    <span>Berdasarkan harga beli</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <i data-lucide="calculator" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <!-- SECOND ROW: Sales, Expenses & Net Profit (Operational performance) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                <i data-lucide="trending-up" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Penjualan Partai (Omzet)</div>
                <div class="text-lg font-bold text-slate-900">
                    Rp {{ number_format($metrics['total_sales_amount'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500">{{ number_format($metrics['total_sales_weight'], 1) }} kg rongsok terjual</div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-red-100 text-red-700 flex items-center justify-center shrink-0">
                <i data-lucide="wallet" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Biaya Operasional</div>
                <div class="text-lg font-bold text-slate-900">
                    Rp {{ number_format($metrics['total_expenses_amount'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500">BBM, upah kuli, konsumsi</div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center shrink-0">
                <i data-lucide="badge-check" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 font-medium">Estimasi Laba Bersih Periode</div>
                <div class="text-lg font-bold {{ $metrics['net_profit_period'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                    Rp {{ number_format($metrics['net_profit_period'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500">Margin kotor - pengeluaran</div>
            </div>
        </div>
    </div>

    <!-- THIRD ROW: 7-Day Trend Chart & Top Stock Items -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- 7-Day Purchases Trend (Interactive Visual Bar Chart) -->
        <div class="lg:col-span-2 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Tren Pembelian Rongsok 7 Hari Terakhir</h3>
                    <p class="text-xs text-slate-500">Total pengeluaran timbangan harian</p>
                </div>
                <span class="text-xs font-semibold px-2 py-1 bg-slate-100 text-slate-700 rounded-md">
                    7 Hari Terakhir
                </span>
            </div>

            <!-- CSS Bar Chart -->
            @php
                $maxSum = max(1, ...$metrics['chart']['purchases']);
            @endphp
            <div class="pt-6 pb-2">
                <div class="grid grid-cols-7 gap-2 sm:gap-4 items-end h-48 border-b border-slate-200 pb-2">
                    @foreach($metrics['chart']['labels'] as $idx => $label)
                        @php
                            $val = $metrics['chart']['purchases'][$idx];
                            $kg = $metrics['chart']['weights'][$idx];
                            $heightPercent = max(6, round(($val / $maxSum) * 100));
                        @endphp
                        <div class="flex flex-col items-center gap-1 group relative">
                            <!-- Tooltip hover -->
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-12 z-10 bg-slate-900 text-white text-[10px] py-1 px-2 rounded pointer-events-none whitespace-nowrap shadow-md">
                                <div>Rp {{ number_format($val, 0, ',', '.') }}</div>
                                <div class="text-emerald-400">{{ number_format($kg, 1) }} kg</div>
                            </div>
                            <!-- Bar -->
                            <div class="w-full max-w-[42px] bg-emerald-500 hover:bg-emerald-600 rounded-t-md transition-all shadow-sm"
                                 style="height: {{ $heightPercent }}%;"></div>
                            <span class="text-[11px] font-medium text-slate-600 mt-1">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center justify-between mt-3 text-xs text-slate-500">
                    <span>Skala Relatif: Rp 0 s/d Rp {{ number_format($maxSum, 0, ',', '.') }}</span>
                    <span class="flex items-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                        Nominal Pembelian (Rp)
                    </span>
                </div>
            </div>
        </div>

        <!-- Top Stock Items in Warehouse -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-900">Stok Terbanyak di Gudang</h3>
                    <a href="{{ route('stock.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-semibold">
                        Lihat Semua
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($metrics['top_stock_items'] as $stockItem)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-800">{{ $stockItem->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $stockItem->code }} • {{ $stockItem->category?->name }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-bold text-slate-900">
                                    {{ number_format($stockItem->current_stock_qty, 1, ',', '.') }} kg
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    @ Rp {{ number_format($stockItem->average_buy_price, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-4 text-center text-xs text-slate-400">Belum ada data stok barang.</div>
                    @endforelse
                </div>
            </div>

            @if($metrics['low_stock_items']->count() > 0)
                <div class="mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                    <div class="font-semibold flex items-center gap-1.5 mb-1">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                        <span>Peringatan Stok Minimum!</span>
                    </div>
                    <p class="text-[11px]">Terdapat {{ $metrics['low_stock_items']->count() }} barang berada di bawah batas stok minimum.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- FOURTH ROW: Recent Purchase Transactions & Recent Audit Logs -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Recent Purchases Table -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Transaksi Penerimaan Terbaru</h3>
                    <p class="text-xs text-slate-500">Nota timbangan barang rongsok masuk</p>
                </div>
                <a href="{{ route('purchases.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-semibold">
                    Semua Transaksi &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">No. Nota</th>
                            <th class="py-2.5 px-3">Pemasok</th>
                            <th class="py-2.5 px-3 text-right">Berat Bersih</th>
                            <th class="py-2.5 px-3 text-right">Total Bayar</th>
                            <th class="py-2.5 px-3">Kasir</th>
                            <th class="py-2.5 px-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($metrics['recent_transactions'] as $tx)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-2.5 px-3 font-semibold text-slate-900">
                                    <a href="{{ route('purchases.show', $tx) }}" class="text-emerald-600 hover:underline">
                                        {{ $tx->invoice_number }}
                                    </a>
                                    <div class="text-[10px] text-slate-400">{{ $tx->transaction_date->format('H:i') }} WIB</div>
                                </td>
                                <td class="py-2.5 px-3 text-slate-700">
                                    {{ $tx->supplier_name }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-medium text-slate-800">
                                    {{ number_format($tx->total_net_weight, 2, ',', '.') }} kg
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold text-slate-900">
                                    Rp {{ number_format($tx->final_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-slate-600">
                                    {{ $tx->user?->name }}
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <a href="{{ route('purchases.print', $tx) }}" target="_blank" title="Cetak Nota"
                                       class="inline-flex p-1.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">Belum ada transaksi hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Audit Activities -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Aktivitas Sistem Terbaru</h3>
            <div class="space-y-3">
                @forelse($metrics['recent_activities'] as $act)
                    <div class="flex items-start gap-2.5 text-xs">
                        <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 mt-0.5">
                            <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-slate-800 font-medium leading-snug">{{ $act->description }}</p>
                            <span class="text-[10px] text-slate-400">{{ $act->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">Belum ada aktivitas tercatat.</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

