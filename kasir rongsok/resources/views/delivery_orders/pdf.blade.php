<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat_Jalan_{{ $deliveryOrder->delivery_number }}</title>
    <style>
        @page {
            size: 210mm 148mm landscape; /* HVS Landscape / Half Folio */
            margin: 6mm 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }
        .title-badge {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            text-transform: uppercase;
            display: inline-block;
            border-radius: 3px;
        }
        .doc-meta {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9.5px;
            color: #0f172a;
            margin-top: 3px;
        }
        .info-card {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 5px 8px;
        }
        .info-title {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 2px;
        }
        .info-row {
            margin-top: 2px;
        }
        .label {
            color: #64748b;
            width: 70px;
            display: inline-block;
        }
        .val-bold {
            font-weight: bold;
            color: #0f172a;
        }
        table.cargo-table {
            margin-top: 6px;
            border: 1px solid #64748b;
        }
        table.cargo-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.cargo-table td {
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            font-size: 8.5px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .total-row {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 2px solid #0f172a;
            font-size: 9px;
        }
        .instructions {
            margin-top: 4px;
            font-size: 8px;
            color: #475569;
            font-style: italic;
        }
        .signatures-table {
            margin-top: 8px;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
        }
        .signatures-table td {
            text-align: center;
            font-size: 8px;
            vertical-align: top;
            width: 25%;
        }
        .sig-space {
            height: 32px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .sig-role {
            font-size: 7.5px;
            color: #64748b;
        }
        .footer-note {
            margin-top: 4px;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            border-top: 1px dashed #e2e8f0;
            padding-top: 2px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                <div class="company-sub">
                    {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}<br>
                    Telp / WhatsApp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                </div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div class="title-badge">SURAT JALAN PENGIRIMAN</div>
                <div class="doc-meta">
                    No. SJ : <strong>{{ $deliveryOrder->delivery_number }}</strong><br>
                    Tanggal: <strong>{{ $deliveryOrder->delivery_date->format('d/m/Y') }}</strong>
                    @if($deliveryOrder->salesTransaction)
                        <br><span style="font-size: 8px; color: #64748b;">Ref Faktur: {{ $deliveryOrder->salesTransaction->invoice_number }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- 2-Column Delivery Details: Penerima & Armada/Sopir -->
    <table style="margin-bottom: 4px;">
        <tr>
            <!-- Kolom 1: Penerima / Pabrik Tujuan -->
            <td style="width: 49%; vertical-align: top;">
                <div class="info-card">
                    <div class="info-title">🏢 KEPADA YTH. (PABRIK / PENERIMA TUJUAN):</div>
                    <div class="info-row">
                        <span class="label">Nama Mitra:</span>
                        <span class="val-bold">{{ strtoupper($deliveryOrder->recipient_name) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Alamat Kirim:</span>
                        <span>{{ $deliveryOrder->recipient_address ?: 'Alamat pabrik / depo pembongkaran muatan' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">No. Kontak:</span>
                        <span style="font-family: monospace;">{{ $deliveryOrder->recipient_phone ?: '-' }}</span>
                    </div>
                </div>
            </td>

            <td style="width: 2%;"></td>

            <!-- Kolom 2: Armada Kendaraan & Pengemudi (Supir) -->
            <td style="width: 49%; vertical-align: top;">
                <div class="info-card">
                    <div class="info-title">🚛 DATA ARMADA KENDARAAN & PENGEMUDI (SOPIR):</div>
                    <div class="info-row">
                        <span class="label">No. Polisi:</span>
                        <span class="val-bold" style="font-family: monospace; background: #e2e8f0; padding: 1px 4px; border-radius: 2px;">
                            {{ $deliveryOrder->vehicle_number }}
                        </span>
                        <span style="color: #64748b; font-size: 8px; margin-left: 4px;">({{ $deliveryOrder->vehicle_type ?: 'Truk Angkutan' }})</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Nama Sopir:</span>
                        <span class="val-bold">{{ strtoupper($deliveryOrder->driver_name) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">No. HP Sopir:</span>
                        <span style="font-family: monospace;">{{ $deliveryOrder->driver_phone ?: '-' }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Cargo Table (Rincian Muatan Barang Rongsok) -->
    <table class="cargo-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 22px;">No</th>
                <th style="width: 65px;">Kode</th>
                <th>Jenis Barang Rongsok & Spesifikasi Muatan</th>
                <th class="text-right" style="width: 80px;">Kemasan / Koli</th>
                <th class="text-right" style="width: 95px;">Berat Bersih (kg)</th>
                <th style="width: 120px;">Keterangan / No. Segel</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveryOrder->items as $idx => $item)
                <tr style="{{ $idx % 2 === 1 ? 'background-color: #f8fafc;' : '' }}">
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace;">{{ $item->scrapItem?->code ?? '-' }}</td>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->item_type_name)
                            <span style="color: #64748b; font-size: 7.5px;">({{ $item->item_type_name }})</span>
                        @endif
                    </td>
                    <td class="text-right" style="font-family: monospace;">
                        {{ $item->package_qty > 0 ? $item->package_qty . ' Koli' : ($item->quantity > 0 ? $item->quantity . ' ' . $item->unit_type : '-') }}
                    </td>
                    <td class="text-right" style="font-weight: bold; font-family: monospace;">
                        {{ number_format($item->weight, 2, ',', '.') }} kg
                    </td>
                    <td style="font-size: 8px; color: #475569;">
                        {{ $item->notes ?: '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right" style="padding-right: 8px;">TOTAL MUATAN RESMI:</td>
                <td class="text-right" style="font-family: monospace;">{{ $deliveryOrder->total_packages }} Koli</td>
                <td class="text-right" style="font-family: monospace; font-size: 10px; color: #047857;">
                    {{ number_format($deliveryOrder->total_weight, 2, ',', '.') }} kg
                </td>
                <td style="font-size: 7.5px; color: #64748b;">Timbangan Resmi Depo</td>
            </tr>
        </tfoot>
    </table>

    @if($deliveryOrder->notes)
        <div class="instructions">
            <strong>Instruksi Pengiriman:</strong> {{ $deliveryOrder->notes }}
        </div>
    @endif

    <!-- 4 Official Signatures (Penerima Pabrik, Supir, Penimbang, Pengirim) -->
    <table class="signatures-table">
        <tr>
            <td>
                Penerima Pabrik / Gudang,
                <div class="sig-space"></div>
                <div class="sig-name">( ................................... )</div>
                <div class="sig-role">Tanda Tangan & Cap Stempel</div>
            </td>
            <td>
                Pengemudi / Sopir Armada,
                <div class="sig-space"></div>
                <div class="sig-name">( {{ strtoupper($deliveryOrder->driver_name) }} )</div>
                <div class="sig-role">Armada Pengantar Muatan</div>
            </td>
            <td>
                Petugas Timbangan Depo,
                <div class="sig-space"></div>
                <div class="sig-name">( ................................... )</div>
                <div class="sig-role">Checker / Timbang Pengirim</div>
            </td>
            <td>
                Hormat Kami (Depo Pengirim),
                <div class="sig-space"></div>
                <div class="sig-name">( {{ strtoupper($deliveryOrder->user?->name ?? 'Admin Depo') }} )</div>
                <div class="sig-role">Pengirim & Penanggung Jawab</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        *Surat Jalan ini adalah dokumen resmi pengangkutan dan penyerahan barang rongsok. Harap diteliti dan ditandatangani saat serah terima di lokasi pabrik.
    </div>

</body>
</html>

