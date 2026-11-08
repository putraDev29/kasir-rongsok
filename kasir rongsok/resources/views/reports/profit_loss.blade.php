@extends('layouts.app', ['header' => 'Laporan Laba / Rugi Usaha (Profit & Loss)'])

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Laporan Laba & Rugi Komprehensif</h2>
            <p class="text-xs text-slate-500">Omzet penjualan, HPP barang terjual, slip gaji karyawan, beban operasional, dan nilai aset persediaan masuk</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('reports.profit-loss', array_merge(request()->query(), ['format' => 'print'])) }}" target="_blank"
               class="px-3.5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak / Print PDF Laba Rugi</span>
            </a>
        </div>
    </div>

    <!-- Date Range Filter & Quick Pills -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm space-y-3">
        <!-- Quick Date Filter Pills -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5 text-xs">
            <div class="flex items-center gap-1.5 font-medium">
                <span class="text-slate-400">Pilihan Cepat:</span>
                <a href="{{ route('reports.profit-loss') }}" 
                   class="px-2.5 py-1 rounded-md transition-colors {{ (!$allDates && $startDate === date('Y-m-01') && $endDate === date('Y-m-t')) ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Bulan Ini (1 - {{ date('t M Y') }})
                </a>
                <a href="{{ route('reports.profit-loss', ['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d')]) }}" 
                   class="px-2.5 py-1 rounded-md transition-colors {{ (!$allDates && $startDate === date('Y-m-d') && $endDate === date('Y-m-d')) ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Hari Ini
                </a>
                <a href="{{ route('reports.profit-loss', ['start_date' => date('Y-m-d', strtotime('-1 day')), 'end_date' => date('Y-m-d', strtotime('-1 day'))]) }}" 
                   class="px-2.5 py-1 rounded-md transition-colors {{ ($startDate === date('Y-m-d', strtotime('-1 day')) && $endDate === date('Y-m-d', strtotime('-1 day'))) ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Kemarin
                </a>
                <a href="{{ route('reports.profit-loss', ['all_dates' => 1]) }}" 
                   class="px-2.5 py-1 rounded-md transition-colors {{ $allDates ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Semua Periode
                </a>
            </div>
            <span class="text-[11px] text-slate-400">Default: 1 Bulan Penuh (Tgl 1 s/d Akhir Bulan)</span>
        </div>

        <form method="GET" action="{{ route('reports.profit-loss') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
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
                    Tampilkan Laporan
                </button>
                <a href="{{ route('reports.profit-loss') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600" title="Reset ke Hari Ini">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Top KPI Highlights -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Penambahan Aset Stok (Beli Kasir) -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider block">Aset Stok Masuk (Beli)</span>
                <span class="text-[10px] bg-blue-100 text-blue-800 font-semibold px-2 py-0.5 rounded-full">Aset Gudang</span>
            </div>
            <div class="text-xl font-black text-slate-900 mt-1 font-mono">
                {{ \App\Services\FormatHelper::weight($totalPurchasesWeight) }} kg
            </div>
            <div class="text-xs font-bold text-slate-700 font-mono mt-0.5">
                Modal Beli: Rp {{ number_format($totalPurchases, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-500 font-semibold mt-1">
                Total {{ $totalPurchasesTxCount }} Transaksi Masuk
            </div>
            <p class="text-[9px] text-slate-400 mt-1 leading-tight">* Pembelian menambah aset persediaan, bukan beban minus</p>
        </div>

        <!-- Barang Keluar -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Barang Keluar (Jual Pabrik)</span>
            <div class="text-xl font-black text-slate-900 mt-1 font-mono">
                {{ \App\Services\FormatHelper::weight($totalSalesWeight) }} kg
            </div>
            <div class="text-xs font-bold text-emerald-600 font-mono mt-0.5">
                Omzet: Rp {{ number_format($totalSales, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1">{{ $totalSalesTxCount }} Penjualan Partai Grosir</div>
        </div>

        <!-- Laba Kotor Penjualan -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Laba Kotor Penjualan</span>
            <div class="text-xl font-black text-emerald-700 mt-1 font-mono">
                Rp {{ number_format($grossProfit, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-500 mt-0.5">Omzet dikurangi HPP timbangan terjual</div>
        </div>

        <!-- Laba Bersih Operasional -->
        <div class="bg-white p-4 rounded-xl border-2 {{ $netProfit >= 0 ? 'border-emerald-500 bg-emerald-50/20' : 'border-amber-500 bg-amber-50/20' }} shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider block {{ $netProfit >= 0 ? 'text-emerald-800' : 'text-amber-800' }}">Laba Bersih Operasional</span>
            <div class="text-2xl font-black mt-1 font-mono {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                Rp {{ number_format($netProfit, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-500 mt-0.5">Setelah dikurangi beban gaji & operasional</div>
            @if($totalSales == 0)
                <div class="mt-1.5 text-[10px] font-medium text-amber-700 bg-amber-100/60 rounded px-1.5 py-0.5 inline-block">
                    Belum ada pengiriman partai ke pabrik
                </div>
            @endif
        </div>
    </div>

    <!-- MAIN FINANCIAL STATEMENT (IKHTISAR LABA RUGI) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        
        <div class="text-center pb-4 border-b border-slate-200">
            <h3 class="text-lg font-black uppercase text-slate-900 tracking-tight">LAPORAN LABA RUGI OPERASIONAL DEPO</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                @if($startDate && $endDate)
                    Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d F Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</strong>
                @else
                    Periode: <strong>Semua Waktu / Kumulatif Keseluruhan</strong>
                @endif
            </p>
        </div>

        <!-- Keterangan Transparansi Aset & Akuntansi Depo Rongsok -->
        <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200 text-xs text-blue-900 flex items-start gap-3">
            <i data-lucide="info" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5"></i>
            <div class="space-y-1">
                <span class="font-bold text-blue-950 block">Prinsip Akuntansi Kasir Rongsok (Stok & Persediaan):</span>
                <p class="text-[11px] text-blue-800 leading-relaxed">
                    Pembelian rongsok dari masyarakat/pengepul pada periode ini sebesar <strong>Rp {{ number_format($totalPurchases, 0, ',', '.') }}</strong> ({{ \App\Services\FormatHelper::weight($totalPurchasesWeight) }} kg) dicatat sebagai <strong>Penambahan Aset Persediaan Fisik Gudang</strong> dan <strong>bukan beban yang mengurangi laba</strong>. Modal kas terkonversi menjadi aset persediaan fisik rongsok di gudang depo untuk siap disortir dan dijual kembali ke pabrik peleburan.
                </p>
            </div>
        </div>

        <div class="space-y-6 text-sm">
            
            <!-- 1. Pendapatan Penjualan -->
            <div class="space-y-2">
                <div class="flex justify-between items-center font-bold text-slate-900 border-b border-slate-200 pb-1 uppercase tracking-wider text-xs">
                    <span>1. Omzet Penjualan</span>
                    <span class="font-mono text-sm">Rp {{ number_format($totalSales, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 pl-4">
                    <span>Penjualan Material Scrap & Daur Ulang Partai Grosir / Kirim ke Pabrik ({{ \App\Services\FormatHelper::weight($totalSalesWeight) }} kg)</span>
                    <span class="font-mono font-medium">Rp {{ number_format($totalSales, 0, ',', '.') }}</span>
                </div>
                @if($totalSales == 0)
                    <div class="pl-4 text-[11px] text-slate-400 italic">
                        Belum ada pengiriman partai besar ke pabrik pada periode ini.
                    </div>
                @endif
            </div>

            <!-- 2. HPP -->
            <div class="space-y-2">
                <div class="flex justify-between items-center font-bold text-slate-900 border-b border-slate-200 pb-1 uppercase tracking-wider text-xs">
                    <span>2. Pembelian (HPP TIMBANGAN TERJUAL)</span>
                    <span class="font-mono text-sm text-red-600">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600 pl-4">
                    <span>Pembelian Material Scrap & Daur Ulang yang Terjual</span>
                    <span class="font-mono font-medium">Rp {{ number_format($totalCogs, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- LABA KOTOR -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex justify-between items-center font-black text-slate-900 text-sm">
                <span>LABA KOTOR PENJUALAN (GROSS PROFIT):</span>
                <span class="font-mono text-base {{ $grossProfit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                    Rp {{ number_format($grossProfit, 0, ',', '.') }}
                </span>
            </div>

            <!-- 3. Beban Operasional -->
            <div class="space-y-4">
                <div class="flex justify-between items-center font-bold text-slate-900 border-b border-slate-200 pb-1 uppercase tracking-wider text-xs">
                    <span>3. BEBAN & BIAYA OPERASIONAL USAHA</span>
                    <span class="font-mono text-sm text-red-600">(Rp {{ number_format($totalOperatingExpenses, 0, ',', '.') }})</span>
                </div>

                <!-- Sub-bagian 3.A: Beban Gaji Karyawan -->
                <div class="pl-4 space-y-1">
                    <div class="flex justify-between text-xs font-bold text-slate-800">
                        <span>A. Beban Gaji & Upah Karyawan (Slip Gaji Terbit Periode Ini)</span>
                        <span class="font-mono text-red-700">Rp {{ number_format($totalSalaries, 0, ',', '.') }}</span>
                    </div>
                    <div class="text-[11px] text-slate-500 pl-2">
                        Total {{ $salarySlips->count() }} dokumen slip gaji dibayarkan pada periode ini.
                    </div>
                </div>

                <!-- Sub-bagian 3.B: Beban Operasional Gudang (Expenses) -->
                <div class="pl-4 space-y-2">
                    <div class="flex justify-between text-xs font-bold text-slate-800">
                        <span>B. Pengeluaran Operasional Gudang & Depo</span>
                        <span class="font-mono text-red-700">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</span>
                    </div>

                    <div class="divide-y divide-slate-100 text-xs pl-2 space-y-1">
                        @forelse($expensesByCategory as $expCat)
                            <div class="flex justify-between pt-1 text-slate-600">
                                <span>• {{ $expCat->name }}</span>
                                <span class="font-mono text-slate-800">Rp {{ number_format($expCat->total, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <div class="text-slate-400 py-0.5 text-[11px] italic">Tidak ada pengeluaran operasional lain pada periode ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- LABA BERSIH -->
            <div class="p-5 rounded-2xl border-2 {{ $netProfit >= 0 ? 'bg-emerald-50 border-emerald-500 text-emerald-950' : 'bg-amber-50 border-amber-500 text-amber-950' }} flex justify-between items-center font-black">
                <div>
                    <span class="text-base uppercase tracking-tight">ESTIMASI LABA BERSIH USAHA (NET PROFIT):</span>
                    <div class="text-xs font-normal opacity-80 mt-0.5">Laba kotor setelah dikurangi beban gaji dan biaya operasional periode ini</div>
                </div>
                <div class="font-mono text-2xl {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </div>
            </div>

        </div>

    </div>

    <!-- 1. DETAIL RINCIAN SISA STOK BARANG & HARGA -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                    <i data-lucide="boxes" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Rincian Sisa Stok Barang &amp; Harga</h4>
                    <p class="text-[10px] text-slate-500">Stok fisik persediaan di depo saat ini beserta harga beli depo dan harga jual pabrik</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-800 font-mono font-bold">
                    Total Stok: {{ \App\Services\FormatHelper::weight($totalCurrentStock) }} kg
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 font-mono font-bold">
                    Nilai Aset: Rp {{ number_format($totalCurrentStockValue, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Nama Material / Barang</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3 text-center">Satuan</th>
                        <th class="py-2.5 px-3 text-right">Sisa Stok Fisik</th>
                        <th class="py-2.5 px-3 text-right">Harga Beli (@ Rp)</th>
                        <th class="py-2.5 px-3 text-right">Harga Jual Pabrik (@ Rp)</th>
                        <th class="py-2.5 px-4 text-right">Total Nilai Modal Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stockBalanceItems as $idx => $stk)
                        <tr class="hover:bg-slate-50/80 {{ $stk->current_stock > 0 ? 'bg-indigo-50/20' : '' }}">
                            <td class="py-2 px-3 text-center text-slate-400 font-mono text-[11px]">{{ $idx + 1 }}</td>
                            <td class="py-2 px-3 font-semibold text-slate-900">{{ $stk->name }}</td>
                            <td class="py-2 px-3 text-slate-500 text-[11px]">{{ $stk->category_name }}</td>
                            <td class="py-2 px-3 text-center text-slate-500 font-mono uppercase text-[10px]">{{ $stk->unit }}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold {{ $stk->current_stock > 0 ? 'text-indigo-700' : 'text-slate-400' }}">
                                {{ \App\Services\FormatHelper::weight($stk->current_stock) }} {{ $stk->unit }}
                            </td>
                            <td class="py-2 px-3 text-right font-mono text-slate-700">Rp {{ number_format($stk->buy_price, 0, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right font-mono text-emerald-700 font-semibold">Rp {{ number_format($stk->sell_price, 0, ',', '.') }}</td>
                            <td class="py-2 px-4 text-right font-mono font-bold text-slate-900">Rp {{ number_format($stk->stock_value, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-slate-400">Belum ada data barang rongsok terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                    <tr>
                        <td colspan="4" class="py-2.5 px-3 text-slate-800">TOTAL SISA STOK & NILAI MODAL</td>
                        <td class="py-2.5 px-3 text-right font-mono text-indigo-700 font-black">{{ \App\Services\FormatHelper::weight($totalCurrentStock) }} kg</td>
                        <td colspan="2" class="py-2.5 px-3 text-center text-slate-400 text-[11px] font-normal">-</td>
                        <td class="py-2.5 px-4 text-right font-mono text-slate-900 font-black">Rp {{ number_format($totalCurrentStockValue, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 2. DETAIL RINCIAN BARANG MASUK (PEMBELIAN) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Rincian Barang Masuk (Pembelian)</h4>
                    <p class="text-[10px] text-slate-500">Penambahan fisik stok persediaan rongsok masuk pada periode terpilih</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded bg-blue-50 text-blue-800 font-mono font-bold">
                    {{ \App\Services\FormatHelper::weight($totalPurchasesWeight) }} kg
                </span>
                <span class="px-2.5 py-1 rounded bg-slate-100 text-slate-800 font-mono font-bold">
                    Rp {{ number_format($totalPurchases, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Jenis Barang</th>
                        <th class="py-2.5 px-3 text-right">Harga Beli (@ Rp/kg)</th>
                        <th class="py-2.5 px-3 text-right">Berat Masuk (kg)</th>
                        <th class="py-2.5 px-4 text-right">Total Modal Beli</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($incomingItems as $idx => $inItem)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-900">{{ $inItem->item_name }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-700">Rp {{ number_format($inItem->avg_buy_price, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-blue-700">{{ \App\Services\FormatHelper::weight($inItem->total_weight) }} kg</td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">Rp {{ number_format($inItem->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">Tidak ada pembelian rongsok pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                    <tr>
                        <td colspan="3" class="py-2.5 px-3 text-slate-800">TOTAL STOK MASUK</td>
                        <td class="py-2.5 px-3 text-right font-mono text-blue-700 font-black">{{ \App\Services\FormatHelper::weight($totalPurchasesWeight) }} kg</td>
                        <td class="py-2.5 px-4 text-right font-mono text-slate-900 font-black">Rp {{ number_format($totalPurchases, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 3. DETAIL RINCIAN BARANG KELUAR (PENJUALAN) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Rincian Barang Keluar (Penjualan)</h4>
                    <p class="text-[10px] text-slate-500">Pengiriman partai besar ke pabrik/peleburan beserta harga beli modal (HPP) dan harga jual pabrik</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded bg-emerald-50 text-emerald-800 font-mono font-bold">
                    {{ \App\Services\FormatHelper::weight($totalSalesWeight) }} kg
                </span>
                <span class="px-2.5 py-1 rounded bg-slate-100 text-slate-800 font-mono font-bold">
                    Omzet: Rp {{ number_format($totalSales, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Jenis Barang</th>
                        <th class="py-2.5 px-3 text-right">Berat Keluar (kg)</th>
                        <th class="py-2.5 px-3 text-right">Harga Beli (@ Rp/kg)</th>
                        <th class="py-2.5 px-3 text-right">Harga Jual (@ Rp/kg)</th>
                        <th class="py-2.5 px-3 text-right">Total Modal HPP</th>
                        <th class="py-2.5 px-3 text-right">Total Omzet Jual</th>
                        <th class="py-2.5 px-4 text-right">Laba Kotor Penjualan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($outgoingItems as $idx => $outItem)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-900">{{ $outItem->item_name }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800">{{ \App\Services\FormatHelper::weight($outItem->total_weight) }} kg</td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-600">Rp {{ number_format($outItem->avg_buy_price, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-emerald-700 font-semibold">Rp {{ number_format($outItem->avg_sell_price, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-red-700">Rp {{ number_format($outItem->total_cogs, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">Rp {{ number_format($outItem->total_amount, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-4 text-right font-mono font-black text-emerald-600">Rp {{ number_format($outItem->total_profit, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-slate-400">Tidak ada penjualan grosir pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                    <tr>
                        <td colspan="2" class="py-2.5 px-3 text-slate-800">TOTAL PENJUALAN</td>
                        <td class="py-2.5 px-3 text-right font-mono text-emerald-700 font-black">{{ \App\Services\FormatHelper::weight($totalSalesWeight) }} kg</td>
                        <td colspan="2" class="py-2.5 px-3 text-center text-slate-400 text-[11px] font-normal">-</td>
                        <td class="py-2.5 px-3 text-right font-mono text-red-700 font-black">Rp {{ number_format($totalCogs, 0, ',', '.') }}</td>
                        <td class="py-2.5 px-3 text-right font-mono text-slate-900 font-black">Rp {{ number_format($totalSales, 0, ',', '.') }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-emerald-700 font-black">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- DETAIL RINCIAN SLIP GAJI KARYAWAN -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                    <i data-lucide="banknote" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Rincian Slip Gaji Karyawan (Payroll Periode Ini)</h4>
                    <p class="text-[10px] text-slate-500">Beban penggajian operator kasir, penimbang, dan staff gudang</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-slate-400 block">Total Gaji Dibayarkan</span>
                <span class="font-mono text-xs font-bold text-red-600">Rp {{ number_format($totalSalaries, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-2.5 px-4">No. Slip & Tanggal</th>
                        <th class="py-2.5 px-4">Nama Karyawan</th>
                        <th class="py-2.5 px-4">Periode</th>
                        <th class="py-2.5 px-4 text-right">Gaji Pokok</th>
                        <th class="py-2.5 px-4 text-right">Tunjangan/Bonus</th>
                        <th class="py-2.5 px-4 text-right">Potongan (Kasbon)</th>
                        <th class="py-2.5 px-4 text-right">Gaji Bersih (THP)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($salarySlips as $slip)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-2.5 px-4 font-mono font-bold text-slate-700">
                                <a href="{{ route('salary-slips.show', $slip) }}" class="text-emerald-600 hover:underline">
                                    {{ $slip->slip_number }}
                                </a>
                                <div class="text-[10px] text-slate-400 font-sans">{{ $slip->payment_date->format('d/m/Y') }}</div>
                            </td>
                            <td class="py-2.5 px-4 font-bold text-slate-900">{{ $slip->employee_name }}</td>
                            <td class="py-2.5 px-4 text-slate-600">{{ $slip->period_month }}</td>
                            <td class="py-2.5 px-4 text-right font-mono text-slate-700">Rp {{ number_format($slip->basic_salary, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-4 text-right font-mono text-emerald-600 font-medium">+ Rp {{ number_format($slip->allowance_amount + $slip->bonus_amount, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-4 text-right font-mono text-red-600 font-medium">- Rp {{ number_format($slip->total_deduction, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-4 text-right font-mono font-black text-slate-900">Rp {{ number_format($slip->net_salary, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">Tidak ada pembayaran slip gaji pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                    <tr>
                        <td colspan="6" class="py-2.5 px-4 text-slate-800">TOTAL BEBAN GAJI KARYAWAN</td>
                        <td class="py-2.5 px-4 text-right font-mono text-red-600 text-sm">Rp {{ number_format($totalSalaries, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- DETAIL RINCIAN PENGELUARAN OPERASIONAL LAINNYA -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Rincian Beban Operasional Gudang & Depo</h4>
                    <p class="text-[10px] text-slate-500">BBM armada, konsumsi, kuli bongkar muat, listrik & perawatan</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-slate-400 block">Total Beban Operasional</span>
                <span class="font-mono text-xs font-bold text-red-600">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-2.5 px-4">No. Pengeluaran & Tanggal</th>
                        <th class="py-2.5 px-4">Kategori Biaya</th>
                        <th class="py-2.5 px-4">Deskripsi / Peruntukan</th>
                        <th class="py-2.5 px-4">Penerima Dana</th>
                        <th class="py-2.5 px-4 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $exp)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-2.5 px-4 font-mono font-bold text-slate-700">
                                {{ $exp->expense_number }}
                                <div class="text-[10px] text-slate-400 font-sans">{{ $exp->expense_date->format('d/m/Y') }}</div>
                            </td>
                            <td class="py-2.5 px-4 font-semibold text-slate-800">{{ $exp->category?->name ?? 'Lain-lain' }}</td>
                            <td class="py-2.5 px-4 text-slate-700">{{ $exp->description }}</td>
                            <td class="py-2.5 px-4 text-slate-600">{{ $exp->recipient ?: '-' }}</td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">Rp {{ number_format($exp->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">Tidak ada pengeluaran operasional lain pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                    <tr>
                        <td colspan="4" class="py-2.5 px-4 text-slate-800">TOTAL BIAYA OPERASIONAL</td>
                        <td class="py-2.5 px-4 text-right font-mono text-red-600 text-sm">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
