<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Harga Beli {{ \App\Models\BusinessSetting::commodityTerm() }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 15px; }
        .company-name { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .title { font-size: 13px; font-weight: bold; margin-top: 5px; color: #047857; text-transform: uppercase; }
        .period { font-size: 10px; color: #64748b; margin-top: 3px; }
        .banner { background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 8px 12px; border-radius: 4px; font-size: 10px; color: #065f46; margin-bottom: 15px; }
        .category-title { font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 15px; margin-bottom: 5px; padding-bottom: 3px; border-bottom: 1px solid #cbd5e1; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; margin-bottom: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 8px; font-size: 10px; }
        th { background-color: #f1f5f9; font-weight: bold; text-transform: uppercase; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
        <div class="title">DAFTAR RESMI HARGA BELI {{ strtoupper(\App\Models\BusinessSetting::commodityTerm()) }}</div>
        <div class="period">Update Terakhir: {{ date('d/m/Y') }} | Timbangan Digital Presisi, Pembayaran Tunai & Transfer Langsung Cair</div>
    </div>

    <div class="banner">
        <strong>Pemberitahuan Pemasok & Pengepul:</strong> Harga yang tertera adalah patokan standar beli per kilogram (kg) bersih di depo. Hubungi kasir/kantor untuk penjemputan armada partai besar.
    </div>

    @foreach($categories as $cat)
        @if($cat->scrapItems->count() > 0)
            <div class="category-title">📂 Kategori: {{ $cat->name }}</div>
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 30px;">No</th>
                        <th style="width: 80px;">Kode</th>
                        <th>Nama {{ \App\Models\BusinessSetting::commodityTerm() }}</th>
                        <th style="width: 100px;">Satuan</th>
                        <th class="text-right" style="width: 130px;">Harga Beli Standar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cat->scrapItems as $idx => $item)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td><code>{{ $item->code }}</code></td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ strtoupper($item->unit ?? 'KG') }}</td>
                            <td class="text-right" style="font-weight: bold; color: #047857;">
                                {{ \App\Services\FormatHelper::rupiah($item->buy_price_per_kg) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="footer">
        <div style="float: left; width: 65%;">
            <strong>{{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong><br>
            {{ $settings['company_address'] ?? 'Sentra Rongsok Daur Ulang' }}<br>
            WhatsApp / Telp: {{ $settings['company_phone'] ?? '-' }}<br>
            <em>*Harga dapat berubah sewaktu-waktu mengikuti fluktuasi pasar pabrik peleburan.</em>
        </div>
        <div style="float: right; width: 30%; text-align: center;">
            Dicetak / Dikeluarkan Pada:<br>
            <strong>{{ date('d F Y') }}</strong><br><br><br>
            <strong>Manajemen Kasir Rongsok</strong>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>

