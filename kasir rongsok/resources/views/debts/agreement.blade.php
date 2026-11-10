<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat_Perjanjian_{{ $debt->agreement_number ? str_replace(['/', ' '], '_', $debt->agreement_number) : $debt->debt_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; padding: 0 !important; }
            .print-container { box-shadow: none !important; border: none !important; padding: 0 !important; max-width: 100% !important; margin-bottom: 0 !important; }
            .print-page-break { page-break-before: always !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center font-serif text-slate-900" x-data>

    <!-- Top Action Bar -->
    <div class="no-print w-full max-w-4xl mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3 font-sans">
        <a href="{{ route('debts.show', $debt) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Detail Pinjaman</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 text-xs font-bold rounded-lg border {{ $debt->getCategoryBadgeClass() }}">
                {{ $debt->getCategoryLabel() }}
            </span>

            <button type="button"
               onclick="openPdfPreview('{{ route('debts.agreement-pdf', $debt) }}', 'Surat Perjanjian #{{ $debt->agreement_number ?: $debt->debt_number }}', '{{ $debt->borrower_phone }}', '{{ $debt->borrower_name }}', 'Berikut Surat Perjanjian Hutang Modal')"
               class="px-3.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
               title="Pratinjau Dokumen PDF Surat Perjanjian">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-400"></i>
                <span>Preview PDF SPH</span>
            </button>

            <button type="button"
                    @click="openWaPdfModal({
                        title: 'Kirim Surat Perjanjian PDF',
                        subtitle: 'Kirim berkas dokumen PDF Surat Perjanjian ke WhatsApp peminjam (2 Lembar: Asli & Salinan)',
                        phone: '{{ $debt->borrower_phone }}',
                        filename: 'SPH_' + ('{{ str_replace(['/', ' '], '_', $debt->agreement_number ?: $debt->debt_number) }}') + '.pdf',
                        fileDescription: 'Dokumen PDF Resmi Surat Perjanjian (A4 - 2 Lembar)',
                        printUrl: '{{ route('debts.agreement', $debt) }}',
                        pdfUrl: '{{ route('debts.agreement-pdf', $debt) }}',
                        previewUrl: '{{ route('debts.agreement-wa-preview', $debt) }}',
                        sendUrl: '{{ route('debts.agreement-wa', $debt) }}',
                        submitButtonText: 'Kirim SPH PDF via WA'
                    })"
                    class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors"
                    title="Kirim Surat Perjanjian PDF ke WhatsApp">
                <x-whatsapp-icon class="w-3.5 h-3.5" />
                <span>Kirim WA (PDF)</span>
            </button>

            <button onclick="window.print()" class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak 2 Lembar (A4)</span>
            </button>
        </div>
    </div>

@php
    $category = $debt->loan_category ?? 'modal';
    $commodityTerm = $settings['commodity_term'] ?? \App\Models\BusinessSetting::commodityTerm();
    $sheets = [
        [
            'num' => 1,
            'badge' => 'LEMBAR 1: ASLI (UNTUK OWNER / PIHAK PERTAMA)',
            'badge_color' => 'bg-emerald-700 text-white border-emerald-800',
            'role_first' => 'PIHAK PERTAMA (OWNER / PEMBERI MODAL)',
            'role_second' => 'PIHAK KEDUA (PENERIMA MODAL KERJA)',
        ],
        [
            'num' => 2,
            'badge' => 'LEMBAR 2: SALINAN (UNTUK PEMINJAM / PIHAK KEDUA)',
            'badge_color' => 'bg-blue-700 text-white border-blue-800',
            'role_first' => 'PIHAK PERTAMA (OWNER / PEMBERI MODAL)',
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

@foreach($sheets as $idx => $sheet)
    <!-- Legal Agreement Paper Sheet (A4 size format) -->
    <div class="print-container {{ $idx > 0 ? 'print-page-break mt-8 sm:mt-12' : '' }} bg-white w-full max-w-4xl p-8 sm:p-12 rounded-xl shadow-md border border-slate-200 space-y-4 leading-relaxed relative">
        
        <!-- Header / Kop Depo -->
        <div class="border-b-2 border-slate-900 pb-3 flex justify-between items-start font-sans">
            <div>
                <h1 class="text-xl font-black uppercase tracking-wider text-slate-900">
                    {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
                </h1>
                <p class="text-xs text-slate-600 mt-0.5">
                    {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}<br>
                    Telp / WhatsApp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                </p>
            </div>
            <div class="text-right flex flex-col items-end gap-1">
                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ $sheet['badge_color'] }}">
                    {{ $sheet['badge'] }}
                </span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">DOKUMEN RESMI DEPO {{ strtoupper($commodityTerm) }}</span>
            </div>
        </div>

        <div class="text-center pt-1 font-sans">
            <h2 class="text-base sm:text-lg font-black underline uppercase tracking-tight text-slate-900">
                {{ $debt->getAgreementTitle() }}
            </h2>
            <p class="text-xs font-mono font-bold text-slate-800 mt-1">
                Nomor: {{ $debt->agreement_number ?: ('SPH-SBL-' . $debt->debt_number) }} &bull; Ref: {{ $debt->debt_number }}
            </p>
            <span class="inline-block px-2 py-0.5 mt-1 rounded text-[11px] font-bold {{ $debt->getCategoryBadgeClass() }}">
                Kategori: {{ $debt->getCategoryLabel() }}
            </span>
        </div>

        <!-- Opening Paragraph -->
        <p class="text-xs sm:text-sm text-justify">
            Pada hari ini, <strong>{{ $debt->date->translatedFormat('l') }}</strong> tanggal <strong>{{ $debt->date->translatedFormat('d F Y') }}</strong> ({{ $debt->date->format('d/m/Y') }}), bertempat di kantor <strong>{{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong>, telah dibuat, disepakati, dan ditandatangani perjanjian antara para pihak di bawah ini:
        </p>

        <!-- Parties Details (Owner & Peminjam) -->
        <div class="space-y-2 text-xs sm:text-sm bg-slate-50 p-3.5 rounded-lg border border-slate-200">
            <div class="grid grid-cols-[140px_10px_1fr] items-start">
                <span class="font-bold text-slate-900">I. {{ $sheet['role_first'] }}</span>
                <span>:</span>
                <div>
                    <strong>{{ $ownerName }}</strong>, bertindak selaku <strong>OWNER / PIMPINAN USAHA {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong> yang beralamat di {{ $settings['company_address'] ?? 'Sentra Rongsok' }}, selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>.
                </div>
            </div>

            <div class="grid grid-cols-[140px_10px_1fr] items-start pt-1.5 border-t border-slate-200">
                <span class="font-bold text-slate-900">II. {{ $sheet['role_second'] }}</span>
                <span>:</span>
                <div>
                    <strong>{{ $debt->borrower_name }}</strong>, No. HP/WhatsApp: <strong>{{ $debt->borrower_phone ?: '-' }}</strong>@if($debt->supplier) (Terdaftar sebagai Pemasok Resmi: <strong>{{ $debt->supplier->code }}</strong> - {{ $debt->supplier->address ?: 'Sentra Pengepul' }})@else (Status: <strong>{{ ucfirst($debt->borrower_type) }}</strong>)@endif, selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.
                </div>
            </div>
        </div>

        <p class="text-xs sm:text-sm text-justify">
            Kedua belah pihak dengan sadar, sukarela, dan tanpa paksaan telah bersepakat mengadakan perjanjian dengan ketentuan pasal-pasal sebagai berikut:
        </p>

        <!-- Articles Sesuai Kategori -->
        <div class="space-y-3 text-xs sm:text-sm text-justify">
            @if($category === 'modal')
                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 1: Jumlah Pinjaman Modal &amp; Penyerahan Dana</h4>
                    <p class="mt-0.5 leading-relaxed">
                        1. PIHAK PERTAMA menyerahkan uang pinjaman modal usaha kepada PIHAK KEDUA sebesar:
                        <span class="font-mono font-bold text-sm text-emerald-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-300 inline-block my-1">
                            {{ \App\Services\FormatHelper::rupiah($debt->initial_amount) }}
                        </span>
                        <em>(Terbilang: <strong>{{ \App\Services\FormatHelper::terbilang($debt->initial_amount) }} Rupiah</strong>)</em>.<br>
                        2. PIHAK KEDUA menyatakan telah menerima uang pinjaman modal tersebut secara utuh dan sah untuk dipergunakan sebagai modal operasional perputaran pembelian dan pengumpulan {{ strtolower($commodityTerm) }} &amp; daur ulang. Surat Perjanjian ini sah sebagai tanda terima kuitansi penerimaan dana.
                    </p>
                </div>

                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 2: Tujuan Modal &amp; Mekanisme Pengembalian (Potong Timbangan)</h4>
                    <p class="mt-0.5 leading-relaxed">
                        1. Pinjaman modal ini dipergunakan oleh PIHAK KEDUA untuk: <strong>{{ $debt->notes ?: ('Modal perputaran operasional pembelian dan pengumpulan ' . strtolower($commodityTerm) . ' & daur ulang') }}</strong>.<br>
                        2. PIHAK KEDUA berkewajiban mengembalikan pinjaman modal melalui pemotongan bertahap nota timbangan saat menyetorkan {{ strtolower($commodityTerm) }} ke depo PIHAK PERTAMA atau melalui pembayaran angsuran tunai/transfer hingga seluruh saldo lunas.<br>
                        3. Target pelunasan disepakati selambat-lambatnya tanggal: <strong>{{ $debt->due_date ? $debt->due_date->translatedFormat('d F Y') : 'Sesuai kesepakatan berjalan / setiap transaksi timbangan' }}</strong>.
                    </p>
                </div>

                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 3: Jaminan &amp; Tanggung Jawab Operasional</h4>
                    <p class="mt-0.5 leading-relaxed">
                        @if($debt->guarantee_info)
                            Sebagai jaminan kepastian perputaran dan pengembalian modal, PIHAK KEDUA menyerahkan agunan berupa: <strong>{{ $debt->guarantee_info }}</strong> kepada PIHAK PERTAMA sampai pinjaman modal lunas sepenuhnya.
                        @else
                            Pinjaman modal ini didasari rasa saling percaya dan kemitraan usaha. PIHAK KEDUA bertanggung jawab penuh secara hukum dan pribadi untuk memutarkan dana pembelian dan menyetorkan pasokan {{ strtolower($commodityTerm) }} ke depo PIHAK PERTAMA sampai lunas.
                        @endif
                    </p>
                </div>

                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 4: Hak Tagih &amp; Penyelesaian Perselisihan</h4>
                    <p class="mt-0.5 leading-relaxed">
                        {{ $debt->agreement_notes ?: ($settings['debt_agreement_terms'] ?? ('Apabila PIHAK KEDUA lalai, berhenti menyetorkan ' . strtolower($commodityTerm) . ', atau menyalahgunakan modal kerja, maka PIHAK PERTAMA berhak menagih dan menarik kembali sisa saldo modal secara seketika melalui musyawarah maupun jalur hukum Republik Indonesia.')) }}
                    </p>
                </div>

            @elseif($category === 'hutang')
                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 1: Pokok Hutang Pinjaman &amp; Tanda Terima Sah</h4>
                    <p class="mt-0.5 leading-relaxed">
                        1. PIHAK PERTAMA meminjamkan uang tunai kepada PIHAK KEDUA sebesar:
                        <span class="font-mono font-bold text-sm text-emerald-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-300 inline-block my-1">
                            {{ \App\Services\FormatHelper::rupiah($debt->initial_amount) }}
                        </span>
                        <em>(Terbilang: <strong>{{ \App\Services\FormatHelper::terbilang($debt->initial_amount) }} Rupiah</strong>)</em>.<br>
                        2. PIHAK KEDUA mengakui telah menerima uang pinjaman hutang tersebut secara utuh dan sah. Surat Perjanjian ini berlaku mengikat sebagai kuitansi tanda terima sah atas pinjaman tersebut.
                    </p>
                </div>

                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 2: Kewajiban Pelunasan &amp; Jatuh Tempo</h4>
                    <p class="mt-0.5 leading-relaxed">
                        1. Keperluan pinjaman: <strong>{{ $debt->notes ?: 'Pinjaman dana kebutuhan operasional / kasbon' }}</strong>.<br>
                        2. PIHAK KEDUA berkewajiban melunasi pinjaman hutang paling lambat tanggal: <strong>{{ $debt->due_date ? $debt->due_date->translatedFormat('d F Y') : 'Sesuai kesepakatan tempo bersama' }}</strong>.<br>
                        3. Pembayaran hutang dapat diangsur berkala, dipotongkan langsung saat timbangan barang, atau disetor tunai/transfer lunas.
                    </p>
                </div>

                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 3: Jaminan / Agunan Hutang</h4>
                    <p class="mt-0.5 leading-relaxed">
                        @if($debt->guarantee_info)
                            Sebagai jaminan pelunasan hutang, PIHAK KEDUA menyerahkan agunan sah berupa: <strong>{{ $debt->guarantee_info }}</strong> yang dipegang oleh PIHAK PERTAMA hingga hutang piutang ini lunas sepenuhnya.
                        @else
                            Pinjaman hutang ini diberikan atas dasar itikad baik dan tanggung jawab pribadi penuh PIHAK KEDUA secara hukum perdata Republik Indonesia untuk melunasi seluruh kewajibannya tepat waktu.
                        @endif
                    </p>
                </div>

                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 4: Wanprestasi &amp; Upaya Hukum</h4>
                    <p class="mt-0.5 leading-relaxed">
                        {{ $debt->agreement_notes ?: ($settings['debt_agreement_terms'] ?? 'Apabila PIHAK KEDUA terlambat, lalai, atau tidak melunasi hutang sesuai batas waktu yang disepakati, maka PIHAK PERTAMA berhak menagih seluruh sisa hutang secara seketika dan mengambil langkah hukum perdata maupun pidana sesuai undang-undang.') }}
                    </p>
                </div>

            @else
                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 1: Dana Uang Muka Komitmen Pasokan</h4>
                    <p class="mt-0.5 leading-relaxed">
                        1. PIHAK PERTAMA menyerahkan dana komitmen ikatan pasokan barang kepada PIHAK KEDUA sebesar:
                        <span class="font-mono font-bold text-sm text-emerald-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-300 inline-block my-1">
                            {{ \App\Services\FormatHelper::rupiah($debt->initial_amount) }}
                        </span>
                        <em>(Terbilang: <strong>{{ \App\Services\FormatHelper::terbilang($debt->initial_amount) }} Rupiah</strong>)</em>.<br>
                        2. PIHAK KEDUA menerima dana tersebut sebagai uang muka komitmen kemitraan suplai {{ strtolower($commodityTerm) }} dan bahan daur ulang ke depo PIHAK PERTAMA. Surat ini sah sebagai tanda terima dana komitmen.
                    </p>
                </div>

                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 2: Kewajiban Pasokan &amp; Pemotongan Komitmen</h4>
                    <p class="mt-0.5 leading-relaxed">
                        1. Target komitmen pasokan: <strong>{{ $debt->notes ?: ('Komitmen pasokan ' . strtolower($commodityTerm) . ' secara berkelanjutan') }}</strong>.<br>
                        2. PIHAK KEDUA berkewajiban menyuplai dan menjual {{ strtolower($commodityTerm) }} secara prioritas/eksklusif ke depo PIHAK PERTAMA hingga tanggal: <strong>{{ $debt->due_date ? $debt->due_date->translatedFormat('d F Y') : 'Target nilai komitmen impas' }}</strong>.<br>
                        3. Nilai komitmen diperhitungkan dan dipotongkan bertahap dari setiap nota timbangan yang disetorkan hingga saldo komitmen tuntas.
                    </p>
                </div>

                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 3: Larangan Pengalihan Pasokan &amp; Jaminan</h4>
                    <p class="mt-0.5 leading-relaxed">
                        @if($debt->guarantee_info)
                            Sebagai jaminan kesungguhan pasokan, PIHAK KEDUA menyerahkan jaminan berupa: <strong>{{ $debt->guarantee_info }}</strong> yang disimpan PIHAK PERTAMA selama masa komitmen berlangsung.
                        @else
                            Selama ikatan komitmen ini berjalan, PIHAK KEDUA dilarang mengalihkan, menyetorkan, atau menjual {{ strtolower($commodityTerm) }} komitmen ke lapak/pihak lain tanpa izin tertulis dari PIHAK PERTAMA.
                        @endif
                    </p>
                </div>

                <div>
                    <h4 class="font-bold underline text-slate-900">Pasal 4: Sanksi Wanprestasi &amp; Pengembalian Dana</h4>
                    <p class="mt-0.5 leading-relaxed">
                        {{ $debt->agreement_notes ?: ($settings['debt_agreement_terms'] ?? ('Apabila PIHAK KEDUA ingkar janji, berhenti menyuplai ' . strtolower($commodityTerm) . ', atau mengalihkan barang ke tempat lain, maka seluruh sisa dana komitmen wajib dikembalikan seketika secara utuh kepada PIHAK PERTAMA beserta biaya kompensasi yang disepakati.')) }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Closing Paragraph -->
        <p class="text-xs sm:text-sm text-justify pt-1">
            Demikian Surat Perjanjian ini dibuat rangkap 2 (dua) bermaterai cukup: Lembar 1 untuk Pihak Pertama (Owner) dan Lembar 2 untuk Pihak Kedua (Peminjam/Penerima), keduanya berkekuatan hukum sama.
        </p>

        <!-- Signatures: Peminjam & Owner -->
        <div class="pt-4 border-t border-slate-300 text-xs sm:text-sm">
            <div class="grid grid-cols-2 gap-6 text-center">
                <!-- Pihak Pertama: Owner -->
                <div>
                    <p class="font-bold uppercase tracking-wider text-slate-900">{{ $sheet['role_first'] }}</p>
                    <p class="text-[10px] text-slate-500 font-sans">Pemilik Usaha / Depo</p>
                    <div class="h-20 flex items-center justify-center text-slate-400 italic text-[10px]" style="height: 70px;">
                        @if(\App\Models\BusinessSetting::hasSignatureOrStamp())
                            @include('components.signature-stamp', ['isPdf' => false, 'height' => 48, 'stampSize' => 56])
                        @else
                            <span class="border border-dashed border-slate-300 px-3 py-1.5 rounded">Cap Stempel Depo &amp; TTD</span>
                        @endif
                    </div>
                    <p class="font-bold underline uppercase text-slate-900">({{ $ownerName }})</p>
                    <p class="text-[10px] text-slate-600 font-sans">Owner / Pimpinan {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</p>
                </div>

                <!-- Pihak Kedua: Peminjam -->
                <div>
                    <p class="font-bold uppercase tracking-wider text-slate-900">{{ $sheet['role_second'] }}</p>
                    <p class="text-[10px] text-slate-500 font-sans">Penerima Dana / Mitra</p>
                    <div class="h-20 flex items-center justify-center text-slate-400 italic text-[10px]" style="height: 70px;">
                        <span class="border border-dashed border-slate-300 px-3 py-1.5 rounded">Materai Rp 10.000 &amp; TTD</span>
                    </div>
                    <p class="font-bold underline uppercase text-slate-900">({{ $debt->borrower_name }})</p>
                    <p class="text-[10px] text-slate-600 font-sans">Penerima (No. HP: {{ $debt->borrower_phone ?: '-' }})</p>
                </div>
            </div>

            <!-- Saksi / Kasir Pencatat -->
            <div class="mt-4 pt-2 border-t border-dashed border-slate-200 text-center font-sans">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">SAKSI / PETUGAS PENCATAT:</span>
                <span class="font-bold underline text-xs uppercase text-slate-800 ml-2">({{ $debt->witness_name ?: ($debt->user?->name ?? 'Kasir Depo') }})</span>
                <span class="text-[10px] text-slate-500 ml-1">({{ $debt->witness_name ? 'Saksi Kesepakatan' : 'Petugas Kasir Depo' }})</span>
            </div>
        </div>

        <div class="text-center text-[10px] text-slate-400 pt-2 font-sans border-t border-dotted border-slate-200">
            {{ $sheet['badge'] }} &bull; Diterbitkan oleh Sistem Kasir Depo {{ $commodityTerm }} &bull; No. Referensi: {{ $debt->debt_number }}
        </div>

    </div>
@endforeach

    <!-- WhatsApp Modal Component Integration -->
    <x-whatsapp-receipt-modal />
    <x-pdf-preview-modal />

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
        });
    </script>
</body>
</html>