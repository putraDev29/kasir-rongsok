<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi_{{ $payment->payment_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media screen {
            .receipt-58mm {
                width: 58mm !important;
                max-width: 58mm !important;
            }
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; background: #fff; }
            .receipt-58mm {
                width: 48mm !important;
                max-width: 48mm !important;
                font-size: 8.5px !important;
                line-height: 1.25 !important;
            }
            .receipt-58mm h1 {
                font-size: 11px !important;
            }
            .receipt-80mm {
                width: 72mm !important;
                max-width: 72mm !important;
                font-size: 11px !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center">

    <!-- Print Control Bar -->
    <div class="no-print w-full max-w-xl mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1.5">
            <span class="text-xs font-semibold text-slate-600 mr-1">Kertas:</span>
            <a href="{{ route('debts.print-payment', ['payment' => $payment, 'format' => 'thermal_58']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium {{ $format === 'thermal_58' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                58mm
            </a>
            <a href="{{ route('debts.print-payment', ['payment' => $payment, 'format' => 'thermal_80']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium {{ $format === 'thermal_80' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                80mm
            </a>
            <a href="{{ route('debts.print-payment', ['payment' => $payment, 'format' => 'a4']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium {{ $format === 'a4' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                A4
            </a>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 justify-end">
            <!-- Direct Bluetooth ESC/POS Print (No RawBT, 100% Free of Watermark) -->
            <button type="button" 
                    id="btnBluetoothPrint"
                    onclick="handleBluetoothPrint()" 
                    class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
                    title="Cetak langsung ke printer Bluetooth via Web Bluetooth (100% Bebas Watermark RawBT)">
                <i data-lucide="bluetooth" class="w-3.5 h-3.5"></i>
                <span id="btnBluetoothText">Cetak Bluetooth</span>
            </button>

            <!-- Quick Printer Intent Button (For Android devices) -->
            <button type="button" 
                    id="btnQuickPrinter"
                    onclick="handleQuickPrinter()" 
                    class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold text-xs flex items-center gap-1 shadow-xs cursor-pointer"
                    title="Cetak via Quick Printer Android (Gratis & Bebas Watermark)">
                <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                <span>Quick Printer</span>
            </button>

            <!-- Standard Browser Print (Dialog Cetak Windows/USB/PDF) -->
            <button type="button" 
                    onclick="window.print()" 
                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
                    title="Cetak lewat dialog browser standar (Printer USB / Windows / PDF)">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak Biasa</span>
            </button>

            <a href="{{ route('debts.show', $payment->debt_id) }}" class="px-2.5 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold">
                Kembali
            </a>
        </div>
    </div>

    <!-- Status Alert for Bluetooth Printing -->
    <div id="printStatusAlert" class="hidden no-print w-full max-w-xl mb-3 px-3.5 py-2.5 rounded-xl text-xs font-medium border flex items-center justify-between gap-2"></div>

    <!-- RECEIPT CONTAINER -->
    <div class="bg-white text-slate-900 shadow-md border border-slate-200 
         {{ $format === 'thermal_58' ? 'receipt-58mm w-[58mm] text-[9px] p-2' : ($format === 'thermal_80' ? 'receipt-80mm w-[80mm] text-xs p-3' : 'receipt-a4 max-w-xl w-full p-8 rounded-xl') }}">
        
        <!-- Header -->
        <div class="text-center pb-2 border-b border-dashed border-slate-400">
            <h1 class="{{ $format === 'a4' ? 'text-lg' : ($format === 'thermal_58' ? 'text-[11px]' : 'text-sm') }} font-bold tracking-tight uppercase">
                {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
            </h1>
            <p class="text-[9px] text-slate-600 mt-0.5 leading-tight">
                {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}
            </p>
            <p class="text-[9px] text-slate-600 font-mono">
                Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
            </p>
            <div class="mt-2 inline-block px-2 py-0.5 border border-slate-800 {{ $format === 'thermal_58' ? 'text-[8.5px]' : 'text-[10px]' }} font-bold uppercase tracking-wider">
                BUKTI PEMBAYARAN HUTANG / BON
            </div>
        </div>

        @php
            $debtMetaGrid = ($format === 'thermal_58') 
                ? 'display: grid; grid-template-columns: 78px 6px 1fr; align-items: center;' 
                : (($format === 'thermal_80') 
                    ? 'display: grid; grid-template-columns: 100px 8px 1fr; align-items: center;' 
                    : 'display: grid; grid-template-columns: 130px 12px 1fr; align-items: center;');
        @endphp

        <!-- Meta Info (Colons aligned, values left-aligned strictly 1 line) -->
        <div class="py-1.5 {{ $format === 'thermal_58' ? 'text-[9px]' : 'text-[10px]' }} space-y-0.5 border-b border-dashed border-slate-400 font-mono">
            <div style="{{ $debtMetaGrid }}">
                <span class="whitespace-nowrap">No. Bukti</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 font-bold whitespace-nowrap overflow-hidden text-ellipsis">{{ $payment->payment_number }}</span>
            </div>
            <div style="{{ $debtMetaGrid }}">
                <span class="whitespace-nowrap">Tanggal</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 whitespace-nowrap">{{ $payment->payment_date->format('d/m/Y H:i') }}</span>
            </div>
            <div style="{{ $debtMetaGrid }}">
                <span class="whitespace-nowrap">Peminjam</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 font-semibold whitespace-nowrap overflow-hidden text-ellipsis">{{ $payment->debt->borrower_name }}</span>
            </div>
            <div style="{{ $debtMetaGrid }}">
                <span class="whitespace-nowrap">Ref. Pinjam</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 whitespace-nowrap overflow-hidden text-ellipsis">{{ $payment->debt->debt_number }}</span>
            </div>
            <div style="{{ $debtMetaGrid }}">
                <span class="whitespace-nowrap">Metode Bayar</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 whitespace-nowrap overflow-hidden text-ellipsis">{{ $payment->paymentMethod?->name ?? 'Tunai' }}</span>
            </div>
            <div style="{{ $debtMetaGrid }}">
                <span class="whitespace-nowrap">Kasir</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 whitespace-nowrap overflow-hidden text-ellipsis">{{ $payment->user?->name ?? 'Kasir' }}</span>
            </div>
        </div>

        <!-- Financial Breakdown -->
        <div class="py-2 font-mono {{ $format === 'thermal_58' ? 'text-[8.5px]' : 'text-[10px]' }} space-y-0.5">
            @php
                $beforeAmount = $payment->remaining_balance + $payment->amount;
            @endphp
            <div class="flex justify-between text-slate-600">
                <span>Sisa Hutang Sebelum:</span>
                <span>Rp {{ number_format($beforeAmount, 0, ',', '.') }}</span>
            </div>

            <div class="pt-1 border-t border-slate-900 flex justify-between {{ $format === 'thermal_58' ? 'text-[9.5px]' : 'text-xs' }} font-bold">
                <span>JUMLAH DIBAYARKAN:</span>
                <span class="{{ $format === 'thermal_58' ? 'text-[10px]' : 'text-sm' }} font-bold text-slate-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
            </div>

            <div class="pt-1 border-t border-dashed border-slate-400 flex justify-between font-bold text-amber-800 {{ $format === 'thermal_58' ? 'text-[9px]' : 'text-[10px]' }}">
                <span>SISA SALDO HUTANG:</span>
                <span class="{{ $format === 'thermal_58' ? 'text-[9.5px]' : 'text-xs' }}">Rp {{ number_format($payment->remaining_balance, 0, ',', '.') }}</span>
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
        window.receiptData = {
            type: 'debt_payment',
            companyName: @json($settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR'),
            companyAddress: @json($settings['company_address'] ?? ''),
            companyPhone: @json($settings['company_phone'] ?? ''),
            paymentNumber: @json($payment->payment_number),
            paymentDate: @json($payment->payment_date->format('d/m/Y H:i')),
            borrowerName: @json($payment->debt->borrower_name),
            borrowerType: @json($payment->debt->borrower_type === 'supplier' ? 'Pemasok' : 'Karyawan'),
            cashierName: @json($payment->user?->name ?? 'Kasir'),
            paymentMethod: @json($payment->paymentMethod?->name ?? 'Tunai'),
            amountPaid: @json(\App\Services\FormatHelper::rupiah($payment->amount_paid)),
            remainingAfter: @json(\App\Services\FormatHelper::rupiah($payment->remaining_balance_after)),
            status: @json($payment->remaining_balance_after <= 0 ? 'LUNAS' : null),
            notes: @json($payment->notes)
        };

        const charWidth = {{ $format === 'thermal_80' ? 48 : 32 }};

        function showStatus(msg, type = 'info') {
            const el = document.getElementById('printStatusAlert');
            if (!el) return;
            el.className = 'no-print w-full max-w-xl mb-3 px-3.5 py-2.5 rounded-xl text-xs font-medium border flex items-center justify-between gap-2 ' +
                (type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 
                 type === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 
                 'bg-blue-50 text-blue-800 border-blue-200');
            el.innerHTML = `<span>${msg}</span><button type="button" onclick="this.parentElement.classList.add('hidden')" class="text-xs opacity-60 hover:opacity-100">&times;</button>`;
            el.classList.remove('hidden');
        }

        async function handleBluetoothPrint() {
            const btn = document.getElementById('btnBluetoothPrint');
            const txt = document.getElementById('btnBluetoothText');
            if (!window.ThermalBluetooth || !window.ThermalBluetooth.isSupported()) {
                showStatus('Browser ini belum mendukung Web Bluetooth. Untuk cetak langsung tanpa RawBT, gunakan Google Chrome di Android atau PC.', 'error');
                return;
            }

            try {
                if (btn) btn.disabled = true;
                await window.ThermalBluetooth.printBluetooth(window.receiptData, parseInt(charWidth), (step) => {
                    if (txt) txt.textContent = step;
                    showStatus(step, 'info');
                }, 'debt_payment');
                showStatus('Kuitansi berhasil dicetak langsung via Bluetooth (100% bebas watermark RawBT)!', 'success');
            } catch (err) {
                console.error(err);
                if (err.name === 'NotFoundError') {
                    showStatus('Pencarian printer dibatalkan.', 'info');
                } else {
                    showStatus('Gagal mencetak Bluetooth: ' + err.message + '. Anda juga dapat menekan tombol Quick Printer atau Cetak Biasa.', 'error');
                }
            } finally {
                if (btn) btn.disabled = false;
                if (txt) txt.textContent = 'Cetak Bluetooth';
            }
        }

        function handleQuickPrinter() {
            if (window.ThermalBluetooth) {
                window.ThermalBluetooth.printQuickPrinter(window.receiptData, parseInt(charWidth), 'debt_payment');
            }
        }

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

