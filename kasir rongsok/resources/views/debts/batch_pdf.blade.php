<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap_Hutang_Modal_{{ date('Ymd') }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .company-name { font-size: 15px; font-weight: bold; text-transform: uppercase; color: #0f172a; }
        .title { font-size: 12px; font-weight: bold; margin-top: 3px; color: #b45309; text-transform: uppercase; }
        .period { font-size: 9.5px; color: #64748b; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 7px; font-size: 9.5px; }
        th { background-color: #f1f5f9; font-weight: bold; text-transform: uppercase; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #f8fafc; font-weight: bold; font-size: 10px; }
        .footer { margin-top: 25px; }
        .signature-box { float: right; width: 180px; text-align: center; font-size: 9.5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
        <div class="title">BUKU KASBON & REKAPITULASI PINJAMAN MODAL BEREDAR</div>
        <div class="period">Status: {{ $status ? strtoupper($status) : 'SEMUA' }} | Dicetak: {{ date('d/m/Y H:i') }} WIB</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 90px;">No. Pinjaman</th>
                <th style="width: 70px;">Tanggal</th>
                <th>Nama Peminjam / Debitur</th>
                <th style="width: 75px;">Tipe</th>
                <th class="text-right" style="width: 90px;">Pinjaman Awal</th>
                <th class="text-right" style="width: 90px;">Telah Diangsur</th>
                <th class="text-right" style="width: 95px;">Sisa Hutang</th>
                <th class="text-center" style="width: 60px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($debts as $idx => $d)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $d->debt_number }}</td>
                    <td>{{ $d->date->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $d->borrower_name }}</strong>
                        @if($d->borrower_phone)
                            <span style="color: #64748b; font-size: 8.5px;">({{ $d->borrower_phone }})</span>
                        @endif
                    </td>
                    <td>{{ ucfirst($d->borrower_type) }}</td>
                    <td class="text-right font-mono">{{ \App\Services\FormatHelper::rupiah($d->amount) }}</td>
                    <td class="text-right font-mono" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($d->amount - $d->remaining_amount) }}</td>
                    <td class="text-right font-mono font-bold" style="color: #b45309;">{{ \App\Services\FormatHelper::rupiah($d->remaining_amount) }}</td>
                    <td class="text-center" style="font-weight: bold; font-size: 8.5px;">
                        {{ $d->status === 'paid' ? 'LUNAS' : ($d->status === 'partially_paid' ? 'DICICIL' : 'BELUM BAYAR') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada data pinjaman modal.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL KESELURUHAN:</td>
                <td class="text-right font-mono">{{ \App\Services\FormatHelper::rupiah($totalAmount) }}</td>
                <td class="text-right font-mono" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($totalPaid) }}</td>
                <td class="text-right font-mono font-bold" style="color: #b45309;">{{ \App\Services\FormatHelper::rupiah($totalRemaining) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div class="signature-box">
            <p>Pengelola Keuangan & Kasbon,</p>
            <div style="height: 45px;"></div>
            <p><strong>( ____________________ )</strong></p>
            <p style="color: #64748b;">Kasir / Owner Depo</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>

