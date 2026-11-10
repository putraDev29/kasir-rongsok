<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Penjualan_{{ $startDate }}_{{ $endDate }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="bg-white p-6 font-sans text-xs text-slate-900">

    <div class="no-print mb-4 flex justify-end gap-2">
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white font-bold rounded shadow text-xs">
            Cetak Laporan Penjualan (Print / PDF)
        </button>
    </div>

    <div class="text-center pb-4 border-b border-slate-300">
        <h1 class="text-lg font-black uppercase">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</h1>
        <p class="text-slate-600 text-[11px]">{{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }} | Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}</p>
        <h2 class="text-sm font-bold uppercase mt-2 text-slate-800">LAPORAN PENJUALAN MATERIAL SCRAP & DAUR ULANG (PARTAI BESAR)</h2>
        <p class="text-[11px] text-slate-500">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    <div class="grid grid-cols-3 gap-4 my-4 p-3 bg-slate-50 border border-slate-200 rounded font-sans">
        <div>
            <span class="text-slate-500 font-semibold block">Total Muatan / Berat Terjual:</span>
            <span class="text-sm font-bold text-slate-900">{{ $totalCargoDisplay ?? (\App\Services\FormatHelper::weight($totalWeight) . ' kg') }} @if(($totalKg ?? $totalWeight) > 0) ({{ \App\Services\FormatHelper::weight(($totalKg ?? $totalWeight) / 1000) }} Ton) @endif</span>
        </div>
        <div>
            <span class="text-slate-500 font-semibold block">Total Omzet Penjualan:</span>
            <span class="text-sm font-bold text-blue-700">Rp {{ number_format($totalSales, 0, ',', '.') }}</span>
        </div>
        <div class="text-right">
            <span class="text-slate-500 font-semibold block">Estimasi Laba Kotor:</span>
            <span class="text-sm font-bold text-emerald-700">Rp {{ number_format($totalProfit, 0, ',', '.') }}</span>
        </div>
    </div>

    <table class="w-full text-left font-mono text-[11px] border border-slate-300 mt-2">
        <thead class="bg-slate-100 border-b border-slate-300 text-[10px] uppercase font-sans">
            <tr>
                <th class="p-1.5 border-r border-slate-300">Waktu</th>
                <th class="p-1.5 border-r border-slate-300">No. Nota</th>
                <th class="p-1.5 border-r border-slate-300">Pembeli</th>
                <th class="p-1.5 border-r border-slate-300">Nama Barang</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Muatan / Satuan</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Harga Beli / Satuan</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Harga Jual / Satuan</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Subtotal</th>
                <th class="p-1.5 text-right font-sans">Est. Laba</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($items as $item)
                @php $tx = $item->transaction; @endphp
                <tr>
                    <td class="p-1.5 border-r border-slate-300 text-slate-600">{{ $tx->transaction_date->format('d/m/y H:i') }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-semibold">{{ $tx->invoice_number }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-sans font-bold">{{ $tx->buyer_name }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-sans">
                        <div class="font-bold text-slate-900">{{ $item->item_name_snapshot }}</div>
                        @if($item->item_type_name)
                            <div class="text-[9px] text-blue-900 font-semibold mt-0.5">
                                ↳ Varian: {{ $item->item_type_name }} ({{ $item->formatted_quantity }} @ Rp {{ \App\Services\FormatHelper::number($item->sell_price_per_kg) }}/{{ $item->effective_unit }})
                            </div>
                        @endif
                    </td>
                    <td class="p-1.5 border-r border-slate-300 text-right font-bold">{{ $item->formatted_quantity }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right">Rp {{ \App\Services\FormatHelper::number($item->buy_price_snapshot) }}/{{ $item->effective_unit }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right">Rp {{ \App\Services\FormatHelper::number($item->sell_price_per_kg) }}/{{ $item->effective_unit }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right font-bold">Rp {{ \App\Services\FormatHelper::number($item->subtotal) }}</td>
                    <td class="p-1.5 text-right font-bold text-emerald-800">Rp {{ \App\Services\FormatHelper::number($item->profit_subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-400">
            <tr>
                <td colspan="4" class="p-2 border-r border-slate-300 text-right font-sans">TOTAL KESELURUHAN:</td>
                <td class="p-2 border-r border-slate-300 text-right">{{ $totalCargoDisplay ?? (\App\Services\FormatHelper::weight($totalWeight) . ' kg') }}</td>
                <td class="p-2 border-r border-slate-300"></td>
                <td class="p-2 border-r border-slate-300"></td>
                <td class="p-2 border-r border-slate-300 text-right">Rp {{ \App\Services\FormatHelper::number($totalSales) }}</td>
                <td class="p-2 text-right text-emerald-800">Rp {{ \App\Services\FormatHelper::number($totalProfit) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-8 text-right text-xs text-slate-500 font-sans">
        Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y, H:i') }}
    </div>

</body>
</html>

