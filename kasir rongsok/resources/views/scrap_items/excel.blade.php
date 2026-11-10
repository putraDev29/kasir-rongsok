<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Harga Material Scrap & Daur Ulang</title>
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background-color: #e2e8f0; font-weight: bold; text-align: center; }
        .num { text-align: right; mso-number-format: "\#\,\#\#0"; }
        .weight { text-align: right; mso-number-format: "0\.00"; }
        .str { mso-number-format: "\@"; }
    </style>
</head>
<body>
    <h3>DAFTAR MASTER BARANG & HARGA RONGSOK</h3>
    <p>Update: {{ date('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Kategori</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Harga Beli (Rp)</th>
                <th>Harga Jual (Rp)</th>
                <th>Margin (Rp)</th>
                <th>Stok Gudang</th>
                <th>Status</th>
                <th>Deskripsi / Catatan</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNo = 1; @endphp
            @foreach($categories as $cat)
                @foreach($cat->scrapItems as $item)
                    <tr>
                        <td align="center">{{ $rowNo++ }}</td>
                        <td class="str">{{ $item->code }}</td>
                        <td>{{ $cat->name }}</td>
                        <td><strong>{{ $item->name }}</strong></td>
                        <td align="center">{{ strtoupper($item->unit) }}</td>
                        <td class="num">{{ $item->buy_price_per_kg }}</td>
                        <td class="num">{{ $item->sell_price_per_kg }}</td>
                        <td class="num">{{ max(0, $item->sell_price_per_kg - $item->buy_price_per_kg) }}</td>
                        <td class="weight">{{ $item->current_stock }}</td>
                        <td align="center">{{ $item->is_active ? 'AKTIF' : 'NONAKTIF' }}</td>
                        <td>{{ $item->description ?: '-' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>

