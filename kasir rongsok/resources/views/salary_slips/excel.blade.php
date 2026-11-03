<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Slip Gaji</title>
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background-color: #e2e8f0; font-weight: bold; text-align: center; }
        .num { text-align: right; mso-number-format: "\#\,\#\#0"; }
        .str { mso-number-format: "\@"; }
    </style>
</head>
<body>
    <h3>REKAP GAJI KARYAWAN</h3>
    <p>Periode: {{ $period ?: 'Semua Periode' }}</p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Slip</th>
                <th>Nama Karyawan</th>
                <th>Periode</th>
                <th>Tanggal Bayar</th>
                <th>Gaji Pokok (Rp)</th>
                <th>Tunjangan (Rp)</th>
                <th>Bonus (Rp)</th>
                <th>Total Kotor (Rp)</th>
                <th>Potongan Kasbon (Rp)</th>
                <th>Potongan Lain (Rp)</th>
                <th>Gaji Bersih (Rp)</th>
                <th>Metode Bayar</th>
                <th>Dibuat Oleh</th>
            </tr>
        </thead>
        <tbody>
            @php $totalNet = 0; @endphp
            @foreach($slips as $idx => $s)
                @php $totalNet += $s->net_salary; @endphp
                <tr>
                    <td align="center">{{ $idx + 1 }}</td>
                    <td class="str">{{ $s->slip_number }}</td>
                    <td>{{ $s->employee_name }}</td>
                    <td>{{ $s->period_month }}</td>
                    <td>{{ $s->payment_date->format('d/m/Y') }}</td>
                    <td class="num">{{ $s->basic_salary }}</td>
                    <td class="num">{{ $s->allowance_amount }}</td>
                    <td class="num">{{ $s->bonus_amount }}</td>
                    <td class="num">{{ $s->total_income }}</td>
                    <td class="num">{{ $s->debt_deduction }}</td>
                    <td class="num">{{ $s->other_deduction }}</td>
                    <td class="num">{{ $s->net_salary }}</td>
                    <td>{{ $s->paymentMethod?->name ?? 'Kas Tunai' }}</td>
                    <td>{{ $s->creator?->name }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f1f5f9;">
                <td colspan="11" align="right">TOTAL GAJI BERSIH DIBAYARKAN:</td>
                <td class="num">{{ $totalNet }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

