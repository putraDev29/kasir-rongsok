<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuratJalan_{{ $sale->invoice_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; background: #fff; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center">

    <div class="no-print w-full max-w-2xl mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-700">Surat Jalan / Invoice Pengiriman Rongsok</span>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak Dokumen</span>
            </button>
            <a href="{{ route('sales.show', $sale) }}" class="px-3 py-1.5 rounded-lg bg-slate-200 text-slate-700 text-xs font-semibold">
                Kembali
            </a>
        </div>
    </div>

    <div class="bg-white text-slate-900 shadow-md border border-slate-200 max-w-3xl w-full p-8 rounded-xl font-sans text-xs">
        
        <!-- Header -->
        <div class="flex justify-between items-start pb-4 border-b border-slate-300">
            <div class="flex items-center gap-3">
                @if(\App\Models\BusinessSetting::hasLogo())
                    <img src="{{ \App\Models\BusinessSetting::logoUrl() }}" alt="Logo" class="h-12 max-w-[90px] object-contain">
                @endif
                <div>
                    <h1 class="text-lg font-black uppercase tracking-tight text-slate-900">
                        {{ $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR' }}
                    </h1>
                    <p class="text-slate-500 text-[11px] mt-0.5 leading-snug">
                        {{ $settings['company_address'] ?? 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok' }}<br>
                        Telp: {{ $settings['company_phone'] ?? '0812-3456-7890' }}
                    </p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-sm font-bold text-blue-700 uppercase">SURAT JALAN & INVOICE</span>
                <div class="font-mono font-bold text-slate-800 text-sm mt-1">{{ $sale->invoice_number }}</div>
                <div class="text-[11px] text-slate-500">{{ $sale->transaction_date->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <!-- Buyer & Shipping Info -->
        <div class="grid grid-cols-2 gap-4 py-4 border-b border-slate-200">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tujuan Pengiriman (Pembeli):</span>
                <div class="font-bold text-slate-900 text-sm">{{ $sale->buyer_name }}</div>
                <div class="text-slate-600">{{ $sale->buyer_address ?? '-' }}</div>
                <div class="text-slate-600 font-mono">Telp: {{ $sale->buyer_phone ?? '-' }}</div>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Catatan Transportasi:</span>
                <div class="text-slate-700 bg-slate-50 p-2.5 rounded border border-slate-200 text-[11px]">
                    {{ $sale->notes ?? 'Pengiriman armada darat' }}
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="py-4">
            <table class="w-full text-left font-mono text-xs">
                <thead>
                    <tr class="border-b-2 border-slate-300 text-[10px] uppercase font-sans text-slate-600">
                        <th class="py-2 px-1">#</th>
                        <th class="py-2 px-2">Nama Material Scrap & Daur Ulang</th>
                        <th class="py-2 px-2 text-right">Berat (kg)</th>
                        <th class="py-2 px-2 text-right">Harga Satuan/kg</th>
                        <th class="py-2 px-2 text-right font-bold">Total Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($sale->items as $idx => $item)
                        <tr>
                            <td class="py-2 px-1 text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-2 px-2 font-sans font-bold text-slate-900">
                                {{ $item->item_name_snapshot }}
                                @if($item->item_type_name)
                                    <span class="text-xs font-semibold text-emerald-700">({{ $item->item_type_name }})</span>
                                @endif
                                <span class="text-slate-400 font-mono text-[10px] font-normal">({{ $item->item_code_snapshot }})</span>
                            </td>
                            <td class="py-2 px-2 text-right font-bold text-slate-800">
                                @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                    {{ \App\Services\FormatHelper::number($item->quantity ?? 1) }} pcs
                                @else
                                    {{ \App\Services\FormatHelper::weight($item->weight) }} kg
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right">Rp {{ \App\Services\FormatHelper::number($item->sell_price_per_kg) }}</td>
                            <td class="py-2 px-2 text-right font-bold text-slate-900">Rp {{ \App\Services\FormatHelper::number($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Calculation Summary -->
        <div class="pt-2 border-t border-slate-300 flex justify-end font-mono">
            <div class="w-64 space-y-1">
                <div class="flex justify-between text-slate-600">
                    <span>Total Berat:</span>
                    <span class="font-bold text-slate-900">{{ \App\Services\FormatHelper::weight($sale->total_weight) }} kg</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span>Rp {{ \App\Services\FormatHelper::number($sale->subtotal_amount) }}</span>
                </div>
                @if($sale->discount_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Diskon:</span>
                        <span>- Rp {{ \App\Services\FormatHelper::number($sale->discount_amount) }}</span>
                    </div>
                @endif
                <div class="pt-1.5 border-t-2 border-slate-900 flex justify-between font-bold text-sm text-slate-900 font-sans">
                    <span>TOTAL TAGIHAN:</span>
                    <span class="font-mono text-base">Rp {{ \App\Services\FormatHelper::number($sale->final_amount) }}</span>
                </div>
                @if($sale->bank_info)
                    <div class="text-[10px] text-slate-500 pt-1 text-right">
                        Rekening: {{ $sale->bank_info }}
                    </div>
                @endif
            </div>
        </div>

        @if(!empty($settings['delivery_order_notes']))
            <div class="mt-4 pt-2 border-t border-dashed border-slate-300 text-center text-[10px] text-slate-500 italic">
                {{ $settings['delivery_order_notes'] }}
            </div>
        @endif

        <!-- Signatures -->
        <div class="grid grid-cols-3 gap-6 pt-10 text-center font-sans text-xs text-slate-600">
            <div>
                <p>Pengirim (Gudang)</p>
                <div class="h-16"></div>
                <p class="font-bold border-t border-slate-300 pt-1">({{ $sale->user?->name }})</p>
            </div>
            <div>
                <p>Sopir / Ekspedisi</p>
                <div class="h-16"></div>
                <p class="font-bold border-t border-slate-300 pt-1">(....................................)</p>
            </div>
            <div>
                <p>Penerima (Pabrik / Pembeli)</p>
                <div class="h-16"></div>
                <p class="font-bold border-t border-slate-300 pt-1">({{ $sale->buyer_name }})</p>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }
        });
    </script>
</body>
</html>

