<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat_Hutang_{{ str_replace(['/', ' '], '_', $debt->debt_number) }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: -0.3px;
        }
        .company-sub {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.3;
        }
        .doc-title-box {
            text-align: right;
            vertical-align: middle;
        }
        .doc-main-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: -0.2px;
        }
        .doc-number {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 2px;
        }
        .status-badge {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-paid {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .status-partial {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .status-unpaid {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* 2-Column Info Grid */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            vertical-align: top;
            width: 49%;
        }
        .info-title {
            font-size: 9px;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 1.5px 0;
            font-size: 8.5px;
            vertical-align: top;
        }
        .info-label {
            color: #64748b;
            width: 95px;
        }
        .info-colon {
            width: 8px;
            color: #94a3b8;
        }
        .info-value {
            color: #0f172a;
            font-weight: bold;
        }

        /* 3-Column Financial KPI Cards */
        table.kpi-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }
        table.kpi-table td {
            padding: 0;
            vertical-align: top;
        }
        .kpi-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 7px 10px;
            background-color: #ffffff;
            text-align: center;
            box-sizing: border-box;
        }
        .kpi-label {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .kpi-amount {
            font-size: 13px;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            margin: 3px 0 1px 0;
        }
        .kpi-subtext {
            font-size: 7.5px;
            color: #94a3b8;
        }

        /* Payments Table */
        .section-header {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 5px;
            padding-left: 2px;
        }
        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }
        table.ledger-table th, table.ledger-table td {
            border: 1px solid #cbd5e1;
            padding: 4.5px 5px;
            font-size: 8.5px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.ledger-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.3px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .bg-alt { background-color: #f8fafc; }
        .ledger-total {
            background-color: #f1f5f9;
            font-weight: bold;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .sig-col {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 8.5px;
            padding: 0 25px;
        }
        .sig-space {
            height: 56px;
            text-align: center;
        }
        .sig-name {
            font-weight: bold;
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
            color: #0f172a;
            display: inline-block;
            min-width: 170px;
        }

        .footer-note {
            margin-top: 15px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 5px;
            font-size: 7.5px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                <div class="company-sub">
                    {{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam & Rongsok Nusantara' }}<br>
                    Telp/WhatsApp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                </div>
            </td>
            <td class="doc-title-box">
                <div class="doc-main-title">KARTU RIWAYAT & SALDO HUTANG</div>
                <div class="doc-number">{{ $debt->debt_number }}</div>
                <div>
                    @if($debt->status === 'paid')
                        <span class="status-badge status-paid">● LUNAS (LUNAS PENUH)</span>
                    @elseif($debt->status === 'partial' || $debt->status === 'partially_paid')
                        <span class="status-badge status-partial">● SEBAGIAN TERBAYAR (TERCICIL)</span>
                    @else
                        <span class="status-badge status-unpaid">● BELUM LUNAS</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- 2-Column Info Grid: Debitur & Parameter Pinjaman -->
    <table class="info-grid">
        <tr>
            <!-- Left: Data Peminjam -->
            <td class="info-card">
                <div class="info-title">Data Peminjam / Debitur</div>
                <table class="info-table">
                    <tr>
                        <td class="info-label">Nama Peminjam</td>
                        <td class="info-colon">:</td>
                        <td class="info-value">{{ $debt->borrower_name }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Tipe Debitur</td>
                        <td class="info-colon">:</td>
                        <td class="info-value" style="text-transform: capitalize;">
                            {{ $debt->borrower_type }}
                            @if($debt->supplier)
                                <span style="font-weight: normal; color: #0284c7;">({{ $debt->supplier->code }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="info-label">Nomor WhatsApp/HP</td>
                        <td class="info-colon">:</td>
                        <td class="info-value font-mono">{{ $debt->borrower_phone ?: '-' }}</td>
                    </tr>
                    @if($debt->supplier && $debt->supplier->hasBankDetails())
                    <tr>
                        <td class="info-label">Rekening Bank</td>
                        <td class="info-colon">:</td>
                        <td class="info-value font-mono" style="color: #1e40af;">{{ $debt->supplier->bankDisplay() }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="info-label">Dicatat Oleh</td>
                        <td class="info-colon">:</td>
                        <td class="info-value">{{ $debt->user?->name ?? 'Kasir / Sistem' }}</td>
                    </tr>
                </table>
            </td>

            <td style="width: 2%;"></td>

            <!-- Right: Detail Pinjaman & Status -->
            <td class="info-card">
                <div class="info-title">Informasi & Syarat Pinjaman</div>
                <table class="info-table">
                    <tr>
                        <td class="info-label">Kategori Transaksi</td>
                        <td class="info-colon">:</td>
                        <td class="info-value">{{ $debt->getCategoryLabel() }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Tanggal Pinjam</td>
                        <td class="info-colon">:</td>
                        <td class="info-value">{{ $debt->date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Jatuh Tempo</td>
                        <td class="info-colon">:</td>
                        <td class="info-value" style="color: {{ $debt->due_date && $debt->due_date->isPast() && !$debt->isPaid() ? '#b91c1c' : '#0f172a' }};">
                            {{ $debt->due_date ? $debt->due_date->format('d/m/Y') : 'Tidak ditentukan' }}
                            @if($debt->due_date && $debt->due_date->isPast() && !$debt->isPaid())
                                <span style="font-size: 7.5px; color: #b91c1c;">(Jatuh Tempo Terlewat)</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="info-label">No. Perjanjian (SPH)</td>
                        <td class="info-colon">:</td>
                        <td class="info-value font-mono">{{ $debt->agreement_number ?: '-' }}</td>
                    </tr>
                    @if($debt->guarantee_info)
                    <tr>
                        <td class="info-label">Jaminan / Agunan</td>
                        <td class="info-colon">:</td>
                        <td class="info-value">{{ $debt->guarantee_info }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- 3 KPI Cards: Posisi Saldo Terkini -->
    <table class="kpi-table">
        <tr>
            <td style="width: 32%;">
                <div class="kpi-box" style="border-top: 3px solid #64748b;">
                    <div class="kpi-label">Nominal Pinjaman Awal</div>
                    <div class="kpi-amount" style="color: #0f172a;">
                        Rp {{ number_format($debt->initial_amount, 0, ',', '.') }}
                    </div>
                    <div class="kpi-subtext">Pokok pinjaman yang disalurkan</div>
                </div>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 32%;">
                <div class="kpi-box" style="border-top: 3px solid #10b981; background-color: #f0fdf4;">
                    <div class="kpi-label" style="color: #047857;">Total Sudah Diangsur</div>
                    <div class="kpi-amount" style="color: #047857;">
                        Rp {{ number_format($debt->paid_amount, 0, ',', '.') }}
                    </div>
                    <div class="kpi-subtext" style="color: #059669;">
                        {{ $debt->initial_amount > 0 ? round(($debt->paid_amount / $debt->initial_amount) * 100) : 0 }}% terbayar ({{ $payments->count() }}x angsuran)
                    </div>
                </div>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 32%;">
                <div class="kpi-box" style="border-top: 3px solid {{ $debt->isPaid() ? '#10b981' : '#f59e0b' }}; background-color: {{ $debt->isPaid() ? '#f0fdf4' : '#fffbeb' }};">
                    <div class="kpi-label" style="color: {{ $debt->isPaid() ? '#047857' : '#b45309' }};">Sisa Saldo Hutang</div>
                    <div class="kpi-amount" style="color: {{ $debt->isPaid() ? '#047857' : '#b45309' }};">
                        Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}
                    </div>
                    <div class="kpi-subtext" style="color: {{ $debt->isPaid() ? '#059669' : '#b45309' }};">
                        {{ $debt->isPaid() ? 'Hutang telah dinyatakan LUNAS' : 'Wajib diselesaikan / dilunasi' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Ledger Table of Repayments -->
    <div class="section-header">
        Riwayat Pembayaran &amp; Angsuran Pelunasan
        <span style="font-weight: normal; font-size: 8px; color: #64748b;">(Tercatat {{ $payments->count() }} Pembayaran)</span>
    </div>
    <table class="ledger-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 4%;">No</th>
                <th style="width: 17%;">No. Bukti Bayar</th>
                <th style="width: 13%;">Tanggal</th>
                <th style="width: 25%;">Sumber Dana / Metode</th>
                <th class="text-right" style="width: 14%;">Nominal Bayar</th>
                <th class="text-right" style="width: 14%;">Sisa Hutang</th>
                <th style="width: 13%;">Kasir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $idx => $payment)
                <tr class="{{ $idx % 2 === 1 ? 'bg-alt' : '' }}">
                    <td class="text-center font-mono">{{ $idx + 1 }}</td>
                    <td class="font-mono font-bold">{{ $payment->payment_number }}</td>
                    <td>{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($payment->purchase_transaction_id)
                            <strong style="color: #047857;">Potong Timbangan Rongsok</strong>
                            <span style="font-size: 7.5px; color: #0284c7;">({{ $payment->purchaseTransaction?->invoice_number }})</span>
                        @else
                            <strong style="color: #2563eb;">Setor {{ $payment->paymentMethod?->name ?? 'Tunai' }}</strong>
                        @endif
                        @if($payment->notes)
                            <div style="font-size: 7px; color: #64748b; font-style: italic;">{{ $payment->notes }}</div>
                        @endif
                    </td>
                    <td class="text-right font-mono" style="font-weight: bold; color: #047857;">
                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-mono" style="font-weight: bold; color: #0f172a;">
                        Rp {{ number_format($payment->remaining_balance, 0, ',', '.') }}
                    </td>
                    <td>{{ $payment->user?->name ?? 'Kasir Depo' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 16px; color: #94a3b8;">
                        <em>Belum ada catatan pembayaran / angsuran untuk pinjaman ini.<br>
                        Sisa saldo hutang saat ini masih utuh sebesar <strong>Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}</strong>.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="ledger-total">
                <td colspan="4" class="text-right">TOTAL TELAH DIANGSUR:</td>
                <td class="text-right font-mono" style="color: #047857;">
                    Rp {{ number_format($debt->paid_amount, 0, ',', '.') }}
                </td>
                <td class="text-right font-mono" style="color: {{ $debt->isPaid() ? '#047857' : '#b45309' }};">
                    Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @if($debt->notes)
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; font-size: 8px;">
            <strong style="color: #475569;">Catatan / Keterangan Tambahan:</strong>
            <span style="color: #1e293b;">{{ $debt->notes }}</span>
        </div>
    @endif

    <!-- Signatures -->
    <table class="signatures-table">
        <tr>
            <td class="sig-col">
                <div>Peminjam / Debitur,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $debt->borrower_name }} )</div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 1px;">Pihak Peminjam / Penerima Dana</div>
            </td>
            <td class="sig-col">
                <div>Pengelola Depo / Pemberi Pinjaman,</div>
                <div class="sig-space">
                    @include('components.signature-stamp', ['isPdf' => true, 'height' => 48, 'stampSize' => 56])
                </div>
                <div class="sig-name">( {{ $ownerName }} )</div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 1px;">Owner / Pengelola Depo Rongsok</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        *Dokumen ini diterbitkan secara otomatis oleh Sistem Kasir Depo Rongsok per {{ now()->format('d/m/Y H:i') }} WIB dan merupakan bukti sah riwayat mutasi angsuran serta posisi sisa saldo pinjaman modal saat ini.
    </div>

</body>
</html>
