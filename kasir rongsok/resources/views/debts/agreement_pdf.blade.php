<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat_Perjanjian_{{ $debt->agreement_number ? str_replace(['/', ' '], '_', $debt->agreement_number) : $debt->debt_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 8mm 13mm 8mm 13mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #0f172a;
            line-height: 1.32;
            margin: 0;
            padding: 0;
        }
        .page-sheet {
            height: 100%;
            position: relative;
        }
        .page-break {
            page-break-before: always;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 12.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.3px;
        }
        .company-sub {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 1px;
            line-height: 1.25;
        }
        .sheet-badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 3px;
            letter-spacing: 0.3px;
        }
        .sheet-badge.original {
            background-color: #047857;
            color: #ffffff;
            border: 1px solid #065f46;
        }
        .sheet-badge.copy {
            background-color: #1e40af;
            color: #ffffff;
            border: 1px solid #1e3a8a;
        }
        .title-box {
            text-align: center;
            margin-bottom: 6px;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            color: #0f172a;
            letter-spacing: 0.3px;
        }
        .doc-number {
            font-size: 8.5pt;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            color: #1e293b;
            margin-top: 2px;
        }
        .category-pill {
            display: inline-block;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 1px 6px;
            border-radius: 3px;
            margin-top: 2px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
        }
        .lead-text {
            text-align: justify;
            font-size: 8pt;
            margin-bottom: 5px;
            line-height: 1.3;
        }
        table.party-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
        }
        table.party-table td {
            vertical-align: top;
            padding: 3.5px 6px;
            font-size: 8pt;
            line-height: 1.25;
        }
        .party-title {
            font-weight: bold;
            color: #0f172a;
            width: 115px;
        }
        .article {
            margin-bottom: 4px;
            text-align: justify;
        }
        .article-title {
            font-weight: bold;
            font-size: 8.5pt;
            text-decoration: underline;
            color: #0f172a;
            margin-bottom: 1.5px;
        }
        .article-body {
            font-size: 8pt;
            line-height: 1.28;
            color: #1e293b;
        }
        .amount-highlight {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9.5pt;
            font-weight: bold;
            background-color: #f1f5f9;
            padding: 1px 5px;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            display: inline-block;
            margin: 1px 0;
            color: #047857;
        }
        table.signatures {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
        }
        table.signatures td {
            text-align: center;
            vertical-align: top;
            width: 50%;
            font-size: 8pt;
        }
        .sig-header {
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
            color: #0f172a;
        }
        .sig-sub {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 1px;
        }
        .sig-space {
            height: 40px;
        }
        .sig-materai {
            display: inline-block;
            border: 1px dashed #94a3b8;
            padding: 3px 8px;
            font-size: 6.5pt;
            color: #64748b;
            border-radius: 3px;
            margin-top: 4px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            font-size: 8.5pt;
            color: #0f172a;
        }
        .sig-role {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 1px;
        }
        .witness-box {
            text-align: center;
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px dashed #cbd5e1;
        }
        .footer-note {
            margin-top: 5px;
            text-align: center;
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1px dotted #e2e8f0;
            padding-top: 2px;
        }
    </style>
</head>
<body>

@php
    $category = $debt->loan_category ?? 'modal';
    $sheets = [
        [
            'copy_number' => 1,
            'badge_class' => 'original',
            'badge_text'  => 'LEMBAR 1: ASLI (UNTUK OWNER / PIHAK PERTAMA)',
            'role_first'  => 'PIHAK PERTAMA (OWNER / PEMBERI MODAL)',
            'role_second' => 'PIHAK KEDUA (PENERIMA MODAL KERJA)',
        ],
        [
            'copy_number' => 2,
            'badge_class' => 'copy',
            'badge_text'  => 'LEMBAR 2: SALINAN (UNTUK PEMINJAM / PIHAK KEDUA)',
            'role_first'  => 'PIHAK PERTAMA (OWNER / PEMBERI MODAL)',
            'role_second' => 'PIHAK KEDUA (PENERIMA MODAL KERJA)',
        ],
    ];

    if ($category === 'hutang') {
        $sheets[0]['role_first'] = 'PIHAK PERTAMA (PEMBERI PINJAMAN / KREDITUR)';
        $sheets[0]['role_second'] = 'PIHAK KEDUA (PEMINJAM / DEBITUR)';
        $sheets[1]['role_first'] = 'PIHAK PERTAMA (PEMBERI PINJAMAN / KREDITUR)';
        $sheets[1]['role_second'] = 'PIHAK KEDUA (PEMINJAM / DEBITUR)';
    } elseif ($category === 'komitmen') {
        $sheets[0]['role_first'] = 'PIHAK PERTAMA (OWNER DEPO / PENERIMA PASOKAN)';
        $sheets[0]['role_second'] = 'PIHAK KEDUA (PENYEDIA PASOKAN / PEMASOK)';
        $sheets[1]['role_first'] = 'PIHAK PERTAMA (OWNER DEPO / PENERIMA PASOKAN)';
        $sheets[1]['role_second'] = 'PIHAK KEDUA (PENYEDIA PASOKAN / PEMASOK)';
    }
@endphp

@foreach($sheets as $index => $sheet)
    @if($index > 0)
        <div class="page-break"></div>
    @endif

    <div class="page-sheet">
        <!-- Kop Surat Resmi Usaha Depo -->
        <table class="header-table">
            <tr>
                <td style="width: 65%; vertical-align: top;">
                    <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                    <div class="company-sub">
                        {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}<br>
                        Telp / WhatsApp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                    </div>
                </td>
                <td style="width: 35%; vertical-align: top; text-align: right;">
                    <div class="sheet-badge {{ $sheet['badge_class'] }}">
                        {{ $sheet['badge_text'] }}
                    </div>
                    <div style="font-size: 7.5pt; color: #64748b; font-weight: bold; margin-top: 3px;">
                        DOKUMEN RESMI DEPO RONGSOK
                    </div>
                </td>
            </tr>
        </table>

        <!-- Judul Dokumen & Nomor Perjanjian -->
        <div class="title-box">
            <div class="doc-title">{{ $debt->getAgreementTitle() }}</div>
            <div class="doc-number">Nomor: {{ $debt->agreement_number ?: ('SPH-SBL-' . $debt->debt_number) }} &bull; Ref: {{ $debt->debt_number }}</div>
            <div class="category-pill">Kategori: {{ $debt->getCategoryLabel() }}</div>
        </div>

        <!-- Kalimat Pembuka -->
        <div class="lead-text">
            Pada hari ini, <strong>{{ $debt->date->translatedFormat('l') }}</strong> tanggal <strong>{{ $debt->date->translatedFormat('d F Y') }}</strong> ({{ $debt->date->format('d/m/Y') }}), bertempat di kantor <strong>{{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong>, telah dibuat dan ditandatangani perjanjian antara para pihak:
        </div>

        <!-- Identitas Para Pihak (Owner & Peminjam) -->
        <table class="party-table">
            <tr>
                <td class="party-title">I. {{ $sheet['role_first'] }}</td>
                <td style="width: 8px;">:</td>
                <td>
                    <strong>{{ $ownerName }}</strong>, bertindak selaku <strong>OWNER / PIMPINAN USAHA {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong> beralamat di {{ $settings['company_address'] ?? 'Sentra Rongsok' }}, selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>.
                </td>
            </tr>
            <tr>
                <td class="party-title">II. {{ $sheet['role_second'] }}</td>
                <td>:</td>
                <td>
                    <strong>{{ $debt->borrower_name }}</strong>, No. HP: <strong>{{ $debt->borrower_phone ?: '-' }}</strong>@if($debt->supplier) (Pemasok Terdaftar: <strong>{{ $debt->supplier->code }}</strong> - {{ $debt->supplier->address ?: 'Sentra Pengepul' }})@else (Status: <strong>{{ ucfirst($debt->borrower_type) }}</strong>)@endif, selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.
                </td>
            </tr>
        </table>

        <div class="lead-text">
            Kedua belah pihak dengan sadar, sukarela, dan tanpa paksaan telah bersepakat mengadakan perjanjian dengan ketentuan pasal-pasal sebagai berikut:
        </div>

        <!-- Pasal-pasal Perjanjian Sesuai Kategori (Modal, Hutang, Komitmen) -->
        @if($category === 'modal')
            <!-- KASUS 1: MODAL USAHA -->
            <div class="article">
                <div class="article-title">Pasal 1: Jumlah Pinjaman Modal &amp; Penyerahan Dana</div>
                <div class="article-body">
                    1. PIHAK PERTAMA menyerahkan dana modal usaha kepada PIHAK KEDUA sebesar:
                    <span class="amount-highlight">{{ \App\Services\FormatHelper::rupiah($debt->initial_amount) }}</span>
                    <em>(Terbilang: <strong>{{ \App\Services\FormatHelper::terbilang($debt->initial_amount) }} Rupiah</strong>)</em>.<br>
                    2. PIHAK KEDUA telah menerima uang modal kerja tersebut secara utuh dan sah untuk perputaran pembelian serta pengumpulan material scrap &amp; daur ulang. Surat Perjanjian ini sah sebagai tanda terima penerimaan modal.
                </div>
            </div>

            <div class="article">
                <div class="article-title">Pasal 2: Pengembalian Modal &amp; Pemotongan Timbangan</div>
                <div class="article-body">
                    1. Keperluan modal: <strong>{{ $debt->notes ?: 'Modal perputaran operasional pembelian material scrap & daur ulang' }}</strong>.<br>
                    2. PIHAK KEDUA berkewajiban mengembalikan pinjaman modal melalui pemotongan bertahap nota timbangan saat menyetorkan material scrap ke depo PIHAK PERTAMA atau setoran tunai/transfer hingga seluruh saldo lunas.<br>
                    3. Target pelunasan disepakati selambat-lambatnya tanggal: <strong>{{ $debt->due_date ? $debt->due_date->translatedFormat('d F Y') : 'Sesuai kesepakatan berjalan / setiap transaksi timbangan' }}</strong>.
                </div>
            </div>

            <div class="article">
                <div class="article-title">Pasal 3: Jaminan &amp; Tanggung Jawab Perputaran</div>
                <div class="article-body">
                    @if($debt->guarantee_info)
                        Sebagai jaminan kepastian perputaran dan pengembalian modal, PIHAK KEDUA menyerahkan agunan berupa: <strong>{{ $debt->guarantee_info }}</strong> kepada PIHAK PERTAMA sampai modal dinyatakan lunas sepenuhnya.
                    @else
                        Pinjaman modal ini didasari kepercayaan kemitraan usaha. PIHAK KEDUA bertanggung jawab penuh secara hukum dan pribadi untuk memutarkan dana pembelian dan menyetorkan pasokan scrap ke depo PIHAK PERTAMA hingga lunas.
                    @endif
                </div>
            </div>

            <div class="article">
                <div class="article-title">Pasal 4: Hak Tagih &amp; Penyelesaian Perselisihan</div>
                <div class="article-body">
                    {{ $debt->agreement_notes ?: ($settings['debt_agreement_terms'] ?? 'Apabila PIHAK KEDUA lalai, berhenti menyetorkan material scrap, atau menyalahgunakan modal kerja, maka PIHAK PERTAMA berhak menagih dan menarik kembali seluruh sisa modal secara seketika melalui musyawarah maupun jalur hukum Republik Indonesia.') }}
                </div>
            </div>

        @elseif($category === 'hutang')
            <!-- KASUS 2: HUTANG PIUTANG / PINJAMAN BIASA -->
            <div class="article">
                <div class="article-title">Pasal 1: Pokok Hutang Pinjaman &amp; Tanda Terima Sah</div>
                <div class="article-body">
                    1. PIHAK PERTAMA meminjamkan uang tunai kepada PIHAK KEDUA sebesar:
                    <span class="amount-highlight">{{ \App\Services\FormatHelper::rupiah($debt->initial_amount) }}</span>
                    <em>(Terbilang: <strong>{{ \App\Services\FormatHelper::terbilang($debt->initial_amount) }} Rupiah</strong>)</em>.<br>
                    2. PIHAK KEDUA mengakui telah menerima uang pinjaman hutang tersebut secara utuh dan sah. Surat Perjanjian ini berlaku mengikat sebagai kuitansi tanda terima penerimaan uang yang sah.
                </div>
            </div>

            <div class="article">
                <div class="article-title">Pasal 2: Kewajiban Pelunasan &amp; Jatuh Tempo</div>
                <div class="article-body">
                    1. Keperluan pinjaman: <strong>{{ $debt->notes ?: 'Pinjaman dana kebutuhan operasional / kasbon' }}</strong>.<br>
                    2. PIHAK KEDUA berkewajiban melunasi pinjaman hutang paling lambat tanggal: <strong>{{ $debt->due_date ? $debt->due_date->translatedFormat('d F Y') : 'Sesuai kesepakatan tempo bersama' }}</strong>.<br>
                    3. Pembayaran hutang dapat diangsur berkala, dipotongkan langsung saat timbangan barang, atau disetor tunai/transfer lunas.
                </div>
            </div>

            <div class="article">
                <div class="article-title">Pasal 3: Jaminan / Agunan Hutang</div>
                <div class="article-body">
                    @if($debt->guarantee_info)
                        Sebagai jaminan pelunasan hutang, PIHAK KEDUA menyerahkan agunan sah berupa: <strong>{{ $debt->guarantee_info }}</strong> yang dipegang oleh PIHAK PERTAMA hingga hutang piutang ini lunas sepenuhnya.
                    @else
                        Pinjaman hutang ini diberikan atas dasar itikad baik dan tanggung jawab pribadi penuh PIHAK KEDUA secara hukum perdata Republik Indonesia untuk melunasi seluruh kewajibannya tepat waktu.
                    @endif
                </div>
            </div>

            <div class="article">
                <div class="article-title">Pasal 4: Wanprestasi &amp; Upaya Hukum</div>
                <div class="article-body">
                    {{ $debt->agreement_notes ?: ($settings['debt_agreement_terms'] ?? 'Apabila PIHAK KEDUA terlambat, lalai, atau tidak melunasi hutang sesuai batas waktu yang disepakati, maka PIHAK PERTAMA berhak menagih seluruh sisa hutang secara seketika dan mengambil langkah hukum perdata maupun pidana sesuai undang-undang.') }}
                </div>
            </div>

        @else
            <!-- KASUS 3: KOMITMEN PASOKAN SCRAP -->
            <div class="article">
                <div class="article-title">Pasal 1: Dana Uang Muka Komitmen Pasokan</div>
                <div class="article-body">
                    1. PIHAK PERTAMA menyerahkan dana komitmen ikatan pasokan barang kepada PIHAK KEDUA sebesar:
                    <span class="amount-highlight">{{ \App\Services\FormatHelper::rupiah($debt->initial_amount) }}</span>
                    <em>(Terbilang: <strong>{{ \App\Services\FormatHelper::terbilang($debt->initial_amount) }} Rupiah</strong>)</em>.<br>
                    2. PIHAK KEDUA menerima dana tersebut sebagai uang muka komitmen kemitraan suplai barang rongsok dan bahan daur ulang ke depo PIHAK PERTAMA. Surat ini sah sebagai tanda terima dana komitmen.
                </div>
            </div>

            <div class="article">
                <div class="article-title">Pasal 2: Kewajiban Pasokan &amp; Pemotongan Komitmen</div>
                <div class="article-body">
                    1. Target komitmen pasokan: <strong>{{ $debt->notes ?: 'Komitmen pasokan material besi, logam, kardus, dan plastik secara berkelanjutan' }}</strong>.<br>
                    2. PIHAK KEDUA berkewajiban menyuplai dan menjual material scrap secara prioritas/eksklusif ke depo PIHAK PERTAMA hingga tanggal: <strong>{{ $debt->due_date ? $debt->due_date->translatedFormat('d F Y') : 'Target nilai komitmen impas' }}</strong>.<br>
                    3. Nilai komitmen diperhitungkan dan dipotongkan bertahap dari setiap nota timbangan yang disetorkan hingga saldo komitmen tuntas.
                </div>
            </div>

            <div class="article">
                <div class="article-title">Pasal 3: Larangan Pengalihan Pasokan &amp; Jaminan</div>
                <div class="article-body">
                    @if($debt->guarantee_info)
                        Sebagai jaminan kesungguhan pasokan, PIHAK KEDUA menyerahkan jaminan berupa: <strong>{{ $debt->guarantee_info }}</strong> yang disimpan PIHAK PERTAMA selama masa komitmen berlangsung.
                    @else
                        Selama ikatan komitmen ini berjalan, PIHAK KEDUA dilarang mengalihkan, menyetorkan, atau menjual barang scrap komitmen ke lapak/pihak lain tanpa izin tertulis dari PIHAK PERTAMA.
                    @endif
                </div>
            </div>

            <div class="article">
                <div class="article-title">Pasal 4: Sanksi Wanprestasi &amp; Pengembalian Dana</div>
                <div class="article-body">
                    {{ $debt->agreement_notes ?: ($settings['debt_agreement_terms'] ?? 'Apabila PIHAK KEDUA ingkar janji, berhenti menyuplai scrap, atau mengalihkan barang ke tempat lain, maka seluruh sisa dana komitmen wajib dikembalikan seketika secara utuh kepada PIHAK PERTAMA beserta biaya kompensasi yang disepakati.') }}
                </div>
            </div>
        @endif

        <div class="lead-text" style="margin-top: 3px;">
            Demikian Surat Perjanjian ini dibuat rangkap 2 (dua) bermaterai cukup: Lembar 1 untuk Pihak Pertama (Owner) dan Lembar 2 untuk Pihak Kedua (Peminjam/Penerima), keduanya berkekuatan hukum sama.
        </div>

        <!-- Tanda Tangan Resmi: Owner & Peminjam -->
        <table class="signatures">
            <tr>
                <!-- Kolom Owner -->
                <td>
                    <div class="sig-header">{{ $sheet['role_first'] }}</div>
                    <div class="sig-sub">Pemilik Usaha / Depo</div>
                    <div class="sig-space">
                        @if(\App\Models\BusinessSetting::hasOwnerSignature())
                            <img src="{{ \App\Models\BusinessSetting::ownerSignatureBase64() }}" alt="TTD Owner" style="max-height: 38px; max-width: 125px; margin: 0 auto; display: block;">
                        @else
                            <span class="sig-materai">Cap Stempel Depo &amp; TTD</span>
                        @endif
                    </div>
                    <div class="sig-name">{{ $ownerName }}</div>
                    <div class="sig-role">Owner {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</div>
                </td>

                <!-- Kolom Peminjam / Penerima -->
                <td>
                    <div class="sig-header">{{ $sheet['role_second'] }}</div>
                    <div class="sig-sub">Penerima Dana / Mitra</div>
                    <div class="sig-space">
                        <span class="sig-materai">Materai Rp 10.000 &amp; TTD</span>
                    </div>
                    <div class="sig-name">{{ $debt->borrower_name }}</div>
                    <div class="sig-role">Penerima (HP: {{ $debt->borrower_phone ?: '-' }})</div>
                </td>
            </tr>
        </table>

        <!-- Saksi / Petugas Kasir Pencatat -->
        <div class="witness-box">
            <span style="font-size: 7.5pt; font-weight: bold; color: #475569; text-transform: uppercase;">
                SAKSI / PETUGAS PENCATAT:
            </span>
            <span class="sig-name" style="font-size: 8pt; margin-left: 6px;">
                {{ $debt->witness_name ?: ($debt->user?->name ?? 'Petugas Kasir Depo') }}
            </span>
            <span class="sig-role" style="font-size: 7.5pt; margin-left: 4px;">
                ({{ $debt->witness_name ? 'Saksi Kesepakatan' : 'Kasir Timbang' }})
            </span>
        </div>

        <div class="footer-note">
            {{ $sheet['badge_text'] }} &bull; Diterbitkan oleh Sistem Kasir Depo Rongsok &bull; No. Referensi: {{ $debt->debt_number }}
        </div>
    </div>
@endforeach

</body>
</html>