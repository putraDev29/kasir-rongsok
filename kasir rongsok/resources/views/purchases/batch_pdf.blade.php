<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Semua_Nota_Pembelian</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 6mm 6mm 8mm 6mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        
        /* Summary Header on Top of Page 1 */
        .page-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .header-title-row {
            width: 100%;
            border-collapse: collapse;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #047857;
        }
        .doc-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin-top: 2px;
        }
        .recap-badges {
            font-size: 8px;
            color: #334155;
            margin-top: 3px;
        }
        .recap-badges strong {
            color: #0f172a;
        }

        /* 3-Column Fixed Table for A4 Layout */
        .batch-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .batch-row {
            page-break-inside: avoid;
        }
        .batch-cell {
            width: 33.333%;
            vertical-align: top;
            padding: 0 3px 6px 3px;
            box-sizing: border-box;
        }

        /* 58mm Thermal Receipt Box Container */
        .receipt-card {
            border: 1px dashed #64748b;
            background: #ffffff;
            padding: 5px 6px;
            box-sizing: border-box;
            page-break-inside: avoid;
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5px;
            line-height: 1.2;
            color: #0f172a;
        }

        /* Receipt Header */
        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #64748b;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }
        .receipt-header .shop-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .receipt-header .shop-address {
            font-size: 6.5px;
            color: #475569;
            margin-top: 1px;
            line-height: 1.1;
        }
        .receipt-header .shop-phone {
            font-size: 6.5px;
            color: #475569;
        }

        /* Meta Table with Vertically Aligned Colons and Left-Aligned Values */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            margin-bottom: 4px;
        }
        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .meta-label {
            width: 70px;
            white-space: nowrap;
            color: #1e293b;
        }
        .meta-colon {
            width: 6px;
            text-align: center;
            color: #1e293b;
        }
        .meta-value {
            text-align: left;
            padding-left: 2px;
            white-space: nowrap;
            overflow: hidden;
            color: #0f172a;
        }

        /* Items Section */
        .items-section {
            border-top: 1px dashed #64748b;
            border-bottom: 1px dashed #64748b;
            padding: 3px 0;
            margin-bottom: 4px;
        }
        .item-row {
            margin-bottom: 2px;
            padding-bottom: 2px;
            border-bottom: 1px dotted #e2e8f0;
        }
        .item-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .item-title {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
        }
        .item-sub {
            font-size: 7px;
            color: #475569;
            font-weight: normal;
        }
        .item-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            color: #334155;
            margin-top: 1px;
        }
        .item-grid td {
            padding: 0;
        }

        /* Calculation / Totals Table */
        .calc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }
        .calc-table td {
            padding: 0.8px 0;
            vertical-align: middle;
        }
        .calc-label {
            width: 62px;
            white-space: nowrap;
        }
        .calc-colon {
            width: 6px;
            text-align: center;
        }
        .calc-value {
            text-align: right;
            white-space: nowrap;
        }
        .calc-divider {
            border-top: 1px dotted #94a3b8;
        }
        .calc-divider-solid {
            border-top: 1px solid #0f172a;
        }

        /* Receipt Footer */
        .receipt-footer {
            border-top: 1px dashed #64748b;
            margin-top: 4px;
            padding-top: 3px;
            text-align: center;
            font-family: sans-serif;
            font-size: 6px;
            color: #64748b;
            line-height: 1.1;
        }
        .receipt-footer .disclaimer {
            font-weight: bold;
            color: #475569;
        }
    </style>
</head>
<body>

    <!-- Summary Header on Top of Document -->
    <div class="page-header">
        <table class="header-title-row">
            <tr>
                <td style="vertical-align: top;">
                    <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                    <div class="doc-title">REKAPITULASI NOTA PEMBELIAN RONGSOK (FORMAT 58MM KERTAS HVS A4)</div>
                    <div class="recap-badges">
                        Periode: <strong>{{ $startDate ? date('d/m/Y', strtotime($startDate)) : 'Semua' }} s/d {{ $endDate ? date('d/m/Y', strtotime($endDate)) : 'Semua' }}</strong>
                        &nbsp;|&nbsp; Dicetak: <strong>{{ date('d/m/Y H:i') }} WIB</strong>
                    </div>
                </td>
                <td style="text-align: right; vertical-align: top; width: 220px;">
                    @php
                        $batchPdfTotalNet = $purchases->sum(fn($p) => $p->items->where('is_cancelled', false)->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? $i->quantity : $i->net_weight));
                        $batchPdfTotalAmount = $purchases->sum(fn($p) => max(0, $p->items->where('is_cancelled', false)->sum('subtotal') - min((float)($p->discount_amount ?? 0), $p->items->where('is_cancelled', false)->sum('subtotal')) + (float)($p->rounding_amount ?? 0)));
                    @endphp
                    <div style="font-size: 8px; color: #475569;">
                        Total Nota: <strong style="color: #0f172a; font-size: 9px;">{{ $purchases->count() }} Nota</strong>
                    </div>
                    <div style="font-size: 8px; color: #475569;">
                        Total Bersih: <strong style="color: #0f172a; font-size: 9px;">{{ \App\Services\FormatHelper::weight($batchPdfTotalNet) }} kg</strong>
                    </div>
                    <div style="font-size: 8.5px; color: #047857; font-weight: bold; margin-top: 1px;">
                        Total Nilai: {{ \App\Services\FormatHelper::rupiah($batchPdfTotalAmount) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Receipts Grid (3 Columns on A4 Paper) -->
    <table class="batch-table">
        @forelse($purchases->chunk(3) as $row)
            <tr class="batch-row">
                @foreach($row as $purchase)
                    <td class="batch-cell">
                        <div class="receipt-card">
                            
                            <!-- Header Depo -->
                            <div class="receipt-header">
                                <div class="shop-title">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                                <div class="shop-address">{{ $settings['company_address'] ?? 'Jl. Sentra Daur Ulang Logam' }}</div>
                                <div class="shop-phone">Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}</div>
                            </div>

                            <!-- Meta Info (Colons vertically aligned, values left-aligned) -->
                            <table class="meta-table">
                                <tr>
                                    <td class="meta-label">No. Nota</td>
                                    <td class="meta-colon">:</td>
                                    <td class="meta-value" style="font-weight: bold;">{{ $purchase->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Tanggal</td>
                                    <td class="meta-colon">:</td>
                                    <td class="meta-value">{{ $purchase->transaction_date->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Pemasok</td>
                                    <td class="meta-colon">:</td>
                                    <td class="meta-value" style="font-weight: bold;">{{ $purchase->supplier_name ?: '-' }}</td>
                                </tr>
                                @if($purchase->supplier_phone)
                                    <tr>
                                        <td class="meta-label">HP Pemasok</td>
                                        <td class="meta-colon">:</td>
                                        <td class="meta-value">{{ $purchase->supplier_phone }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="meta-label">Kasir</td>
                                    <td class="meta-colon">:</td>
                                    <td class="meta-value">{{ $purchase->user?->name ?? 'Kasir' }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Metode Bayar</td>
                                    <td class="meta-colon">:</td>
                                    <td class="meta-value" style="font-weight: bold;">{{ $purchase->paymentMethod?->name ?? 'Tunai' }}</td>
                                </tr>
                                @if($purchase->isBankPayment())
                                    @if($purchase->getBankAccountNumber())
                                        <tr>
                                            <td class="meta-label">No. Rekening</td>
                                            <td class="meta-colon">:</td>
                                            <td class="meta-value" style="font-family: monospace; font-weight: bold;">{{ $purchase->getBankAccountNumber() }}</td>
                                        </tr>
                                    @endif
                                    @if($purchase->getBankAccountName())
                                        <tr>
                                            <td class="meta-label">Nama Rekening</td>
                                            <td class="meta-colon">:</td>
                                            <td class="meta-value">{{ $purchase->getBankAccountName() }}</td>
                                        </tr>
                                    @endif
                                    @if(!$purchase->getBankAccountNumber() && !$purchase->getBankAccountName() && !empty($purchase->bank_info))
                                        <tr>
                                            <td class="meta-label">Data Bayar</td>
                                            <td class="meta-colon">:</td>
                                            <td class="meta-value" style="font-size: 6.5px;">{{ $purchase->bank_info }}</td>
                                        </tr>
                                    @endif
                                @endif
                            </table>

                            <!-- Items Section -->
                            <div class="items-section">
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
                                    <div style="text-align: center; color: #dc2626; font-weight: bold; padding: 4px 0; font-size: 7px;">
                                        (Semua Barang Dibatalkan)
                                    </div>
                                @else
                                    @foreach($activeItems as $item)
                                        <div class="item-row">
                                            <div class="item-title">
                                                {{ $item->item_name_snapshot }}
                                                @if($item->item_type_name)
                                                    <span class="item-sub">({{ $item->item_type_name }})</span>
                                                @endif
                                            </div>
                                            <table class="item-grid">
                                                @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                                    <tr>
                                                        <td>
                                                            @if($item->tare_weight > 0)
                                                                {{ \App\Services\FormatHelper::number($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight)) }} - {{ \App\Services\FormatHelper::number($item->tare_weight) }} pcs
                                                                @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight), $item->tare_weight))
                                                                    ({{ $pct }})
                                                                @endif
                                                            @else
                                                                Satuan
                                                            @endif
                                                        </td>
                                                        <td style="text-align: right; font-weight: bold; color: #0f172a;">{{ \App\Services\FormatHelper::number($item->quantity ?? 1) }} {{ $item->unit_type ?: 'pcs' }}</td>
                                                    </tr>
                                                @else
                                                    <tr>
                                                        <td>
                                                            {{ \App\Services\FormatHelper::weight($item->gross_weight) }} - {{ \App\Services\FormatHelper::weight($item->tare_weight) }} kg
                                                            @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight, $item->tare_weight))
                                                                ({{ $pct }})
                                                            @endif
                                                        </td>
                                                        <td style="text-align: right; font-weight: bold; color: #0f172a;">{{ \App\Services\FormatHelper::weight($item->net_weight) }} kg</td>
                                                    </tr>
                                                @endif
                                                <tr>
                                                    <td>@ {{ \App\Services\FormatHelper::rupiah($item->price_per_kg) }}</td>
                                                    <td style="text-align: right; font-weight: bold; color: #0f172a;">{{ \App\Services\FormatHelper::rupiah($item->subtotal) }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <!-- Calculation Totals -->
                            <table class="calc-table">
                                <tr>
                                    <td class="calc-label">Berat Kotor</td>
                                    <td class="calc-colon">:</td>
                                    <td class="calc-value">{{ \App\Services\FormatHelper::weight($activeGross) }} kg</td>
                                </tr>
                                <tr>
                                    <td class="calc-label">Pot./Tara</td>
                                    <td class="calc-colon">:</td>
                                    <td class="calc-value">{{ \App\Services\FormatHelper::weight($activeTare) }} kg</td>
                                </tr>
                                <tr style="font-weight: bold;">
                                    <td class="calc-label">BERAT BERSIH</td>
                                    <td class="calc-colon">:</td>
                                    <td class="calc-value">{{ \App\Services\FormatHelper::weight($activeNet) }} kg</td>
                                </tr>
                                
                                <tr>
                                    <td class="calc-label calc-divider">Subtotal</td>
                                    <td class="calc-colon calc-divider">:</td>
                                    <td class="calc-value calc-divider">{{ \App\Services\FormatHelper::rupiah($activeSubtotal) }}</td>
                                </tr>
                                @if($discount > 0)
                                    <tr>
                                        <td class="calc-label" style="color: #64748b;">Potongan</td>
                                        <td class="calc-colon" style="color: #64748b;">:</td>
                                        <td class="calc-value" style="color: #64748b;">- {{ \App\Services\FormatHelper::rupiah($discount) }}</td>
                                    </tr>
                                @endif
                                @if($rounding != 0)
                                    <tr>
                                        <td class="calc-label" style="color: #64748b;">Pembulatan</td>
                                        <td class="calc-colon" style="color: #64748b;">:</td>
                                        <td class="calc-value" style="color: #64748b;">{{ \App\Services\FormatHelper::rupiah($rounding) }}</td>
                                    </tr>
                                @endif

                                <tr style="font-weight: bold;">
                                    <td class="calc-label calc-divider-solid" style="padding-top: 2px;">TOTAL NOTA</td>
                                    <td class="calc-colon calc-divider-solid" style="padding-top: 2px;">:</td>
                                    <td class="calc-value calc-divider-solid" style="padding-top: 2px;">{{ \App\Services\FormatHelper::rupiah($activeFinal) }}</td>
                                </tr>

                                @php
                                    $debtAfter = (float) ($purchase->remaining_debt_after ?? ($purchase->supplier ? $purchase->supplier->debts->sum('remaining_amount') : 0));
                                    $debtBefore = $debtAfter + $debtDeduction;
                                    $hasDebtInfo = ($debtBefore > 0 || $debtAfter > 0 || $debtDeduction > 0);
                                @endphp

                                @if($hasDebtInfo)
                                    <tr style="color: #b45309;">
                                        <td class="calc-label">Hutang Awal</td>
                                        <td class="calc-colon">:</td>
                                        <td class="calc-value">{{ \App\Services\FormatHelper::rupiah($debtBefore) }}</td>
                                    </tr>
                                    <tr style="color: {{ $debtDeduction > 0 ? '#047857' : '#64748b' }}; font-weight: bold;">
                                        <td class="calc-label">Potong Hutang</td>
                                        <td class="calc-colon">:</td>
                                        <td class="calc-value">{{ $debtDeduction > 0 ? '- ' . \App\Services\FormatHelper::rupiah($debtDeduction) : 'Rp 0' }}</td>
                                    </tr>
                                    <tr style="color: #b45309; font-weight: bold;">
                                        <td class="calc-label">SISA HUTANG</td>
                                        <td class="calc-colon">:</td>
                                        <td class="calc-value">{{ \App\Services\FormatHelper::rupiah($debtAfter) }}</td>
                                    </tr>
                                @endif

                                <tr style="font-weight: bold; font-size: 8px;">
                                    <td class="calc-label calc-divider-solid" style="padding-top: 2px;">UANG DITERIMA</td>
                                    <td class="calc-colon calc-divider-solid" style="padding-top: 2px;">:</td>
                                    <td class="calc-value calc-divider-solid" style="padding-top: 2px;">{{ \App\Services\FormatHelper::rupiah($activeNetCash) }}</td>
                                </tr>
                            </table>

                            <!-- Receipt Footer -->
                            <div class="receipt-footer">
                                <div class="disclaimer">*** {{ $settings['receipt_disclaimer'] ?? 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI' }} ***</div>
                                <div>{{ $settings['receipt_footer'] ?? 'Barang yang sudah ditimbang & dibayar tidak dapat ditarik kembali.' }}</div>
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
                <td colspan="3" style="text-align: center; padding: 40px; color: #94a3b8; font-size: 10px;">
                    Tidak ada nota pembelian yang sesuai dengan kriteria filter pencarian.
                </td>
            </tr>
        @endforelse
    </table>

</body>
</html>

