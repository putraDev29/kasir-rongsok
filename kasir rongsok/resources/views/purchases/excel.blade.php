<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Pembelian</title>
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
    <h3>REKAP TRANSAKSI PEMBELIAN MATERIAL SCRAP & DAUR ULANG</h3>
    <p>Periode: {{ $startDate ?: '-' }} s/d {{ $endDate ?: '-' }}</p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Nota</th>
                <th>Tanggal</th>
                <th>Pemasok</th>
                <th>HP Pemasok</th>
                <th>Rincian Barang</th>
                <th>Total Berat Bersih (kg)</th>
                <th>Subtotal (Rp)</th>
                <th>Potongan Diskon (Rp)</th>
                <th>Potongan Bon/Hutang (Rp)</th>
                <th>Total Bayar (Rp)</th>
                <th>Metode Bayar</th>
                <th>Kasir</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $sumWeight = 0; 
                $sumAmount = 0; 
            @endphp
            @foreach($purchases as $idx => $p)
                @php 
                    $sumWeight += $p->total_net_weight; 
                    $sumAmount += $p->final_amount; 
                    $itemNames = $p->items->map(fn($it) => $it->item_name_snapshot . ($it->item_type_name ? ' ('.$it->item_type_name.')' : '') . ': ' . ($it->unit_type == 'pcs' ? $it->quantity.' pcs' : $it->net_weight.' kg'))->implode('; ');
                @endphp
                <tr>
                    <td align="center">{{ $idx + 1 }}</td>
                    <td class="str">{{ $p->invoice_number }}</td>
                    <td>{{ $p->transaction_date->format('d/m/Y H:i') }}</td>
                    <td>{{ $p->supplier_name }}</td>
                    <td class="str">{{ $p->supplier_phone }}</td>
                    <td>{{ $itemNames }}</td>
                    <td class="weight">{{ $p->total_net_weight }}</td>
                    <td class="num">{{ $p->subtotal_amount }}</td>
                    <td class="num">{{ $p->discount_amount }}</td>
                    <td class="num">{{ $p->debt_deduction_amount }}</td>
                    <td class="num">{{ $p->final_amount }}</td>
                    <td>{{ $p->paymentMethod?->name ?? 'Tunai' }} {{ $p->bank_info ? '('.$p->bank_info.')' : '' }}</td>
                    <td>{{ $p->user?->name }}</td>
                    <td align="center">{{ $p->status }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f1f5f9;">
                <td colspan="6" align="right">TOTAL KESELURUHAN:</td>
                <td class="weight">{{ $sumWeight }}</td>
                <td colspan="3"></td>
                <td class="num">{{ $sumAmount }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

