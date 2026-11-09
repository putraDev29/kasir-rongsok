<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip_Gaji_{{ $salarySlip->slip_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .border-box {
            border: 1.5px solid #0f172a;
            padding: 14px 18px;
            border-radius: 6px;
            page-break-inside: avoid;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .company-sub {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }
        .title-badge {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 3px;
            letter-spacing: 0.5px;
        }
        .doc-meta {
            font-size: 8.5pt;
            font-family: 'Courier New', Courier, monospace;
            color: #1e293b;
            margin-top: 3px;
            text-align: right;
        }

        /* Metadata Table */
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.meta-table td {
            padding: 2.5px 4px;
            font-size: 8.5pt;
            vertical-align: top;
            white-space: nowrap;
        }
        .meta-label {
            color: #64748b;
            width: 16%;
        }
        .meta-colon {
            width: 2%;
            color: #94a3b8;
        }
        .meta-value {
            color: #0f172a;
            width: 32%;
        }

        /* 2-Column Ledger Container */
        table.ledger-wrapper {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.ledger-wrapper td.ledger-col {
            width: 48.5%;
            vertical-align: top;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 10px;
            background-color: #ffffff;
        }
        .section-header {
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #cbd5e1;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }
        .header-income {
            color: #047857;
            border-bottom-color: #a7f3d0;
        }
        .header-deduct {
            color: #b91c1c;
            border-bottom-color: #fecaca;
        }

        table.item-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.item-table td {
            padding: 3px 0;
            font-size: 8.5pt;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        .row-total {
            border-top: 1px dashed #cbd5e1;
            font-weight: bold;
        }

        /* Net Take Home Pay Box */
        .net-pay-box {
            border: 1.5px solid #047857;
            background-color: #ecfdf5;
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 6px;
            margin-bottom: 12px;
        }
        .net-pay-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .net-pay-amount {
            font-size: 15pt;
            font-weight: bold;
            color: #047857;
            font-family: 'Courier New', Courier, monospace;
        }
        .terbilang-text {
            font-size: 8pt;
            font-style: italic;
            color: #334155;
            margin-top: 2px;
        }

        /* Signatures */
        table.sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.sig-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 8.5pt;
        }
        .sig-space {
            height: 50px;
            text-align: center;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            color: #0f172a;
        }
    </style>
</head>
<body>
    @php
        if (!function_exists('penyebutGajiPdf')) {
            function penyebutGajiPdf($nilai) {
                $nilai = abs($nilai);
                $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
                if ($nilai < 12) {
                    return " " . $huruf[$nilai];
                } elseif ($nilai < 20) {
                    return penyebutGajiPdf($nilai - 10) . " Belas";
                } elseif ($nilai < 100) {
                    return penyebutGajiPdf(floor($nilai / 10)) . " Puluh" . penyebutGajiPdf($nilai % 10);
                } elseif ($nilai < 200) {
                    return " Seratus" . penyebutGajiPdf($nilai - 100);
                } elseif ($nilai < 1000) {
                    return penyebutGajiPdf(floor($nilai / 100)) . " Ratus" . penyebutGajiPdf($nilai % 100);
                } elseif ($nilai < 2000) {
                    return " Seribu" . penyebutGajiPdf($nilai - 1000);
                } elseif ($nilai < 1000000) {
                    return penyebutGajiPdf(floor($nilai / 1000)) . " Ribu" . penyebutGajiPdf($nilai % 1000);
                } elseif ($nilai < 1000000000) {
                    return penyebutGajiPdf(floor($nilai / 1000000)) . " Juta" . penyebutGajiPdf($nilai % 1000000);
                }
                return "";
            }
        }
        $terbilang = trim(penyebutGajiPdf($salarySlip->net_salary)) . " Rupiah";
    @endphp

    <div class="border-box">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="vertical-align: middle;">
                    <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                    <div class="company-sub">
                        {{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam & Rongsok' }} | Telp: {{ $settings['company_phone'] ?? '-' }}
                    </div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <span class="title-badge">SLIP GAJI KARYAWAN</span>
                    <div class="doc-meta">
                        No: <strong>{{ $salarySlip->slip_number }}</strong><br>
                        Periode: <strong>{{ $salarySlip->period_month }}</strong>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Metadata -->
        <table class="meta-table">
            <tr>
                <td class="meta-label">Nama Karyawan</td>
                <td class="meta-colon">:</td>
                <td class="meta-value">
                    <strong>{{ $salarySlip->employee_name }}</strong>
                    @if($salarySlip->employeeUser)
                        <span style="color: #64748b; font-size: 7.5pt;">({{ ucfirst($salarySlip->employeeUser->role) }})</span>
                    @endif
                </td>
                <td class="meta-label">Tanggal Bayar</td>
                <td class="meta-colon">:</td>
                <td class="meta-value font-mono">{{ $salarySlip->payment_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Metode Bayar</td>
                <td class="meta-colon">:</td>
                <td class="meta-value">{{ $salarySlip->paymentMethod?->name ?? 'Kas Tunai' }}</td>
                <td class="meta-label">Status Slip</td>
                <td class="meta-colon">:</td>
                <td class="meta-value" style="font-weight: bold; color: #047857;">● LUNAS / TELAH DIBAYAR</td>
            </tr>
        </table>

        <!-- 2-Column Earnings & Deductions -->
        <table class="ledger-wrapper">
            <tr>
                <!-- Column A: Earnings -->
                <td class="ledger-col">
                    <div class="section-header header-income">A. Penghasilan / Penerimaan</div>
                    <table class="item-table font-mono">
                        <tr>
                            <td style="vertical-align: top;">
                                Gaji Pokok
                                @if($salarySlip->work_days && $salarySlip->daily_rate)
                                    <div style="font-size: 7.5pt; color: #047857; font-weight: normal; margin-top: 1px;">
                                        {{ $salarySlip->work_days }} hari &times; {{ \App\Services\FormatHelper::rupiah($salarySlip->daily_rate) }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-right font-bold" style="vertical-align: top; white-space: nowrap;">{{ \App\Services\FormatHelper::rupiah($salarySlip->basic_salary) }}</td>
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
                        <tr class="row-total">
                            <td style="padding-top: 5px;">Total Penghasilan (A):</td>
                            <td class="text-right" style="padding-top: 5px; color: #047857;">{{ \App\Services\FormatHelper::rupiah($salarySlip->total_income) }}</td>
                        </tr>
                    </table>
                </td>

                <td style="width: 3%;"></td>

                <!-- Column B: Deductions -->
                <td class="ledger-col">
                    <div class="section-header header-deduct">B. Potongan Gaji &amp; Kasbon</div>
                    <table class="item-table font-mono">
                        <tr>
                            <td>Potongan Kasbon / Bon</td>
                            <td class="text-right" style="color: #b91c1c;">- {{ \App\Services\FormatHelper::rupiah($salarySlip->debt_deduction) }}</td>
                        </tr>
                        @if($salarySlip->other_deduction > 0)
                            <tr>
                                <td>Potongan Lainnya / BPJS</td>
                                <td class="text-right" style="color: #b91c1c;">- {{ \App\Services\FormatHelper::rupiah($salarySlip->other_deduction) }}</td>
                            </tr>
                        @endif
                        <tr class="row-total">
                            <td style="padding-top: 5px;">Total Potongan (B):</td>
                            <td class="text-right" style="padding-top: 5px; color: #b91c1c;">- {{ \App\Services\FormatHelper::rupiah($salarySlip->total_deduction) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Grand Total Net Received Callout Box -->
        <table style="width: 100%; border-collapse: collapse; margin-top: 4px; margin-bottom: 8px;">
            <tr>
                <td style="border: 1.5px solid #047857; background-color: #f0fdf4; border-radius: 4px; padding: 7px 12px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="vertical-align: middle;">
                                <div class="net-pay-title">GAJI BERSIH DITERIMA (NETTO = A - B)</div>
                                <div class="terbilang-text"># Terbilang: {{ $terbilang }} #</div>
                            </td>
                            <td style="text-align: right; vertical-align: middle;">
                                <span class="net-pay-amount">{{ \App\Services\FormatHelper::rupiah($salarySlip->net_salary) }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Signatures -->
        <table class="sig-table">
            <tr>
                <td>
                    <p style="margin: 0 0 4px 0;">Penerima (Karyawan),</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">( {{ $salarySlip->employee_name }} )</p>
                </td>
                <td>
                    <p style="margin: 0 0 4px 0;">Pimpinan / Kasir Penggajian,</p>
                    <div class="sig-space">
                        @if(\App\Models\BusinessSetting::hasOwnerSignature())
                            <img src="{{ \App\Models\BusinessSetting::ownerSignatureBase64() }}" alt="TTD Owner" style="height: 46px; max-height: 48px; width: auto; max-width: 140px; margin: 0 auto; display: block;">
                        @endif
                    </div>
                    <p class="sig-name">( {{ $salarySlip->creator?->name ?? 'Pimpinan / Owner Depo' }} )</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
