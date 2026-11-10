<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Pembelian_{{ $startDate }}_{{ $endDate }}</title>
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
        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded shadow text-xs">
            Cetak Laporan (Print / PDF)
        </button>
    </div>

    <!-- Header -->
    <div class="text-center pb-4 border-b border-slate-300">
        <h1 class="text-lg font-black uppercase">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</h1>
        <p class="text-slate-600 text-[11px]">{{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }} | Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}</p>
        <h2 class="text-sm font-bold uppercase mt-2 text-slate-800">LAPORAN PEMBELIAN MATERIAL SCRAP & DAUR ULANG</h2>
        <p class="text-[11px] text-slate-500">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    <!-- Summary Box -->
    <div class="grid grid-cols-2 gap-4 my-4 p-3 bg-slate-50 border border-slate-200 rounded">
        <div>
            <span class="text-slate-500 font-semibold block">Total Muatan / Berat Bersih Diterima:</span>
            <span class="text-sm font-bold text-slate-900">{{ $totalCargoDisplay ?? (\App\Services\FormatHelper::weight($totalWeight) . ' kg') }} @if(($totalKg ?? $totalWeight) > 0) ({{ \App\Services\FormatHelper::weight(($totalKg ?? $totalWeight) / 1000) }} Ton) @endif</span>
        </div>
        <div class="text-right">
            <span class="text-slate-500 font-semibold block">Total Nominal Pembelian Kas:</span>
            <span class="text-sm font-bold text-slate-900">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Table -->
    <table class="w-full text-left font-mono text-[11px] border border-slate-300 mt-2">
        <thead class="bg-slate-100 border-b border-slate-300 text-[10px] uppercase font-sans">
            <tr>
                <th class="p-1.5 border-r border-slate-300">Waktu</th>
                <th class="p-1.5 border-r border-slate-300">No. Nota</th>
                <th class="p-1.5 border-r border-slate-300">Pemasok</th>
                <th class="p-1.5 border-r border-slate-300">Nama Barang</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Berat / Satuan</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Harga / Satuan</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Subtotal</th>
                <th class="p-1.5 font-sans">Kasir</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($items as $item)
                @php $tx = $item->transaction; @endphp
                <tr>
                    <td class="p-1.5 border-r border-slate-300 text-slate-600">{{ $tx->transaction_date->format('d/m/y H:i') }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-semibold">{{ $tx->invoice_number }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-sans">{{ $tx->supplier_name }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-sans font-bold">{{ $item->item_name_snapshot }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right">{{ $item->formatted_quantity }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right">Rp {{ number_format($item->price_per_kg, 0, ',', '.') }}/{{ $item->effective_unit }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    <td class="p-1.5 font-sans">{{ $tx->user?->name }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-400">
            <tr>
                <td colspan="4" class="p-2 border-r border-slate-300 text-right font-sans">TOTAL KESELURUHAN:</td>
                <td class="p-2 border-r border-slate-300 text-right">{{ $totalCargoDisplay ?? (\App\Services\FormatHelper::weight($totalWeight) . ' kg') }}</td>
                <td class="p-2 border-r border-slate-300"></td>
                <td class="p-2 border-r border-slate-300 text-right">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-8 text-right text-xs text-slate-500 font-sans">
        Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y, H:i') }}
    </div>

</body>
</html>

