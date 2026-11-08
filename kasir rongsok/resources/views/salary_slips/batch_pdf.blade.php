<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap_Penggajian_Karyawan_{{ $period ?: date('Y-m') }}</title>
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
            color: #4338ca;
            text-transform: uppercase;
        }
        .period {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }

        /* Summary KPI Cards */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-left: -6px;
            margin-right: -6px;
            margin-bottom: 8px;
        }
        .kpi-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 8px;
            background-color: #ffffff;
            width: 33.33%;
            text-align: center;
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
        <div class="title">REKAPITULASI PENGGAJIAN KARYAWAN (PAYROLL)</div>
        <div class="period">
            {{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam & Rongsok' }} | Telp: {{ $settings['company_phone'] ?? '-' }}
            <br>
            Periode: <strong>{{ $period ?: 'Semua Periode' }}</strong> | Dicetak: <strong>{{ date('d/m/Y H:i') }} WIB</strong>
        </div>
    </div>

    <!-- 3 KPI Cards -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-box" style="border-top: 2.5px solid #64748b;">
                <div class="kpi-label">Total Gaji Pokok</div>
                <div class="kpi-amount" style="color: #0f172a;">{{ \App\Services\FormatHelper::rupiah($slips->sum('basic_salary')) }}</div>
                <div class="kpi-subtext">Dari {{ $slips->count() }} slip gaji karyawan</div>
            </td>
            <td class="kpi-box" style="border-top: 2.5px solid #10b981; background-color: #f0fdf4;">
                <div class="kpi-label" style="color: #047857;">Tunjangan &amp; Lembur</div>
                <div class="kpi-amount" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($slips->sum('allowance_amount') + $slips->sum('bonus_amount')) }}</div>
                <div class="kpi-subtext" style="color: #059669;">
                    Potongan kasbon: {{ \App\Services\FormatHelper::rupiah($slips->sum('total_deduction')) }}
                </div>
            </td>
            <td class="kpi-box" style="border-top: 2.5px solid #4338ca; background-color: #eef2ff;">
                <div class="kpi-label" style="color: #4338ca;">Total Pengeluaran Gaji Bersih</div>
                <div class="kpi-amount" style="color: #4338ca;">{{ \App\Services\FormatHelper::rupiah($totalNetSalary) }}</div>
                <div class="kpi-subtext" style="color: #4338ca;">Total kas keluar payroll karyawan</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 4%;">No</th>
                <th style="width: 14%;">No. Slip</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 17%;">Nama Karyawan</th>
                <th style="width: 9%;">Periode</th>
                <th class="text-right" style="width: 12%;">Gaji Pokok</th>
                <th class="text-right" style="width: 10%;">Tunj./Bonus</th>
                <th class="text-right" style="width: 9%;">Potongan</th>
                <th class="text-right" style="width: 11%;">Gaji Bersih</th>
                <th class="text-center" style="width: 5%;">Metode</th>
            </tr>
        </thead>
        <tbody>
            @forelse($slips as $idx => $s)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-mono" style="font-weight: bold; font-size: 7pt; color: #1e40af;">{{ $s->slip_number }}</td>
                    <td>{{ $s->payment_date->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $s->employee_name }}</strong>
                        @if($s->employeeUser)
                            <br><span style="color: #64748b; font-size: 6.5pt;">({{ ucfirst($s->employeeUser->role) }})</span>
                        @endif
                    </td>
                    <td>{{ $s->period_month }}</td>
                    <td class="text-right font-mono">{{ \App\Services\FormatHelper::rupiah($s->basic_salary) }}</td>
                    <td class="text-right font-mono" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($s->allowance_amount + $s->bonus_amount) }}</td>
                    <td class="text-right font-mono" style="color: #dc2626;">{{ \App\Services\FormatHelper::rupiah($s->total_deduction) }}</td>
                    <td class="text-right font-mono font-bold" style="color: #4338ca;">{{ \App\Services\FormatHelper::rupiah($s->net_salary) }}</td>
                    <td class="text-center" style="font-size: 6.5pt;">{{ $s->paymentMethod?->name ?? 'Tunai' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px; color: #94a3b8;">Tidak ada data slip gaji karyawan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL KESELURUHAN:</td>
                <td class="text-right font-mono">{{ \App\Services\FormatHelper::rupiah($slips->sum('basic_salary')) }}</td>
                <td class="text-right font-mono" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($slips->sum('allowance_amount') + $slips->sum('bonus_amount')) }}</td>
                <td class="text-right font-mono" style="color: #dc2626;">{{ \App\Services\FormatHelper::rupiah($slips->sum('total_deduction')) }}</td>
                <td class="text-right font-mono font-bold" style="color: #4338ca;">{{ \App\Services\FormatHelper::rupiah($totalNetSalary) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div class="signature-box">
            <p style="margin: 0 0 4px 0;">Manajer Operasional / Owner,</p>
            <div style="height: 48px; text-align: center;">
                @if(\App\Models\BusinessSetting::hasOwnerSignature())
                    <img src="{{ \App\Models\BusinessSetting::ownerSignatureBase64() }}" alt="TTD Owner" style="height: 44px; max-height: 46px; width: auto; max-width: 140px; margin: 0 auto; display: block;">
                @endif
            </div>
            <p style="margin: 3px 0 0 0; font-weight: bold; text-decoration: underline;">
                ( {{ \App\Models\User::where('role', 'super_admin')->first()?->name ?? 'Pimpinan / Owner Depo' }} )
            </p>
            <p style="margin: 1px 0 0 0; color: #64748b; font-size: 7pt;">Pimpinan / Owner Depo</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>
