<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur_{{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: a5 portrait;
            margin: 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title .main {
            font-size: 11px;
            font-weight: bold;
            color: #2563eb;
            text-transform: uppercase;
        }
        .doc-title .num {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 10px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px 4px;
            font-size: 9px;
            vertical-align: top;
        }
        .meta-label {
            color: #64748b;
            width: 80px;
        }
        .meta-val {
            font-weight: bold;
            color: #0f172a;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.items-table th, table.items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-size: 9px;
        }
        table.items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .master-row {
            background-color: #f1f5f9;
        }
        .variant-row td {
            background-color: #f8fafc;
            font-size: 8px;
            color: #334155;
            padding: 3px 6px;
            border-top: 1px dashed #cbd5e1;
        }
        .variant-badge {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 1px 4px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 7.5px;
        }
        .total-box {
            margin-top: 10px;
            float: right;
            width: 210px;
        }
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 3px 6px;
            font-size: 9.5px;
        }
        .grand-total {
            background-color: #eff6ff;
            border-top: 2px solid #2563eb;
            border-bottom: 2px solid #2563eb;
            font-weight: bold;
            font-size: 11px;
            color: #1e3a8a;
        }
        .signatures {
            margin-top: 35px;
            width: 100%;
            clear: both;
        }
        .sig-col {
            float: left;
            width: 48%;
            text-align: center;
            font-size: 8.5px;
        }
        .sig-space {
            height: 45px;
        }
        .footer-note {
            margin-top: 20px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            font-size: 8px;
            color: #64748b;
            text-align: center;
            clear: both;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table style="width: 100%; border-collapse: collapse;" class="header">
        <tr>
            <td style="vertical-align: middle;">
                <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                <div class="company-sub">
                    {{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam & Rongsok' }}<br>
                    Telp/WhatsApp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                </div>
            </td>
            <td style="vertical-align: middle; text-align: right;">
                <div class="doc-title">
                    <div class="main">{{ $sale->isPickup() ? 'FAKTUR PENJUALAN (DIAMBIL SENDIRI)' : 'FAKTUR PENJUALAN PARTAI' }}</div>
                    <div class="num">{{ $sale->invoice_number }}</div>
                    <div style="font-size: 8px; color: {{ $sale->isPickup() ? '#047857' : '#1d4ed8' }}; font-weight: bold; margin-top: 2px;">
                        {{ $sale->isPickup() ? 'DIAMBIL SENDIRI DI TEMPAT' : 'PENGIRIMAN ARMADA PERUSAHAAN' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Metadata Info Box -->
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Tanggal Transaksi</td>
                <td style="width: 5px;">:</td>
                <td class="meta-val">{{ \Carbon\Carbon::parse($sale->transaction_date)->format('d/m/Y H:i') }} WIB</td>
                <td class="meta-label">Pembeli / Pabrik</td>
                <td style="width: 5px;">:</td>
                <td class="meta-val">{{ $sale->buyer_name }}</td>
            </tr>
            <tr>
                <td class="meta-label">Operator Kasir</td>
                <td>:</td>
                <td class="meta-val">{{ $sale->user?->name ?? 'Kasir Depo' }}</td>
                <td class="meta-label">Kontak Pembeli</td>
                <td>:</td>
                <td class="meta-val">{{ $sale->buyer_phone ?: '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Metode Bayar</td>
                <td>:</td>
                <td class="meta-val">
                    {{ $sale->paymentMethod?->name ?? 'Transfer Bank' }}
                    @php
                        $isBankSale = str_contains(strtolower($sale->paymentMethod?->name ?? ''), 'bank') || 
                                      str_contains(strtolower($sale->paymentMethod?->name ?? ''), 'transfer') || 
                                      !empty($sale->bank_info);
                        $saleBankText = $sale->bank_info ?: ($sale->user?->bankDisplay() ?? null);
                    @endphp
                    @if($isBankSale && $saleBankText)
                        <div style="font-size: 7.5px; color: #1e40af; font-weight: bold; margin-top: 1px;">
                            {{ $saleBankText }}
                        </div>
                    @endif
                </td>
                <td class="meta-label">{{ $sale->isPickup() ? 'Metode Serah' : 'Alamat Kirim' }}</td>
                <td>:</td>
                <td class="meta-val">{{ $sale->isPickup() ? 'Diambil Sendiri di Depo/Gudang' : ($sale->buyer_address ?: '-') }}</td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 20px;">No</th>
                <th style="width: 60px;">Kode</th>
                <th>Nama Material Scrap & Daur Ulang</th>
                <th class="text-right" style="width: 65px;">Berat (kg)</th>
                <th class="text-right" style="width: 70px;">Harga Jual/kg</th>
                <th class="text-right" style="width: 85px;">Subtotal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->grouped_master_items as $idx => $master)
                <!-- Master Item (Total Akumulasi Master) -->
                <tr class="{{ $master->has_variants ? 'master-row' : ($idx % 2 === 1 ? 'bg-alt' : '') }}">
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><code>{{ $master->item_code }}</code></td>
                    <td>
                        <strong>{{ $master->item_name }}</strong>
                        @if($master->has_variants)
                            <span style="font-size: 7.5px; color: #2563eb; font-weight: normal; margin-left: 3px;">[Total Master: {{ $master->items->count() }} Varian]</span>
                        @endif
                    </td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($master->total_weight, 2, ',', '.') }} kg</td>
                    <td class="text-right">
                        @if($master->has_variants)
                            <span style="font-size: 7.5px; color: #64748b;">Rerata Rp {{ number_format($master->avg_sell_price, 0, ',', '.') }}</span>
                        @else
                            Rp {{ number_format($master->items->first()->sell_price_per_kg, 0, ',', '.') }}
                        @endif
                    </td>
                    <td class="text-right" style="font-weight: bold;">Rp {{ number_format($master->total_subtotal, 0, ',', '.') }}</td>
                </tr>

                <!-- Rincian Jenis Varian (Bila Ada Varian) -->
                @if($master->has_variants)
                    @foreach($master->items as $vIt)
                        <tr class="variant-row">
                            <td class="text-center" style="color: #64748b;">↳</td>
                            <td style="color: #94a3b8; font-size: 7.5px;">{{ $vIt->item_code_snapshot ?: '-' }}</td>
                            <td style="padding-left: 14px;">
                                <span class="variant-badge">Varian: {{ $vIt->item_type_name ?: 'Standar' }}</span>
                                @if($vIt->notes)
                                    <span style="color: #64748b; font-size: 7px; font-style: italic;">({{ $vIt->notes }})</span>
                                @endif
                            </td>
                            <td class="text-right" style="color: #334155;">{{ number_format($vIt->weight, 2, ',', '.') }} kg</td>
                            <td class="text-right" style="color: #334155;">Rp {{ number_format($vIt->sell_price_per_kg, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-weight: bold; color: #1e293b;">Rp {{ number_format($vIt->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    <!-- Totals Table -->
    <div class="total-box">
        <table class="total-table">
            <tr>
                <td style="color: #64748b;">Total Muatan:</td>
                <td class="text-right" style="font-weight: bold;">{{ number_format($sale->total_weight, 2, ',', '.') }} kg</td>
            </tr>
            @if($sale->discount_amount > 0)
                <tr>
                    <td style="color: #dc2626;">Potongan:</td>
                    <td class="text-right" style="color: #dc2626; font-weight: bold;">- Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td>TOTAL TAGIHAN:</td>
                <td class="text-right">Rp {{ number_format($sale->final_amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="sig-col">
            Diterima Oleh (Pembeli/Pabrik),<br>
            <div class="sig-space"></div>
            <strong>( {{ $sale->buyer_name }} )</strong>
        </div>
        <div class="sig-col" style="float: right;">
            Hormat Kami ({{ $sale->isPickup() ? 'Kasir / Pengelola' : 'Depo Pengirim' }}),<br>
            <div class="sig-space" style="text-align: center;">
                @if(\App\Models\BusinessSetting::hasOwnerSignature())
                    <img src="{{ \App\Models\BusinessSetting::ownerSignatureBase64() }}" alt="TTD Owner" style="height: 42px; max-height: 44px; width: auto; max-width: 140px; margin: 0 auto; display: block;">
                @endif
            </div>
            <strong>( {{ $sale->user?->name ?? 'Admin / Kasir' }} )</strong>
        </div>
    </div>

    <div class="footer-note">
        {{ $sale->isPickup() ? '*Faktur ini merupakan bukti resmi transaksi penjualan barang yang diambil langsung oleh pembeli di tempat/depo.' : '*Faktur ini merupakan bukti resmi pengiriman muatan rongsok partai besar. Dokumen sah terbitan sistem otomatis.' }}
    </div>

</body>
</html>

