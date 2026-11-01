<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi_{{ $payment->payment_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; background: #fff; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center">

    <!-- Print Control Bar -->
    <div class="no-print w-full max-w-xl mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-600">Format Kertas:</span>
            <a href="{{ route('debts.print-payment', ['payment' => $payment, 'format' => 'thermal_58']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium {{ $format === 'thermal_58' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Thermal 58mm
            </a>
            <a href="{{ route('debts.print-payment', ['payment' => $payment, 'format' => 'thermal_80']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium {{ $format === 'thermal_80' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Thermal 80mm
            </a>
            <a href="{{ route('debts.print-payment', ['payment' => $payment, 'format' => 'a4']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium {{ $format === 'a4' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                A4 Standar
            </a>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak Nota</span>
            </button>
            <a href="{{ route('debts.show', $payment->debt_id) }}" class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold">
                Kembali
            </a>
        </div>
    </div>

    <!-- RECEIPT CONTAINER -->
    <div class="bg-white text-slate-900 shadow-md border border-slate-200 
         {{ $format === 'thermal_58' ? 'receipt-58mm w-[58mm] text-[10px] p-2' : ($format === 'thermal_80' ? 'receipt-80mm w-[80mm] text-xs p-3' : 'receipt-a4 max-w-xl w-full p-8 rounded-xl') }}">
        
        <!-- Header -->
        <div class="text-center pb-2 border-b border-dashed border-slate-400">
            <h1 class="{{ $format === 'a4' ? 'text-lg' : 'text-sm' }} font-black tracking-tight uppercase">
                {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
            </h1>
            <p class="text-[9px] text-slate-600 mt-0.5 leading-tight">
                {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}
            </p>
            <p class="text-[9px] text-slate-600 font-mono">
                Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
            </p>
            <div class="mt-2 inline-block px-2 py-0.5 border border-slate-800 text-[10px] font-black uppercase tracking-wider">
                BUKTI PEMBAYARAN HUTANG / BON
            </div>
        </div>

        <!-- Meta Info -->
        <div class="py-2 text-[10px] space-y-0.5 border-b border-dashed border-slate-400 font-mono">
            <div class="flex justify-between">
                <span>No. Bukti:</span>
                <span class="font-bold">{{ $payment->payment_number }}</span>
            </div>
            <div class="flex justify-between">
                <span>Tanggal:</span>
                <span>{{ $payment->payment_date->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Peminjam/Penyetor:</span>
                <span class="font-semibold">{{ $payment->debt->borrower_name }}</span>
            </div>
            <div class="flex justify-between">
                <span>Ref. Pinjaman:</span>
                <span>{{ $payment->debt->debt_number }}</span>
            </div>
            <div class="flex justify-between">
                <span>Metode Bayar:</span>
                <span>{{ $payment->paymentMethod?->name ?? 'Tunai' }}</span>
            </div>
            <div class="flex justify-between">
                <span>Kasir/Penerima:</span>
                <span>{{ $payment->user?->name ?? 'Kasir' }}</span>
            </div>
        </div>

        <!-- Financial Breakdown -->
        <div class="py-2.5 font-mono text-[10px] space-y-1">
            @php
                $beforeAmount = $payment->remaining_balance + $payment->amount;
            @endphp
            <div class="flex justify-between text-slate-600">
                <span>Sisa Hutang Sebelum:</span>
                <span>Rp {{ number_format($beforeAmount, 0, ',', '.') }}</span>
            </div>

            <div class="pt-1 border-t border-slate-900 flex justify-between text-xs font-black">
                <span>JUMLAH DIBAYARKAN:</span>
                <span class="text-sm font-bold text-slate-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
            </div>

            <div class="pt-1 border-t border-dashed border-slate-400 flex justify-between font-bold text-amber-800">
                <span>SISA SALDO HUTANG:</span>
                <span class="text-xs">Rp {{ number_format($payment->remaining_balance, 0, ',', '.') }}</span>
            </div>

            @if($payment->remaining_balance <= 0)
                <div class="mt-2 text-center py-1 bg-emerald-50 text-emerald-800 font-black text-xs border border-emerald-300 rounded">
                    *** PINJAMAN INI TELAH LUNAS ***
                </div>
            @endif

            @if($payment->notes)
                <div class="text-[9px] text-slate-500 pt-1 italic">
                    Keterangan: {{ $payment->notes }}
                </div>
            @endif
        </div>

        <!-- Signatures -->
        <div class="mt-5 pt-2 border-t border-slate-300 text-center font-sans">
            <div class="grid grid-cols-2 gap-4 text-[10px] text-slate-600">
                <div>
                    <p>Yang Menyerahkan</p>
                    <div class="h-10"></div>
                    <p class="font-bold">({{ $payment->debt->borrower_name }})</p>
                </div>
                <div>
                    <p>Penerima / Kasir</p>
                    <div class="h-10"></div>
                    <p class="font-bold">({{ $payment->user?->name ?? 'Kasir' }})</p>
                </div>
            </div>

            <p class="mt-4 text-[8px] text-slate-400 italic leading-tight">
                Simpan bukti pembayaran ini sebagai bukti pembayaran hutang / bon yang sah.
            </p>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(() => window.print(), 300);
            }
        });
    </script>
</body>
</html>

