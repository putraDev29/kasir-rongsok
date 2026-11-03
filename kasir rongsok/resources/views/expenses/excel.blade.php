<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Biaya Operasional</title>
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background-color: #e2e8f0; font-weight: bold; text-align: center; }
        .num { text-align: right; mso-number-format: "\#\,\#\#0"; }
        .str { mso-number-format: "\@"; }
    </style>
</head>
<body>
    <h3>REKAP BIAYA OPERASIONAL</h3>
    <p>Periode: {{ $startDate ?: '-' }} s/d {{ $endDate ?: '-' }}</p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Bukti</th>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Penerima</th>
                <th>Metode Bayar</th>
                <th>Jumlah (Rp)</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($expenses as $idx => $exp)
                @php $total += $exp->amount; @endphp
                <tr>
                    <td align="center">{{ $idx + 1 }}</td>
                    <td class="str">{{ $exp->expense_number }}</td>
                    <td>{{ $exp->expense_date->format('d/m/Y') }}</td>
                    <td>{{ $exp->category?->name ?? 'Lain-lain' }}</td>
                    <td>{{ $exp->description }}</td>
                    <td>{{ $exp->recipient }}</td>
                    <td>{{ $exp->paymentMethod?->name ?? 'Kas Tunai' }}</td>
                    <td class="num">{{ $exp->amount }}</td>
                    <td>{{ $exp->user?->name }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f1f5f9;">
                <td colspan="7" align="right">TOTAL:</td>
                <td class="num">{{ $total }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

