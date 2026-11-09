<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Biaya Operasional</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 15px; }
        .company-name { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .title { font-size: 13px; font-weight: bold; margin-top: 5px; color: #047857; text-transform: uppercase; }
        .period { font-size: 10px; color: #64748b; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 10px; }
        th { background-color: #f1f5f9; font-weight: bold; text-transform: uppercase; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #f8fafc; font-weight: bold; font-size: 11px; }
        .footer { margin-top: 30px; }
        .signature-box { float: right; width: 200px; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
        <div class="title">LAPORAN BIAYA OPERASIONAL & PENGELUARAN KAS</div>
        <div class="period">Periode: {{ $startDate ? date('d/m/Y', strtotime($startDate)) : 'Semua' }} s/d {{ $endDate ? date('d/m/Y', strtotime($endDate)) : 'Semua' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 85px;">No. Bukti</th>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 100px;">Kategori</th>
                <th>Keterangan / Penerima</th>
                <th style="width: 80px;">Metode</th>
                <th class="text-right" style="width: 100px;">Jumlah Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $idx => $exp)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $exp->expense_number }}</td>
                    <td>{{ $exp->expense_date->format('d/m/Y') }}</td>
                    <td><strong>{{ $exp->category?->name ?? 'Lain-lain' }}</strong></td>
                    <td>
                        {{ $exp->description }}
                        @if($exp->recipient)
                            <span style="color: #64748b;">(Penerima: {{ $exp->recipient }})</span>
                        @endif
                    </td>
                    <td>{{ $exp->paymentMethod?->name ?? 'Kas Tunai' }}</td>
                    <td class="text-right font-bold">{{ \App\Services\FormatHelper::rupiah($exp->amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada data pengeluaran operasional.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-right">TOTAL PENGELUARAN:</td>
                <td class="text-right">{{ \App\Services\FormatHelper::rupiah($totalAmount) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div class="signature-box">
            <p>Dicetak pada: {{ date('d/m/Y H:i') }}</p>
            <div style="height: 56px; text-align: center;">
                @include('components.signature-stamp', ['isPdf' => true, 'height' => 48, 'stampSize' => 56])
            </div>
            <p><strong>( {{ \App\Models\User::where('role', 'super_admin')->first()?->name ?? 'Owner Depo' }} )</strong></p>
            <p style="color: #64748b;">Bagian Keuangan / Owner</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>

