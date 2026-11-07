<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip_Gaji_{{ $salarySlip->slip_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 0; }
        .border-box { border: 1.5px solid #0f172a; padding: 15px; border-radius: 6px; page-break-inside: avoid; }
        .header { text-align: center; border-bottom: 1.5px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .company-name { font-size: 15px; font-weight: bold; text-transform: uppercase; }
        .title { font-size: 12px; font-weight: bold; margin-top: 3px; color: #047857; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 4px 6px; font-size: 10px; }
        .section-title { font-weight: bold; background-color: #f1f5f9; padding: 4px 6px; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .grand-total { border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a; font-size: 13px; font-weight: bold; }
        .footer { margin-top: 25px; }
        .sig { float: left; width: 45%; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
    <div class="border-box">
        <div class="header">
            <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
            <div class="title">SLIP GAJI KARYAWAN & HONOR OPERASIONAL</div>
            <div style="font-size: 9px; color: #64748b;">{{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam' }} | Telp: {{ $settings['company_phone'] ?? '-' }}</div>
        </div>

        <table>
            <tr>
                <td style="width: 15%;">No. Dokumen</td>
                <td style="width: 2%;">:</td>
                <td style="width: 33%; font-weight: bold;">{{ $salarySlip->slip_number }}</td>
                <td style="width: 15%;">Periode Gaji</td>
                <td style="width: 2%;">:</td>
                <td style="width: 33%; font-weight: bold;">{{ $salarySlip->period_month }}</td>
            </tr>
            <tr>
                <td>Nama Karyawan</td>
                <td>:</td>
                <td style="font-weight: bold;">{{ $salarySlip->employee_name }}</td>
                <td>Tanggal Bayar</td>
                <td>:</td>
                <td>{{ $salarySlip->payment_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>Metode Bayar</td>
                <td>:</td>
                <td>{{ $salarySlip->paymentMethod?->name ?? 'Kas Tunai' }}</td>
                <td>Status</td>
                <td>:</td>
                <td style="font-weight: bold; color: #047857;">LUNAS / DIBAYAR</td>
            </tr>
        </table>

        <table style="margin-top: 10px;">
            <tr>
                <!-- Income Column -->
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <div class="section-title">PENGHASILAN / PENDAPATAN</div>
                    <table>
                        <tr>
                            <td>Gaji Pokok</td>
                            <td class="text-right font-bold">{{ \App\Services\FormatHelper::rupiah($salarySlip->basic_salary) }}</td>
                        </tr>
                        @if($salarySlip->allowance_amount > 0)
                            <tr>
                                <td>Tunjangan Operasional</td>
                                <td class="text-right">{{ \App\Services\FormatHelper::rupiah($salarySlip->allowance_amount) }}</td>
                            </tr>
                        @endif
                        @if($salarySlip->bonus_amount > 0)
                            <tr>
                                <td>Bonus / Uang Lembur</td>
                                <td class="text-right">{{ \App\Services\FormatHelper::rupiah($salarySlip->bonus_amount) }}</td>
                            </tr>
                        @endif
                        <tr style="border-top: 1px dashed #cbd5e1; font-weight: bold;">
                            <td>Total Penghasilan:</td>
                            <td class="text-right">{{ \App\Services\FormatHelper::rupiah($salarySlip->total_income) }}</td>
                        </tr>
                    </table>
                </td>

                <!-- Deductions Column -->
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <div class="section-title">POTONGAN & KASBON</div>
                    <table>
                        <tr>
                            <td>Potongan Pinjaman/Kasbon</td>
                            <td class="text-right" style="color: #b91c1c;">- {{ \App\Services\FormatHelper::rupiah($salarySlip->debt_deduction) }}</td>
                        </tr>
                        @if($salarySlip->other_deduction > 0)
                            <tr>
                                <td>Potongan Lain-lain</td>
                                <td class="text-right" style="color: #b91c1c;">- {{ \App\Services\FormatHelper::rupiah($salarySlip->other_deduction) }}</td>
                            </tr>
                        @endif
                        <tr style="border-top: 1px dashed #cbd5e1; font-weight: bold;">
                            <td>Total Potongan:</td>
                            <td class="text-right" style="color: #b91c1c;">- {{ \App\Services\FormatHelper::rupiah($salarySlip->total_deduction) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Grand Total Net Received -->
        <table style="margin-top: 12px;">
            <tr class="grand-total">
                <td style="padding: 8px; font-size: 12px; font-weight: bold;">GAJI BERSIH DITERIMA (NETTO)</td>
                <td class="text-right" style="padding: 8px; font-size: 14px; font-weight: bold; color: #047857;">
                    {{ \App\Services\FormatHelper::rupiah($salarySlip->net_salary) }}
                </td>
            </tr>
        </table>

        <div class="footer">
            <div class="sig">
                <p>Penerima (Karyawan)</p>
                <div style="height: 55px;"></div>
                <p><strong>( {{ $salarySlip->employee_name }} )</strong></p>
            </div>
            <div class="sig" style="float: right;">
                <p>Dibuat &amp; Disetujui Oleh,</p>
                <div style="height: 55px; text-align: center;">
                    @if(\App\Models\BusinessSetting::hasOwnerSignature())
                        <img src="{{ \App\Models\BusinessSetting::ownerSignatureBase64() }}" alt="TTD Owner" style="height: 50px; max-height: 54px; width: auto; max-width: 150px; margin: 0 auto; display: block;">
                    @endif
                </div>
                <p><strong>( {{ $salarySlip->creator?->name ?? 'Pimpinan / Owner Depo' }} )</strong></p>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</body>
</html>

