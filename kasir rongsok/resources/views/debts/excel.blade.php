<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Hutang Piutang</title>
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background-color: #e2e8f0; font-weight: bold; text-align: center; }
        .num { text-align: right; mso-number-format: "\#\,\#\#0"; }
        .str { mso-number-format: "\@"; }
    </style>
</head>
<body>
    <h3>REKAP PINJAMAN MODAL & HUTANG PIUTANG</h3>
    <p>Tanggal Unduh: {{ date('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Pinjaman</th>
                <th>No. Surat Perjanjian</th>
                <th>Tanggal Pinjaman</th>
                <th>Jatuh Tempo</th>
                <th>Nama Peminjam</th>
                <th>Tipe Peminjam</th>
                <th>No. HP</th>
                <th>Jaminan / Agunan</th>
                <th>Saksi</th>
                <th>Nominal Pinjaman Awal (Rp)</th>
                <th>Total Terbayar (Rp)</th>
                <th>Sisa Hutang (Rp)</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $sumInitial = 0;
                $sumPaid = 0;
                $sumRemaining = 0;
            @endphp
            @foreach($debts as $idx => $d)
                @php
                    $sumInitial += $d->initial_amount;
                    $sumPaid += $d->paid_amount;
                    $sumRemaining += $d->remaining_amount;
                @endphp
                <tr>
                    <td align="center">{{ $idx + 1 }}</td>
                    <td class="str">{{ $d->debt_number }}</td>
                    <td class="str">{{ $d->agreement_number ?: '-' }}</td>
                    <td>{{ $d->date->format('d/m/Y') }}</td>
                    <td>{{ $d->due_date ? $d->due_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $d->borrower_name }}</td>
                    <td>{{ ucfirst($d->borrower_type) }}</td>
                    <td class="str">{{ $d->borrower_phone ?: '-' }}</td>
                    <td>{{ $d->guarantee_info ?: '-' }}</td>
                    <td>{{ $d->witness_name ?: '-' }}</td>
                    <td class="num">{{ $d->initial_amount }}</td>
                    <td class="num">{{ $d->paid_amount }}</td>
                    <td class="num">{{ $d->remaining_amount }}</td>
                    <td align="center">
                        @if($d->status === 'paid') LUNAS
                        @elseif($d->status === 'partially_paid') DIANGSUR SEBAGIAN
                        @else BELUM LUNAS
                        @endif
                    </td>
                    <td>{{ $d->notes ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f8fafc;">
                <td colspan="10" align="right">TOTAL KESELURUHAN</td>
                <td class="num">{{ $sumInitial }}</td>
                <td class="num">{{ $sumPaid }}</td>
                <td class="num">{{ $sumRemaining }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

