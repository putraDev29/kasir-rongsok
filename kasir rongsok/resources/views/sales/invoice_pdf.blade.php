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
                    <div class="main">FAKTUR PENJUALAN PARTAI</div>
                    <div class="num">{{ $sale->invoice_number }}</div>
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
                <td class="meta-val">{{ $sale->paymentMethod?->name ?? 'Transfer Bank' }}</td>
                <td class="meta-label">Alamat Kirim</td>
                <td>:</td>
                <td class="meta-val">{{ $sale->buyer_address ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 20px;">No</th>
                <th style="width: 60px;">Kode</th>
                <th>Nama Barang Rongsok</th>
                <th class="text-right" style="width: 65px;">Berat (kg)</th>
                <th class="text-right" style="width: 70px;">Harga Jual/kg</th>
                <th class="text-right" style="width: 85px;">Subtotal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $idx => $it)
                <tr style="{{ $idx % 2 === 1 ? 'background-color: #f8fafc;' : '' }}">
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><code>{{ $it->item_code_snapshot ?: ($it->scrapItem?->code ?? '-') }}</code></td>
                    <td><strong>{{ $it->item_name_snapshot }}</strong></td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($it->weight, 2, ',', '.') }} kg</td>
                    <td class="text-right">Rp {{ number_format($it->sell_price_per_kg, 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold;">Rp {{ number_format($it->subtotal, 0, ',', '.') }}</td>
                </tr>
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
            <strong>( .................................................. )</strong>
        </div>
        <div class="sig-col" style="float: right;">
            Hormat Kami (Depo Pengirim),<br>
            <div class="sig-space"></div>
            <strong>( {{ $sale->user?->name ?? 'Admin / Kasir' }} )</strong>
        </div>
    </div>

    <div class="footer-note">
        *Faktur ini merupakan bukti resmi pengiriman muatan rongsok partai besar. Dokumen sah terbitan sistem otomatis.
    </div>

</body>
</html>

