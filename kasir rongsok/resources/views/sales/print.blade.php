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

    <div class="no-print w-full max-w-3xl mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <span class="text-xs font-semibold text-slate-700">Preview Invoice Penjualan Rongsok</span>
        <div class="flex flex-wrap items-center gap-1.5 justify-end">
            <!-- Direct Bluetooth ESC/POS Print (No RawBT, 100% Free of Watermark) -->
            <button type="button" 
                    id="btnBluetoothPrint"
                    onclick="handleBluetoothPrint()" 
                    class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
                    title="Cetak langsung ke printer Bluetooth via Web Bluetooth (100% Bebas Watermark RawBT)">
                <i data-lucide="bluetooth" class="w-3.5 h-3.5"></i>
                <span id="btnBluetoothText">Cetak Bluetooth</span>
            </button>

            <!-- Quick Printer Intent Button (For Android devices) -->
            <button type="button" 
                    id="btnQuickPrinter"
                    onclick="handleQuickPrinter()" 
                    class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold text-xs flex items-center gap-1 shadow-xs cursor-pointer"
                    title="Cetak via Quick Printer Android (Gratis & Bebas Watermark)">
                <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                <span>Quick Printer</span>
            </button>

            <!-- Standard Browser Print (Dialog Cetak Windows/USB/PDF) -->
            <button type="button" 
                    onclick="window.print()" 
                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
                    title="Cetak lewat dialog browser standar (Printer USB / Windows / PDF)">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak Invoice</span>
            </button>

            <a href="{{ route('sales.show', $sale) }}" class="px-2.5 py-1.5 rounded-lg bg-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-300">
                Kembali
            </a>
        </div>
    </div>

    <!-- Status Alert for Bluetooth Printing -->
    <div id="printStatusAlert" class="hidden no-print w-full max-w-3xl mb-3 px-3.5 py-2.5 rounded-xl text-xs font-medium border flex items-center justify-between gap-2"></div>

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

                @if($sale->isDelivery() && $sale->deliveryOrder)
                    <div class="mt-2 p-2 bg-blue-50/70 rounded border border-blue-200 text-[11px] text-blue-950 space-y-0.5">
                        <div><strong>Supir / Armada:</strong> {{ $sale->deliveryOrder->driver_name }} ({{ $sale->deliveryOrder->vehicle_number }}{{ $sale->deliveryOrder->driver_phone ? ' - Telp: '.$sale->deliveryOrder->driver_phone : '' }})</div>
                        <div class="font-mono text-[10px] text-blue-900">
                            Ongkos Armada: <strong>{{ ($sale->deliveryOrder->shipping_cost ?? 0) > 0 ? 'Rp ' . \App\Services\FormatHelper::number($sale->deliveryOrder->shipping_cost) : '-' }}</strong> | 
                            Bongkar: <strong>{{ ($sale->deliveryOrder->loading_cost ?? 0) > 0 ? 'Rp ' . \App\Services\FormatHelper::number($sale->deliveryOrder->loading_cost) : '-' }}</strong>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <div class="py-4">
            <table class="w-full text-left font-mono text-xs">
                <thead>
                    <tr class="border-b-2 border-slate-300 text-[10px] uppercase font-sans text-slate-600">
                        <th class="py-2 px-1">#</th>
                        <th class="py-2 px-2">Nama Material Scrap & Daur Ulang</th>
                        <th class="py-2 px-2 text-right">Berat / Satuan</th>
                        <th class="py-2 px-2 text-right">Harga Beli / Satuan</th>
                        <th class="py-2 px-2 text-right">Subtotal Beli</th>
                        <th class="py-2 px-2 text-right">Harga Jual / Satuan</th>
                        <th class="py-2 px-2 text-right font-bold text-slate-900">Subtotal Harga Jual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($sale->grouped_master_items as $idx => $master)
                        @php
                            $isPcs = ($master->unit_type === 'pcs' || $master->unit_type === 'satuan');
                            $masterBuyPrice = $master->has_variants ? ($master->avg_buy_price ?? 0) : ($master->items->first()->buy_price_snapshot ?: ($master->items->first()->scrapItem?->buy_price_per_kg ?? 0));
                            $masterSellPrice = $master->has_variants ? $master->avg_sell_price : $master->items->first()->sell_price_per_kg;
                        @endphp
                        <!-- Baris Master Barang (Perhitungan Akumulasi Total) -->
                        <tr class="{{ $master->has_variants ? 'bg-slate-50 font-bold' : '' }}">
                            <td class="py-2 px-1 text-slate-500 font-mono">{{ $idx + 1 }}</td>
                            <td class="py-2 px-2 font-sans text-slate-900">
                                <span class="font-bold">{{ $master->item_name }}</span>
                                @if($master->has_variants)
                                    <span class="ml-1 text-[10px] font-semibold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200">
                                        Total Master ({{ $master->items->count() }} Varian)
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right font-bold text-slate-900 font-mono">
                                @if($isPcs)
                                    {{ \App\Services\FormatHelper::numberOrDash($master->total_quantity) }} {{ $master->total_quantity > 0 ? 'pcs' : '' }}
                                @else
                                    {{ \App\Services\FormatHelper::weightOrDash($master->total_weight) }}
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right font-mono text-slate-700">
                                @if($master->has_variants)
                                    <span class="text-slate-400 text-[10px] italic">{{ $master->avg_buy_price > 0 ? 'Rerata Rp ' . \App\Services\FormatHelper::number($master->avg_buy_price) : '-' }}</span>
                                @else
                                    {{ \App\Services\FormatHelper::rupiahOrDash($masterBuyPrice) }}
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right font-mono text-slate-700">
                                {{ \App\Services\FormatHelper::rupiahOrDash($master->total_cogs) }}
                            </td>
                            <td class="py-2 px-2 text-right font-mono text-slate-700">
                                @if($master->has_variants)
                                    <span class="text-slate-400 text-[10px] italic">{{ $master->avg_sell_price > 0 ? 'Rerata Rp ' . \App\Services\FormatHelper::number($master->avg_sell_price) : '-' }}</span>
                                @else
                                    {{ \App\Services\FormatHelper::rupiahOrDash($masterSellPrice) }}
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right font-bold text-slate-900 font-mono">
                                {{ \App\Services\FormatHelper::rupiahOrDash($master->total_subtotal) }}
                            </td>
                        </tr>

                        <!-- Rincian Jenis Varian (Bila Ada Varian) -->
                        @if($master->has_variants)
                            @foreach($master->items as $vItem)
                                @php
                                    $vIsPcs = ($vItem->unit_type === 'pcs' || $vItem->unit_type === 'satuan');
                                    $vBuyPrice = $vItem->buy_price_snapshot ?: ($vItem->scrapItem?->buy_price_per_kg ?? 0);
                                @endphp
                                <tr class="bg-blue-50/20 text-[11px]">
                                    <td class="py-1 px-1 text-center text-blue-400 font-mono">↳</td>
                                    <td class="py-1 px-2 pl-6 font-sans">
                                        <span class="font-semibold text-blue-900">• Varian: {{ $vItem->item_type_name ?: 'Standar' }}</span>
                                        @if($vItem->notes)
                                            <span class="text-slate-400 italic text-[10px]">({{ $vItem->notes }})</span>
                                        @endif
                                    </td>
                                    <td class="py-1 px-2 text-right font-mono text-slate-700">
                                        @if($vIsPcs)
                                            {{ \App\Services\FormatHelper::numberOrDash($vItem->quantity) }} {{ $vItem->quantity > 0 ? 'pcs' : '' }}
                                        @else
                                            {{ \App\Services\FormatHelper::weightOrDash($vItem->weight) }}
                                        @endif
                                    </td>
                                    <td class="py-1 px-2 text-right font-mono text-slate-600">
                                        {{ \App\Services\FormatHelper::rupiahOrDash($vBuyPrice) }}
                                    </td>
                                    <td class="py-1 px-2 text-right font-mono text-slate-600">
                                        {{ \App\Services\FormatHelper::rupiahOrDash($vItem->cogs_subtotal) }}
                                    </td>
                                    <td class="py-1 px-2 text-right font-mono text-slate-600">
                                        {{ \App\Services\FormatHelper::rupiahOrDash($vItem->sell_price_per_kg) }}
                                    </td>
                                    <td class="py-1 px-2 text-right font-mono font-bold text-slate-800">
                                        {{ \App\Services\FormatHelper::rupiahOrDash($vItem->subtotal) }}
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
            <div class="w-72 space-y-1">
                <div class="flex justify-between text-slate-600">
                    <span>Total Muatan / Berat:</span>
                    <span class="font-bold text-slate-900">{{ $sale->total_cargo_display }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Total Subtotal Beli:</span>
                    <span>{{ \App\Services\FormatHelper::rupiahOrDash($sale->estimated_cogs) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal Harga Jual:</span>
                    <span>{{ \App\Services\FormatHelper::rupiahOrDash($sale->subtotal_amount) }}</span>
                </div>
                @if($sale->discount_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Diskon:</span>
                        <span>- Rp {{ \App\Services\FormatHelper::number($sale->discount_amount) }}</span>
                    </div>
                @endif
                <div class="pt-1.5 border-t-2 border-slate-900 flex justify-between font-bold text-sm text-slate-900 font-sans">
                    <span>TOTAL TAGIHAN:</span>
                    <span class="font-mono text-base">{{ \App\Services\FormatHelper::rupiahOrDash($sale->final_amount) }}</span>
                </div>
            </div>
        </div>

    </div>

    <script>
        window.receiptData = {
            type: 'sale',
            companyName: @json($settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR'),
            companyAddress: @json($settings['company_address'] ?? ''),
            companyPhone: @json($settings['company_phone'] ?? ''),
            invoiceNumber: @json($sale->invoice_number),
            transactionDate: @json($sale->transaction_date->format('d/m/Y H:i')),
            buyerName: @json($sale->buyer_name),
            buyerPhone: @json($sale->buyer_phone),
            cashierName: @json($sale->user?->name ?? 'Kasir'),
            paymentMethod: @json($sale->paymentMethod?->name ?? 'Tunai'),
            isBankPayment: @json($sale->isBankPayment()),
            bankAccountNumber: @json($sale->getBankAccountNumber()),
            bankAccountName: @json($sale->getBankAccountName()),
            bankInfo: @json($sale->bank_info),
            deliveryType: @json($sale->isPickup() ? 'Ambil Sendiri di Depo' : 'Kirim Armada'),
            totalWeight: @json(\App\Services\FormatHelper::weightOrDash($sale->total_weight)),
            subtotalAmount: @json(\App\Services\FormatHelper::rupiahOrDash($sale->subtotal_amount)),
            discountAmount: @json($sale->discount_amount > 0 ? \App\Services\FormatHelper::rupiah($sale->discount_amount) : null),
            finalAmount: @json(\App\Services\FormatHelper::rupiahOrDash($sale->final_amount)),
            items: [
                @foreach($sale->items as $item)
                    {
                        name: @json(($item->scrapItem?->name ?? 'Barang') . ($item->item_type_name ? ' (' . $item->item_type_name . ')' : '')),
                        weight: @json(\App\Services\FormatHelper::weightOrDash($item->weight)),
                        price: @json(\App\Services\FormatHelper::rupiahOrDash($item->sell_price_per_kg)),
                        subtotal: @json(\App\Services\FormatHelper::rupiahOrDash($item->subtotal))
                    },
                @endforeach
            ],
            footer: @json($settings['receipt_footer'] ?? 'Terima kasih atas kerja samanya.')
        };

        const charWidth = 32;

        function showStatus(msg, type = 'info') {
            const el = document.getElementById('printStatusAlert');
            if (!el) return;
            el.className = 'no-print w-full max-w-3xl mb-3 px-3.5 py-2.5 rounded-xl text-xs font-medium border flex items-center justify-between gap-2 ' +
                (type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 
                 type === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 
                 'bg-blue-50 text-blue-800 border-blue-200');
            el.innerHTML = `<span>${msg}</span><button type="button" onclick="this.parentElement.classList.add('hidden')" class="text-xs opacity-60 hover:opacity-100">&times;</button>`;
            el.classList.remove('hidden');
        }

        async function handleBluetoothPrint() {
            const btn = document.getElementById('btnBluetoothPrint');
            const txt = document.getElementById('btnBluetoothText');
            if (!window.ThermalBluetooth || !window.ThermalBluetooth.isSupported()) {
                if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                    showStatus('Web Bluetooth dibatasi browser Chrome pada HTTP lokal. Silakan gunakan tombol "Quick Printer" di sebelah, atau aktifkan flag di Chrome Tablet (chrome://flags/#unsafely-treat-insecure-origin-as-secure).', 'error');
                } else {
                    showStatus('Browser ini belum mendukung Web Bluetooth. Untuk cetak langsung tanpa RawBT, gunakan Google Chrome di Android atau PC.', 'error');
                }
                return;
            }

            try {
                if (btn) btn.disabled = true;
                await window.ThermalBluetooth.printBluetooth(window.receiptData, charWidth, (step) => {
                    if (txt) txt.textContent = step;
                    showStatus(step, 'info');
                }, 'sale');
                showStatus('Nota berhasil dicetak langsung via Bluetooth (100% bebas watermark RawBT)!', 'success');
            } catch (err) {
                console.error(err);
                if (err.name === 'NotFoundError') {
                    showStatus('Pencarian printer dibatalkan.', 'info');
                } else {
                    showStatus('Gagal mencetak Bluetooth: ' + err.message + '. Anda juga dapat menekan tombol Quick Printer atau Cetak Biasa.', 'error');
                }
            } finally {
                if (btn) btn.disabled = false;
                if (txt) txt.textContent = 'Cetak Bluetooth';
            }
        }

        function handleQuickPrinter() {
            if (window.ThermalBluetooth) {
                window.ThermalBluetooth.printQuickPrinter(window.receiptData, charWidth, 'sale');
            }
        }

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
    <x-pdf-preview-modal />
</body>
</html>

