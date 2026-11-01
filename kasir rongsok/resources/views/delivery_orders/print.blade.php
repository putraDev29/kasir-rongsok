<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat_Jalan_{{ $deliveryOrder->delivery_number }}_{{ Str::slug($deliveryOrder->recipient_name) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: 210mm 148mm landscape; /* HVS Landscape Bagi 2 / Half Folio */
            margin: 5mm;
        }
        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 0; 
                padding: 0; 
                background: #fff;
                font-size: 10px;
            }
            .sj-card {
                box-shadow: none !important;
                border: 1.5px solid #0f172a !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 5mm !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 flex flex-col items-center">

    <!-- Print Control Bar -->
    <div class="no-print w-full max-w-[200mm] mb-3 bg-white p-2.5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-700">Format:</span>
            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[11px] font-bold">
                HVS Landscape Bagi 2 (210mm × 148mm)
            </span>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak / Simpan PDF</span>
            </button>
            <a href="{{ route('delivery-orders.show', $deliveryOrder) }}" class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold">
                Kembali
            </a>
        </div>
    </div>

    <!-- SURAT JALAN CONTAINER (210mm x 148mm Landscape) -->
    <div class="sj-card bg-white text-slate-900 border border-slate-300 rounded-xl shadow-md p-5 w-full max-w-[200mm] text-[10px]">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-2 border-b-2 border-slate-900">
            <div class="flex items-center gap-3">
                @if(\App\Models\BusinessSetting::hasLogo())
                    <img src="{{ \App\Models\BusinessSetting::logoUrl() }}" alt="Logo" class="h-12 max-w-[90px] object-contain">
                @endif
                <div>
                    <h1 class="text-base font-black tracking-tight uppercase text-slate-900">
                        {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
                    </h1>
                    <p class="text-[9px] text-slate-600 leading-tight">
                        {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}
                    </p>
                    <p class="text-[9px] text-slate-600 font-mono">
                        Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                    </p>
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block px-3 py-0.5 bg-slate-900 text-white font-black text-xs uppercase tracking-wider rounded">
                    SURAT JALAN PENGIRIMAN
                </div>
                <div class="text-[10px] font-mono mt-1 text-slate-800">
                    <div>No. SJ: <strong>{{ $deliveryOrder->delivery_number }}</strong></div>
                    <div>Tanggal: <strong>{{ $deliveryOrder->delivery_date->format('d/m/Y') }}</strong></div>
                </div>
            </div>
        </div>

        <!-- Info Grid (Recipient & Vehicle) -->
        <div class="grid grid-cols-2 gap-3 py-2 border-b border-slate-300 text-[10px]">
            
            <!-- Left: Recipient -->
            <div class="p-1.5 bg-slate-50/80 rounded border border-slate-200">
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Kepada Yth. (Penerima):</div>
                <div class="font-bold text-slate-900 uppercase mt-0.5">{{ $deliveryOrder->recipient_name }}</div>
                <div class="text-[9px] text-slate-600 leading-tight">{{ $deliveryOrder->recipient_address ?: 'Alamat pabrik / depo pembongkaran' }}</div>
                @if($deliveryOrder->recipient_phone)
                    <div class="text-[9px] text-slate-600 font-mono">Telp: {{ $deliveryOrder->recipient_phone }}</div>
                @endif
            </div>

            <!-- Right: Vehicle & Driver -->
            <div class="p-1.5 bg-slate-50/80 rounded border border-slate-200">
                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Armada & Pengemudi:</div>
                <div class="flex items-center justify-between mt-0.5">
                    <span class="font-mono font-black text-slate-900 bg-white px-1.5 py-0.5 rounded border border-slate-300">
                        {{ $deliveryOrder->vehicle_number }}
                    </span>
                    <span class="text-[9px] text-slate-500 font-medium">({{ $deliveryOrder->vehicle_type ?? 'Truk' }})</span>
                </div>
                <div class="text-[9px] text-slate-700 mt-1">
                    Sopir: <strong class="uppercase">{{ $deliveryOrder->driver_name }}</strong>
                    @if($deliveryOrder->driver_phone)
                        <span class="font-mono text-slate-500">({{ $deliveryOrder->driver_phone }})</span>
                    @endif
                </div>
            </div>

        </div>

        <!-- Cargo Table -->
        <div class="py-2">
            <table class="w-full text-left border-collapse border border-slate-400 text-[10px]">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 font-bold uppercase text-[9px] border-b border-slate-400">
                        <th class="py-1 px-2 border-r border-slate-300 w-8 text-center">No</th>
                        <th class="py-1 px-2 border-r border-slate-300">Jenis Barang & Spesifikasi Muatan</th>
                        <th class="py-1 px-2 border-r border-slate-300 w-24 text-right">Kemasan / Koli</th>
                        <th class="py-1 px-2 border-r border-slate-300 w-28 text-right">Berat Bersih (kg)</th>
                        <th class="py-1 px-2">Keterangan / Segel</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300">
                    @foreach($deliveryOrder->items as $idx => $item)
                        <tr>
                            <td class="py-1 px-2 text-center font-mono text-slate-500 border-r border-slate-300">{{ $idx + 1 }}</td>
                            <td class="py-1 px-2 font-bold text-slate-900 border-r border-slate-300">{{ $item->item_name }}</td>
                            <td class="py-1 px-2 text-right font-mono border-r border-slate-300">
                                {{ $item->package_qty > 0 ? $item->package_qty . ' Koli' : '-' }}
                            </td>
                            <td class="py-1 px-2 text-right font-mono font-bold text-slate-900 border-r border-slate-300">
                                {{ number_format($item->weight, 2) }} kg
                            </td>
                            <td class="py-1 px-2 text-[9px] text-slate-600 italic">
                                {{ $item->notes ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-900 font-bold bg-slate-50">
                    <tr>
                        <td colspan="2" class="py-1 px-2 uppercase text-slate-900 border-r border-slate-300">TOTAL MUATAN RESMI</td>
                        <td class="py-1 px-2 text-right font-mono border-r border-slate-300">{{ $deliveryOrder->total_packages }} Koli</td>
                        <td class="py-1 px-2 text-right font-mono font-black text-slate-900 border-r border-slate-300">
                            {{ number_format($deliveryOrder->total_weight, 2) }} kg
                        </td>
                        <td class="py-1 px-2 text-[8px] text-slate-400">Validitas muatan depo</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($deliveryOrder->notes)
            <div class="text-[9px] text-slate-600 italic">
                Instruksi Pengiriman: {{ $deliveryOrder->notes }}
            </div>
        @endif

        <!-- 4-Column Official Signatures -->
        <div class="mt-2 pt-2 border-t border-slate-300 grid grid-cols-4 gap-2 text-center text-[9px] text-slate-700">
            <div>
                <p class="font-medium">Penerima Pabrik,</p>
                <div class="h-10"></div>
                <p class="font-bold underline uppercase">( ............................ )</p>
                <p class="text-[8px] text-slate-400">Tanda Tangan & Cap</p>
            </div>

            <div>
                <p class="font-medium">Pengemudi / Sopir,</p>
                <div class="h-10"></div>
                <p class="font-bold underline uppercase">({{ $deliveryOrder->driver_name }})</p>
                <p class="text-[8px] text-slate-400">Armada Pengantar</p>
            </div>

            <div>
                <p class="font-medium">Petugas Timbangan,</p>
                <div class="h-10"></div>
                <p class="font-bold underline uppercase">( ............................ )</p>
                <p class="text-[8px] text-slate-400">Penimbang Gudang</p>
            </div>

            <div>
                <p class="font-medium">Hormat Kami (Pengirim),</p>
                <div class="h-10"></div>
                <p class="font-bold underline uppercase">({{ $deliveryOrder->user?->name ?? 'Pengirim' }})</p>
                <p class="text-[8px] text-slate-400">Staff Gudang Rongsok</p>
            </div>
        </div>

        <!-- Footer note -->
        <div class="mt-2 text-center text-[7.5px] text-slate-400 italic">
            Surat Jalan ini merupakan dokumen bukti penyerahan barang rongsok yang sah. Mohon diperiksa kembali sebelum ditandatangani.
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

