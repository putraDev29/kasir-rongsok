<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Penjualan Rongsok</title>
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
    <h3>REKAP PENJUALAN RONGSOK (PARTAI BESAR / PABRIK)</h3>
    <p>Periode: {{ $startDate ?: '-' }} s/d {{ $endDate ?: '-' }}</p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Faktur</th>
                <th>Tanggal</th>
                <th>Nama Pembeli / Pabrik</th>
                <th>HP Pembeli</th>
                <th>Rincian Muatan Barang</th>
                <th>Total Berat (kg)</th>
                <th>Total Modal HPP (Rp)</th>
                <th>Total Penjualan (Rp)</th>
                <th>Estimasi Laba Kotor (Rp)</th>
                <th>Metode Bayar</th>
                <th>Status</th>
                <th>Operator</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $sumWeight = 0;
                $sumCogs = 0;
                $sumSales = 0;
                $sumProfit = 0;
            @endphp
            @foreach($sales as $idx => $s)
                @php 
                    $sumWeight += $s->total_weight;
                    $sumCogs += $s->cogs_amount;
                    $sumSales += $s->final_amount;
                    $sumProfit += $s->estimated_profit;
                    $itemDetails = $s->items->map(fn($it) => $it->item_name_snapshot . ($it->item_type_name ? ' ('.$it->item_type_name.')' : '') . ': ' . ($it->unit_type == 'pcs' ? $it->quantity.' pcs' : $it->weight.' kg'))->implode('; ');
                @endphp
                <tr>
                    <td align="center">{{ $idx + 1 }}</td>
                    <td class="str">{{ $s->invoice_number }}</td>
                    <td>{{ $s->transaction_date->format('d/m/Y H:i') }}</td>
                    <td><strong>{{ $s->buyer_name }}</strong></td>
                    <td class="str">{{ $s->buyer_phone ?: '-' }}</td>
                    <td>{{ $itemDetails }}</td>
                    <td class="weight">{{ $s->total_weight }}</td>
                    <td class="num">{{ $s->cogs_amount }}</td>
                    <td class="num">{{ $s->final_amount }}</td>
                    <td class="num">{{ $s->estimated_profit }}</td>
                    <td>{{ $s->paymentMethod?->name ?? 'Tunai' }} {{ ($s->isBankPayment() && $s->bank_info) ? '('.$s->bank_info.')' : '' }}</td>
                    <td align="center">{{ $s->isCancelled() ? 'DIBATALKAN' : 'SELESAI' }}</td>
                    <td>{{ $s->user?->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f8fafc;">
                <td colspan="6" align="right">TOTAL KESELURUHAN</td>
                <td class="weight">{{ $sumWeight }}</td>
                <td class="num">{{ $sumCogs }}</td>
                <td class="num">{{ $sumSales }}</td>
                <td class="num">{{ $sumProfit }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

