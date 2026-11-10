<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar_Harga_Beli_{{ strtoupper(\App\Models\BusinessSetting::commodityTerm()) }}_{{ date('Ymd') }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .company-sub {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 1px;
            line-height: 1.25;
        }
        .company-phone {
            font-size: 8pt;
            font-weight: bold;
            color: #047857;
            font-family: 'Courier New', Courier, monospace;
            margin-top: 2px;
        }
        .title-badge {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 3px;
            letter-spacing: 0.5px;
        }
        .update-date {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 3px;
        }

        /* Banner */
        .banner-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .banner-table td {
            padding: 5px 8px;
            font-size: 7.5pt;
            color: #064e3b;
            vertical-align: middle;
        }

        /* Category Blocks */
        .category-block {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .category-header {
            width: 100%;
            border-collapse: collapse;
            background-color: #0f172a;
            color: #ffffff;
            border-radius: 4px 4px 0 0;
        }
        .category-header td {
            padding: 4px 8px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Items Table */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-left: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        table.items-table thead {
            display: table-header-group;
        }
        table.items-table tr {
            page-break-inside: avoid !important;
        }
        table.items-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7pt;
            letter-spacing: 0.3px;
            padding: 4px 6px;
            border-bottom: 1px solid #cbd5e1;
        }
        table.items-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8pt;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        /* Terms & Footer */
        .terms-wrapper {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border-top: 1.5px solid #cbd5e1;
            padding-top: 6px;
            page-break-inside: avoid;
        }
        .terms-wrapper td {
            vertical-align: top;
            font-size: 7pt;
            color: #475569;
            line-height: 1.35;
        }
        .terms-title {
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 2px;
            font-size: 7.5pt;
        }

        /* Legal Disclaimer Box */
        .disclaimer-box {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 4px;
            padding: 6px 10px;
            text-align: center;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .disclaimer-badge {
            display: inline-block;
            background-color: #b91c1c;
            color: #ffffff;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 1.5px 5px;
            border-radius: 2px;
            letter-spacing: 0.4px;
        }
        .disclaimer-title {
            font-weight: bold;
            text-transform: uppercase;
            color: #7f1d1d;
            font-size: 7.5pt;
            margin: 2px 0 1px 0;
        }
        .disclaimer-sub {
            font-size: 6.5pt;
            color: #991b1b;
            margin: 0;
            line-height: 1.25;
        }

        .doc-watermark {
            text-align: center;
            margin-top: 4px;
            font-size: 6.5pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- Header Table: Logo & Company Information -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: middle;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        @if(\App\Models\BusinessSetting::hasLogo())
                            <td style="width: 58px; vertical-align: middle; padding-right: 8px;">
                                <img src="{{ \App\Models\BusinessSetting::logoBase64() }}" alt="Logo" style="height: 48px; max-width: 55px; object-fit: contain;">
                            </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                            <div class="company-sub">
                                {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}
                            </div>
                            <div class="company-phone">
                                WhatsApp / Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: middle;">
                <span class="title-badge">DAFTAR HARGA BELI {{ strtoupper(\App\Models\BusinessSetting::commodityTerm()) }}</span>
                <div class="update-date">
                    Update Resmi: <strong>{{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- Banner Keunggulan Timbangan & Kasir -->
    <table class="banner-table">
        <tr>
            <td style="width: 75%;">
                <strong>Timbangan Digital Presisi &amp; Transparan:</strong> Pembayaran tunai / transfer langsung lunas di kasir saat timbang.
            </td>
            <td style="width: 25%; text-align: right; font-weight: bold; color: #047857; text-transform: uppercase;">
                Timbang &rarr; Bayar Lunas
            </td>
        </tr>
    </table>

    <!-- Price Tables Grouped by Category -->
    @php $counter = 1; @endphp
    @foreach($categories as $category)
        @if($category->scrapItems->count() > 0)
            <div class="category-block">
                <!-- Category Bar -->
                <table class="category-header">
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td style="text-align: right; font-size: 7pt; font-weight: normal; color: #cbd5e1;">
                            {{ $category->scrapItems->count() }} Jenis
                        </td>
                    </tr>
                </table>

                <!-- Items Table -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 8%;">No</th>
                            <th style="width: 62%; text-align: left;">Nama {{ \App\Models\BusinessSetting::commodityTerm() }}</th>
                            <th class="text-right" style="width: 30%;">Harga Beli / Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category->scrapItems as $item)
                            <tr>
                                <td class="text-center font-mono" style="color: #64748b; font-size: 7.5pt;">{{ $counter++ }}</td>
                                <td style="font-weight: bold; color: #0f172a; font-size: 8.5pt;">
                                    {{ $item->name }}
                                </td>
                                <td class="text-right font-mono" style="font-weight: bold; color: #047857; font-size: 9.5pt;">
                                    {{ \App\Services\FormatHelper::rupiah($item->buy_price_per_kg) }}
                                    <span style="font-size: 7pt; font-weight: normal; color: #64748b; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">/ {{ $item->unit ?? 'kg' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    <!-- Ketentuan Penerimaan & Layanan Muatan -->
    <table class="terms-wrapper">
        <tr>
            <td style="width: 48%;">
                <div class="terms-title">Ketentuan Penerimaan Barang:</div>
                <div style="margin-left: 8px;">
                    &bull; Harga di atas berlaku untuk barang kondisi normal dan bersih.<br>
                    &bull; Barang basah atau bercampur kotoran akan dikenakan potongan tara secara adil &amp; transparan.<br>
                    &bull; Harga dapat berubah sewaktu-waktu mengikuti perkembangan pasar daur ulang &amp; peleburan.
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;">
                <div class="terms-title">Layanan Jemput Muatan Borongan:</div>
                <div>
                    Melayani penjemputan material scrap &amp; daur ulang partai borongan dari bengkel, gudang, kantor, atau pabrik. Hubungi kantor kami via WhatsApp: <strong style="color: #0f172a;">{{ $settings['company_phone'] ?? '0812-3456-7890' }}</strong>.
                </div>
            </td>
        </tr>
    </table>

    <!-- Legal Disclaimer Warning -->
    <div class="disclaimer-box">
        <span class="disclaimer-badge">PERHATIAN HUKUM</span>
        <div class="disclaimer-title">
            *** {{ $settings['receipt_disclaimer'] ?? 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI / ILEGAL' }} ***
        </div>
        <p class="disclaimer-sub">
            Seluruh barang yang dijual wajib milik sah pribadi penjual. Depo kami tegas menolak segala bentuk barang curian atau melanggar hukum.
        </p>
    </div>

    <div class="doc-watermark">
        Dokumen resmi diterbitkan oleh {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }} untuk mitra pemasok &amp; penjual rongsok.
    </div>

</body>
</html>
