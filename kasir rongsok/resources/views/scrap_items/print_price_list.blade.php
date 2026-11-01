<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar_Harga_Beli_Rongsok_{{ date('Ymd') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 0; 
                padding: 0; 
                background: #fff; 
                font-size: 11px; 
            }
            .price-sheet {
                box-shadow: none !important;
                border: 1.5px solid #0f172a !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 8mm !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center font-sans text-slate-900">

    <!-- Print Control Bar -->
    <div class="no-print w-full max-w-4xl mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-700">Format Dokumen:</span>
            <span class="px-2.5 py-1 rounded bg-blue-100 text-blue-900 text-xs font-bold flex items-center gap-1.5">
                <i data-lucide="share-2" class="w-3.5 h-3.5 text-blue-700"></i>
                <span>Daftar Harga Beli (Khusus Share ke Penjual / Pemasok)</span>
            </span>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak / Simpan PDF</span>
            </button>
            <button onclick="window.history.back()" class="px-3.5 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold">
                Kembali
            </button>
        </div>
    </div>

    <!-- DOCUMENT CONTAINER (A4 Flyer / Pricelist) -->
    <div class="price-sheet bg-white text-slate-900 border border-slate-300 rounded-2xl shadow-xl p-8 w-full max-w-4xl space-y-6">
        
        <!-- Header: Logo & Depo Info -->
        <div class="flex items-center justify-between pb-4 border-b-2 border-slate-900 gap-4">
            <div class="flex items-center gap-3.5">
                @if(\App\Models\BusinessSetting::hasLogo())
                    <img src="{{ \App\Models\BusinessSetting::logoUrl() }}" alt="Logo" class="h-16 max-w-[110px] object-contain">
                @else
                    <div class="w-12 h-12 rounded-xl bg-slate-900 text-emerald-400 flex items-center justify-center font-bold text-xl">
                        <i data-lucide="recycle" class="w-7 h-7"></i>
                    </div>
                @endif
                <div>
                    <h1 class="text-xl font-black tracking-tight uppercase text-slate-900">
                        {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
                    </h1>
                    <p class="text-xs text-slate-600 mt-0.5 leading-tight">
                        {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}
                    </p>
                    <p class="text-xs font-bold text-emerald-700 font-mono mt-0.5">
                        WhatsApp / Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                    </p>
                </div>
            </div>

            <div class="text-right shrink-0">
                <div class="inline-block px-3 py-1 bg-slate-900 text-white font-black text-xs uppercase tracking-wider rounded">
                    DAFTAR HARGA BELI RONGSOK
                </div>
                <div class="text-[11px] text-slate-600 mt-1 font-medium">
                    Update Resmi: <strong class="text-slate-900">{{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</strong>
                </div>
            </div>
        </div>

        <!-- Banner Keunggulan Timbangan & Kasir -->
        <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span><strong>Timbangan Digital Presisi & Transparan:</strong> Pembayaran tunai / transfer langsung lunas di kasir saat timbang.</span>
            </div>
            <div class="font-bold text-xs uppercase text-emerald-800 shrink-0">
                Timbang ➔ Bayar Lunas
            </div>
        </div>

        <!-- Price Tables: Hanya Nama Barang & Harganya -->
        <div class="space-y-4">
            @php $counter = 1; @endphp
            @foreach($categories as $category)
                @if($category->scrapItems->count() > 0)
                    <div class="rounded-xl border border-slate-300 overflow-hidden shadow-xs">
                        <!-- Category Header -->
                        <div class="px-4 py-2 bg-slate-900 text-white flex items-center justify-between">
                            <h3 class="font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                                <i data-lucide="layers" class="w-3.5 h-3.5 text-emerald-400"></i>
                                <span>{{ $category->name }}</span>
                            </h3>
                            <span class="text-[10px] text-slate-300 font-mono">
                                {{ $category->scrapItems->count() }} Jenis
                            </span>
                        </div>

                        <!-- Table: Hanya No, Nama Barang, dan Harga Beli -->
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                    <th class="py-2 px-3 w-12 text-center">No</th>
                                    <th class="py-2 px-4">Nama Jenis Barang Rongsok</th>
                                    <th class="py-2 px-4 w-48 text-right">Harga Beli per Kg</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($category->scrapItems as $item)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="py-2 px-3 text-center text-slate-400 font-mono text-[11px]">{{ $counter++ }}</td>
                                        <td class="py-2 px-4 font-bold text-slate-900 text-sm">
                                            {{ $item->name }}
                                        </td>
                                        <td class="py-2 px-4 text-right font-mono font-black text-emerald-700 text-base">
                                            Rp {{ number_format($item->buy_price_per_kg, 0, ',', '.') }}
                                            <span class="text-[11px] text-slate-500 font-sans font-normal">/ kg</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Ketentuan & Peringatan Legal (Paling Bawah) -->
        <div class="pt-4 border-t-2 border-slate-200 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-[10px] text-slate-600 leading-relaxed">
                <div>
                    <h4 class="font-bold text-slate-900 uppercase mb-1">Ketentuan Penerimaan Barang:</h4>
                    <ul class="list-disc pl-4 space-y-0.5">
                        <li>Harga di atas berlaku untuk barang kondisi normal dan bersih.</li>
                        <li>Barang basah atau bercampur kotoran akan dikenakan potongan tara secara adil & transparan.</li>
                        <li>Harga dapat berubah sewaktu-waktu mengikuti perkembangan pasar daur ulang & pabrik peleburan.</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 uppercase mb-1">Layanan Jemput Muatan Borongan:</h4>
                    <p>
                        Melayani penjemputan barang rongsok partai borongan dari bengkel, gudang, kantor, atau pabrik.
                        Hubungi kantor kami via WhatsApp: <strong class="text-slate-900">{{ $settings['company_phone'] ?? '0812-3456-7890' }}</strong>.
                    </p>
                </div>
            </div>

            <!-- Disclaimer Warning (TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI) -->
            <div class="p-2.5 rounded-lg border border-red-300 bg-red-50 text-center text-red-950 font-sans">
                <span class="inline-block px-1.5 py-0.5 bg-red-700 text-white font-black text-[8px] uppercase tracking-wider rounded mb-1">
                    PERHATIAN HUKUM
                </span>
                <p class="font-black uppercase text-[10px] tracking-tight">
                    *** {{ $settings['receipt_disclaimer'] ?? 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI / ILEGAL' }} ***
                </p>
                <p class="text-[8px] text-red-800 mt-0.5">
                    Seluruh barang yang dijual wajib milik sah pribadi penjual. Depo kami tegas menolak segala bentuk barang curian atau melanggar hukum.
                </p>
            </div>

            <div class="text-center pt-1 text-[9px] text-slate-400">
                Dokumen resmi diterbitkan oleh {{ $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur' }} untuk mitra pemasok & penjual rongsok.
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(() => window.print(), 300);
            }
        });
    </script>
</body>
</html>
