<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap_Hutang_Modal_{{ date('Ymd') }}</title>
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
            line-height: 1.35;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .title {
            font-size: 10.5pt;
            font-weight: bold;
            margin-top: 2px;
            color: #b45309;
            text-transform: uppercase;
        }
        .period {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }

        /* Summary KPI Cards */
        table.kpi-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
        }
        table.kpi-table td {
            padding: 0;
            vertical-align: top;
        }
        .kpi-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 8px;
            background-color: #ffffff;
            text-align: center;
            box-sizing: border-box;
        }
        .kpi-label {
            font-size: 7pt;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .kpi-amount {
            font-size: 11pt;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            margin: 2px 0 1px 0;
        }
        .kpi-subtext {
            font-size: 6.5pt;
            color: #94a3b8;
        }

        /* Ledger Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 4px;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            font-size: 7.5pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: middle;
        }
        table.data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7pt;
            letter-spacing: 0.2px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .total-row {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 8pt;
        }
        .status-badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #ecfdf5;
            color: #047857;
            border: 0.5px solid #a7f3d0;
        }
        .status-partial {
            background-color: #fffbeb;
            color: #b45309;
            border: 0.5px solid #fde68a;
        }
        .status-unpaid {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 0.5px solid #fecaca;
        }

        .footer {
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signature-box {
            float: right;
            width: 180px;
            text-align: center;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
        <div class="title">BUKU KASBON &amp; REKAPITULASI PINJAMAN MODAL BEREDAR</div>
        <div class="period">
            {{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam & Rongsok' }} | Telp: {{ $settings['company_phone'] ?? '-' }}
            <br>
            Filter: Status: <strong>{{ $status ? strtoupper($status) : 'SEMUA' }}</strong> | Tipe: <strong>{{ $type ? strtoupper($type) : 'SEMUA' }}</strong> | Dicetak: <strong>{{ date('d/m/Y H:i') }} WIB</strong>
        </div>
    </div>

    <!-- 3 KPI Cards -->
    <table class="kpi-table">
        <tr>
            <td style="width: 32%;">
                <div class="kpi-box" style="border-top: 2.5px solid #64748b;">
                    <div class="kpi-label">Total Pinjaman Modal</div>
                    <div class="kpi-amount" style="color: #0f172a;">{{ \App\Services\FormatHelper::rupiah($totalAmount) }}</div>
                    <div class="kpi-subtext">Akumulasi seluruh kasbon &amp; pinjaman</div>
                </div>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 32%;">
                <div class="kpi-box" style="border-top: 2.5px solid #10b981; background-color: #f0fdf4;">
                    <div class="kpi-label" style="color: #047857;">Telah Diangsur / Dipotong</div>
                    <div class="kpi-amount" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($totalPaid) }}</div>
                    <div class="kpi-subtext" style="color: #059669;">
                        {{ $totalAmount > 0 ? round(($totalPaid / $totalAmount) * 100) : 0 }}% terbayar / terpotong timbangan
                    </div>
                </div>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 32%;">
                <div class="kpi-box" style="border-top: 2.5px solid #f59e0b; background-color: #fffbeb;">
                    <div class="kpi-label" style="color: #b45309;">Sisa Saldo Kasbon Aktif</div>
                    <div class="kpi-amount" style="color: #b45309;">{{ \App\Services\FormatHelper::rupiah($totalRemaining) }}</div>
                    <div class="kpi-subtext" style="color: #b45309;">Piutang berjalan belum lunas</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 4%;">No</th>
                <th style="width: 14%;">No. Pinjaman</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 19%;">Peminjam / Debitur</th>
                <th style="width: 8%;">Tipe</th>
                <th class="text-right" style="width: 13%;">Pinjaman Awal</th>
                <th class="text-right" style="width: 12%;">Telah Diangsur</th>
                <th class="text-right" style="width: 13%;">Sisa Hutang</th>
                <th class="text-center" style="width: 8%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($debts as $idx => $d)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-mono" style="font-weight: bold; font-size: 7pt; color: #1e40af;">{{ $d->debt_number }}</td>
                    <td>{{ $d->date->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $d->borrower_name }}</strong>
                        @if($d->borrower_phone)
                            <br><span style="color: #64748b; font-size: 6.5pt;">{{ $d->borrower_phone }}</span>
                        @endif
                    </td>
                    <td style="text-transform: capitalize;">{{ $d->borrower_type }}</td>
                    <td class="text-right font-mono">{{ \App\Services\FormatHelper::rupiah($d->amount) }}</td>
                    <td class="text-right font-mono" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($d->amount - $d->remaining_amount) }}</td>
                    <td class="text-right font-mono font-bold" style="color: #b45309;">{{ \App\Services\FormatHelper::rupiah($d->remaining_amount) }}</td>
                    <td class="text-center">
                        @if($d->status === 'paid')
                            <span class="status-badge status-paid">LUNAS</span>
                        @elseif($d->status === 'partially_paid' || $d->status === 'partial')
                            <span class="status-badge status-partial">DICICIL</span>
                        @else
                            <span class="status-badge status-unpaid">BELUM</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 15px; color: #94a3b8;">Tidak ada data kasbon / pinjaman modal.</td>
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
            <p style="margin: 0 0 4px 0;">Pengelola Keuangan &amp; Kasbon,</p>
            <div style="height: 48px; text-align: center;">
                @if(\App\Models\BusinessSetting::hasOwnerSignature())
                    <img src="{{ \App\Models\BusinessSetting::ownerSignatureBase64() }}" alt="TTD Owner" style="height: 44px; max-height: 46px; width: auto; max-width: 140px; margin: 0 auto; display: block;">
                @endif
            </div>
            <p style="margin: 3px 0 0 0; font-weight: bold; text-decoration: underline;">
                ( {{ \App\Models\User::where('role', 'super_admin')->first()?->name ?? 'Pimpinan / Owner Depo' }} )
            </p>
            <p style="margin: 1px 0 0 0; color: #64748b; font-size: 7pt;">Kasir / Owner Depo</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>
