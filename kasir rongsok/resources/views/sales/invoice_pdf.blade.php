<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice_{{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: a5 portrait;
            margin: 8mm 10mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.25;
            background: #ffffff;
        }
        .mono {
            font-family: 'DejaVu Sans Mono', 'Courier New', Courier, monospace;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .company-sub {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.25;
        }
        .doc-title {
            text-align: right;
            vertical-align: top;
            width: 140px;
        }
        .doc-main {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .doc-num {
            font-family: 'DejaVu Sans Mono', 'Courier New', Courier, monospace;
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
            margin-top: 2px;
        }
        .doc-date {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }
        .status-badge {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
        }
        .badge-pickup {
            color: #047857;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
        }
        .badge-delivery {
            color: #1d4ed8;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .info-label {
            font-size: 7.5px;
            font-weight: bold;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .buyer-name {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }
        .buyer-sub {
            font-size: 8.5px;
            color: #475569;
            margin-top: 1px;
        }
        .buyer-phone {
            font-size: 8.5px;
            color: #475569;
            font-family: 'DejaVu Sans Mono', 'Courier New', Courier, monospace;
            margin-top: 1px;
        }
        .payment-method {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
        }
        .payment-bank {
            font-size: 8px;
            color: #475569;
            font-family: 'DejaVu Sans Mono', 'Courier New', Courier, monospace;
            margin-top: 1px;
        }
        .notes-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 6px;
            font-size: 8px;
            color: #334155;
            line-height: 1.25;
            margin-top: 2px;
        }
        .delivery-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            padding: 4px 6px;
            font-size: 7.5px;
            color: #1e3a8a;
            line-height: 1.25;
            margin-top: 4px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 6px;
        }
        .items-table th {
            padding: 5px 4px;
            font-size: 7.5px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1.5px solid #cbd5e1;
        }
        .items-table td {
            padding: 5px 4px;
            font-size: 8.5px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .variant-badge {
            font-size: 7px;
            color: #1d4ed8;
            background-color: #eff6ff;
            border: 1px solid #dbeafe;
            padding: 1px 4px;
            border-radius: 2px;
            margin-left: 3px;
            display: inline-block;
        }
        .variant-subrow td {
            padding: 3px 4px;
            font-size: 8px;
            background-color: #fbfcfe;
            border-bottom: 1px solid #f8fafc;
        }

        .summary-wrapper {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            border-top: 1px solid #cbd5e1;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'DejaVu Sans Mono', 'Courier New', Courier, monospace;
            font-size: 8.5px;
        }
        .summary-table td {
            padding: 2px 0;
        }
        .total-row td {
            border-top: 2px solid #0f172a;
            padding-top: 4px;
            padding-bottom: 2px;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
            clear: both;
        }
        .sig-col {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 8px;
            color: #475569;
        }
        .sig-space {
            height: 40px;
        }
        .sig-name {
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
        }

        .footer-note {
            margin-top: 15px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 5px;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
            clear: both;
        }
    </style>
</head>
<body>

    <!-- Header Section (Matching Image 2 / sales.print) -->
    <table class="header-table">
        <tr>
            @if(\App\Models\BusinessSetting::hasLogo())
                <td style="width: 52px; vertical-align: top; padding-right: 8px;">
                    <img src="{{ \App\Models\BusinessSetting::logoBase64() }}" alt="Logo" style="max-height: 45px; max-width: 52px; object-fit: contain;">
                </td>
            @endif
            <td style="vertical-align: top;">
                <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                <div class="company-sub">
                    {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}<br>
                    Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                </div>
            </td>
            <td class="doc-title">
                <div class="doc-main">INVOICE</div>
                <div class="doc-num">{{ $sale->invoice_number }}</div>
                <div class="doc-date">{{ $sale->transaction_date->format('d/m/Y H:i') }}</div>
                <div>
                    @if($sale->isPickup())
                        <span class="status-badge badge-pickup">Diambil Langsung Pembeli</span>
                    @else
                        <span class="status-badge badge-delivery">Pengiriman Armada Gudang</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Buyer & Shipping/Payment Info (Matching Image 2) -->
    <table class="info-table">
        <tr>
            <!-- Left Column: Pembeli -->
            <td style="width: 48%; vertical-align: top; padding-right: 10px;">
                <div class="info-label">
                    {{ $sale->isPickup() ? 'PEMBELI (DIAMBIL LANGSUNG):' : 'TUJUAN PENGIRIMAN (PEMBELI):' }}
                </div>
                <div class="buyer-name">{{ $sale->buyer_name }}</div>
                <div class="buyer-sub">{{ $sale->buyer_address ?? '-' }}</div>
                <div class="buyer-phone">Telp: {{ $sale->buyer_phone ?? '-' }}</div>
            </td>

            <!-- Right Column: Metode Pembayaran & Pengambilan -->
            <td style="width: 52%; vertical-align: top; padding-left: 10px;">
                <div class="info-label">METODE PEMBAYARAN:</div>
                <div class="payment-method">{{ $sale->paymentMethod?->name ?? 'Tunai' }}</div>
                @if($sale->isBankPayment())
                    <div class="payment-bank">
                        @if($sale->getBankAccountNumber())
                            No. Rek: <strong style="color: #0f172a;">{{ $sale->getBankAccountNumber() }}</strong>
                        @endif
                        @if($sale->getBankAccountName())
                            (a/n {{ $sale->getBankAccountName() }})
                        @endif
                        @if(!$sale->getBankAccountNumber() && !$sale->getBankAccountName() && !empty($sale->bank_info))
                            {{ $sale->bank_info }}
                        @endif
                    </div>
                @endif

                <div class="info-label" style="margin-top: 5px;">
                    {{ $sale->isPickup() ? 'METODE PENGAMBILAN:' : 'CATATAN TRANSPORTASI:' }}
                </div>
                <div class="notes-box">
                    @if($sale->isPickup())
                        {{ $sale->notes ?? 'Diambil langsung oleh pembeli di gudang/depo (Tanpa kendaraan perusahaan)' }}
                    @else
                        {{ $sale->notes ?? 'Pengiriman armada darat' }}
                    @endif
                </div>

                @if($sale->isDelivery() && $sale->deliveryOrder)
                    <div class="delivery-box">
                        <div><strong>Supir / Armada:</strong> {{ $sale->deliveryOrder->driver_name }} ({{ $sale->deliveryOrder->vehicle_number }}{{ $sale->deliveryOrder->driver_phone ? ' - Telp: '.$sale->deliveryOrder->driver_phone : '' }})</div>
                        <div class="mono" style="margin-top: 1px; color: #1e40af;">
                            Ongkos Armada: <strong>Rp {{ \App\Services\FormatHelper::number($sale->deliveryOrder->shipping_cost ?? 0) }}</strong> | 
                            Bongkar: <strong>Rp {{ \App\Services\FormatHelper::number($sale->deliveryOrder->loading_cost ?? 0) }}</strong>
                        </div>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Items Table (Matching Image 2 columns and style) -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 20px; text-align: left;">#</th>
                <th style="text-align: left;">NAMA MATERIAL SCRAP &amp; DAUR ULANG</th>
                <th style="width: 65px; text-align: right;">BERAT (KG)</th>
                <th style="width: 85px; text-align: right;">HARGA SATUAN/KG</th>
                <th style="width: 85px; text-align: right;">TOTAL NILAI</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->grouped_master_items as $idx => $master)
                <tr style="{{ $master->has_variants ? 'background-color: #f8fafc;' : '' }}">
                    <td class="mono" style="color: #64748b;">{{ $idx + 1 }}</td>
                    <td>
                        <span style="font-weight: bold; color: #0f172a;">{{ $master->item_name }}</span>
                        <span class="mono" style="color: #94a3b8; font-size: 7.5px;">({{ $master->item_code }})</span>
                        @if($master->has_variants)
                            <span class="variant-badge">
                                Total Master ({{ $master->items->count() }} Varian)
                            </span>
                        @endif
                    </td>
                    <td class="mono" style="text-align: right; font-weight: bold; color: #0f172a;">
                        {{ \App\Services\FormatHelper::weight($master->total_weight) }} kg
                    </td>
                    <td class="mono" style="text-align: right; color: #334155;">
                        @if($master->has_variants)
                            <span style="color: #64748b; font-size: 7.5px; font-style: italic;">Rerata Rp {{ \App\Services\FormatHelper::number($master->avg_sell_price) }}</span>
                        @else
                            Rp {{ \App\Services\FormatHelper::number($master->items->first()->sell_price_per_kg) }}
                        @endif
                    </td>
                    <td class="mono" style="text-align: right; font-weight: bold; color: #0f172a;">
                        Rp {{ \App\Services\FormatHelper::number($master->total_subtotal) }}
                    </td>
                </tr>

                @if($master->has_variants)
                    @foreach($master->items as $vItem)
                        <tr class="variant-subrow">
                            <td class="mono" style="text-align: center; color: #93c5fd; font-size: 7.5px;">↳</td>
                            <td style="padding-left: 14px;">
                                <span style="font-weight: 600; color: #1e40af;">• Varian: {{ $vItem->item_type_name ?: 'Standar' }}</span>
                                @if($vItem->notes)
                                    <span style="color: #94a3b8; font-style: italic; font-size: 7px;">({{ $vItem->notes }})</span>
                                @endif
                            </td>
                            <td class="mono" style="text-align: right; color: #475569;">
                                {{ \App\Services\FormatHelper::weight($vItem->weight) }} kg
                            </td>
                            <td class="mono" style="text-align: right; color: #475569;">
                                Rp {{ \App\Services\FormatHelper::number($vItem->sell_price_per_kg) }}
                            </td>
                            <td class="mono" style="text-align: right; font-weight: bold; color: #334155;">
                                Rp {{ \App\Services\FormatHelper::number($vItem->subtotal) }}
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    <!-- Summary Section (Right-aligned, matching Image 2) -->
    <table class="summary-wrapper">
        <tr>
            <td></td>
            <td style="width: 210px; padding-top: 4px;">
                <table class="summary-table">
                    <tr>
                        <td style="color: #475569;">Total Berat:</td>
                        <td class="mono" style="text-align: right; font-weight: bold; color: #0f172a;">
                            {{ \App\Services\FormatHelper::weight($sale->total_weight) }} kg
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #475569;">Subtotal:</td>
                        <td class="mono" style="text-align: right; color: #0f172a;">
                            Rp {{ \App\Services\FormatHelper::number($sale->subtotal_amount) }}
                        </td>
                    </tr>
                    @if($sale->discount_amount > 0)
                        <tr>
                            <td style="color: #dc2626;">Diskon:</td>
                            <td class="mono" style="text-align: right; color: #dc2626; font-weight: bold;">
                                - Rp {{ \App\Services\FormatHelper::number($sale->discount_amount) }}
                            </td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; font-weight: bold; color: #0f172a;">
                            TOTAL TAGIHAN:
                        </td>
                        <td class="mono" style="text-align: right; font-size: 11px; font-weight: bold; color: #0f172a;">
                            Rp {{ \App\Services\FormatHelper::number($sale->final_amount) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
