<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat_Hutang_{{ str_replace(['/', ' '], '_', $debt->debt_number) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: a4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 0 !important; 
                padding: 0 !important; 
                background: #fff !important; 
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .paper-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-6 flex flex-col items-center font-sans text-slate-900" x-data>

    <!-- Top Action Toolbar (Screen Only) -->
    <div class="no-print w-full max-w-4xl mb-4 bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3 sticky top-4 z-40">
        <div class="flex items-center gap-2">
            <a href="{{ route('debts.show', $debt) }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Detail</span>
            </a>
            <span class="hidden sm:inline text-slate-300">|</span>
            <span class="text-xs font-bold text-slate-800">Kartu Riwayat &amp; Saldo #{{ $debt->debt_number }}</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                KERTAS HVS A4
            </span>
        </div>

        <div class="flex items-center gap-2">
            <!-- Standard Browser Print -->
            <button type="button" 
                    onclick="window.print()" 
                    class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
                    title="Cetak lewat dialog browser standar (Printer USB / Windows / PDF)">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Biasa</span>
            </button>

            <!-- Preview Official PDF Modal -->
            <button type="button"
               onclick="openPdfPreview('{{ route('debts.pdf', $debt) }}', 'Kartu Riwayat & Saldo #{{ $debt->debt_number }}', 'Riwayat_Hutang_{{ preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($debt->borrower_name ?: 'Peminjam')) }}.pdf', 'Pratinjau Dokumen PDF Riwayat Hutang')"
               class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
               title="Pratinjau Berkas Dokumen PDF Resmi">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Preview PDF HVS</span>
            </button>

            <!-- Send via WhatsApp Modal -->
            <button type="button"
                    @click="openWaPdfModal({
                        title: 'Kirim PDF Riwayat Hutang & Saldo Pinjaman',
                        subtitle: 'Kirim berkas PDF ringkasan riwayat pinjaman dan mutasi cicilan sesuai status saat ini ke WhatsApp peminjam',
                        phone: '{{ $debt->borrower_phone }}',
                        filename: 'Riwayat_Hutang_' + ('{{ preg_replace("/[^a-zA-Z0-9_\-]/", "_", trim($debt->borrower_name ?: "Peminjam")) }}') + '.pdf',
                        fileDescription: 'Dokumen PDF Resmi Kartu Riwayat & Posisi Saldo Hutang Modal',
                        printUrl: '{{ route('debts.print', $debt) }}',
                        pdfUrl: '{{ route('debts.pdf', $debt) }}',
                        previewUrl: '{{ route('debts.wa-preview', $debt) }}',
                        sendUrl: '{{ route('debts.send-wa', $debt) }}',
                        submitButtonText: 'Kirim Riwayat PDF via WA'
                    })"
                    class="px-3.5 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
                    title="Kirim Berkas PDF ke WhatsApp">
                <x-whatsapp-icon class="w-3.5 h-3.5" />
                <span>Kirim WA</span>
            </button>
        </div>
    </div>

    <!-- Printable Paper Container (A4 Portrait Style) -->
    <div class="paper-container w-full max-w-4xl bg-white p-6 sm:p-10 rounded-2xl shadow-xl border border-slate-200 text-slate-800 text-xs">
        
        <!-- Header Section -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-3 mb-4 gap-4">
            <div>
                <div class="text-base font-extrabold uppercase text-slate-900 tracking-wide">
                    {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                    {{ $settings['company_address'] ?? 'Sentra Daur Ulang Logam & Rongsok Nusantara' }}<br>
                    Telp/WhatsApp: <strong class="text-slate-700">{{ $settings['company_phone'] ?? '0812-3456-7890' }}</strong>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-extrabold uppercase text-slate-900 tracking-tight">
                    KARTU RIWAYAT &amp; SALDO HUTANG
                </div>
                <div class="text-xs font-mono font-bold text-blue-700 mt-0.5">
                    {{ $debt->debt_number }}
                </div>
                <div class="mt-1">
                    @if($debt->status === 'paid')
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            ● LUNAS (LUNAS PENUH)
                        </span>
                    @elseif($debt->status === 'partial' || $debt->status === 'partially_paid')
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            ● SEBAGIAN TERBAYAR (TERCICIL)
                        </span>
                    @else
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                            ● BELUM LUNAS
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2-Column Info Grid: Debitur & Parameter Pinjaman -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <!-- Left: Data Peminjam -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <div class="text-[10px] font-bold text-slate-600 uppercase border-b border-slate-200 pb-1 mb-2 tracking-wider">
                    Data Peminjam / Debitur
                </div>
                <table class="w-full text-[11px]">
                    <tr class="py-0.5">
                        <td class="text-slate-500 w-28 py-0.5">Nama Peminjam</td>
                        <td class="text-slate-400 w-3">:</td>
                        <td class="font-bold text-slate-900">{{ $debt->borrower_name }}</td>
                    </tr>
                    <tr class="py-0.5">
                        <td class="text-slate-500 py-0.5">Tipe Debitur</td>
                        <td class="text-slate-400">:</td>
                        <td class="capitalize font-semibold text-slate-800">
                            {{ $debt->borrower_type }}
                            @if($debt->supplier)
                                <span class="font-normal text-blue-700">({{ $debt->supplier->code }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="py-0.5">
                        <td class="text-slate-500 py-0.5">Nomor WhatsApp/HP</td>
                        <td class="text-slate-400">:</td>
                        <td class="font-mono text-slate-800">{{ $debt->borrower_phone ?: '-' }}</td>
                    </tr>
                    @if($debt->supplier && $debt->supplier->hasBankDetails())
                    <tr class="py-0.5">
                        <td class="text-slate-500 py-0.5">Rekening Bank</td>
                        <td class="text-slate-400">:</td>
                        <td class="font-mono text-blue-900 font-semibold">{{ $debt->supplier->bankDisplay() }}</td>
                    </tr>
                    @endif
                    <tr class="py-0.5">
                        <td class="text-slate-500 py-0.5">Dicatat Oleh</td>
                        <td class="text-slate-400">:</td>
                        <td class="text-slate-800">{{ $debt->user?->name ?? 'Kasir / Sistem' }}</td>
                    </tr>
                </table>
            </div>

            <!-- Right: Detail Pinjaman & Status -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                <div class="text-[10px] font-bold text-slate-600 uppercase border-b border-slate-200 pb-1 mb-2 tracking-wider">
                    Informasi &amp; Syarat Pinjaman
                </div>
                <table class="w-full text-[11px]">
                    <tr class="py-0.5">
                        <td class="text-slate-500 w-32 py-0.5">Kategori Transaksi</td>
                        <td class="text-slate-400 w-3">:</td>
                        <td class="font-bold text-slate-900">{{ $debt->getCategoryLabel() }}</td>
                    </tr>
                    <tr class="py-0.5">
                        <td class="text-slate-500 py-0.5">Tanggal Pinjam</td>
                        <td class="text-slate-400">:</td>
                        <td class="font-semibold text-slate-800">{{ $debt->date->format('d/m/Y') }}</td>
                    </tr>
                    <tr class="py-0.5">
                        <td class="text-slate-500 py-0.5">Jatuh Tempo</td>
                        <td class="text-slate-400">:</td>
                        <td class="font-semibold {{ $debt->due_date && $debt->due_date->isPast() && !$debt->isPaid() ? 'text-rose-600' : 'text-slate-800' }}">
                            {{ $debt->due_date ? $debt->due_date->format('d/m/Y') : 'Tidak ditentukan' }}
                            @if($debt->due_date && $debt->due_date->isPast() && !$debt->isPaid())
                                <span class="text-[10px] text-rose-600 font-normal">(Jatuh Tempo Terlewat)</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="py-0.5">
                        <td class="text-slate-500 py-0.5">No. Perjanjian (SPH)</td>
                        <td class="text-slate-400">:</td>
                        <td class="font-mono text-slate-800">{{ $debt->agreement_number ?: '-' }}</td>
                    </tr>
                    @if($debt->guarantee_info)
                    <tr class="py-0.5">
                        <td class="text-slate-500 py-0.5">Jaminan / Agunan</td>
                        <td class="text-slate-400">:</td>
                        <td class="text-slate-800">{{ $debt->guarantee_info }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- 3-Column Financial KPI Cards -->
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="border border-slate-200 border-t-3 border-t-slate-700 rounded-lg p-3 bg-white text-center">
                <div class="text-[10px] font-bold text-slate-500 uppercase">Nominal Pinjaman Awal</div>
                <div class="text-base font-bold font-mono text-slate-900 mt-1 whitespace-nowrap">
                    {{ \App\Services\FormatHelper::rupiah($debt->initial_amount) }}
                </div>
                <div class="text-[9px] text-slate-400 mt-0.5">Pokok pinjaman yang disalurkan</div>
            </div>

            <div class="border border-emerald-200 border-t-3 border-t-emerald-600 rounded-lg p-3 bg-emerald-50/50 text-center">
                <div class="text-[10px] font-bold text-emerald-800 uppercase">Total Sudah Diangsur</div>
                <div class="text-base font-bold font-mono text-emerald-700 mt-1 whitespace-nowrap">
                    {{ \App\Services\FormatHelper::rupiah($debt->paid_amount) }}
                </div>
                <div class="text-[9px] text-emerald-600 font-semibold mt-0.5">
                    {{ $debt->initial_amount > 0 ? round(($debt->paid_amount / $debt->initial_amount) * 100) : 0 }}% terbayar ({{ $payments->count() }}x angsuran)
                </div>
            </div>

            <div class="border {{ $debt->isPaid() ? 'border-emerald-200 border-t-emerald-600 bg-emerald-50/50' : 'border-amber-200 border-t-amber-500 bg-amber-50/50' }} border-t-3 rounded-lg p-3 text-center">
                <div class="text-[10px] font-bold {{ $debt->isPaid() ? 'text-emerald-800' : 'text-amber-800' }} uppercase">Sisa Saldo Hutang</div>
                <div class="text-base font-bold font-mono {{ $debt->isPaid() ? 'text-emerald-700' : 'text-amber-700' }} mt-1 whitespace-nowrap">
                    {{ \App\Services\FormatHelper::rupiah($debt->remaining_amount) }}
                </div>
                <div class="text-[9px] {{ $debt->isPaid() ? 'text-emerald-600' : 'text-amber-700' }} font-semibold mt-0.5">
                    {{ $debt->isPaid() ? 'Hutang telah dinyatakan LUNAS' : 'Wajib diselesaikan / dilunasi' }}
                </div>
            </div>
        </div>

        <!-- Ledger Table of Repayments -->
        <div class="mb-4">
            <div class="text-[11px] font-bold text-slate-900 uppercase tracking-wide mb-2 flex items-center justify-between">
                <span>Riwayat Pembayaran &amp; Angsuran Pelunasan</span>
                <span class="text-[10px] font-normal text-slate-500">Tercatat {{ $payments->count() }} Pembayaran</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-slate-300 text-xs">
                    <thead>
                        <tr class="bg-slate-900 text-white uppercase text-[10px] tracking-wider">
                            <th class="border border-slate-700 py-2 px-2 text-center w-8">No</th>
                            <th class="border border-slate-700 py-2 px-2.5 text-left w-28 whitespace-nowrap">No. Bukti Bayar</th>
                            <th class="border border-slate-700 py-2 px-2 text-center w-28 whitespace-nowrap">Tanggal</th>
                            <th class="border border-slate-700 py-2 px-2.5 text-left">Sumber Dana / Metode</th>
                            <th class="border border-slate-700 py-2 px-3 text-right w-36 whitespace-nowrap">Nominal Bayar</th>
                            <th class="border border-slate-700 py-2 px-3 text-right w-36 whitespace-nowrap">Sisa Hutang</th>
                            <th class="border border-slate-700 py-2 px-2 text-center w-24">Kasir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($payments as $idx => $payment)
                            <tr class="hover:bg-slate-50/50 {{ $idx % 2 === 1 ? 'bg-slate-50/30' : '' }}">
                                <td class="border border-slate-300 py-2 px-2 text-center font-mono">{{ $idx + 1 }}</td>
                                <td class="border border-slate-300 py-2 px-2.5 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    {{ $payment->payment_number }}
                                </td>
                                <td class="border border-slate-300 py-2 px-2 text-center text-slate-600 whitespace-nowrap">
                                    {{ $payment->payment_date->format('d/m/Y H:i') }}
                                </td>
                                <td class="border border-slate-300 py-2 px-2.5">
                                    @if($payment->purchase_transaction_id)
                                        <div class="font-bold text-emerald-700 flex items-center gap-1">
                                            <span>Potong Timbangan Rongsok</span>
                                            <span class="text-[10px] text-blue-700 font-normal">({{ $payment->purchaseTransaction?->invoice_number }})</span>
                                        </div>
                                    @else
                                        <div class="font-bold text-blue-700">
                                            Setor {{ $payment->paymentMethod?->name ?? 'Tunai' }}
                                        </div>
                                    @endif
                                    @if($payment->notes)
                                        <div class="text-[10px] text-slate-500 italic">{{ $payment->notes }}</div>
                                    @endif
                                </td>
                                <td class="border border-slate-300 py-2 px-3 text-right font-mono font-bold text-emerald-600 whitespace-nowrap">
                                    {{ \App\Services\FormatHelper::rupiah($payment->amount) }}
                                </td>
                                <td class="border border-slate-300 py-2 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                    {{ \App\Services\FormatHelper::rupiah($payment->remaining_balance) }}
                                </td>
                                <td class="border border-slate-300 py-2 px-2 text-center text-slate-600 whitespace-nowrap">
                                    {{ $payment->user?->name ?? 'Kasir Depo' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="border border-slate-300 py-8 text-center text-slate-400">
                                    <em>Belum ada catatan pembayaran / angsuran untuk pinjaman ini.<br>
                                    Sisa saldo hutang saat ini masih utuh sebesar <strong>{{ \App\Services\FormatHelper::rupiah($debt->remaining_amount) }}</strong>.</em>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-400">
                            <td colspan="4" class="border border-slate-300 py-2.5 px-3 text-right whitespace-nowrap">
                                TOTAL TELAH DIANGSUR:
                            </td>
                            <td class="border border-slate-300 py-2.5 px-3 text-right font-mono text-xs text-emerald-700 whitespace-nowrap">
                                {{ \App\Services\FormatHelper::rupiah($debt->paid_amount) }}
                            </td>
                            <td class="border border-slate-300 py-2.5 px-3 text-right font-mono text-xs {{ $debt->isPaid() ? 'text-emerald-700' : 'text-amber-700' }} whitespace-nowrap">
                                {{ \App\Services\FormatHelper::rupiah($debt->remaining_amount) }}
                            </td>
                            <td class="border border-slate-300"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($debt->notes)
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5 mb-4 text-[11px]">
                <strong class="text-slate-600">Catatan / Keterangan Tambahan:</strong>
                <span class="text-slate-800 ml-1">{{ $debt->notes }}</span>
            </div>
        @endif

        <!-- Signatures Section -->
        <div class="mt-6 pt-4 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-xs">
            <!-- Left: Borrower Signature -->
            <div>
                <p class="font-medium text-slate-700 mb-2">Peminjam / Debitur,</p>
                <div class="h-16 flex items-center justify-center my-1">
                    <!-- Blank area for physical signature / receiver -->
                </div>
                <p class="font-bold text-slate-900 border-t border-slate-400 inline-block px-8 pt-1">
                    {{ $debt->borrower_name }}
                </p>
                <p class="text-[10px] text-slate-500 mt-0.5">Pihak Peminjam / Penerima Dana</p>
            </div>

            <!-- Right: Owner Signature with Stamp -->
            <div>
                <p class="font-medium text-slate-700 mb-2">Pengelola Depo / Pemberi Pinjaman,</p>
                <div class="h-16 flex items-center justify-center my-1">
                    @include('components.signature-stamp', ['isPdf' => false, 'height' => 48, 'stampSize' => 56])
                </div>
                <p class="font-bold text-slate-900 border-t border-slate-400 inline-block px-8 pt-1">
                    {{ $ownerName }}
                </p>
                <p class="text-[10px] text-slate-500 mt-0.5">Owner / Pengelola Depo Rongsok</p>
            </div>
        </div>

        <!-- Legal Disclaimer / Auto-generated notice -->
        <div class="mt-6 pt-3 border-t border-dashed border-slate-300 text-center text-[10px] text-slate-400">
            *Dokumen ini diterbitkan secara otomatis oleh Sistem Kasir Depo Rongsok per {{ now()->format('d/m/Y H:i') }} WIB dan merupakan bukti sah riwayat mutasi angsuran serta posisi sisa saldo pinjaman modal saat ini.
        </div>

    </div>

    <!-- PDF Preview Modal Component -->
    @include('components.pdf-preview-modal')

    <!-- WhatsApp Modal Component -->
    @include('components.whatsapp-receipt-modal')

</body>
</html>

