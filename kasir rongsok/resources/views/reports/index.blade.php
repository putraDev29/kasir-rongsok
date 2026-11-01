@extends('layouts.app', ['header' => 'Pusat Laporan & Rekapitulasi Usaha'])

@section('content')
<div class="space-y-6">

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <h2 class="text-base font-bold text-slate-900">Pusat Laporan Usaha Rongsok & Depo</h2>
        <p class="text-xs text-slate-500 mt-0.5">Pilih jenis laporan yang ingin Anda analisis, ekspor ke spreadsheet Excel/CSV, atau cetak ke dokumen fisik/PDF.</p>
    </div>

    <!-- Report Modules Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        
        <!-- 1. Laporan Pembelian -->
        <a href="{{ route('reports.purchases') }}" class="group bg-white p-5 rounded-xl border border-slate-200 hover:border-emerald-500 hover:shadow-md transition-all flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                    <i data-lucide="hand-coins" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-emerald-700">Laporan Pembelian Barang</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Rekapitulasi seluruh nota timbangan pembelian dari masyarakat dan pengepul keliling beserta rincian berat bersih dan nominal uang tunai yang keluar.</p>
                </div>
            </div>
            <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs font-semibold text-emerald-700">
                <span>Buka Laporan Pembelian</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <!-- 2. Laporan Penjualan -->
        <a href="{{ route('reports.sales') }}" class="group bg-white p-5 rounded-xl border border-slate-200 hover:border-blue-500 hover:shadow-md transition-all flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-blue-700">Laporan Penjualan Partai (Grosir)</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Rekap pengiriman rongsok ke pabrik peleburan / pengepul besar, omzet penjualan, perbandingan harga perolehan (HPP), dan estimasi laba.</p>
                </div>
            </div>
            <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs font-semibold text-blue-700">
                <span>Buka Laporan Penjualan</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <!-- 3. Laporan Stok -->
        <a href="{{ route('reports.stock') }}" class="group bg-white p-5 rounded-xl border border-slate-200 hover:border-purple-500 hover:shadow-md transition-all flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                    <i data-lucide="boxes" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-purple-700">Laporan Stok & Valuasi Persediaan</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Posisi timbangan fisik di gudang, rata-rata harga beli modal, total valuasi aset barang rongsok, dan barang dengan stok menipis.</p>
                </div>
            </div>
            <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs font-semibold text-purple-700">
                <span>Buka Laporan Stok</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <!-- 4. Laporan Laba Rugi -->
        <a href="{{ route('reports.profit-loss') }}" class="group bg-white p-5 rounded-xl border border-slate-200 hover:border-emerald-500 hover:shadow-md transition-all flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                    <i data-lucide="pie-chart" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-emerald-700">Laporan Laba / Rugi Usaha (P&L)</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Kalkulasi akuntansi komprehensif: Omzet Penjualan dikurangi HPP Barang menghasilkan Laba Kotor, lalu dikurangi seluruh Biaya Operasional menghasilkan Laba Bersih.</p>
                </div>
            </div>
            <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs font-semibold text-emerald-700">
                <span>Buka Laba Rugi Usaha</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <!-- 5. Laporan Pengeluaran -->
        <a href="{{ route('reports.expenses') }}" class="group bg-white p-5 rounded-xl border border-slate-200 hover:border-red-500 hover:shadow-md transition-all flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 text-red-700 flex items-center justify-center group-hover:bg-red-600 group-hover:text-white transition-colors">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-red-700">Laporan Beban Operasional</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Rincian beban biaya operasional per kategori: bahan bakar truk, upah bongkar muat kuli, konsumsi kasir, dan perawatan timbangan.</p>
                </div>
            </div>
            <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs font-semibold text-red-700">
                <span>Buka Laporan Biaya</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <!-- 6. Laporan Kasir -->
        <a href="{{ route('reports.cashier') }}" class="group bg-white p-5 rounded-xl border border-slate-200 hover:border-slate-500 hover:shadow-md transition-all flex flex-col justify-between">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center group-hover:bg-slate-700 group-hover:text-white transition-colors">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-slate-800">Laporan Transaksi Per Kasir</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Monitoring produktivitas tiap operator timbang: jumlah transaksi nota, total tonase barang yang ditimbang, dan pengeluaran kas.</p>
                </div>
            </div>
            <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs font-semibold text-slate-800">
                <span>Buka Rekap Kasir</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

    </div>

</div>
@endsection

