<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap_Penjualan_Partai_{{ ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd') }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .company-name { font-size: 15px; font-weight: bold; text-transform: uppercase; color: #0f172a; }
        .title { font-size: 12px; font-weight: bold; margin-top: 3px; color: #047857; text-transform: uppercase; }
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
    @php
        $batchKg = 0;
        $batchPcs = 0;
        foreach ($transactions as $t) {
            foreach ($t->items as $i) {
                $u = strtolower(trim($i->unit_type ?: 'kg'));
                if (in_array($u, ['pcs', 'satuan', 'unit', 'buah'])) {
                    $batchPcs += ($i->quantity > 0 ? $i->quantity : $i->weight);
                } else {
                    $batchKg += $i->weight;
                }
            }
        }
        $batchParts = [];
        if ($batchKg > 0) $batchParts[] = \App\Services\FormatHelper::weight($batchKg) . ' kg';
        if ($batchPcs > 0) $batchParts[] = \App\Services\FormatHelper::number($batchPcs) . ' pcs';
        $batchCargoDisplay = count($batchParts) > 0 ? implode(' + ', $batchParts) : '0 kg';
    @endphp
    <div class="header">
        <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
        <div class="title">REKAPITULASI PENJUALAN RONGSOK PARTAI BESAR (PABRIK)</div>
        <div class="period">Periode: {{ $startDate ? date('d/m/Y', strtotime($startDate)) : 'Semua' }} s/d {{ $endDate ? date('d/m/Y', strtotime($endDate)) : 'Semua' }} | Dicetak: {{ date('d/m/Y H:i') }} WIB</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 90px;">No. Faktur</th>
                <th style="width: 70px;">Tanggal</th>
                <th>Tujuan Pembeli / Pabrik</th>
                <th class="text-right" style="width: 80px;">Muatan / Satuan</th>
                <th class="text-right" style="width: 95px;">Total Tagihan</th>
                <th class="text-right" style="width: 90px;">Estimasi Laba</th>
                <th class="text-center" style="width: 65px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $idx => $t)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $t->invoice_number }}</td>
                    <td>{{ $t->transaction_date->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $t->buyer_name }}</strong>
                        @if($t->buyer_phone)
                            <span style="color: #64748b; font-size: 8.5px;">({{ $t->buyer_phone }})</span>
                        @endif
                        <div style="margin-top: 2px; font-size: 8px; color: #475569;">
                            @foreach($t->grouped_master_items as $gm)
                                <div>
                                    • <strong>{{ $gm->item_name }}</strong>: {{ $gm->total_quantity > 0 && ($gm->unit_type === 'pcs' || $gm->unit_type === 'satuan') ? \App\Services\FormatHelper::number($gm->total_quantity) . ' pcs' : \App\Services\FormatHelper::weight($gm->total_weight) . ' kg' }}
                                    @if($gm->has_variants)
                                        <div style="padding-left: 6px; color: #1e40af;">
                                            @foreach($gm->items as $vi)
                                                ↳ Varian: {{ $vi->item_type_name ?: 'Standar' }} ({{ $vi->formatted_quantity }} @ Rp {{ number_format($vi->sell_price_per_kg, 0, ',', '.') }})
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="text-right font-mono">{{ $t->total_cargo_display }}</td>
                    <td class="text-right font-mono font-bold">{{ \App\Services\FormatHelper::rupiah($t->final_amount) }}</td>
                    <td class="text-right font-mono" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($t->estimated_profit) }}</td>
                    <td class="text-center" style="font-weight: bold; font-size: 8.5px;">
                        {{ $t->isCancelled() ? 'DIBATALKAN' : 'SELESAI' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #94a3b8;">Tidak ada data penjualan partai.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL KESELURUHAN:</td>
                <td class="text-right font-mono">{{ $batchCargoDisplay }}</td>
                <td class="text-right font-mono font-bold">{{ \App\Services\FormatHelper::rupiah($totalSales) }}</td>
                <td class="text-right font-mono" style="color: #047857;">{{ \App\Services\FormatHelper::rupiah($totalProfit) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div class="signature-box">
            <p>Penanggung Jawab Penjualan,</p>
            <div style="height: 56px; text-align: center;">
                @include('components.signature-stamp', ['isPdf' => true, 'height' => 48, 'stampSize' => 56])
            </div>
            <p><strong>( {{ \App\Models\User::where('role', 'super_admin')->first()?->name ?? 'Owner Depo' }} )</strong></p>
            <p style="color: #64748b;">Administrasi / Owner Depo</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>

