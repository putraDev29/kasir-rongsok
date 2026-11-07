<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $cleanPrintInv = str_replace(['/', '\\', ' '], '-', $purchase->invoice_number);
        $cleanPrintSup = trim(preg_replace('/_+/', '_', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $purchase->supplier_name ?: 'Pemasok_Umum')), '_');
    @endphp
    <title>Nota_{{ $cleanPrintInv }}_{{ $cleanPrintSup }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page {
                size: auto;
                margin: 0mm;
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
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 1.5mm 1mm !important;
                background: #fff !important;
            }
            .receipt-58mm {
                width: 48mm !important;
                max-width: 48mm !important;
                font-size: 8.5px !important;
                line-height: 1.25 !important;
            }
            .receipt-58mm h1,
            .receipt-58mm .thermal-shrink-title {
                font-size: 11px !important;
            }
            .receipt-58mm .thermal-total-label,
            .receipt-58mm .thermal-total-val {
                font-size: 9.5px !important;
            }
            .receipt-58mm .thermal-cash-label {
                font-size: 10px !important;
            }
            .receipt-58mm .thermal-cash-val {
                font-size: 10.5px !important;
            }
            .receipt-80mm {
                width: 72mm !important;
                max-width: 72mm !important;
                font-size: 11px !important;
                line-height: 1.35 !important;
            }
            .receipt-a4 {
                width: 100% !important;
                max-width: 210mm !important;
                padding: 10mm !important;
            }
            /* Pastikan warna teks thermal murni hitam pekat agar hasil bakar thermal tajam */
            .receipt-container * {
                color: #000 !important;
                border-color: #000 !important;
                box-shadow: none !important;
            }
        }
        @media screen {
            .receipt-58mm {
                width: 58mm !important;
                max-width: 58mm !important;
            }
            .receipt-58mm .thermal-shrink-title {
                font-size: 11px !important;
            }
            .receipt-58mm .thermal-total-label,
            .receipt-58mm .thermal-total-val {
                font-size: 9.5px !important;
            }
            .receipt-58mm .thermal-cash-label {
                font-size: 10px !important;
            }
            .receipt-58mm .thermal-cash-val {
                font-size: 10.5px !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center">

    <!-- Print Control Bar -->
    <div class="no-print w-full max-w-xl mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-600">Format Kertas:</span>
            <a href="{{ route('purchases.print', ['purchase' => $purchase, 'format' => 'thermal_58']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium {{ $format === 'thermal_58' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Thermal 58mm
            </a>
            <a href="{{ route('purchases.print', ['purchase' => $purchase, 'format' => 'thermal_80']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium {{ $format === 'thermal_80' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Thermal 80mm
            </a>
            <a href="{{ route('purchases.print', ['purchase' => $purchase, 'format' => 'a4']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium {{ $format === 'a4' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                A4 Standar
            </a>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak Nota</span>
            </button>
            <a href="{{ route('purchases.show', $purchase) }}" class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold">
                Kembali
            </a>
        </div>
    </div>

    <!-- RECEIPT CONTAINER -->
    <div class="receipt-container bg-white text-slate-900 shadow-md border border-slate-200 
         {{ $format === 'thermal_58' ? 'receipt-58mm w-[58mm] max-w-[58mm] text-[9px] p-2' : ($format === 'thermal_80' ? 'receipt-80mm w-[80mm] max-w-[80mm] text-xs p-3' : 'receipt-a4 max-w-3xl w-full p-8 rounded-xl') }}">
        
        <!-- Header -->
        <div class="text-center pb-2 border-b border-dashed border-slate-400">
            @if(\App\Models\BusinessSetting::hasLogo())
                <div class="flex justify-center mb-1.5">
                    <img src="{{ \App\Models\BusinessSetting::logoUrl() }}" alt="Logo" class="{{ $format === 'a4' ? 'h-14' : 'h-8' }} max-w-[140px] object-contain">
                </div>
            @endif
            <h1 class="{{ $format === 'a4' ? 'text-xl' : ($format === 'thermal_58' ? 'text-[11px] thermal-shrink-title' : 'text-sm') }} font-bold tracking-tight uppercase">
                {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
            </h1>
            <p class="text-[9px] text-slate-600 mt-0.5 leading-tight">
                {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}
            </p>
            <p class="text-[9px] text-slate-600 font-mono">
                Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
            </p>
        </div>

        @php
            $metaGrid = ($format === 'thermal_58') 
                ? 'display: grid; grid-template-columns: 66px 6px 1fr; align-items: center;' 
                : (($format === 'thermal_80') 
                    ? 'display: grid; grid-template-columns: 95px 8px 1fr; align-items: center;' 
                    : 'display: grid; grid-template-columns: 130px 12px 1fr; align-items: center;');

            $calcGrid = ($format === 'thermal_58') 
                ? 'display: grid; grid-template-columns: 70px 6px 1fr; align-items: center;' 
                : (($format === 'thermal_80') 
                    ? 'display: grid; grid-template-columns: 105px 8px 1fr; align-items: center;' 
                    : 'display: grid; grid-template-columns: 140px 12px 1fr; align-items: center;');
        @endphp

        <!-- Meta Info (Colons vertically aligned right after longest label, values left-aligned strictly 1 line) -->
        <div class="py-1.5 {{ $format === 'thermal_58' ? 'text-[9px]' : 'text-[10px]' }} space-y-0.5 border-b border-dashed border-slate-400 font-mono">
            <div style="{{ $metaGrid }}">
                <span class="whitespace-nowrap">No. Nota</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 font-bold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->invoice_number }}</span>
            </div>
            <div style="{{ $metaGrid }}">
                <span class="whitespace-nowrap">Tanggal</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 whitespace-nowrap">{{ $purchase->transaction_date->format('d/m/Y H:i') }}</span>
            </div>
            <div style="{{ $metaGrid }}">
                <span class="whitespace-nowrap">Pemasok</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 font-semibold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->supplier_name ?: '-' }}</span>
            </div>
            @if($purchase->supplier_phone)
                <div style="{{ $metaGrid }}">
                    <span class="whitespace-nowrap">HP Pemasok</span>
                    <span class="text-center">:</span>
                    <span class="text-left pl-1.5 font-mono whitespace-nowrap">{{ $purchase->supplier_phone }}</span>
                </div>
            @endif
            <div style="{{ $metaGrid }}">
                <span class="whitespace-nowrap">Kasir</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->user?->name }}</span>
            </div>
            <div style="{{ $metaGrid }}">
                <span class="whitespace-nowrap">Metode Bayar</span>
                <span class="text-center">:</span>
                <span class="text-left pl-1.5 font-semibold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->paymentMethod?->name ?? 'Tunai' }}</span>
            </div>
            @if($purchase->isBankPayment())
                @if($purchase->getBankAccountNumber())
                    <div style="{{ $metaGrid }}">
                        <span class="whitespace-nowrap">No. Rekening</span>
                        <span class="text-center">:</span>
                        <span class="text-left pl-1.5 font-mono font-bold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->getBankAccountNumber() }}</span>
                    </div>
                @endif
                @if($purchase->getBankAccountName())
                    <div style="{{ $metaGrid }}">
                        <span class="whitespace-nowrap">Nama Rekening</span>
                        <span class="text-center">:</span>
                        <span class="text-left pl-1.5 font-semibold whitespace-nowrap overflow-hidden text-ellipsis">{{ $purchase->getBankAccountName() }}</span>
                    </div>
                @endif
                @if(!$purchase->getBankAccountNumber() && !$purchase->getBankAccountName() && !empty($purchase->bank_info))
                    <div style="{{ $metaGrid }}">
                        <span class="whitespace-nowrap">Data Rekening</span>
                        <span class="text-center">:</span>
                        <span class="text-left pl-1.5 font-mono text-[8.5px] font-bold whitespace-nowrap overflow-hidden text-ellipsis" title="{{ $purchase->bank_info }}">{{ $purchase->bank_info }}</span>
                    </div>
                @endif
            @endif
        </div>

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

        <!-- Items Breakdown -->
        @if($format === 'thermal_58')
            <!-- Layout Khusus Kertas 58mm (Pas 32 karakter, rapi tanpa terpotong untuk Eppos/Iware) -->
            <div class="py-1 border-b border-dashed border-slate-400 font-mono text-[9px]">
                @if($activeItems->isEmpty())
                    <div class="py-2 text-center text-[9px] text-red-600 font-bold uppercase tracking-wider">
                        (Semua Barang Dibatalkan)
                    </div>
                @else
                    @foreach($activeItems as $item)
                        <div class="py-1 {{ !$loop->last ? 'border-b border-dotted border-slate-300' : '' }}">
                            <div class="font-bold text-[10px] uppercase text-slate-900 leading-tight">
                                {{ $item->item_name_snapshot }}
                                @if($item->item_type_name)
                                    <span class="font-normal text-[9px] text-slate-700">({{ $item->item_type_name }})</span>
                                @endif
                            </div>
                            @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                <div class="flex justify-between text-[9px] text-slate-600">
                                    <span>
                                        @if($item->tare_weight > 0)
                                            {{ \App\Services\FormatHelper::number($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight)) }} - {{ \App\Services\FormatHelper::number($item->tare_weight) }} pcs
                                            @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight), $item->tare_weight))
                                                ({{ $pct }})
                                            @endif
                                        @else
                                            Jumlah Satuan
                                        @endif
                                    </span>
                                    <span class="font-bold text-slate-900">{{ \App\Services\FormatHelper::number($item->quantity ?? 1) }} {{ $item->unit_type ?: 'pcs' }}</span>
                                </div>
                            @else
                                <div class="flex justify-between text-[9px] text-slate-600">
                                    <span>
                                        {{ \App\Services\FormatHelper::weight($item->gross_weight) }} - {{ \App\Services\FormatHelper::weight($item->tare_weight) }} kg
                                        @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight, $item->tare_weight))
                                            ({{ $pct }})
                                        @endif
                                    </span>
                                    <span class="font-bold text-slate-900">{{ \App\Services\FormatHelper::weight($item->net_weight) }} kg</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-[9px] pt-0.5">
                                <span class="text-slate-600">@ {{ \App\Services\FormatHelper::rupiah($item->price_per_kg) }}</span>
                                <span class="font-black text-slate-900">{{ \App\Services\FormatHelper::rupiah($item->subtotal) }}</span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @else
            <!-- Layout Tabel Lebar (80mm & A4) -->
            <div class="py-2">
                <table class="w-full text-left font-mono">
                    <thead>
                        <tr class="border-b border-slate-300 text-[9px] uppercase">
                            <th class="py-1">Barang</th>
                            <th class="py-1 text-right">Kotor-Tara / Qty</th>
                            <th class="py-1 text-right">Bersih</th>
                            <th class="py-1 text-right">Harga</th>
                            <th class="py-1 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dotted divide-slate-200 text-[10px]">
                        @if($activeItems->isEmpty())
                            <tr>
                                <td colspan="5" class="py-3 text-center text-xs text-red-600 font-bold uppercase tracking-wider">
                                    (Semua Barang Pada Transaksi Ini Telah Dibatalkan)
                                </td>
                            </tr>
                        @else
                            @foreach($activeItems as $item)
                                <tr>
                                    <td class="py-1 pr-1 font-semibold" colspan="5">
                                        {{ $item->item_name_snapshot }}
                                        @if($item->item_type_name)
                                            <span class="font-normal text-[9px] text-slate-600">- {{ $item->item_type_name }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="text-slate-600 text-[9px]">
                                    <td class="pb-1 pl-2 text-slate-400">↳</td>
                                    <td class="pb-1 text-right">
                                        @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                            @if($item->tare_weight > 0)
                                                {{ \App\Services\FormatHelper::number($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight)) }}-{{ \App\Services\FormatHelper::number($item->tare_weight) }}
                                                @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight), $item->tare_weight))
                                                    <span class="text-[8px] text-slate-500">({{ $pct }})</span>
                                                @endif
                                            @else
                                                {{ \App\Services\FormatHelper::number($item->quantity ?? 1) }} {{ $item->unit_type ?: 'pcs' }}
                                            @endif
                                        @else
                                            {{ \App\Services\FormatHelper::weight($item->gross_weight) }}-{{ \App\Services\FormatHelper::weight($item->tare_weight) }}
                                            @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight, $item->tare_weight))
                                                <span class="text-[8px] text-slate-500">({{ $pct }})</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="pb-1 text-right font-bold text-slate-900">
                                        @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                            {{ \App\Services\FormatHelper::number($item->quantity ?? 1) }} pcs
                                        @else
                                            {{ \App\Services\FormatHelper::weight($item->net_weight) }} kg
                                        @endif
                                    </td>
                                    <td class="pb-1 text-right">@ {{ \App\Services\FormatHelper::rupiah($item->price_per_kg) }}</td>
                                    <td class="pb-1 text-right font-bold text-slate-900">{{ \App\Services\FormatHelper::rupiah($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        @endif

        @php
            $groupedPrintCommodities = $activeItems->groupBy('item_name_snapshot');
            $hasPrintMultiPrice = $groupedPrintCommodities->contains(fn($grp) => $grp->count() > 1);
        @endphp

        @if($hasPrintMultiPrice)
            <div class="py-1.5 border-t border-dotted border-slate-300 font-mono text-[9px]">
                <div class="font-bold text-[9px] uppercase mb-0.5 text-slate-800">Akumulasi Komoditas Barang:</div>
                @foreach($groupedPrintCommodities as $name => $grp)
                    @php
                        $totW = $grp->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? $i->quantity : $i->net_weight);
                        $u = $grp->first()->unit_type ?: 'kg';
                        $totS = $grp->sum('subtotal');
                    @endphp
                    <div class="flex justify-between text-slate-700">
                        <span>• {{ $name }}:</span>
                        <span class="font-bold text-slate-900">{{ \App\Services\FormatHelper::weight($totW) }} {{ $u }} ({{ \App\Services\FormatHelper::rupiah($totS) }})</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Total Calculation (Colons aligned right after longest label, values right-aligned strictly 1 line) -->
        <div class="pt-1.5 border-t border-dashed border-slate-400 font-mono {{ $format === 'thermal_58' ? 'text-[8.5px]' : 'text-[10px]' }} space-y-0.5">
            <div style="{{ $calcGrid }}">
                <span class="whitespace-nowrap">Berat Kotor</span>
                <span class="text-center">:</span>
                <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::weight($activeGross) }} kg</span>
            </div>
            <div style="{{ $calcGrid }}">
                <span class="whitespace-nowrap">Pot./Tara</span>
                <span class="text-center">:</span>
                <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::weight($activeTare) }} kg</span>
            </div>
            <div style="{{ $calcGrid }}" class="font-bold text-slate-900">
                <span class="whitespace-nowrap">BERAT BERSIH</span>
                <span class="text-center">:</span>
                <span class="text-right font-bold whitespace-nowrap">{{ \App\Services\FormatHelper::weight($activeNet) }} kg</span>
            </div>
            
            <div class="pt-1 border-t border-dotted border-slate-300" style="{{ $calcGrid }}">
                <span class="whitespace-nowrap">Subtotal</span>
                <span class="text-center">:</span>
                <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::rupiah($activeSubtotal) }}</span>
            </div>
            @if($discount > 0)
                <div class="text-slate-600" style="{{ $calcGrid }}">
                    <span class="whitespace-nowrap">Potongan</span>
                    <span class="text-center">:</span>
                    <span class="text-right whitespace-nowrap">- {{ \App\Services\FormatHelper::rupiah($discount) }}</span>
                </div>
            @endif
            @if($rounding != 0)
                <div class="text-slate-600" style="{{ $calcGrid }}">
                    <span class="whitespace-nowrap">Pembulatan</span>
                    <span class="text-center">:</span>
                    <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::rupiah($rounding) }}</span>
                </div>
            @endif

            <div class="pt-1 border-t border-slate-900 {{ $format === 'thermal_58' ? 'text-[9.5px]' : 'text-xs' }} font-bold" style="{{ $calcGrid }}">
                <span class="whitespace-nowrap thermal-total-label">TOTAL HARGA</span>
                <span class="text-center">:</span>
                <span class="text-right {{ $format === 'thermal_58' ? 'text-[9.5px]' : 'text-xs' }} font-bold whitespace-nowrap thermal-total-val">{{ \App\Services\FormatHelper::rupiah($activeFinal) }}</span>
            </div>

            @php
                $hasDebtInfo = ($debtDeduction > 0) || (($purchase->remaining_debt_after ?? 0) > 0) || ($purchase->supplier && $purchase->supplier->debts->where('remaining_amount', '>', 0)->count() > 0);
                $debtBefore = ($purchase->remaining_debt_after ?? 0) + $debtDeduction;
                $debtAfter = $purchase->remaining_debt_after ?? ($purchase->supplier ? $purchase->supplier->debts->sum('remaining_amount') : 0);
            @endphp

            @if($hasDebtInfo)
                <div class="mt-1.5 pt-1.5 border-t border-dashed border-slate-400 font-mono {{ $format === 'thermal_58' ? 'text-[8.5px]' : 'text-[9px] sm:text-[10px]' }} space-y-0.5 bg-slate-50 p-1 rounded">
                    <div class="font-bold text-center {{ $format === 'thermal_58' ? 'text-[8.5px]' : 'text-[10px]' }} uppercase tracking-wider text-slate-800 pb-0.5 border-b border-dotted border-slate-300">
                        -- RINCIAN HUTANG / BON MODAL --
                    </div>
                    <div style="{{ $calcGrid }}">
                        <span class="whitespace-nowrap">Hutang Awal</span>
                        <span class="text-center">:</span>
                        <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::rupiah($debtBefore) }}</span>
                    </div>
                    <div class="text-slate-800 font-semibold" style="{{ $calcGrid }}">
                        <span class="whitespace-nowrap">Potong Hutang</span>
                        <span class="text-center">:</span>
                        <span class="text-right text-emerald-700 whitespace-nowrap">- {{ \App\Services\FormatHelper::rupiah($debtDeduction) }}</span>
                    </div>
                    <div class="font-bold text-amber-800 pt-0.5 border-t border-dotted border-slate-300" style="{{ $calcGrid }}">
                        <span class="whitespace-nowrap">SISA HUTANG</span>
                        <span class="text-center">:</span>
                        <span class="text-right whitespace-nowrap">{{ \App\Services\FormatHelper::rupiah($debtAfter) }}</span>
                    </div>
                </div>
            @endif

            <div class="pt-1.5 border-t border-slate-900 {{ $format === 'thermal_58' ? 'text-[10px]' : 'text-xs' }} font-bold" style="{{ $calcGrid }}">
                <span class="whitespace-nowrap thermal-cash-label">UANG DITERIMA</span>
                <span class="text-center">:</span>
                <span class="text-right {{ $format === 'thermal_58' ? 'text-[10.5px]' : 'text-sm' }} font-bold whitespace-nowrap thermal-cash-val">{{ \App\Services\FormatHelper::rupiah($activeNetCash) }}</span>
            </div>
        </div>

        <!-- Signatures (Especially in A4 / Receipt for supplier confirmation) -->
        <div class="mt-4 pt-1.5 border-t border-slate-300 text-center font-sans">
            <div class="grid grid-cols-2 gap-2 {{ $format === 'thermal_58' ? 'text-[8.5px]' : 'text-[10px]' }} text-slate-600">
                <div>
                    <p>Penjual / Pemasok</p>
                    <div class="{{ $format === 'thermal_58' ? 'h-7' : 'h-10' }}"></div>
                    <p class="font-bold">({{ $purchase->supplier_name ?: '...................' }})</p>
                </div>
                <div>
                    <p>Operator Kasir</p>
                    <div class="{{ $format === 'thermal_58' ? 'h-7' : 'h-10' }}"></div>
                    <p class="font-bold">({{ $purchase->user?->name }})</p>
                </div>
            </div>

            <!-- Peringatan Disclaimer Hukum: Tidak Menerima Barang Bukan Milik Pribadi -->
            <div class="mt-3 pt-2 border-t border-dashed border-slate-900 text-center font-sans space-y-0.5">
                <div class="inline-block px-1.5 py-0.2 bg-slate-900 text-white font-bold {{ $format === 'thermal_58' ? 'text-[7.5px]' : 'text-[8px] sm:text-[9px]' }} uppercase tracking-wider rounded">
                    PERHATIAN / DISCLAIMER
                </div>
                <div class="font-bold uppercase {{ $format === 'thermal_58' ? 'text-[8px]' : 'text-[9px] sm:text-[10px]' }} text-slate-900 tracking-tight leading-tight">
                    *** {{ $settings['receipt_disclaimer'] ?? 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI' }} ***
                </div>
                <p class="text-[7.5px] text-slate-600 leading-tight">
                    Barang yang dijual wajib milik sah pribadi penjual. Depo tidak melayani barang curian / ilegal.
                </p>
                @if(!empty($settings['receipt_terms']))
                    <p class="text-[7.5px] text-slate-500 leading-tight">
                        {{ $settings['receipt_terms'] }}
                    </p>
                @endif
                <p class="text-[7px] text-slate-400 italic leading-tight pt-0.5">
                    {{ $settings['receipt_footer'] ?? 'Barang yang sudah ditimbang & dibayar tidak dapat ditarik kembali.' }}
                </p>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
            // Auto open print dialog if requested
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(() => window.print(), 300);
            }
        });
    </script>
</body>
</html>

