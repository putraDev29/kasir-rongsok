<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat_Perjanjian_{{ $debt->agreement_number ? str_replace(['/', ' '], '_', $debt->agreement_number) : $debt->debt_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 14mm 18mm 14mm 18mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #0f172a;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 8.5pt;
            color: #475569;
            margin-top: 2px;
        }
        .title-box {
            text-align: center;
            margin-bottom: 12px;
        }
        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            color: #047857;
            letter-spacing: 0.3px;
        }
        .doc-number {
            font-size: 9.5pt;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            color: #1e293b;
            margin-top: 3px;
        }
        .lead-text {
            text-align: justify;
            font-size: 9.5pt;
            margin-bottom: 8px;
        }
        table.party-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
        }
        table.party-table td {
            vertical-align: top;
            padding: 5px 8px;
            font-size: 9.5pt;
        }
        .party-title {
            font-weight: bold;
            color: #0f172a;
            width: 130px;
        }
        .article {
            margin-bottom: 8px;
            text-align: justify;
        }
        .article-title {
            font-weight: bold;
            font-size: 9.5pt;
            text-decoration: underline;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .article-body {
            font-size: 9.2pt;
            line-height: 1.4;
            color: #1e293b;
        }
        .amount-highlight {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11pt;
            font-weight: bold;
            background-color: #f1f5f9;
            padding: 2px 6px;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            display: inline-block;
            margin: 2px 0;
            color: #047857;
        }
        table.signatures {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
        }
        table.signatures td {
            text-align: center;
            vertical-align: top;
            width: 50%;
            font-size: 9.5pt;
        }
        .sig-header {
            font-weight: bold;
            font-size: 9.5pt;
            text-transform: uppercase;
            color: #0f172a;
        }
        .sig-sub {
            font-size: 8pt;
            color: #64748b;
            margin-top: 1px;
        }
        .sig-space {
            height: 52px;
        }
        .sig-materai {
            display: inline-block;
            border: 1px dashed #94a3b8;
            padding: 6px 10px;
            font-size: 7pt;
            color: #64748b;
            border-radius: 3px;
            margin-top: 6px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            font-size: 9.5pt;
            color: #0f172a;
        }
        .sig-role {
            font-size: 8.5pt;
            color: #475569;
            margin-top: 2px;
        }
        .witness-box {
            text-align: center;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px dashed #cbd5e1;
        }
        .footer-note {
            margin-top: 8px;
            text-align: center;
            font-size: 7.5pt;
            color: #94a3b8;
            border-top: 1px dotted #e2e8f0;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    <!-- Kop Surat Resmi Usaha Depo -->
    <table class="header-table">
        <tr>
            <td style="width: 70%; vertical-align: top;">
                <div class="company-name">{{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}</div>
                <div class="company-sub">
                    {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}<br>
                    Telp / WhatsApp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                </div>
            </td>
            <td style="width: 30%; vertical-align: top; text-align: right;">
                <div style="font-size: 8pt; color: #64748b; font-weight: bold; text-transform: uppercase;">DOKUMEN HUKUM RESMI</div>
                <div style="font-size: 8pt; color: #047857; font-weight: bold; margin-top: 3px;">KEMITRAAN DEPO RONGSOK</div>
            </td>
        </tr>
    </table>

    <!-- Judul Dokumen & Nomor Perjanjian -->
    <div class="title-box">
        <div class="doc-title">SURAT PERJANJIAN PINJAMAN MODAL USAHA &amp; HUTANG PIUTANG</div>
        <div class="doc-number">Nomor Perjanjian: {{ $debt->agreement_number ?: ('SPH-PPJ-' . $debt->debt_number) }}</div>
    </div>

    <!-- Kalimat Pembuka -->
    <div class="lead-text">
        Pada hari ini, <strong>{{ $debt->date->translatedFormat('l') }}</strong> tanggal <strong>{{ $debt->date->translatedFormat('d F Y') }}</strong> ({{ $debt->date->format('d/m/Y') }}), bertempat di kantor <strong>{{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong>, telah dibuat, disepakati, dan ditandatangani perjanjian pinjaman modal usaha / hutang piutang antara pihak-pihak di bawah ini:
    </div>

    <!-- Identitas Para Pihak (Owner & Peminjam) -->
    <table class="party-table">
        <tr>
            <td class="party-title">I. PIHAK PERTAMA<br><span style="font-size: 8pt; font-weight: normal; color: #64748b;">(OWNER / PEMBERI PINJAMAN)</span></td>
            <td style="width: 10px;">:</td>
            <td>
                <strong>{{ $ownerName }}</strong>, bertindak dalam jabatannya selaku <strong>OWNER / PEMILIK USAHA {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong> yang beralamat di {{ $settings['company_address'] ?? 'Sentra Rongsok' }}, selanjutnya dalam perjanjian ini disebut sebagai <strong>PIHAK PERTAMA (OWNER)</strong>.
            </td>
        </tr>
        <tr>
            <td class="party-title">II. PIHAK KEDUA<br><span style="font-size: 8pt; font-weight: normal; color: #64748b;">(PEMINJAM / PENERIMA DANA)</span></td>
            <td>:</td>
            <td>
                <strong>{{ $debt->borrower_name }}</strong>, No. HP/WhatsApp: <strong>{{ $debt->borrower_phone ?: '-' }}</strong>, bertindak selaku <strong>PEMINJAM / PENERIMA DANA MODAL</strong>@if($debt->supplier) (Terdaftar sebagai Pemasok Resmi: <strong>{{ $debt->supplier->code }}</strong> - {{ $debt->supplier->address ?: 'Sentra Pengepul' }})@else (Status: <strong>{{ ucfirst($debt->borrower_type) }}</strong>)@endif, selanjutnya dalam perjanjian ini disebut sebagai <strong>PIHAK KEDUA (PEMINJAM)</strong>.
            </td>
        </tr>
    </table>

    <div class="lead-text" style="margin-top: 4px;">
        Kedua belah pihak dengan sadar, sukarela, dan tanpa paksaan dari pihak manapun telah bersepakat mengadakan perjanjian pinjaman modal usaha dengan ketentuan pasal-pasal sebagai berikut:
    </div>

    <!-- Pasal-pasal Perjanjian -->
    <div class="article">
        <div class="article-title">Pasal 1: Jumlah Pinjaman Modal &amp; Penyerahan Dana</div>
        <div class="article-body">
            1. PIHAK PERTAMA menyerahkan uang pinjaman modal usaha kepada PIHAK KEDUA sebesar:
            <br>
            <span class="amount-highlight">{{ \App\Services\FormatHelper::rupiah($debt->initial_amount) }}</span>
            <br>
            <em>(Terbilang: <strong>{{ \App\Services\FormatHelper::terbilang($debt->initial_amount) }} Rupiah</strong>)</em>.
            <br>
            2. PIHAK KEDUA menyatakan telah menerima uang pinjaman modal tersebut secara utuh, penuh, dan sah dari PIHAK PERTAMA, dan Surat Perjanjian ini sekaligus berfungsi sebagai bukti kuitansi tanda terima penerimaan dana yang sah.
        </div>
    </div>

    <div class="article">
        <div class="article-title">Pasal 2: Tujuan Modal &amp; Mekanisme Pengembalian (Potong Timbangan)</div>
        <div class="article-body">
            1. Pinjaman modal ini dipergunakan oleh PIHAK KEDUA untuk keperluan: <strong>{{ $debt->notes ?: 'Modal perputaran operasional pembelian dan pengumpulan barang rongsok' }}</strong>.
            <br>
            2. PIHAK KEDUA berkewajiban mengembalikan pinjaman tersebut melalui pemotongan nota timbangan hasil penyetoran barang rongsok ke depo PIHAK PERTAMA secara bertahap atau melalui pembayaran angsuran tunai/transfer hingga seluruh hutang dinyatakan lunas.
            <br>
            3. Target pelunasan pinjaman modal disepakati selambat-lambatnya tanggal: <strong>{{ $debt->due_date ? $debt->due_date->translatedFormat('d F Y') : 'Sesuai kesepakatan berjalan / setiap transaksi timbangan' }}</strong>.
        </div>
    </div>

    <div class="article">
        <div class="article-title">Pasal 3: Jaminan &amp; Tanggung Jawab</div>
        <div class="article-body">
            @if($debt->guarantee_info)
                Sebagai itikad baik dan jaminan kepastian pengembalian hutang, PIHAK KEDUA menyerahkan jaminan berupa: <strong>{{ $debt->guarantee_info }}</strong> kepada PIHAK PERTAMA sampai hutang piutang ini dinyatakan lunas sepenuhnya oleh PIHAK PERTAMA.
            @else
                Pinjaman modal ini diberikan atas dasar rasa saling percaya dan komitmen kemitraan usaha, di mana PIHAK KEDUA bertanggung jawab penuh secara hukum dan pribadi untuk menyetorkan pasokan barang rongsok ke depo PIHAK PERTAMA sampai pinjaman lunas.
            @endif
        </div>
    </div>

    <div class="article">
        <div class="article-title">Pasal 4: Hak Tagih &amp; Penyelesaian Perselisihan</div>
        <div class="article-body">
            {{ $debt->agreement_notes ?: ($settings['debt_agreement_terms'] ?? 'Apabila PIHAK KEDUA lalai, berhenti menyetorkan barang rongsok, atau tidak memenuhi kewajiban pembayaran angsuran sesuai kesepakatan, maka PIHAK PERTAMA berhak menagih sisa saldo piutang modal secara penuh atau mengambil langkah penyelesaian musyawarah maupun hukum yang berlaku di Republik Indonesia.') }}
        </div>
    </div>

    <div class="lead-text" style="margin-top: 6px;">
        Demikian Surat Perjanjian Pinjaman Modal Usaha ini dibuat rangkap 2 (dua) bermaterai cukup dan masing-masing mempunyai kekuatan hukum yang sama bagi kedua belah pihak sejak ditandatangani.
    </div>

    <!-- Tanda Tangan Resmi: Owner & Peminjam -->
    <table class="signatures">
        <tr>
            <!-- Kolom Owner -->
            <td>
                <div class="sig-header">PIHAK PERTAMA (OWNER)</div>
                <div class="sig-sub">Pemberi Pinjaman Modal / Pemilik Usaha</div>
                <div class="sig-space">
                    <span class="sig-materai">Cap Stempel Depo &amp; TTD</span>
                </div>
                <div class="sig-name">{{ $ownerName }}</div>
                <div class="sig-role">Owner / Pimpinan {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</div>
            </td>

            <!-- Kolom Peminjam -->
            <td>
                <div class="sig-header">PIHAK KEDUA (PEMINJAM)</div>
                <div class="sig-sub">Peminjam Modal / Debitur Penerima Dana</div>
                <div class="sig-space">
                    <span class="sig-materai">Materai Rp 10.000 &amp; TTD</span>
                </div>
                <div class="sig-name">{{ $debt->borrower_name }}</div>
                <div class="sig-role">Peminjam (No. HP: {{ $debt->borrower_phone ?: '-' }})</div>
            </td>
        </tr>
    </table>

    <!-- Saksi / Petugas Kasir Pencatat -->
    <div class="witness-box">
        <div style="font-size: 8pt; font-weight: bold; color: #475569; text-transform: uppercase;">
            SAKSI / PETUGAS KASIR PENCATAT TRANSAKSI:
        </div>
        <div style="height: 35px;"></div>
        <div class="sig-name" style="font-size: 9pt;">
            {{ $debt->witness_name ?: ($debt->user?->name ?? 'Kasir / Petugas Timbang') }}
        </div>
        <div class="sig-role" style="font-size: 8pt;">
            {{ $debt->witness_name ? 'Saksi Perjanjian' : 'Petugas Kasir Depo' }}
        </div>
    </div>

    <div class="footer-note">
        *Dokumen ini diterbitkan secara sah oleh Sistem Kasir Depo Rongsok. No. Referensi Sistem: {{ $debt->debt_number }}
    </div>

</body>
</html>
