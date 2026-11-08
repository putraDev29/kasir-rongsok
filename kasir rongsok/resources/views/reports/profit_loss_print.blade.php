<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Laba_Rugi_{{ $startDate ?? 'semua' }}_{{ $endDate ?? 'waktu' }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; margin: 0; padding: 0; font-size: 10px; }
            .print-container { width: 100% !important; max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 flex flex-col items-center font-sans text-xs text-slate-900">

    <div class="no-print w-full max-w-4xl mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
        <span class="text-xs font-bold text-slate-700">Laporan Laba Rugi Komprehensif (Print / PDF)</span>
        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow text-xs flex items-center gap-1.5">
            <span>Cetak Dokumen (Print / PDF)</span>
        </button>
    </div>

    <!-- Print Container -->
    <div class="print-container bg-white p-8 max-w-4xl w-full border border-slate-300 rounded-2xl shadow-lg space-y-6">
        
        <!-- Header -->
        <div class="text-center pb-4 border-b-2 border-slate-900">
            <h1 class="text-xl font-black uppercase tracking-tight">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</h1>
            <p class="text-slate-600 text-[11px]">{{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }} | Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}</p>
            <h2 class="text-sm font-bold uppercase mt-2 tracking-wider text-slate-800">LAPORAN LABA / RUGI OPERASIONAL DEPO</h2>
            <p class="text-[11px] text-slate-500">
                @if($startDate && $endDate)
                    Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d F Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</strong>
                @else
                    Periode: <strong>Semua Waktu / Kumulatif</strong>
                @endif
            </p>
        </div>

        <!-- Catatan Akuntansi Kasir Rongsok -->
        <div class="p-2.5 rounded border border-slate-300 bg-slate-50 text-[10px] text-slate-700 leading-relaxed">
            <strong>Catatan Akuntansi Stok Depo:</strong> Pembelian rongsok masuk pada periode ini sebesar <strong>Rp {{ number_format($totalPurchases, 0, ',', '.') }}</strong> ({{ \App\Services\FormatHelper::weight($totalPurchasesWeight) }} kg) dicatat sebagai <strong>Penambahan Aset Persediaan Fisik Gudang</strong> (bukan pengurang laba/beban kerugian). Modal kas terkonversi menjadi aset persediaan fisik rongsok di gudang depo untuk siap disortir dan dijual kembali ke pabrik peleburan.
        </div>

        <!-- 1. IKHTISAR KEUANGAN LABA RUGI -->
        <div class="space-y-3 font-mono text-xs">
            <div class="font-bold text-slate-900 font-sans uppercase border-b border-slate-300 pb-1">
                I. IKHTISAR LABA RUGI OPERASIONAL
            </div>

            <div class="flex justify-between border-b border-slate-200 pb-1">
                <span>1. OMZET PENJUALAN ({{ \App\Services\FormatHelper::weight($totalSalesWeight) }} kg)</span>
                <span class="font-bold">Rp {{ number_format($totalSales, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between border-b border-slate-200 pb-1 text-red-700">
                <span>2. PEMBELIAN (HPP TIMBANGAN TERJUAL)</span>
                <span class="font-bold">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</span>
            </div>

            <div class="p-2 bg-slate-100 rounded font-bold flex justify-between text-xs text-slate-900 font-sans">
                <span>LABA KOTOR PENJUALAN (GROSS PROFIT):</span>
                <span class="font-mono">Rp {{ number_format($grossProfit, 0, ',', '.') }}</span>
            </div>

            <div class="pt-1">
                <div class="flex justify-between font-bold text-slate-900 mb-1">
                    <span>3. TOTAL BEBAN OPERASIONAL USAHA:</span>
                    <span class="text-red-700">(Rp {{ number_format($totalOperatingExpenses, 0, ',', '.') }})</span>
                </div>
                <div class="pl-4 space-y-1 text-[11px] text-slate-700">
                    <div class="flex justify-between">
                        <span>A. Beban Gaji Karyawan ({{ $salarySlips->count() }} Slip):</span>
                        <span class="text-red-700 font-semibold">Rp {{ number_format($totalSalaries, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>B. Beban Pengeluaran Operasional Gudang:</span>
                        <span class="text-red-700 font-semibold">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</span>
                    </div>
                    @foreach($expensesByCategory as $exp)
                        <div class="flex justify-between pl-3 text-slate-500 text-[10px]">
                            <span>- {{ $exp->name }}:</span>
                            <span>Rp {{ number_format($exp->total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="p-3 border-2 border-slate-900 rounded font-black flex justify-between text-sm font-sans mt-3">
                <span>ESTIMASI LABA BERSIH OPERASIONAL:</span>
                <span class="font-mono {{ $netProfit >= 0 ? 'text-slate-900' : 'text-amber-700' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- 2. DATA SISA STOK BARANG & HARGA -->
        <div class="border border-slate-300 rounded p-2.5">
            <div class="font-bold text-[10px] uppercase text-slate-800 pb-1 border-b border-slate-300 flex justify-between">
                <span>II. RINCIAN SISA STOK BARANG &amp; HARGA DEPO</span>
                <span class="font-mono">Stok: {{ \App\Services\FormatHelper::weight($totalCurrentStock) }} kg | Aset: Rp {{ number_format($totalCurrentStockValue, 0, ',', '.') }}</span>
            </div>
            <table class="w-full text-left text-[9px] mt-1.5 font-mono">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-200">
                        <th class="pb-1 w-6 text-center">No</th>
                        <th class="pb-1 font-sans">Nama Material / Barang</th>
                        <th class="pb-1 font-sans">Kategori</th>
                        <th class="pb-1 text-right">Sisa Stok</th>
                        <th class="pb-1 text-right">Harga Beli (@ Rp)</th>
                        <th class="pb-1 text-right">Harga Jual (@ Rp)</th>
                        <th class="pb-1 text-right">Nilai Modal Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stockBalanceItems as $idx => $stk)
                        <tr>
                            <td class="py-0.5 text-center text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-0.5 font-sans font-medium">{{ $stk->name }}</td>
                            <td class="py-0.5 font-sans text-slate-500">{{ $stk->category_name }}</td>
                            <td class="py-0.5 text-right font-bold">{{ \App\Services\FormatHelper::weight($stk->current_stock) }} {{ $stk->unit }}</td>
                            <td class="py-0.5 text-right">Rp {{ number_format($stk->buy_price, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right text-emerald-800 font-semibold">Rp {{ number_format($stk->sell_price, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right font-bold">Rp {{ number_format($stk->stock_value, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-2 text-center text-slate-400 font-sans">Belum ada data barang rongsok terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-slate-300 font-bold">
                    <tr>
                        <td colspan="3" class="pt-1 font-sans">TOTAL SISA STOK FISIK</td>
                        <td class="pt-1 text-right">{{ \App\Services\FormatHelper::weight($totalCurrentStock) }} kg</td>
                        <td colspan="2" class="pt-1 text-center font-sans font-normal">-</td>
                        <td class="pt-1 text-right">Rp {{ number_format($totalCurrentStockValue, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 3. DATA BARANG MASUK (PEMBELIAN) -->
        <div class="border border-slate-300 rounded p-2.5">
            <div class="font-bold text-[10px] uppercase text-slate-800 pb-1 border-b border-slate-300 flex justify-between">
                <span>III. RINCIAN BARANG MASUK (PEMBELIAN)</span>
                <span class="font-mono">{{ \App\Services\FormatHelper::weight($totalPurchasesWeight) }} kg | Rp {{ number_format($totalPurchases, 0, ',', '.') }}</span>
            </div>
            <table class="w-full text-left text-[9px] mt-1.5 font-mono">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-200">
                        <th class="pb-1 w-6 text-center">No</th>
                        <th class="pb-1 font-sans">Jenis Barang</th>
                        <th class="pb-1 text-right">Harga Beli (@ Rp/kg)</th>
                        <th class="pb-1 text-right">Berat Masuk (kg)</th>
                        <th class="pb-1 text-right">Total Modal Beli</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($incomingItems as $idx => $in)
                        <tr>
                            <td class="py-0.5 text-center text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-0.5 font-sans font-medium">{{ $in->item_name }}</td>
                            <td class="py-0.5 text-right">Rp {{ number_format($in->avg_buy_price, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right font-bold">{{ \App\Services\FormatHelper::weight($in->total_weight) }}</td>
                            <td class="py-0.5 text-right font-bold">Rp {{ number_format($in->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-2 text-center text-slate-400 font-sans">Tidak ada pembelian rongsok pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-slate-300 font-bold">
                    <tr>
                        <td colspan="3" class="pt-1 font-sans">TOTAL STOK MASUK</td>
                        <td class="pt-1 text-right">{{ \App\Services\FormatHelper::weight($totalPurchasesWeight) }} kg</td>
                        <td class="pt-1 text-right">Rp {{ number_format($totalPurchases, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 4. DATA BARANG KELUAR (PENJUALAN PABRIK) -->
        <div class="border border-slate-300 rounded p-2.5">
            <div class="font-bold text-[10px] uppercase text-slate-800 pb-1 border-b border-slate-300 flex justify-between">
                <span>IV. RINCIAN BARANG KELUAR (PENJUALAN PABRIK)</span>
                <span class="font-mono">{{ \App\Services\FormatHelper::weight($totalSalesWeight) }} kg | Omzet: Rp {{ number_format($totalSales, 0, ',', '.') }}</span>
            </div>
            <table class="w-full text-left text-[9px] mt-1.5 font-mono">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-200">
                        <th class="pb-1 w-6 text-center">No</th>
                        <th class="pb-1 font-sans">Jenis Barang</th>
                        <th class="pb-1 text-right">Berat Keluar (kg)</th>
                        <th class="pb-1 text-right">Harga Beli (@ Rp)</th>
                        <th class="pb-1 text-right">Harga Jual (@ Rp)</th>
                        <th class="pb-1 text-right">Modal HPP</th>
                        <th class="pb-1 text-right">Omzet Jual</th>
                        <th class="pb-1 text-right">Laba Penjualan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($outgoingItems as $idx => $out)
                        <tr>
                            <td class="py-0.5 text-center text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-0.5 font-sans font-medium">{{ $out->item_name }}</td>
                            <td class="py-0.5 text-right font-bold">{{ \App\Services\FormatHelper::weight($out->total_weight) }}</td>
                            <td class="py-0.5 text-right">Rp {{ number_format($out->avg_buy_price, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right text-emerald-800 font-semibold">Rp {{ number_format($out->avg_sell_price, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right text-red-700">Rp {{ number_format($out->total_cogs, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right font-bold">Rp {{ number_format($out->total_amount, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right font-bold text-emerald-700">Rp {{ number_format($out->total_profit, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-2 text-center text-slate-400 font-sans">Tidak ada penjualan grosir pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-slate-300 font-bold">
                    <tr>
                        <td colspan="2" class="pt-1 font-sans">TOTAL PENJUALAN</td>
                        <td class="pt-1 text-right">{{ \App\Services\FormatHelper::weight($totalSalesWeight) }} kg</td>
                        <td colspan="2" class="pt-1 text-center font-sans font-normal">-</td>
                        <td class="pt-1 text-right text-red-700">Rp {{ number_format($totalCogs, 0, ',', '.') }}</td>
                        <td class="pt-1 text-right">Rp {{ number_format($totalSales, 0, ',', '.') }}</td>
                        <td class="pt-1 text-right text-emerald-700">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 5. RINCIAN SLIP GAJI KARYAWAN -->
        <div class="border border-slate-300 rounded p-2.5">
            <div class="font-bold text-[10px] uppercase text-slate-800 pb-1 border-b border-slate-300 flex justify-between">
                <span>V. RINCIAN PENGGAJIAN KARYAWAN (SLIP GAJI)</span>
                <span class="font-mono text-red-700">Rp {{ number_format($totalSalaries, 0, ',', '.') }}</span>
            </div>
            <table class="w-full text-left text-[9px] mt-1.5 font-mono">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-200">
                        <th class="pb-1">No. Slip</th>
                        <th class="pb-1">Nama Karyawan</th>
                        <th class="pb-1">Periode</th>
                        <th class="pb-1 text-right">Gaji Pokok</th>
                        <th class="pb-1 text-right">Tunjangan/Bonus</th>
                        <th class="pb-1 text-right">Potongan</th>
                        <th class="pb-1 text-right">Gaji Bersih (THP)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($salarySlips as $slip)
                        <tr>
                            <td class="py-0.5">{{ $slip->slip_number }}</td>
                            <td class="py-0.5 font-sans font-semibold">{{ $slip->employee_name }}</td>
                            <td class="py-0.5">{{ $slip->period_month }}</td>
                            <td class="py-0.5 text-right">Rp {{ number_format($slip->basic_salary, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right">Rp {{ number_format($slip->allowance_amount + $slip->bonus_amount, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right text-red-600">-Rp {{ number_format($slip->total_deduction, 0, ',', '.') }}</td>
                            <td class="py-0.5 text-right font-bold">Rp {{ number_format($slip->net_salary, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-2 text-center text-slate-400">Tidak ada pengeluaran slip gaji pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tanda Tangan -->
        <div class="grid grid-cols-2 gap-8 pt-6 text-center font-sans text-xs">
            <div>
                <p>Bagian Keuangan / Admin</p>
                <div class="h-16" style="height: 65px;"></div>
                <p class="font-bold underline">( .......................................... )</p>
            </div>
            <div>
                <p>Pimpinan / Owner Depo</p>
                <div class="h-16 flex items-center justify-center" style="height: 65px;">
                    @if(\App\Models\BusinessSetting::hasOwnerSignature())
                        <img src="{{ \App\Models\BusinessSetting::ownerSignatureUrl() }}" alt="TTD Owner" style="height: 55px; max-height: 60px; width: auto; max-width: 170px; object-fit: contain; margin: 0 auto; display: block;">
                    @endif
                </div>
                <p class="font-bold underline">({{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }})</p>
            </div>
        </div>

    </div>

</body>
</html>
