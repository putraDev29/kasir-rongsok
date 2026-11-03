<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu_Member_{{ $supplier->member_number }}</title>
    <style>
        @page {
            size: a6 landscape;
            margin: 6mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .card {
            border: 2px solid #0f172a;
            border-radius: 8px;
            padding: 10px 14px;
            background-color: #0f172a;
            color: #ffffff;
            margin-bottom: 8px;
        }
        .platinum { background-color: #1e1b4b; border-color: #818cf8; }
        .gold { background-color: #451a03; border-color: #f59e0b; }
        .silver { background-color: #1e293b; border-color: #94a3b8; }
        .bronze { background-color: #292524; border-color: #b45309; }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #ffffff;
        }
        .header-subtitle {
            font-size: 8px;
            color: #94a3b8;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f59e0b;
            color: #000000;
        }
        .badge-platinum { background-color: #c084fc; color: #ffffff; }
        .badge-gold { background-color: #fbbf24; color: #451a03; }
        .badge-silver { background-color: #e2e8f0; color: #0f172a; }
        .badge-bronze { background-color: #d97706; color: #ffffff; }

        .chip {
            width: 32px;
            height: 22px;
            background-color: #fbbf24;
            border: 1px solid #d97706;
            border-radius: 4px;
        }
        .member-num {
            font-family: 'Courier New', Courier, monospace;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #ffffff;
        }
        .supplier-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #ffffff;
        }
        .stats-box {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            margin-top: 6px;
            color: #1e293b;
        }
        .stat-item {
            text-align: center;
            padding: 4px;
        }
        .stat-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .stat-val {
            font-size: 11px;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
        }
        .reward-box {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            padding: 6px 8px;
            margin-top: 6px;
            font-size: 9px;
            color: #065f46;
        }
        .footer-note {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.2;
        }
    </style>
</head>
<body>

    <!-- CARD FRONT -->
    <div class="card {{ strtolower($supplier->tier) }}">
        <table>
            <tr>
                <td style="vertical-align: top;">
                    <div class="header-title">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                    <div class="header-subtitle">VIP SUPPLIER MEMBERSHIP CARD</div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <span class="badge badge-{{ strtolower($supplier->tier) }}">
                        ★ {{ strtoupper($supplier->tier) }}
                    </span>
                    <div style="font-size: 9px; color: #fbbf24; margin-top: 2px;">
                        {{ str_repeat('★', $supplier->rating_stars) }} ({{ $supplier->rating_stars }}/5)
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px 0 6px 0;">
                    <div class="chip"></div>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <div style="font-size: 8px; color: #94a3b8; text-transform: uppercase;">ID Anggota Pemasok</div>
                    <div class="member-num">{{ $supplier->member_number }}</div>
                </td>
            </tr>
            <tr>
                <td style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 6px;">
                    <div style="font-size: 8px; color: #94a3b8; text-transform: uppercase;">Nama Pemasok:</div>
                    <div class="supplier-name">{{ $supplier->name }}</div>
                    <div style="font-size: 8.5px; color: #cbd5e1;">{{ $supplier->phone ?: 'Terdaftar' }} • Bergabung: {{ $supplier->join_date ? $supplier->join_date->format('d/m/Y') : ($supplier->created_at ? $supplier->created_at->format('d/m/Y') : '-') }}</div>
                </td>
                <td style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 6px; text-align: right; vertical-align: bottom;">
                    <div style="font-size: 8.5px; color: #cbd5e1;">Total Muatan: <strong>{{ \App\Services\FormatHelper::weight($supplier->total_weight) }} kg</strong></div>
                    <div style="font-size: 10px; font-weight: bold; color: #fbbf24; margin-top: 2px;">
                        🎁 {{ number_format($supplier->points, 0, ',', '.') }} Poin Reward
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- CARD BACK / DETAIL STATS & REWARDS -->
    <div class="stats-box">
        <table style="margin-bottom: 4px;">
            <tr>
                <td style="font-weight: bold; font-size: 9px; text-transform: uppercase; color: #0f172a;">
                    Akumulasi Penjualan & Loyalitas
                </td>
                <td style="text-align: right; font-size: 8.5px; color: #64748b;">
                    Rating: <strong>{{ $supplier->rating_stars }} Bintang</strong> | Tier: <strong>{{ $supplier->tier }}</strong>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="stat-item" style="border: 1px solid #e2e8f0; width: 33%;">
                    <div class="stat-label">Total Berat Setor</div>
                    <div class="stat-val" style="color: #047857;">{{ \App\Services\FormatHelper::weight($supplier->total_weight) }} kg</div>
                    <div style="font-size: 7.5px; color: #64748b;">{{ $supplier->total_transactions_count }}x Penimbangan</div>
                </td>
                <td class="stat-item" style="border: 1px solid #e2e8f0; width: 34%;">
                    <div class="stat-label">Total Omzet Penjualan</div>
                    <div class="stat-val" style="color: #0f172a;">{{ \App\Services\FormatHelper::rupiah($supplier->total_amount) }}</div>
                    <div style="font-size: 7.5px; color: #64748b;">Akumulasi Depo</div>
                </td>
                <td class="stat-item" style="border: 1px solid #e2e8f0; width: 33%; background-color: #fef3c7;">
                    <div class="stat-label" style="color: #92400e;">Poin Member</div>
                    <div class="stat-val" style="color: #b45309;">{{ number_format($supplier->points, 0, ',', '.') }} Poin</div>
                    <div style="font-size: 7.5px; color: #92400e;">1 Poin / Rp 100rb</div>
                </td>
            </tr>
        </table>

        <div class="reward-box">
            <strong>🎁 Hak Reward dari Owner:</strong><br>
            {{ $supplier->eligible_reward }}
        </div>

        <div class="footer-note">
            • Kartu ini adalah identitas resmi pemasok/pengepul di <strong>{{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong>.<br>
            • Tunjukkan kartu ini saat penimbangan untuk mendapatkan poin reward, harga prioritas, dan fasilitas kasbon modal.<br>
            Alamat Depo: {{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam' }} | Telp: {{ $settings['company_phone'] ?? '-' }}
        </div>
    </div>

</body>
</html>

