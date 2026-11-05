<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap_Penggajian_Karyawan_{{ $period ?: date('Y-m') }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .company-name { font-size: 15px; font-weight: bold; text-transform: uppercase; color: #0f172a; }
        .title { font-size: 12px; font-weight: bold; margin-top: 3px; color: #4338ca; text-transform: uppercase; }
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
        <div class="title">REKAPITULASI PENGGAJIAN KARYAWAN (PAYROLL)</div>
        <div class="period">Periode: {{ $period ?: 'Semua Periode' }} | Dicetak: {{ date('d/m/Y H:i') }} WIB</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 90px;">No. Slip</th>
                <th style="width: 70px;">Tanggal</th>
                <th>Nama Karyawan</th>
                <th style="width: 70px;">Periode</th>
                <th class="text-right" style="width: 85px;">Gaji Pokok</th>
                <th class="text-right" style="width: 85px;">Bonus/Tunj.</th>
                <th class="text-right" style="width: 80px;">Potongan</th>
                <th class="text-right" style="width: 95px;">Gaji Bersih</th>
                <th style="width: 70px;">Metode</th>
            </tr>
        </thead>
        <tbody>
            @forelse($slips as $idx => $s)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $s->slip_number }}</td>
                    <td>{{ $s->payment_date->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $s->employee_name }}</strong>
                        @if($s->employeeUser)
                            <span style="color: #64748b; font-size: 8.5px;">({{ ucfirst($s->employeeUser->role) }})</span>
                        @endif
                    </td>
                    <td>{{ $s->period_month }}</td>
                    <td class="text-right font-mono">{{ \App\Services\FormatHelper::rupiah($s->basic_salary) }}</td>
                    <td class="text-right font-mono" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($s->allowance_amount + $s->bonus_amount) }}</td>
                    <td class="text-right font-mono" style="color: #dc2626;">{{ \App\Services\FormatHelper::rupiah($s->total_deduction) }}</td>
                    <td class="text-right font-mono font-bold" style="color: #4338ca;">{{ \App\Services\FormatHelper::rupiah($s->net_salary) }}</td>
                    <td>{{ $s->paymentMethod?->name ?? 'Kas Tunai' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada data slip gaji karyawan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL PENGELUARAN GAJI:</td>
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
            <p>Manajer Operasional / Owner,</p>
            <div style="height: 45px; text-align: center;">
                @if(\App\Models\BusinessSetting::hasOwnerSignature())
                    <img src="{{ \App\Models\BusinessSetting::ownerSignatureBase64() }}" alt="TTD Owner" style="max-height: 42px; max-width: 120px; margin: 0 auto; display: block;">
                @endif
            </div>
            <p><strong>( {{ \App\Models\User::where('role', 'super_admin')->first()?->name ?? 'Owner Depo' }} )</strong></p>
            <p style="color: #64748b;">Pimpinan / Owner Depo</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>

