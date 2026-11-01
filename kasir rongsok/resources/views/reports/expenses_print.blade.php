<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan_Biaya_Operasional_{{ $startDate }}_{{ $endDate }}</title>
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
        <button onclick="window.print()" class="px-4 py-2 bg-red-600 text-white font-bold rounded shadow text-xs">
            Cetak Laporan Biaya (Print / PDF)
        </button>
    </div>

    <div class="text-center pb-4 border-b border-slate-300">
        <h1 class="text-lg font-black uppercase">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</h1>
        <p class="text-slate-600 text-[11px]">{{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }} | Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}</p>
        <h2 class="text-sm font-bold uppercase mt-2 text-slate-800">LAPORAN BIAYA & PENGELUARAN OPERASIONAL DEPO</h2>
        <p class="text-[11px] text-slate-500">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    <div class="my-4 p-3 bg-slate-50 border border-slate-200 rounded font-sans flex justify-between items-center">
        <span class="text-slate-600 font-bold uppercase">Total Pengeluaran Kas Operasional:</span>
        <span class="text-base font-bold text-red-700 font-mono">Rp {{ number_format($totalExpense, 0, ',', '.') }}</span>
    </div>

    <table class="w-full text-left font-mono text-[11px] border border-slate-300 mt-2">
        <thead class="bg-slate-100 border-b border-slate-300 text-[10px] uppercase font-sans">
            <tr>
                <th class="p-1.5 border-r border-slate-300">Tanggal</th>
                <th class="p-1.5 border-r border-slate-300">No. Biaya</th>
                <th class="p-1.5 border-r border-slate-300">Kategori</th>
                <th class="p-1.5 border-r border-slate-300">Keterangan</th>
                <th class="p-1.5 border-r border-slate-300">Penerima</th>
                <th class="p-1.5 border-r border-slate-300 text-right">Nominal</th>
                <th class="p-1.5 font-sans">Dicatat Oleh</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($expenses as $exp)
                <tr>
                    <td class="p-1.5 border-r border-slate-300 text-slate-600">{{ $exp->expense_date->format('d/m/y') }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-semibold">{{ $exp->expense_number }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-sans">{{ $exp->category?->name }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-sans">{{ $exp->description }}</td>
                    <td class="p-1.5 border-r border-slate-300 font-sans">{{ $exp->recipient ?? '-' }}</td>
                    <td class="p-1.5 border-r border-slate-300 text-right font-bold text-red-700">Rp {{ number_format($exp->amount, 0, ',', '.') }}</td>
                    <td class="p-1.5 font-sans">{{ $exp->user?->name }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-400">
            <tr>
                <td colspan="5" class="p-2 border-r border-slate-300 text-right font-sans">TOTAL BEBAN OPERASIONAL:</td>
                <td class="p-2 border-r border-slate-300 text-right text-red-700 font-mono">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-8 text-right text-xs text-slate-500 font-sans">
        Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y, H:i') }}
    </div>

</body>
</html>

