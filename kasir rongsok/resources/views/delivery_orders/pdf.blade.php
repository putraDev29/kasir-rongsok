<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat_Jalan_{{ $deliveryOrder->delivery_number }}</title>
    <style>
        @page {
            size: 210mm 148mm landscape; /* HVS Landscape / Half Folio / A5 Landscape */
            margin: 5mm 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }
        .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.15;
        }
        .title-badge {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 8px;
            text-transform: uppercase;
            display: inline-block;
            border-radius: 3px;
        }
        .copy-badge-1 {
            background-color: #dbeafe;
            color: #1e3a8a;
            border: 1px solid #93c5fd;
            padding: 1px 6px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 2px;
            text-transform: uppercase;
            display: inline-block;
            margin-top: 2px;
        }
        .copy-badge-2 {
            background-color: #fef3c7;
            color: #78350f;
            border: 1px solid #fcd34d;
            padding: 1px 6px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 2px;
            text-transform: uppercase;
            display: inline-block;
            margin-top: 2px;
        }
        .doc-meta {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9px;
            color: #0f172a;
            margin-top: 2px;
        }
        .info-card {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 3px;
            padding: 4px 6px;
        }
        .info-title {
            font-size: 7.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 2px;
        }
        .info-row {
            margin-top: 1.5px;
            font-size: 8px;
        }
        .label {
            color: #64748b;
            width: 65px;
            display: inline-block;
        }
        .val-bold {
            font-weight: bold;
            color: #0f172a;
        }
        table.cargo-table {
            margin-top: 5px;
            border: 1px solid #64748b;
        }
        table.cargo-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 5px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.cargo-table td {
            padding: 3px 5px;
            border: 1px solid #cbd5e1;
            font-size: 8px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .master-row {
            background-color: #f8fafc;
        }
        .variant-subrow td {
            background-color: #ffffff;
            font-size: 7.5px;
            color: #334155;
            padding: 2px 5px;
            border-top: 1px dashed #cbd5e1;
        }
        .variant-pill {
            background-color: #eff6ff;
            color: #1e40af;
            border: 0.5px solid #bfdbfe;
            padding: 0.5px 3px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 7px;
        }
        .total-row {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 2px solid #0f172a;
            font-size: 8.5px;
        }
        .instructions {
            margin-top: 3px;
            font-size: 7.5px;
            color: #475569;
            font-style: italic;
        }
        .rangkap-box {
            margin-top: 4px;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 7.5px;
        }
        .rangkap-box-1 {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
        }
        .rangkap-box-2 {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            color: #78350f;
        }
        .signatures-table {
            margin-top: 6px;
            border-top: 1px solid #cbd5e1;
            padding-top: 3px;
        }
        .signatures-table td {
            text-align: center;
            font-size: 7.5px;
            vertical-align: top;
            width: 25%;
        }
        .sig-space {
            height: 26px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            font-size: 7.5px;
        }
        .sig-role {
            font-size: 7px;
            color: #64748b;
        }
        .footer-note {
            margin-top: 3px;
            text-align: center;
            font-size: 6.5px;
            color: #94a3b8;
            border-top: 1px dashed #e2e8f0;
            padding-top: 2px;
        }
        .page-sheet {
            width: 100%;
        }
    </style>
</head>
<body>

@foreach($copies as $copyIndex => $copyNum)
    <div class="page-sheet">

        <!-- Header Section -->
        <table class="header-table">
            <tr>
                <td style="width: 52%; vertical-align: top;">
                    <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                    <div class="company-sub">
                        {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}<br>
                        Telp / WhatsApp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                    </div>
                </td>
                <td style="width: 48%; vertical-align: top; text-align: right;">
                    <div class="title-badge">SURAT JALAN PENGIRIMAN</div>
                    <div>
                        @if($copyNum === 1)
                            <span class="copy-badge-1">LEMBAR 1 : GUDANG (DIPEGANG OLEH GUDANG)</span>
                        @else
                            <span class="copy-badge-2">LEMBAR 2 : SUPIR (DIPEGANG OLEH SUPIR)</span>
                        @endif
                    </div>
                    <div class="doc-meta">
                        No. SJ : <strong>{{ $deliveryOrder->delivery_number }}</strong> | 
                        Tanggal: <strong>{{ $deliveryOrder->delivery_date->format('d/m/Y') }}</strong>
                        @if($deliveryOrder->salesTransaction)
                            <br><span style="font-size: 7.5px; color: #64748b;">Ref Faktur: {{ $deliveryOrder->salesTransaction->invoice_number }}</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- 2-Column Delivery Details: Penerima & Armada/Sopir -->
        <table style="margin-bottom: 3px;">
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

                <!-- Kolom 2: Armada & Pengemudi -->
                <td style="width: 49%; vertical-align: top;">
                    <div class="info-card">
                        <div class="info-title">🚛 DATA ARMADA &amp; PENGEMUDI (SOPIR):</div>
                        <div class="info-row">
                            <span class="label">Nama Sopir:</span>
                            <span class="val-bold">{{ strtoupper($deliveryOrder->driver_name ?: '-') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Kontak HP:</span>
                            <span style="font-family: monospace;">{{ $deliveryOrder->driver_phone ?: '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">No. Polisi:</span>
                            <span class="val-bold" style="font-family: monospace; background: #e2e8f0; padding: 0.5px 3px; border-radius: 2px;">
                                {{ $deliveryOrder->vehicle_number }}
                            </span>
                            <span style="color: #64748b; font-size: 7.5px; margin-left: 3px;">({{ $deliveryOrder->vehicle_type ?: 'Truk Angkutan' }})</span>
                        </div>
                        @if($deliveryOrder->hasDriverBankDetails())
                            <div class="info-row">
                                <span class="label">Rek. Sopir:</span>
                                <span style="font-family: monospace; font-size: 7.5px; color: #1e3a8a;">{{ $deliveryOrder->driverBankDisplay() }}</span>
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- Cargo Table (Rincian Muatan Material Scrap & Daur Ulang) -->
        <table class="cargo-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 20px;">No</th>
                    <th style="width: 55px;">Kode</th>
                    <th>Jenis Material Scrap & Daur Ulang</th>
                    <th class="text-right" style="width: 75px;">Kemasan / Koli</th>
                    <th class="text-right" style="width: 90px;">Berat Bersih (kg)</th>
                    <th style="width: 110px;">Keterangan / No. Segel</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveryOrder->grouped_master_items as $idx => $master)
                    <!-- Baris Master Barang (Akumulasi Total) -->
                    <tr class="{{ $master->has_variants ? 'master-row' : ($idx % 2 === 1 ? 'bg-alt' : '') }}">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td style="font-family: monospace;">{{ $master->item_code }}</td>
                        <td>
                            <strong>{{ $master->item_name }}</strong>
                            @if($master->has_variants)
                                <span style="font-size: 7px; color: #2563eb; margin-left: 2px;">[Total Master: {{ $master->items->count() }} Varian]</span>
                            @endif
                        </td>
                        <td class="text-right" style="font-family: monospace; font-weight: bold;">
                            {{ $master->total_packages > 0 ? $master->total_packages . ' Koli' : '-' }}
                        </td>
                        <td class="text-right" style="font-weight: bold; font-family: monospace;">
                            {{ \App\Services\FormatHelper::weightOrDash($master->total_weight) }}
                        </td>
                        <td style="font-size: 7.5px; color: #475569;">
                            @if(!$master->has_variants && $master->items->first()->notes)
                                {{ $master->items->first()->notes }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>

                    <!-- Rincian Jenis Varian (Bila Ada Varian) -->
                    @if($master->has_variants)
                        @foreach($master->items as $vItem)
                            <tr class="variant-subrow">
                                <td class="text-center" style="color: #64748b;">↳</td>
                                <td style="color: #94a3b8; font-size: 7px;">{{ $vItem->scrapItem?->code ?? '-' }}</td>
                                <td style="padding-left: 12px;">
                                    <span class="variant-pill">Varian: {{ $vItem->item_type_name ?: 'Standar' }}</span>
                                    @if($vItem->effective_price > 0)
                                        <span style="color: #047857; font-size: 7px; margin-left: 3px;">
                                            (@ Rp {{ number_format($vItem->effective_price, 0, ',', '.') }}/kg)
                                        </span>
                                    @endif
                                    @if($vItem->notes)
                                        <span style="color: #64748b; font-size: 7px; font-style: italic; margin-left: 2px;">({{ $vItem->notes }})</span>
                                    @endif
                                </td>
                                <td class="text-right" style="font-family: monospace; color: #334155;">
                                    {{ $vItem->package_qty > 0 ? $vItem->package_qty . ' Koli' : '-' }}
                                </td>
                                <td class="text-right" style="font-family: monospace; color: #0f172a;">
                                    {{ \App\Services\FormatHelper::weightOrDash($vItem->weight) }}
                                </td>
                                <td style="font-size: 7px; color: #475569;">
                                    @if($vItem->effective_price > 0)
                                        Rp {{ number_format($vItem->effective_subtotal, 0, ',', '.') }}
                                    @else
                                        {{ $vItem->notes ?: '-' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-right" style="padding-right: 6px;">TOTAL MUATAN RESMI:</td>
                    <td class="text-right" style="font-family: monospace;">{{ $deliveryOrder->total_packages > 0 ? $deliveryOrder->total_packages . ' Koli' : '-' }}</td>
                    <td class="text-right" style="font-family: monospace; font-size: 9.5px; color: #047857;">
                        {{ \App\Services\FormatHelper::weightOrDash($deliveryOrder->total_weight) }}
                    </td>
                    <td style="font-size: 7px; color: #64748b;">Timbangan Resmi Depo</td>
                </tr>
            </tfoot>
        </table>

        @if($deliveryOrder->notes)
            <div class="instructions">
                <strong>Instruksi Pengiriman:</strong> {{ $deliveryOrder->notes }}
            </div>
        @endif

        <!-- Keterangan Rangkap Box -->
        <div class="rangkap-box {{ $copyNum === 1 ? 'rangkap-box-1' : 'rangkap-box-2' }}">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="text-align: left; border: none; padding: 0;">
                        <strong>* Keterangan Rangkap:</strong> Surat jalan ini dibuat <strong>2 (dua) rangkap</strong> — 
                        <span style="font-weight: bold; text-decoration: {{ $copyNum === 1 ? 'underline' : 'none' }};">Lembar 1 dipegang oleh Gudang</span> dan 
                        <span style="font-weight: bold; text-decoration: {{ $copyNum === 2 ? 'underline' : 'none' }};">Lembar 2 dipegang oleh Supir</span>.
                    </td>
                    <td style="text-align: right; border: none; padding: 0; font-weight: bold; font-size: 7.5px; text-transform: uppercase;">
                        @if($copyNum === 1)
                            [ LEMBAR 1 : ARSIP GUDANG ]
                        @else
                            [ LEMBAR 2 : DIBAWA SUPIR KE TUJUAN ]
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- 4 Official Signatures (Penerima Pabrik, Supir, Penimbang, Pengirim) -->
        <table class="signatures-table">
            <tr>
                <td>
                    Penerima Pabrik / Gudang,
                    <div class="sig-space"></div>
                    <div class="sig-name">( ................................... )</div>
                    <div class="sig-role">
                        @if($copyNum === 2)
                            <strong style="color: #78350f;">Tanda Tangan & Cap Asli</strong>
                        @else
                            Tanda Tangan & Cap Stempel
                        @endif
                    </div>
                </td>
                <td>
                    Pengemudi / Sopir Armada,
                    <div class="sig-space"></div>
                    <div class="sig-name">( {{ strtoupper($deliveryOrder->driver_name) }} )</div>
                    <div class="sig-role">
                        @if($copyNum === 2)
                            <strong style="color: #78350f;">Pengemudi (Bawa ke Tujuan)</strong>
                        @else
                            Armada Pengantar Muatan
                        @endif
                        @if($deliveryOrder->driver_phone)
                            <div style="font-size: 7px; color: #64748b; font-family: monospace;">Telp: {{ $deliveryOrder->driver_phone }}</div>
                        @endif
                    </div>
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
                    <div class="sig-role">
                        @if($copyNum === 1)
                            <strong style="color: #1e3a8a;">Staff Gudang (Arsip Depo)</strong>
                        @else
                            Pengirim & Penanggung Jawab
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            {{ $settings['delivery_order_notes'] ?? 'Surat jalan ini dibuat 2 (dua) rangkap: Lembar 1 dipegang oleh gudang dan Lembar 2 dipegang oleh supir. Dokumen sah penyerahan material scrap & daur ulang.' }}
        </div>

    </div>

    @if(!$loop->last)
        <div style="page-break-after: always; clear: both;"></div>
    @endif
@endforeach

</body>
</html>
