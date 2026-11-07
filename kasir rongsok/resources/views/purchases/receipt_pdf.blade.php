<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota_{{ $cleanInv }}_{{ $cleanSup }}</title>
    <style>
        @page {
            size: a5 portrait;
            margin: 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #059669;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #047857;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 8px;
            color: #475569;
            margin-top: 1px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }
        .status-void {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #ef4444;
        }
        .doc-title-row {
            margin-bottom: 6px;
        }
        .doc-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 8px;
            margin-bottom: 8px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 1.5px 3px;
            font-size: 8.5px;
            vertical-align: top;
        }
        .meta-label {
            color: #64748b;
            width: 75px;
        }
        .meta-val {
            font-weight: bold;
            color: #0f172a;
        }
        .meta-val-accent {
            font-weight: bold;
            color: #059669;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.items-table th, table.items-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            font-size: 8.5px;
        }
        table.items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .total-container {
            width: 100%;
            margin-top: 8px;
        }
        .total-table {
            width: 230px;
            float: right;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 2px 4px;
            font-size: 8.5px;
        }
        .total-label {
            color: #475569;
            text-align: right;
        }
        .total-value {
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
        }
        .grand-total-row td {
            border-top: 1.5px solid #0f172a;
            font-size: 10px;
            font-weight: bold;
            color: #047857;
            padding-top: 4px;
        }
        .debt-box {
            background-color: #fffbeb;
            border: 1px dashed #d97706;
            padding: 4px;
            border-radius: 4px;
            margin-top: 4px;
            font-size: 8px;
        }
        .signatures-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            font-size: 8px;
            color: #475569;
            vertical-align: top;
        }
        .sig-space {
            height: 35px;
        }
        .sig-name {
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
        }
        .footer-note {
            margin-top: 12px;
            font-size: 7.5px;
            color: #64748b;
            text-align: center;
            border-top: 1px dashed #e2e8f0;
            padding-top: 4px;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>

    @php
        $activeItems = $purchase->items->where('is_cancelled', false);
        $activeGross = $activeItems->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? ($i->gross_weight > 0 ? $i->gross_weight : ($i->quantity + $i->tare_weight)) : $i->gross_weight);
        $activeTare = $activeItems->sum('tare_weight');
        $activeNet = $activeItems->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? $i->quantity : $i->net_weight);
        $activeSubtotal = $activeItems->sum('subtotal');
        $discount = min((float) ($purchase->discount_amount ?? 0), $activeSubtotal);
        $rounding = (float) ($purchase->rounding_amount ?? 0);
        $activeFinal = max(0, $activeSubtotal - $discount + $rounding);
        $debtDeduction = min((float) ($purchase->debt_deduction_amount ?? 0), $activeFinal);
        $activeNetCash = max(0, $activeFinal - $debtDeduction);
        $isCancelled = $purchase->isCancelled();
    @endphp

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                <div class="company-sub">{{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam & Rongsok' }}</div>
                <div class="company-sub">Telp / WA: {{ $settings['company_phone'] ?? '0812-3456-7890' }}</div>
            </td>
            <td style="vertical-align: middle; text-align: right;">
                @if($isCancelled)
                    <span class="status-badge status-void">DIBATALKAN</span>
                @else
                    <span class="status-badge status-paid">LUNAS / DIBAYAR</span>
                @endif
                <div style="font-size: 8px; color: #64748b; margin-top: 4px;">Nota Pembelian Resmi</div>
            </td>
        </tr>
    </table>

    <!-- Document Title -->
    <div class="doc-title-row">
        <span class="doc-title">BUKTI PENERIMAAN / NOTA TIMBANG</span>
    </div>

    <!-- Metadata Box -->
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td class="meta-label">No. Nota</td>
                <td class="meta-val-accent">: {{ $purchase->invoice_number }}</td>
                <td class="meta-label">Nama Pemasok</td>
                <td class="meta-val">: {{ $purchase->supplier_name ?: 'Pemasok Umum' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Waktu / Tanggal</td>
                <td class="meta-val">: {{ \Carbon\Carbon::parse($purchase->transaction_date)->translatedFormat('d F Y, H:i') }} WIB</td>
                <td class="meta-label">No. HP</td>
                <td class="meta-val">: {{ $purchase->supplier_phone ?? ($purchase->supplier?->phone ?? '-') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Kasir / Timbang</td>
                <td class="meta-val">: {{ $purchase->user?->name ?? 'Kasir Timbang' }}</td>
                <td class="meta-label">Metode Bayar</td>
                <td class="meta-val" style="color: #0d9488;">: {{ $purchase->paymentMethod?->name ?? 'Tunai' }}</td>
            </tr>
            @if($purchase->isBankPayment())
                @if($purchase->getBankAccountNumber())
                    <tr>
                        <td class="meta-label"></td>
                        <td class="meta-val"></td>
                        <td class="meta-label">No. Rekening</td>
                        <td class="meta-val" style="font-family: 'Courier New', Courier, monospace;">: {{ $purchase->getBankAccountNumber() }}</td>
                    </tr>
                @endif
                @if($purchase->getBankAccountName())
                    <tr>
                        <td class="meta-label"></td>
                        <td class="meta-val"></td>
                        <td class="meta-label">Nama Rekening</td>
                        <td class="meta-val">: {{ $purchase->getBankAccountName() }}</td>
                    </tr>
                @endif
                @if(!$purchase->getBankAccountNumber() && !$purchase->getBankAccountName() && !empty($purchase->bank_info))
                    <tr>
                        <td class="meta-label"></td>
                        <td class="meta-val"></td>
                        <td class="meta-label">Bank Info</td>
                        <td class="meta-val">: {{ $purchase->bank_info }}</td>
                    </tr>
                @endif
            @endif
        </table>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 20px;" class="text-center">#</th>
                <th class="text-left">Nama Barang / Rongsok</th>
                <th style="width: 55px;" class="text-right">Bruto</th>
                <th style="width: 45px;" class="text-right">Tara</th>
                <th style="width: 55px;" class="text-right">Netto</th>
                <th style="width: 65px;" class="text-right">Harga Beli</th>
                <th style="width: 75px;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activeItems as $idx => $it)
                @php
                    $isPcs = ($it->unit_type === 'pcs' || $it->unit_type === 'satuan');
                    $grossVal = $isPcs ? ($it->gross_weight > 0 ? $it->gross_weight : ($it->quantity + $it->tare_weight)) : $it->gross_weight;
                    $netVal = $isPcs ? $it->quantity : $it->net_weight;
                    $unit = $it->unit_type ?: 'kg';
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $it->item_name_snapshot }}</strong>
                        @if($it->item_type_name)
                            <span style="color: #64748b; font-size: 7.5px;">({{ $it->item_type_name }})</span>
                        @endif
                    </td>
                    <td class="text-right">{{ \App\Services\FormatHelper::weight($grossVal) }} {{ $unit }}</td>
                    <td class="text-right">{{ \App\Services\FormatHelper::weight($it->tare_weight) }} {{ $unit }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ \App\Services\FormatHelper::weight($netVal) }} {{ $unit }}</td>
                    <td class="text-right">Rp {{ number_format($it->price_per_kg, 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold;">Rp {{ number_format($it->subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="color: #ef4444; font-weight: bold; padding: 10px;">
                        (Semua barang dalam transaksi ini telah dibatalkan)
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Totals Breakdown -->
    <div class="total-container">
        <table class="total-table">
            <tr>
                <td class="total-label">Total Berat Kotor:</td>
                <td class="total-value">{{ \App\Services\FormatHelper::weight($activeGross) }} kg</td>
            </tr>
            <tr>
                <td class="total-label">Total Potongan Tara:</td>
                <td class="total-value">{{ \App\Services\FormatHelper::weight($activeTare) }} kg</td>
            </tr>
            <tr style="border-top: 1px dotted #cbd5e1;">
                <td class="total-label" style="font-weight: bold;">TOTAL BERAT BERSIH:</td>
                <td class="total-value" style="color: #047857;">{{ \App\Services\FormatHelper::weight($activeNet) }} kg</td>
            </tr>
            <tr style="border-top: 1px solid #cbd5e1;">
                <td class="total-label">Subtotal:</td>
                <td class="total-value">Rp {{ number_format($activeSubtotal, 0, ',', '.') }}</td>
            </tr>
            @if($discount > 0)
                <tr>
                    <td class="total-label">Potongan Diskon:</td>
                    <td class="total-value" style="color: #b91c1c;">- Rp {{ number_format($discount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($rounding != 0)
                <tr>
                    <td class="total-label">Pembulatan:</td>
                    <td class="total-value">Rp {{ number_format($rounding, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr style="border-top: 1px solid #94a3b8; font-weight: bold;">
                <td class="total-label" style="font-size: 9px; font-weight: bold; color: #0f172a;">TOTAL NOTA:</td>
                <td class="total-value" style="font-size: 9px;">Rp {{ number_format($activeFinal, 0, ',', '.') }}</td>
            </tr>

            @if($debtDeduction > 0 || ($purchase->remaining_debt_after ?? 0) > 0)
                <tr style="border-top: 1px dashed #d97706;">
                    <td class="total-label" style="color: #b45309;">Potong Kasbon:</td>
                    <td class="total-value" style="color: #b91c1c;">- Rp {{ number_format($debtDeduction, 0, ',', '.') }}</td>
                </tr>
                @if(($purchase->remaining_debt_after ?? 0) > 0)
                    <tr>
                        <td class="total-label" style="color: #b45309;">Sisa Hutang:</td>
                        <td class="total-value" style="color: #b45309;">Rp {{ number_format($purchase->remaining_debt_after, 0, ',', '.') }}</td>
                    </tr>
                @endif
            @endif

            <tr class="grand-total-row">
                <td class="total-label" style="font-size: 10px; font-weight: bold; color: #047857;">UANG DITERIMA:</td>
                <td class="total-value" style="font-size: 10px; color: #047857;">Rp {{ number_format($activeNetCash, 0, ',', '.') }}</td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>

    <!-- Signatures -->
    <table class="signatures-table">
        <tr>
            <td>
                <div>Pemasok / Penjual,</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $purchase->supplier_name ?: 'Pemasok Umum' }}</div>
            </td>
            <td>
                <div>Kasir / Penimbang,</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $purchase->user?->name ?? 'Kasir Timbang' }}</div>
            </td>
        </tr>
    </table>

    <!-- Footer Note -->
    <div class="footer-note">
        {{ $settings['receipt_footer'] ?? 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali.' }}
        <br>
        <span style="font-size: 7px; color: #94a3b8;">{{ $settings['receipt_disclaimer'] ?? 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI' }} • Dicetak secara sistematis pada {{ now()->translatedFormat('d F Y H:i:s') }} WIB</span>
    </div>

</body>
</html>

