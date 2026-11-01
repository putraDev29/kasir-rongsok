<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Stok_Gudang_{{ date('Ymd') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="bg-white p-6 font-sans text-xs text-slate-900">

    <div class="no-print mb-4 flex justify-end">
        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded shadow text-xs">
            Cetak Laporan Stok (Print / PDF)
        </button>
    </div>

    <div class="text-center pb-4 border-b border-slate-300">
        <h1 class="text-lg font-black uppercase">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</h1>
        <p class="text-slate-600 text-[11px]">{{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }} | Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}</p>
        <h2 class="text-sm font-bold uppercase mt-2 text-slate-800">LAPORAN POSISI STOK & VALUASI PERSEDIAAN GUDANG</h2>
        <p class="text-[11px] text-slate-500">Per Tanggal: {{ \Carbon\Carbon::now()->format('d F Y, H:i') }}</p>
    </div>

    <div class="grid grid-cols-2 gap-4 my-4 p-3 bg-slate-50 border border-slate-200 rounded font-sans">
        <div>
            <span class="text-slate-500 font-semibold block">Total Akumulasi Stok:</span>
            <span class="text-sm font-bold text-slate-900">{{ number_format($totalStock, 2) }} kg ({{ number_format($totalStock / 1000, 2) }} Ton)</span>
        </div>
        <div class="text-right">
            <span class="text-slate-500 font-semibold block">Total Nilai Persediaan (HPP):</span>
            <span class="text-sm font-bold text-purple-700">Rp {{ number_format($totalValue, 0, ',', '.') }}</span>
        </div>
    </div>

    <table class="w-full text-left font-mono text-[11px] border border-slate-300 mt-2">
        <thead class="bg-slate-100 border-b border-slate-300 text-[10px] uppercase font-sans">
            <tr>
                <th class="p-1.5 border-r border-slate-300">Kode</th>
                <th class="p-1.5 border-r border-slate-300">Nama Barang Rongsok</th>
                <th class="p-1.5 border-r border-slate-300">Kategori</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Stok Fisik</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Avg Beli/kg</th>
                <th class="p-1.5 border-r border-slate-300 text-right font-bold text-purple-900">Estimasi Nilai</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Harga Beli</th>
                <th class="p-1.5 text-right">Harga Jual</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($items as $item)
                @php
                    $bal = $item->stockBalance;
                    $cur = (float) ($bal?->current_stock ?? 0);
                    $avg = (float) ($bal?->average_buy_price ?? 0);
                    $val = $cur * $avg;
                @endphp
                <tr>
                    <td class="p-1.5 border-r border-slate-300 font-bold">{{ $item->code }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-sans font-bold">{{ $item->name }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-sans">{{ $item->category?->name }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right font-bold">{{ number_format($cur, 2) }} kg</td>
                    <td class="p-1.5 border-r border-slate-300 text-right">Rp {{ number_format($avg, 0, ',', '.') }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right font-bold text-purple-900">Rp {{ number_format($val, 0, ',', '.') }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right">Rp {{ number_format($item->buy_price_per_kg, 0, ',', '.') }}</td>
                    <td class="p-1.5 text-right">Rp {{ number_format($item->sell_price_per_kg, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-400">
            <tr>
                <td colspan="3" class="p-2 border-r border-slate-300 text-right font-sans">TOTAL GUDANG:</td>
                <td class="p-2 border-r border-slate-300 text-right">{{ number_format($totalStock, 2) }} kg</td>
                <td class="p-2 border-r border-slate-300"></td>
                <td class="p-2 border-r border-slate-300 text-right text-purple-900">Rp {{ number_format($totalValue, 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-8 text-right text-xs text-slate-500 font-sans">
        Penanggung Jawab Gudang: (.......................................)
    </div>

</body>
</html>

