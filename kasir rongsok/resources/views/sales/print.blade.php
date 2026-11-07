<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice_{{ $sale->invoice_number }}</title>
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
        <span class="text-xs font-semibold text-slate-700">Preview Invoice Penjualan Rongsok</span>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak Invoice</span>
            </button>
            <a href="{{ route('sales.pdf', [$sale, 'download' => 1]) }}" class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span>Unduh PDF</span>
            </a>
            <a href="{{ route('sales.show', $sale) }}" class="px-3 py-1.5 rounded-lg bg-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-300">
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
                <span class="text-sm font-bold text-slate-900 uppercase">
                    INVOICE
                </span>
                <div class="font-mono font-bold text-slate-800 text-sm mt-1">{{ $sale->invoice_number }}</div>
                <div class="text-[11px] text-slate-500">{{ $sale->transaction_date->format('d/m/Y H:i') }}</div>
                @if($sale->isPickup())
                    <span class="inline-block mt-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        Diambil Langsung Pembeli
                    </span>
                @else
                    <span class="inline-block mt-1 text-[10px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                        Pengiriman Armada Gudang
                    </span>
                @endif
            </div>
        </div>

        <!-- Buyer & Shipping Info -->
        <div class="grid grid-cols-2 gap-4 py-4 border-b border-slate-200">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">
                    {{ $sale->isPickup() ? 'Pembeli (Diambil Langsung):' : 'Tujuan Pengiriman (Pembeli):' }}
                </span>
                <div class="font-bold text-slate-900 text-sm">{{ $sale->buyer_name }}</div>
                <div class="text-slate-600">{{ $sale->buyer_address ?? '-' }}</div>
                <div class="text-slate-600 font-mono">Telp: {{ $sale->buyer_phone ?? '-' }}</div>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">
                    Metode Pembayaran:
                </span>
                <div class="font-bold text-slate-900 text-xs">
                    {{ $sale->paymentMethod?->name ?? 'Tunai' }}
                </div>
                @if($sale->isBankPayment())
                    <div class="text-slate-600 font-mono text-[11px] mt-0.5">
                        @if($sale->getBankAccountNumber())
                            No. Rek: <span class="font-bold">{{ $sale->getBankAccountNumber() }}</span>
                        @endif
                        @if($sale->getBankAccountName())
                            (a/n {{ $sale->getBankAccountName() }})
                        @endif
                        @if(!$sale->getBankAccountNumber() && !$sale->getBankAccountName() && !empty($sale->bank_info))
                            {{ $sale->bank_info }}
                        @endif
                    </div>
                @endif

                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mt-2 mb-1">
                    {{ $sale->isPickup() ? 'Metode Pengambilan:' : 'Catatan Transportasi:' }}
                </span>
                <div class="text-slate-700 bg-slate-50 p-2 rounded border border-slate-200 text-[11px]">
                    @if($sale->isPickup())
                        {{ $sale->notes ?? 'Diambil langsung oleh pembeli di gudang/depo (Tanpa kendaraan perusahaan)' }}
                    @else
                        {{ $sale->notes ?? 'Pengiriman armada darat' }}
                    @endif
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
                    @foreach($sale->grouped_master_items as $idx => $master)
                        <!-- Baris Master Barang (Perhitungan Akumulasi Total) -->
                        <tr class="{{ $master->has_variants ? 'bg-slate-50 font-bold' : '' }}">
                            <td class="py-2 px-1 text-slate-500 font-mono">{{ $idx + 1 }}</td>
                            <td class="py-2 px-2 font-sans text-slate-900">
                                <span class="font-bold">{{ $master->item_name }}</span>
                                <span class="text-slate-400 font-mono text-[10px] font-normal">({{ $master->item_code }})</span>
                                @if($master->has_variants)
                                    <span class="ml-1 text-[10px] font-semibold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200">
                                        Total Master ({{ $master->items->count() }} Varian)
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right font-bold text-slate-900 font-mono">
                                {{ \App\Services\FormatHelper::weight($master->total_weight) }} kg
                            </td>
                            <td class="py-2 px-2 text-right font-mono">
                                @if($master->has_variants)
                                    <span class="text-slate-400 text-[10px] italic">Rerata Rp {{ \App\Services\FormatHelper::number($master->avg_sell_price) }}</span>
                                @else
                                    Rp {{ \App\Services\FormatHelper::number($master->items->first()->sell_price_per_kg) }}
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right font-bold text-slate-900 font-mono">
                                Rp {{ \App\Services\FormatHelper::number($master->total_subtotal) }}
                            </td>
                        </tr>

                        <!-- Rincian Jenis Varian (Bila Ada Varian) -->
                        @if($master->has_variants)
                            @foreach($master->items as $vItem)
                                <tr class="bg-blue-50/20 text-[11px]">
                                    <td class="py-1 px-1 text-center text-blue-400 font-mono">↳</td>
                                    <td class="py-1 px-2 pl-6 font-sans">
                                        <span class="font-semibold text-blue-900">• Varian: {{ $vItem->item_type_name ?: 'Standar' }}</span>
                                        @if($vItem->notes)
                                            <span class="text-slate-400 italic text-[10px]">({{ $vItem->notes }})</span>
                                        @endif
                                    </td>
                                    <td class="py-1 px-2 text-right font-mono text-slate-700">
                                        {{ \App\Services\FormatHelper::weight($vItem->weight) }} kg
                                    </td>
                                    <td class="py-1 px-2 text-right font-mono text-slate-600">
                                        Rp {{ \App\Services\FormatHelper::number($vItem->sell_price_per_kg) }}
                                    </td>
                                    <td class="py-1 px-2 text-right font-mono font-bold text-slate-800">
                                        Rp {{ \App\Services\FormatHelper::number($vItem->subtotal) }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
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
            </div>
        </div>

        @if(!empty($settings['delivery_order_notes']))
            <div class="mt-4 pt-2 border-t border-dashed border-slate-300 text-center text-[10px] text-slate-500 italic">
                {{ $settings['delivery_order_notes'] }}
            </div>
        @endif

        <!-- Signatures -->
        @if($sale->isPickup())
            <div class="grid grid-cols-2 gap-12 pt-10 text-center font-sans text-xs text-slate-600 max-w-lg mx-auto">
                <div>
                    <p class="font-medium text-slate-700">Hormat Kami (Gudang/Kasir)</p>
                    <div class="h-16 flex items-center justify-center">
                        @if(\App\Models\BusinessSetting::hasOwnerSignature())
                            <img src="{{ \App\Models\BusinessSetting::ownerSignatureUrl() }}" alt="TTD Owner" class="h-14 max-w-[140px] object-contain mx-auto">
                        @endif
                    </div>
                    <p class="font-bold border-t border-slate-300 pt-1">({{ $sale->user?->name ?? 'Kasir / Pengelola' }})</p>
                </div>
                <div>
                    <p class="font-medium text-slate-700">Penerima / Pembeli</p>
                    <div class="h-16"></div>
                    <p class="font-bold border-t border-slate-300 pt-1">({{ $sale->buyer_name }})</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-3 gap-6 pt-10 text-center font-sans text-xs text-slate-600">
                <div>
                    <p class="font-medium text-slate-700">Pengirim (Gudang)</p>
                    <div class="h-16 flex items-center justify-center">
                        @if(\App\Models\BusinessSetting::hasOwnerSignature())
                            <img src="{{ \App\Models\BusinessSetting::ownerSignatureUrl() }}" alt="TTD Owner" class="h-14 max-w-[140px] object-contain mx-auto">
                        @endif
                    </div>
                    <p class="font-bold border-t border-slate-300 pt-1">({{ $sale->user?->name ?? 'Gudang' }})</p>
                </div>
                <div>
                    <p class="font-medium text-slate-700">Sopir / Ekspedisi</p>
                    <div class="h-16"></div>
                    <p class="font-bold border-t border-slate-300 pt-1">(....................................)</p>
                </div>
                <div>
                    <p class="font-medium text-slate-700">Penerima (Pabrik / Pembeli)</p>
                    <div class="h-16"></div>
                    <p class="font-bold border-t border-slate-300 pt-1">({{ $sale->buyer_name }})</p>
                </div>
            </div>
        @endif

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

