<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat_Jalan_{{ $deliveryOrder->delivery_number }}_{{ Str::slug($deliveryOrder->recipient_name) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: 210mm 148mm landscape; /* HVS Landscape Bagi 2 / Half Folio / A5 */
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
            .sheet-container {
                page-break-after: always;
                break-after: page;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .sheet-container:last-of-type,
            .sheet-container.is-last-visible {
                page-break-after: auto !important;
                break-after: auto !important;
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
    <div class="no-print w-full max-w-[200mm] mb-3 bg-white p-2.5 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-700">Pilihan Cetak:</span>
            <div class="inline-flex rounded-lg border border-slate-300 p-0.5 bg-slate-100 text-xs font-semibold">
                <button type="button" onclick="setCopy('all')" id="btn-copy-all" class="px-2.5 py-1 rounded-md transition-colors bg-white text-slate-900 shadow-xs">
                    2 Rangkap (Gudang & Supir)
                </button>
                <button type="button" onclick="setCopy('1')" id="btn-copy-1" class="px-2.5 py-1 rounded-md transition-colors text-slate-600 hover:text-slate-900">
                    Lembar 1 (Gudang)
                </button>
                <button type="button" onclick="setCopy('2')" id="btn-copy-2" class="px-2.5 py-1 rounded-md transition-colors text-slate-600 hover:text-slate-900">
                    Lembar 2 (Supir)
                </button>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak / Print</span>
            </button>
            <a href="{{ route('delivery-orders.pdf', [$deliveryOrder, 'copy' => $copy, 'download' => 1]) }}" class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span>Unduh PDF</span>
            </a>
            <a href="{{ route('delivery-orders.show', $deliveryOrder) }}" class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold">
                Kembali
            </a>
        </div>
    </div>

    <!-- SHEET 1: LEMBAR 1 (GUDANG) -->
    <div id="sheet-copy-1" class="sheet-container w-full max-w-[200mm] mb-6 print:mb-0">
        <div class="sj-card bg-white text-slate-900 border border-slate-300 rounded-xl shadow-md p-5 w-full text-[10px]">
            
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
                    <div class="mt-1 flex items-center justify-end">
                        <span class="inline-block px-2.5 py-0.5 bg-blue-100 text-blue-900 border border-blue-400 font-black text-[10px] rounded uppercase tracking-wide">
                            LEMBAR 1 : GUDANG
                        </span>
                    </div>
                    <div class="text-[8.5px] text-blue-800 font-semibold italic mt-0.5">
                        (Dipegang oleh Gudang / Arsip Depo)
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
                            <th class="py-1 px-2 border-r border-slate-300">Jenis Material Scrap & Daur Ulang</th>
                            <th class="py-1 px-2 border-r border-slate-300 w-24 text-right">Kemasan / Koli</th>
                            <th class="py-1 px-2 border-r border-slate-300 w-28 text-right">Berat Bersih (kg)</th>
                            <th class="py-1 px-2">Keterangan / Segel</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-300">
                        @foreach($deliveryOrder->grouped_master_items as $idx => $master)
                            <!-- Baris Master Barang (Akumulasi Total dari Master) -->
                            <tr class="{{ $master->has_variants ? 'bg-slate-50 font-bold' : '' }}">
                                <td class="py-1 px-2 text-center font-mono text-slate-500 border-r border-slate-300">{{ $idx + 1 }}</td>
                                <td class="py-1 px-2 font-bold text-slate-900 border-r border-slate-300">
                                    {{ $master->item_name }}
                                    <span class="text-slate-400 font-mono text-[9px] font-normal">({{ $master->item_code }})</span>
                                    @if($master->has_variants)
                                        <span class="ml-1 text-[8px] font-semibold text-blue-700 bg-blue-50 px-1 py-0.2 rounded border border-blue-200">
                                            Total Master ({{ $master->items->count() }} Varian)
                                        </span>
                                    @endif
                                </td>
                                <td class="py-1 px-2 text-right font-mono border-r border-slate-300 font-bold">
                                    {{ $master->total_packages > 0 ? $master->total_packages . ' Koli' : '-' }}
                                </td>
                                <td class="py-1 px-2 text-right font-mono font-bold text-slate-900 border-r border-slate-300">
                                    {{ number_format($master->total_weight, 2) }} kg
                                </td>
                                <td class="py-1 px-2 text-[9px] text-slate-600">
                                    @if(!$master->has_variants && $master->items->first()->notes)
                                        <span class="italic">{{ $master->items->first()->notes }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>

                            <!-- Rincian Jenis Varian (Bila Ada Varian) -->
                            @if($master->has_variants)
                                @foreach($master->items as $vItem)
                                    <tr class="bg-blue-50/20 text-[9px]">
                                        <td class="py-0.5 px-2 text-center font-mono text-blue-400 border-r border-slate-300">↳</td>
                                        <td class="py-0.5 px-2 pl-4 border-r border-slate-300">
                                            <span class="font-semibold text-blue-900">• Varian: {{ $vItem->item_type_name ?: 'Standar' }}</span>
                                            @if($vItem->effective_price > 0)
                                                <span class="text-emerald-700 font-mono font-medium ml-1">
                                                    (@ Rp {{ number_format($vItem->effective_price, 0, ',', '.') }}/kg)
                                                </span>
                                            @endif
                                            @if($vItem->notes)
                                                <span class="text-slate-500 italic ml-1">({{ $vItem->notes }})</span>
                                            @endif
                                        </td>
                                        <td class="py-0.5 px-2 text-right font-mono border-r border-slate-300 text-slate-700">
                                            {{ $vItem->package_qty > 0 ? $vItem->package_qty . ' Koli' : '-' }}
                                        </td>
                                        <td class="py-0.5 px-2 text-right font-mono font-semibold text-slate-800 border-r border-slate-300">
                                            {{ number_format($vItem->weight, 2) }} kg
                                        </td>
                                        <td class="py-0.5 px-2 text-[8.5px] text-slate-600">
                                            @if($vItem->effective_price > 0)
                                                <span class="font-mono font-bold text-slate-800">Rp {{ number_format($vItem->effective_subtotal, 0, ',', '.') }}</span>
                                            @else
                                                <span class="italic text-slate-400">{{ $vItem->notes ?: '-' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
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

            <!-- Keterangan Rangkap Banner -->
            <div class="mt-2 py-1.5 px-3 bg-blue-50/90 border border-blue-200 rounded-lg text-[9px] text-slate-800 flex items-center justify-between">
                <div>
                    <strong>ℹ️ Keterangan Rangkap:</strong> Surat jalan ini dibuat <strong>2 (dua) rangkap</strong> — 
                    <span class="text-blue-700 font-bold underline">Lembar 1 dipegang oleh Gudang</span> dan 
                    <span class="text-amber-700 font-bold">Lembar 2 dipegang oleh Supir</span>.
                </div>
                <div class="px-2 py-0.5 bg-blue-600 text-white font-black text-[8px] uppercase tracking-wider rounded">
                    Arsip Gudang
                </div>
            </div>

            <!-- 4-Column Official Signatures -->
            <div class="mt-2 pt-2 border-t border-slate-300 grid grid-cols-4 gap-2 text-center text-[9px] text-slate-700">
                <div>
                    <p class="font-medium">Penerima Pabrik,</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline uppercase">( ............................ )</p>
                    <p class="text-[8px] text-slate-400">Tanda Tangan & Cap</p>
                </div>

                <div>
                    <p class="font-medium">Pengemudi / Sopir,</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline uppercase">({{ $deliveryOrder->driver_name }})</p>
                    <p class="text-[8px] text-slate-400">Armada Pengantar</p>
                </div>

                <div>
                    <p class="font-medium">Petugas Timbangan,</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline uppercase">( ............................ )</p>
                    <p class="text-[8px] text-slate-400">Penimbang Gudang</p>
                </div>

                <div>
                    <p class="font-medium">Hormat Kami (Pengirim),</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline uppercase">({{ $deliveryOrder->user?->name ?? 'Pengirim' }})</p>
                    <p class="text-[8px] text-blue-700 font-bold">Staff Gudang (Arsip Depo)</p>
                </div>
            </div>

            <!-- Footer note -->
            <div class="mt-1.5 text-center text-[7.5px] text-slate-400 italic">
                {{ $settings['delivery_order_notes'] ?? 'Surat jalan ini dibuat 2 (dua) rangkap: Lembar 1 dipegang oleh gudang dan Lembar 2 dipegang oleh supir. Dokumen sah penyerahan material scrap & daur ulang.' }}
            </div>

        </div>
    </div>

    <!-- SHEET 2: LEMBAR 2 (SUPIR) -->
    <div id="sheet-copy-2" class="sheet-container w-full max-w-[200mm]">
        <div class="sj-card bg-white text-slate-900 border border-slate-300 rounded-xl shadow-md p-5 w-full text-[10px]">
            
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
                    <div class="mt-1 flex items-center justify-end">
                        <span class="inline-block px-2.5 py-0.5 bg-amber-100 text-amber-900 border border-amber-400 font-black text-[10px] rounded uppercase tracking-wide">
                            LEMBAR 2 : SUPIR
                        </span>
                    </div>
                    <div class="text-[8.5px] text-amber-800 font-semibold italic mt-0.5">
                        (Dipegang oleh Supir / Dibawa Armada)
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
                            <th class="py-1 px-2 border-r border-slate-300">Jenis Material Scrap & Daur Ulang</th>
                            <th class="py-1 px-2 border-r border-slate-300 w-24 text-right">Kemasan / Koli</th>
                            <th class="py-1 px-2 border-r border-slate-300 w-28 text-right">Berat Bersih (kg)</th>
                            <th class="py-1 px-2">Keterangan / Segel</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-300">
                        @foreach($deliveryOrder->grouped_master_items as $idx => $master)
                            <!-- Baris Master Barang (Akumulasi Total dari Master) -->
                            <tr class="{{ $master->has_variants ? 'bg-slate-50 font-bold' : '' }}">
                                <td class="py-1 px-2 text-center font-mono text-slate-500 border-r border-slate-300">{{ $idx + 1 }}</td>
                                <td class="py-1 px-2 font-bold text-slate-900 border-r border-slate-300">
                                    {{ $master->item_name }}
                                    <span class="text-slate-400 font-mono text-[9px] font-normal">({{ $master->item_code }})</span>
                                    @if($master->has_variants)
                                        <span class="ml-1 text-[8px] font-semibold text-blue-700 bg-blue-50 px-1 py-0.2 rounded border border-blue-200">
                                            Total Master ({{ $master->items->count() }} Varian)
                                        </span>
                                    @endif
                                </td>
                                <td class="py-1 px-2 text-right font-mono border-r border-slate-300 font-bold">
                                    {{ $master->total_packages > 0 ? $master->total_packages . ' Koli' : '-' }}
                                </td>
                                <td class="py-1 px-2 text-right font-mono font-bold text-slate-900 border-r border-slate-300">
                                    {{ number_format($master->total_weight, 2) }} kg
                                </td>
                                <td class="py-1 px-2 text-[9px] text-slate-600">
                                    @if(!$master->has_variants && $master->items->first()->notes)
                                        <span class="italic">{{ $master->items->first()->notes }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>

                            <!-- Rincian Jenis Varian (Bila Ada Varian) -->
                            @if($master->has_variants)
                                @foreach($master->items as $vItem)
                                    <tr class="bg-blue-50/20 text-[9px]">
                                        <td class="py-0.5 px-2 text-center font-mono text-blue-400 border-r border-slate-300">↳</td>
                                        <td class="py-0.5 px-2 pl-4 border-r border-slate-300">
                                            <span class="font-semibold text-blue-900">• Varian: {{ $vItem->item_type_name ?: 'Standar' }}</span>
                                            @if($vItem->effective_price > 0)
                                                <span class="text-emerald-700 font-mono font-medium ml-1">
                                                    (@ Rp {{ number_format($vItem->effective_price, 0, ',', '.') }}/kg)
                                                </span>
                                            @endif
                                            @if($vItem->notes)
                                                <span class="text-slate-500 italic ml-1">({{ $vItem->notes }})</span>
                                            @endif
                                        </td>
                                        <td class="py-0.5 px-2 text-right font-mono border-r border-slate-300 text-slate-700">
                                            {{ $vItem->package_qty > 0 ? $vItem->package_qty . ' Koli' : '-' }}
                                        </td>
                                        <td class="py-0.5 px-2 text-right font-mono font-semibold text-slate-800 border-r border-slate-300">
                                            {{ number_format($vItem->weight, 2) }} kg
                                        </td>
                                        <td class="py-0.5 px-2 text-[8.5px] text-slate-600">
                                            @if($vItem->effective_price > 0)
                                                <span class="font-mono font-bold text-slate-800">Rp {{ number_format($vItem->effective_subtotal, 0, ',', '.') }}</span>
                                            @else
                                                <span class="italic text-slate-400">{{ $vItem->notes ?: '-' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
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

            <!-- Keterangan Rangkap Banner -->
            <div class="mt-2 py-1.5 px-3 bg-amber-50/90 border border-amber-200 rounded-lg text-[9px] text-slate-800 flex items-center justify-between">
                <div>
                    <strong>ℹ️ Keterangan Rangkap:</strong> Surat jalan ini dibuat <strong>2 (dua) rangkap</strong> — 
                    <span class="text-blue-700 font-bold">Lembar 1 dipegang oleh Gudang</span> dan 
                    <span class="text-amber-700 font-bold underline">Lembar 2 dipegang oleh Supir</span>.
                </div>
                <div class="px-2 py-0.5 bg-amber-600 text-white font-black text-[8px] uppercase tracking-wider rounded">
                    Dibawa Supir
                </div>
            </div>

            <!-- 4-Column Official Signatures -->
            <div class="mt-2 pt-2 border-t border-slate-300 grid grid-cols-4 gap-2 text-center text-[9px] text-slate-700">
                <div>
                    <p class="font-medium">Penerima Pabrik,</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline uppercase">( ............................ )</p>
                    <p class="text-[8px] text-amber-700 font-bold">Tanda Tangan & Cap Asli</p>
                </div>

                <div>
                    <p class="font-medium">Pengemudi / Sopir,</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline uppercase">({{ $deliveryOrder->driver_name }})</p>
                    <p class="text-[8px] text-amber-700 font-bold">Pengemudi (Bawa ke Tujuan)</p>
                </div>

                <div>
                    <p class="font-medium">Petugas Timbangan,</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline uppercase">( ............................ )</p>
                    <p class="text-[8px] text-slate-400">Penimbang Gudang</p>
                </div>

                <div>
                    <p class="font-medium">Hormat Kami (Pengirim),</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline uppercase">({{ $deliveryOrder->user?->name ?? 'Pengirim' }})</p>
                    <p class="text-[8px] text-slate-400">Staff Gudang Pengirim</p>
                </div>
            </div>

            <!-- Footer note -->
            <div class="mt-1.5 text-center text-[7.5px] text-slate-400 italic">
                {{ $settings['delivery_order_notes'] ?? 'Surat jalan ini dibuat 2 (dua) rangkap: Lembar 1 dipegang oleh gudang dan Lembar 2 dipegang oleh supir. Dokumen sah penyerahan material scrap & daur ulang.' }}
            </div>

        </div>
    </div>

    <script>
        function setCopy(type) {
            const sheet1 = document.getElementById('sheet-copy-1');
            const sheet2 = document.getElementById('sheet-copy-2');
            const btnAll = document.getElementById('btn-copy-all');
            const btn1 = document.getElementById('btn-copy-1');
            const btn2 = document.getElementById('btn-copy-2');

            // Reset buttons
            [btnAll, btn1, btn2].forEach(b => {
                b.className = 'px-2.5 py-1 rounded-md transition-colors text-slate-600 hover:text-slate-900';
            });

            if (type === '1') {
                sheet1.style.display = 'block';
                sheet2.style.display = 'none';
                sheet1.classList.add('is-last-visible');
                btn1.className = 'px-2.5 py-1 rounded-md transition-colors bg-blue-600 text-white font-bold shadow-xs';
            } else if (type === '2') {
                sheet1.style.display = 'none';
                sheet2.style.display = 'block';
                sheet2.classList.add('is-last-visible');
                btn2.className = 'px-2.5 py-1 rounded-md transition-colors bg-amber-600 text-white font-bold shadow-xs';
            } else {
                sheet1.style.display = 'block';
                sheet2.style.display = 'block';
                sheet1.classList.remove('is-last-visible');
                sheet2.classList.add('is-last-visible');
                btnAll.className = 'px-2.5 py-1 rounded-md transition-colors bg-white text-slate-900 font-bold shadow-xs';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
            const urlParams = new URLSearchParams(window.location.search);
            const initialCopy = urlParams.get('copy') || '{{ $copy ?? "all" }}';
            if (initialCopy === '1' || initialCopy === '2') {
                setCopy(initialCopy);
            } else {
                setCopy('all');
            }
            if (urlParams.get('autoprint') === '1') {
                setTimeout(() => window.print(), 300);
            }
        });
    </script>
</body>
</html>
