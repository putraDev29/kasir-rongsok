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
            .print-container { box-shadow: none !important; border: none !important; padding: 0 !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center font-serif text-slate-900"
      x-data>

    <!-- Top Action Bar -->
    <div class="no-print w-full max-w-3xl mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3 font-sans">
        <a href="{{ route('debts.show', $debt) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Detail Pinjaman</span>
        </a>

        <div class="flex items-center gap-2">
            <a href="{{ route('debts.agreement-pdf', $debt) }}"
               class="px-3.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span>Unduh PDF</span>
            </a>

            <button type="button"
                    @click="$store.waGateway.openReceiptModal({
                        title: 'Kirim Surat Perjanjian Pinjaman PDF',
                        subtitle: 'Kirim berkas dokumen PDF Surat Perjanjian Hutang Modal (SPH) ke WhatsApp peminjam',
                        phone: '{{ $debt->borrower_phone }}',
                        filename: 'SPH_' + ('{{ str_replace(['/', ' '], '_', $debt->agreement_number ?: $debt->debt_number) }}') + '.pdf',
                        fileDescription: 'Dokumen PDF Resmi Surat Perjanjian Hutang Piutang Modal',
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
                <span>Cetak Lembar</span>
            </button>
        </div>
    </div>

    <!-- Legal Agreement Paper (A4 size format) -->
    <div class="print-container bg-white w-full max-w-3xl p-8 sm:p-12 rounded-xl shadow-md border border-slate-200 space-y-5 leading-relaxed">
        
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
            <div class="text-right">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">DOKUMEN RESMI</span>
                <span class="text-xs font-bold text-emerald-700">Kemitraan Modal Usaha</span>
            </div>
        </div>

        <div class="text-center pt-2">
            <h2 class="text-base sm:text-lg font-black underline uppercase tracking-tight text-emerald-800">
                SURAT PERJANJIAN PINJAMAN MODAL USAHA &amp; HUTANG PIUTANG
            </h2>
            <p class="text-xs font-mono font-bold text-slate-800 mt-1">
                Nomor Perjanjian: {{ $debt->agreement_number ?: ('SPH-PPJ-' . $debt->debt_number) }}
            </p>
        </div>

        <!-- Opening Paragraph -->
        <p class="text-xs sm:text-sm text-justify">
            Pada hari ini, <strong>{{ $debt->date->translatedFormat('l') }}</strong> tanggal <strong>{{ $debt->date->translatedFormat('d F Y') }}</strong> ({{ $debt->date->format('d/m/Y') }}), bertempat di kantor <strong>{{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong>, telah dibuat, disepakati, dan ditandatangani perjanjian pinjaman modal usaha / hutang piutang antara pihak-pihak di bawah ini:
        </p>

        <!-- Parties Details (Owner & Peminjam) -->
        <div class="space-y-2.5 text-xs sm:text-sm bg-slate-50 p-4 rounded-lg border border-slate-200">
            <div class="grid grid-cols-[130px_10px_1fr] items-start">
                <span class="font-bold text-slate-900">I. PIHAK PERTAMA</span>
                <span>:</span>
                <div>
                    <strong>{{ $ownerName }}</strong>, bertindak dalam jabatannya selaku <strong>OWNER / PEMILIK USAHA {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</strong> yang beralamat di {{ $settings['company_address'] ?? 'Sentra Rongsok' }}, selanjutnya dalam perjanjian ini disebut sebagai <strong>PIHAK PERTAMA (OWNER)</strong>.
                </div>
            </div>

            <div class="grid grid-cols-[130px_10px_1fr] items-start pt-1.5 border-t border-slate-200">
                <span class="font-bold text-slate-900">II. PIHAK KEDUA</span>
                <span>:</span>
                <div>
                    <strong>{{ $debt->borrower_name }}</strong>, No. HP/WhatsApp: <strong>{{ $debt->borrower_phone ?: '-' }}</strong>, bertindak selaku <strong>PEMINJAM / PENERIMA DANA MODAL</strong>@if($debt->supplier) (Terdaftar sebagai Pemasok Resmi: <strong>{{ $debt->supplier->code }}</strong> - {{ $debt->supplier->address ?: 'Sentra Pengepul' }})@else (Status: <strong>{{ ucfirst($debt->borrower_type) }}</strong>)@endif, selanjutnya dalam perjanjian ini disebut sebagai <strong>PIHAK KEDUA (PEMINJAM)</strong>.
                </div>
            </div>
        </div>

        <p class="text-xs sm:text-sm text-justify">
            Kedua belah pihak dengan sadar, sukarela, dan tanpa paksaan dari pihak manapun telah bersepakat mengadakan perjanjian pinjaman modal usaha dengan ketentuan pasal-pasal sebagai berikut:
        </p>

        <!-- Articles -->
        <div class="space-y-3.5 text-xs sm:text-sm text-justify">
            <div>
                <h4 class="font-bold underline text-slate-900">Pasal 1: Jumlah Pinjaman Modal &amp; Penyerahan Dana</h4>
                <p class="mt-1 leading-relaxed">
                    1. PIHAK PERTAMA menyerahkan uang pinjaman modal usaha kepada PIHAK KEDUA sebesar:
                    <br>
                    <span class="font-mono font-bold text-sm text-emerald-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-300 inline-block my-1">
                        {{ \App\Services\FormatHelper::rupiah($debt->initial_amount) }}
                    </span>
                    <br>
                    <em>(Terbilang: <strong>{{ \App\Services\FormatHelper::terbilang($debt->initial_amount) }} Rupiah</strong>)</em>.
                    <br>
                    2. PIHAK KEDUA menyatakan telah menerima uang pinjaman modal tersebut secara utuh, penuh, dan sah dari PIHAK PERTAMA, dan Surat Perjanjian ini sekaligus berfungsi sebagai bukti kuitansi tanda terima penerimaan dana yang sah.
                </p>
            </div>

            <div>
                <h4 class="font-bold underline text-slate-900">Pasal 2: Tujuan Modal &amp; Mekanisme Pengembalian (Potong Timbangan)</h4>
                <p class="mt-1 leading-relaxed">
                    1. Pinjaman modal ini dipergunakan oleh PIHAK KEDUA untuk keperluan: <strong>{{ $debt->notes ?: 'Modal perputaran operasional pembelian dan pengumpulan barang rongsok' }}</strong>.
                    <br>
                    2. PIHAK KEDUA berkewajiban mengembalikan pinjaman tersebut melalui pemotongan nota timbangan hasil penyetoran barang rongsok ke depo PIHAK PERTAMA secara bertahap atau melalui pembayaran angsuran tunai/transfer hingga seluruh hutang dinyatakan lunas.
                    <br>
                    3. Target pelunasan pinjaman modal disepakati selambat-lambatnya tanggal: <strong>{{ $debt->due_date ? $debt->due_date->translatedFormat('d F Y') : 'Sesuai kesepakatan berjalan / setiap transaksi timbangan' }}</strong>.
                </p>
            </div>

            <div>
                <h4 class="font-bold underline text-slate-900">Pasal 3: Jaminan &amp; Tanggung Jawab</h4>
                <p class="mt-1 leading-relaxed">
                    @if($debt->guarantee_info)
                        Sebagai itikad baik dan jaminan kepastian pengembalian hutang, PIHAK KEDUA menyerahkan jaminan berupa: <strong>{{ $debt->guarantee_info }}</strong> kepada PIHAK PERTAMA sampai hutang piutang ini dinyatakan lunas sepenuhnya oleh PIHAK PERTAMA.
                    @else
                        Pinjaman modal ini diberikan atas dasar rasa saling percaya dan komitmen kemitraan usaha, di mana PIHAK KEDUA bertanggung jawab penuh secara hukum dan pribadi untuk menyetorkan pasokan barang rongsok ke depo PIHAK PERTAMA sampai pinjaman lunas.
                    @endif
                </p>
            </div>

            <div>
                <h4 class="font-bold underline text-slate-900">Pasal 4: Hak Tagih &amp; Penyelesaian Perselisihan</h4>
                <p class="mt-1 leading-relaxed">
                    {{ $debt->agreement_notes ?: ($settings['debt_agreement_terms'] ?? 'Apabila PIHAK KEDUA lalai, berhenti menyetorkan barang rongsok, atau tidak memenuhi kewajiban pembayaran angsuran sesuai kesepakatan, maka PIHAK PERTAMA berhak menagih sisa saldo piutang modal secara penuh atau mengambil langkah penyelesaian musyawarah maupun hukum yang berlaku di Republik Indonesia.') }}
                </p>
            </div>
        </div>

        <!-- Closing Paragraph -->
        <p class="text-xs sm:text-sm text-justify pt-1">
            Demikian Surat Perjanjian Pinjaman Modal Usaha ini dibuat rangkap 2 (dua) bermaterai cukup dan masing-masing mempunyai kekuatan hukum yang sama bagi kedua belah pihak sejak ditandatangani.
        </p>

        <!-- Signatures: Peminjam & Owner -->
        <div class="pt-6 border-t border-slate-300 text-xs sm:text-sm">
            <div class="grid grid-cols-2 gap-6 text-center">
                <!-- Pihak Pertama: Owner -->
                <div>
                    <p class="font-bold uppercase tracking-wider text-slate-900">PIHAK PERTAMA (OWNER)</p>
                    <p class="text-[10px] text-slate-500 font-sans">Pemberi Pinjaman Modal / Pemilik Usaha</p>
                    <div class="h-20 flex items-center justify-center text-slate-400 italic text-[10px]">
                        <span class="border border-dashed border-slate-300 px-3 py-1.5 rounded">Cap Stempel Depo &amp; TTD</span>
                    </div>
                    <p class="font-bold underline uppercase text-slate-900">({{ $ownerName }})</p>
                    <p class="text-[10px] text-slate-600 font-sans">Owner / Pimpinan {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }}</p>
                </div>

                <!-- Pihak Kedua: Peminjam -->
                <div>
                    <p class="font-bold uppercase tracking-wider text-slate-900">PIHAK KEDUA (PEMINJAM)</p>
                    <p class="text-[10px] text-slate-500 font-sans">Peminjam Modal / Debitur Penerima Dana</p>
                    <div class="h-20 flex items-center justify-center text-slate-400 italic text-[10px]">
                        <span class="border border-dashed border-slate-300 px-3 py-1.5 rounded">Materai Rp 10.000 &amp; TTD</span>
                    </div>
                    <p class="font-bold underline uppercase text-slate-900">({{ $debt->borrower_name }})</p>
                    <p class="text-[10px] text-slate-600 font-sans">Peminjam (No. HP: {{ $debt->borrower_phone ?: '-' }})</p>
                </div>
            </div>

            <!-- Saksi / Kasir Pencatat -->
            <div class="mt-6 pt-3 border-t border-dashed border-slate-200 text-center">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider font-sans">SAKSI / PETUGAS KASIR PENCATAT TRANSAKSI:</p>
                <div class="h-12 flex items-center justify-center text-slate-300 italic text-[10px]">Tanda Tangan</div>
                <p class="font-bold underline text-xs uppercase text-slate-800">({{ $debt->witness_name ?: ($debt->user?->name ?? 'Kasir Depo') }})</p>
                <p class="text-[10px] text-slate-500 font-sans">{{ $debt->witness_name ? 'Saksi Perjanjian' : 'Petugas Kasir Depo' }}</p>
            </div>
        </div>

    </div>

    <!-- WhatsApp Modal Component Integration -->
    <x-whatsapp-receipt-modal />

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
        });
    </script>
</body>
</html>
