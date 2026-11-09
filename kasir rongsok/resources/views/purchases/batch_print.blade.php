<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak_Semua_Nota_Pembelian_HVS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 6mm 6mm 8mm 6mm;
            }
            .no-print {
                display: none !important;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .hvs-container {
                max-width: 100% !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
            }
            .batch-row {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .receipt-card {
                box-shadow: none !important;
                border: 1px dashed #000 !important;
                margin: 0 !important;
                padding: 2mm !important;
                background: #fff !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .receipt-card * {
                color: #000 !important;
                border-color: #000 !important;
            }
        }

        .batch-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .batch-cell {
            width: 33.333%;
            vertical-align: top;
            padding: 0 4px 10px 4px;
            box-sizing: border-box;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 flex flex-col items-center font-sans">

    <!-- Top Toolbar (Screen Only) -->
    <div class="no-print w-full max-w-5xl mb-4 bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3 sticky top-4 z-40">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-800">Cetak Semua Nota Pembelian (Format 58mm Kertas HVS)</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    KERTAS HVS A4 (3 KOLOM)
                </span>
            </div>
            @php
                $batchTotalNet = $purchases->sum(fn($p) => $p->items->where('is_cancelled', false)->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? $i->quantity : $i->net_weight));
                $batchTotalAmount = $purchases->sum(fn($p) => max(0, $p->items->where('is_cancelled', false)->sum('subtotal') - min((float)($p->discount_amount ?? 0), $p->items->where('is_cancelled', false)->sum('subtotal')) + (float)($p->rounding_amount ?? 0)));
            @endphp
            <div class="text-xs text-slate-500 mt-0.5">
                Total: <strong class="text-slate-800">{{ $purchases->count() }} Nota</strong> | 
                Berat Bersih: <strong class="text-slate-800">{{ \App\Services\FormatHelper::weight($batchTotalNet) }} kg</strong> | 
                Total Nilai: <strong class="text-emerald-700">{{ \App\Services\FormatHelper::rupiah($batchTotalAmount) }}</strong>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak ke Kertas HVS ({{ $purchases->count() }})</span>
            </button>

            <a href="{{ route('purchases.batch-pdf', request()->query()) }}" target="_blank"
               class="px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors"
               title="Preview & Unduh Berkas PDF Resmi Kertas HVS">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Preview PDF HVS</span>
            </a>

            <a href="{{ route('purchases.index') }}" class="px-3 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition-colors">
                Kembali
            </a>
        </div>
    </div>

    <!-- HVS Paper Simulation Wrapper -->
    <div class="hvs-container w-full max-w-5xl bg-white p-6 rounded-2xl shadow-md border border-slate-200">
        
        <!-- Document Page Summary Header -->
        <div class="pb-3 mb-4 border-b-2 border-slate-900 flex justify-between items-end">
            <div>
                <h1 class="text-base font-black text-emerald-800 tracking-tight uppercase">
                    {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
                </h1>
                <div class="text-xs font-bold text-slate-900 mt-0.5">
                    KUMPULAN NOTA TRANSAKSI PEMBELIAN RONGSOK
                </div>
                <div class="text-[11px] text-slate-600 mt-0.5">
                    Layout Format 58mm Gabungan Kertas HVS A4 | Dicetak: {{ date('d/m/Y H:i') }} WIB
                </div>
            </div>
            <div class="text-right text-xs">
                <div class="text-slate-600">Total Transaksi: <strong class="text-slate-900 text-sm font-black">{{ $purchases->count() }} Nota</strong></div>
                <div class="text-slate-600">Total Berat: <strong class="text-slate-900 font-bold">{{ \App\Services\FormatHelper::weight($batchTotalNet) }} kg</strong></div>
                <div class="text-emerald-700 font-bold text-sm">{{ \App\Services\FormatHelper::rupiah($batchTotalAmount) }}</div>
            </div>
        </div>

        <!-- 3 Columns per Row Grid -->
        <table class="batch-table">
            @forelse($purchases->chunk(3) as $row)
                <tr class="batch-row">
                    @foreach($row as $purchase)
                        <td class="batch-cell">
                            <div class="receipt-card bg-white text-slate-900 border border-dashed border-slate-400 rounded-lg p-2.5 text-[9.5px] font-mono leading-tight shadow-xs">
                                
                                <!-- Header Depo -->
                                <div class="text-center pb-2 border-b border-dashed border-slate-400">
                                    <div class="text-[11px] font-black tracking-tight uppercase text-slate-900">
                                        {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
                                    </div>
                                    <div class="text-[8.5px] text-slate-600 mt-0.5 leading-tight">
                                        {{ $settings['company_address'] ?? 'Jl. Sentra Daur Ulang Logam' }}
                                    </div>
                                    <div class="text-[8.5px] text-slate-600 font-mono">
                                        Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                                    </div>
                                </div>

                                <!-- Meta Info (Colons vertically aligned, dynamic width never overlapping text) -->
                                <div class="py-1.5 text-[9.5px] border-b border-dashed border-slate-400 font-mono"
                                     style="display: grid; grid-template-columns: max-content 6px 1fr; column-gap: 3px; row-gap: 2px; align-items: baseline;">
                                    <span class="whitespace-nowrap text-slate-700">No. Nota</span>
                                    <span class="text-center">:</span>
                                    <span class="text-left font-bold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->invoice_number }}</span>

                                    <span class="whitespace-nowrap text-slate-700">Tanggal</span>
                                    <span class="text-center">:</span>
                                    <span class="text-left whitespace-nowrap">{{ $purchase->transaction_date->format('d/m/Y H:i') }}</span>

                                    <span class="whitespace-nowrap text-slate-700">Pemasok</span>
                                    <span class="text-center">:</span>
                                    <span class="text-left font-semibold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->supplier_name ?: '-' }}</span>

                                    @if($purchase->supplier_phone)
                                        <span class="whitespace-nowrap text-slate-700">HP Pemasok</span>
                                        <span class="text-center">:</span>
                                        <span class="text-left font-mono whitespace-nowrap">{{ $purchase->supplier_phone }}</span>
                                    @endif

                                    <span class="whitespace-nowrap text-slate-700">Kasir</span>
                                    <span class="text-center">:</span>
                                    <span class="text-left whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->user?->name }}</span>

                                    <span class="whitespace-nowrap text-slate-700">Metode Bayar</span>
                                    <span class="text-center">:</span>
                                    <span class="text-left font-semibold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->paymentMethod?->name ?? 'Tunai' }}</span>

                                    @if($purchase->isBankPayment())
                                        @if($purchase->getBankAccountNumber())
                                            <span class="whitespace-nowrap text-slate-700">No. Rekening</span>
                                            <span class="text-center">:</span>
                                            <span class="text-left font-mono font-bold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->getBankAccountNumber() }}</span>
                                        @endif
                                        @if($purchase->getBankAccountName())
                                            <span class="whitespace-nowrap text-slate-700">Nama Rekening</span>
                                            <span class="text-center">:</span>
                                            <span class="text-left font-semibold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->getBankAccountName() }}</span>
                                        @endif
                                        @if(!$purchase->getBankAccountNumber() && !$purchase->getBankAccountName() && !empty($purchase->bank_info))
                                            <span class="whitespace-nowrap text-slate-700">Data Rekening</span>
                                            <span class="text-center">:</span>
                                            <span class="text-left text-[8px] font-bold whitespace-nowrap overflow-hidden text-ellipsis" title="{{ $purchase->bank_info }}">{{ $purchase->bank_info }}</span>
                                        @endif
                                    @endif
                                </div>

                                <!-- Items Breakdown -->
                                <div class="py-1 border-b border-dashed border-slate-400 font-mono text-[9px]">
                                    @php
                                        $activeItems = $purchase->items->where('is_cancelled', false);
                                        $activeGross = $activeItems->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? ($i->gross_weight > 0 ? $i->gross_weight : ($i->quantity + $i->tare_weight)) : $i->gross_weight);
                                        $activeTare = $activeItems->sum('tare_weight');
                                        $activeNet = $activeItems->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? $i->quantity : $i->net_weight);
                                        $activeSubtotal = $activeItems->sum('subtotal');
                                        $discount = min((float) ($purchase->discount_amount ?? 0), $activeSubtotal);
                                        $rounding = (float) ($purchase->rounding_amount ?? 0);
                                        $activeFinal = max(0, $activeSubtotal - $discount + $rounding);
                                        $debtDeduction = min((float) ($purchase->debt_deduction_amount ?? 0), $activeFinal);
                                        $activeNetCash = max(0, $activeFinal - $debtDeduction);
                                    @endphp
                                    @if($activeItems->isEmpty())
                                        <div class="py-2 text-center text-[9px] text-red-600 font-bold uppercase tracking-wider">
                                            (Semua Barang Dibatalkan)
                                        </div>
                                    @else
                                        @foreach($activeItems as $item)
                                            <div class="py-1 {{ !$loop->last ? 'border-b border-dotted border-slate-300' : '' }}">
                                                <div class="font-bold text-[9.5px] uppercase text-slate-900 leading-tight">
                                                    {{ $item->item_name_snapshot }}
                                                    @if($item->item_type_name)
                                                        <span class="font-normal text-[8.5px] text-slate-600">({{ $item->item_type_name }})</span>
                                                    @endif
                                                </div>
                                                @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                                    <div class="flex justify-between text-[8.5px] text-slate-600">
                                                        <span>
                                                            @if($item->tare_weight > 0)
                                                                {{ \App\Services\FormatHelper::number($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight)) }} - {{ \App\Services\FormatHelper::number($item->tare_weight) }} pcs
                                                                @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight), $item->tare_weight))
                                                                    ({{ $pct }})
                                                                @endif
                                                            @else
                                                                Satuan
                                                            @endif
                                                        </span>
                                                        <span class="font-bold text-slate-900">{{ \App\Services\FormatHelper::number($item->quantity ?? 1) }} {{ $item->unit_type ?: 'pcs' }}</span>
                                                    </div>
                                                @else
                                                    <div class="flex justify-between text-[8.5px] text-slate-600">
                                                        <span>
                                                            {{ \App\Services\FormatHelper::weight($item->gross_weight) }} - {{ \App\Services\FormatHelper::weight($item->tare_weight) }} kg
                                                            @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight, $item->tare_weight))
                                                                ({{ $pct }})
                                                            @endif
                                                        </span>
                                                        <span class="font-bold text-slate-900">{{ \App\Services\FormatHelper::weight($item->net_weight) }} kg</span>
                                                    </div>
                                                @endif
                                                <div class="flex justify-between text-[8.5px] pt-0.5">
                                                    <span class="text-slate-600">@ {{ \App\Services\FormatHelper::rupiah($item->price_per_kg) }}</span>
                                                    <span class="font-black text-slate-900">{{ \App\Services\FormatHelper::rupiah($item->subtotal) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                @php
                                    $grpComm = $activeItems->groupBy('item_name_snapshot');
                                    $hasMulti = $grpComm->contains(fn($g) => $g->count() > 1);
                                @endphp
                                @if($hasMulti)
                                    <div class="py-1 border-t border-dotted border-slate-300 font-mono text-[8px] text-slate-700">
                                        <div class="font-bold uppercase text-[7.5px]">Akumulasi Komoditas:</div>
                                        @foreach($grpComm as $cName => $cGrp)
                                            @php
                                                $tW = $cGrp->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? $i->quantity : $i->net_weight);
                                                $u = $cGrp->first()->unit_type ?: 'kg';
                                                $tS = $cGrp->sum('subtotal');
                                            @endphp
                                            <div class="flex justify-between">
                                                <span>{{ $cName }}:</span>
                                                <span class="font-bold">{{ \App\Services\FormatHelper::weight($tW) }} {{ $u }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Total Calculation -->
                                <div class="pt-1.5 font-mono text-[9px] space-y-0.5">
                                    <div style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                        <span class="whitespace-nowrap text-slate-700">Berat Kotor</span>
                                        <span class="text-center">:</span>
                                        <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::weight($activeGross) }} kg</span>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                        <span class="whitespace-nowrap text-slate-700">Pot./Tara</span>
                                        <span class="text-center">:</span>
                                        <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::weight($activeTare) }} kg</span>
                                    </div>
                                    <div class="font-bold text-slate-900" style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                        <span class="whitespace-nowrap">BERAT BERSIH</span>
                                        <span class="text-center">:</span>
                                        <span class="text-right font-bold whitespace-nowrap">{{ \App\Services\FormatHelper::weight($activeNet) }} kg</span>
                                    </div>
                                    
                                    <div class="pt-1 border-t border-dotted border-slate-300" style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                        <span class="whitespace-nowrap text-slate-700">Subtotal</span>
                                        <span class="text-center">:</span>
                                        <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::rupiah($activeSubtotal) }}</span>
                                    </div>
                                    @if($discount > 0)
                                        <div class="text-slate-600" style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                            <span class="whitespace-nowrap">Potongan</span>
                                            <span class="text-center">:</span>
                                            <span class="text-right whitespace-nowrap">- {{ \App\Services\FormatHelper::rupiah($discount) }}</span>
                                        </div>
                                    @endif
                                    @if($rounding != 0)
                                        <div class="text-slate-600" style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                            <span class="whitespace-nowrap">Pembulatan</span>
                                            <span class="text-center">:</span>
                                            <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::rupiah($rounding) }}</span>
                                        </div>
                                    @endif

                                    <div class="pt-1 border-t border-slate-900 text-[9.5px] font-bold" style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                        <span class="whitespace-nowrap">TOTAL NOTA</span>
                                        <span class="text-center">:</span>
                                        <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::rupiah($activeFinal) }}</span>
                                    </div>

                                    @php
                                        $debtAfter = (float) ($purchase->remaining_debt_after ?? ($purchase->supplier ? $purchase->supplier->debts->sum('remaining_amount') : 0));
                                        $debtBefore = $debtAfter + $debtDeduction;
                                        $hasDebtInfo = ($debtBefore > 0 || $debtAfter > 0 || $debtDeduction > 0);
                                    @endphp

                                    @if($hasDebtInfo)
                                        <div class="text-amber-900 font-semibold" style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                            <span class="whitespace-nowrap">Hutang Awal</span>
                                            <span class="text-center">:</span>
                                            <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::rupiah($debtBefore) }}</span>
                                        </div>
                                        <div class="{{ $debtDeduction > 0 ? 'text-emerald-800' : 'text-slate-600' }} font-semibold" style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                            <span class="whitespace-nowrap">Potong Hutang</span>
                                            <span class="text-center">:</span>
                                            <span class="text-right whitespace-nowrap">{{ $debtDeduction > 0 ? '- ' . \App\Services\FormatHelper::rupiah($debtDeduction) : 'Rp 0' }}</span>
                                        </div>
                                        <div class="text-amber-900 font-bold" style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                            <span class="whitespace-nowrap">SISA HUTANG</span>
                                            <span class="text-center">:</span>
                                            <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::rupiah($debtAfter) }}</span>
                                        </div>
                                    @endif

                                    <div class="pt-1 border-t border-slate-900 text-[10px] font-bold" style="display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;">
                                        <span class="whitespace-nowrap">UANG DITERIMA</span>
                                        <span class="text-center">:</span>
                                        <span class="text-right whitespace-nowrap text-slate-900">{{ \App\Services\FormatHelper::rupiah($activeNetCash) }}</span>
                                    </div>
                                </div>

                                <!-- Footer Disclaimer -->
                                <div class="mt-2.5 pt-1.5 border-t border-slate-300 text-center font-sans">
                                    <div class="text-[7.5px] font-bold text-slate-600 leading-tight">
                                        *** {{ $settings['receipt_disclaimer'] ?? 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI' }} ***
                                    </div>
                                    <div class="text-[7px] text-slate-400 italic leading-tight pt-0.5">
                                        {{ $settings['receipt_footer'] ?? 'Barang yang sudah ditimbang & dibayar tidak dapat ditarik kembali.' }}
                                    </div>
                                </div>

                            </div>
                        </td>
                    @endforeach
                    @for($k = 0; $k < (3 - $row->count()); $k++)
                        <td class="batch-cell"></td>
                    @endfor
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="p-8 text-center text-slate-400 bg-white rounded-xl">
                        Tidak ada nota pembelian yang sesuai dengan kriteria filter pencarian.
                    </td>
                </tr>
            @endforelse
        </table>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
        });
    </script>
</body>
</html>
