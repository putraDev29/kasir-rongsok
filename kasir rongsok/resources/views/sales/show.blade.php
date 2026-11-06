@extends('layouts.app', ['header' => 'Detail Nota Penjualan Rongsok'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ showCancelModal: false }">

    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('sales.index') }}" class="text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Kembali ke Riwayat Penjualan</span>
                </a>
            </div>
            <h2 class="text-xl font-bold text-slate-900 mt-1 flex items-center gap-2 flex-wrap">
                <span>Nota Penjualan: {{ $sale->invoice_number }}</span>
                @if($sale->isCancelled())
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">
                        DIBATALKAN
                    </span>
                @elseif($sale->isPendingDelivery())
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                        🚚 MENUNGGU PENGIRIMAN
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        ✓ {{ $sale->isPickup() ? 'SELESAI (DIAMBIL SENDIRI)' : 'SELESAI (TERKIRIM)' }}
                    </span>
                @endif
            </h2>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($sale->isPendingDelivery())
                <form action="{{ route('sales.mark-delivered', $sale) }}" method="POST" onsubmit="return confirm('Konfirmasi bahwa barang muatan telah selesai diantar/diterima pabrik? Transaksi akan resmi SELESAI.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-colors">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                        <span>Konfirmasi Pengiriman Selesai</span>
                    </button>
                </form>

                @if(!$sale->deliveryOrder)
                    <a href="{{ route('delivery-orders.create', ['sales_transaction_id' => $sale->id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-colors">
                        <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                        <span>+ Buat Surat Jalan</span>
                    </a>
                @endif
            @endif

            @if($sale->deliveryOrder)
                <a href="{{ route('delivery-orders.show', $sale->deliveryOrder) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 text-xs font-bold transition-colors">
                    <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                    <span>Surat Jalan: {{ $sale->deliveryOrder->delivery_number }}</span>
                </a>
            @endif

            <button type="button" 
                    @click="$store.waGateway.openReceipt('sale', {{ $sale->id }}, '{{ $sale->buyer_phone }}')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-colors">
                <x-whatsapp-icon class="w-3.5 h-3.5" />
                <span>Kirim Faktur WA</span>
            </button>

            <button type="button"
               @click="openPdfPreview('{{ route('sales.pdf', $sale) }}', 'Faktur Penjualan {{ $sale->invoice_number }}', 'Faktur_{{ str_replace(['/', ' '], '_', $sale->invoice_number) }}.pdf', 'Pembeli: {{ $sale->buyer_name }} - Total: Rp {{ number_format($sale->final_amount, 0, ',', '.') }}')"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm cursor-pointer"
               title="Preview & Unduh Berkas PDF Faktur Penjualan">
                <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-400"></i>
                <span>Preview / Unduh PDF</span>
            </button>

            <a href="{{ route('sales.print', $sale) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak Faktur Penjualan</span>
            </a>

            @if(Auth::user()->canCancelTransactions() && !$sale->isCancelled())
                <button type="button" @click="showCancelModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold border border-red-200">
                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                    <span>Batalkan Penjualan</span>
                </button>
            @endif
        </div>
    </div>

    @if($sale->isCancelled())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-sm">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-red-600"></i>
                <span>Transaksi Penjualan Ini Telah Dibatalkan</span>
            </div>
            <p>Dibatalkan oleh: <strong>{{ $sale->cancelledByUser?->name ?? 'Admin' }}</strong> pada {{ $sale->cancelled_at?->format('d/m/Y H:i') }}</p>
            <p>Alasan: <em class="text-red-800">{{ $sale->cancel_reason }}</em></p>
            <p class="text-[11px] text-red-700">Stok barang telah dikembalikan secara otomatis ke dalam inventori gudang.</p>
        </div>
    @endif

    <!-- Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 pb-4 border-b border-slate-200 text-xs">
            <div>
                <span class="text-slate-400 block font-medium uppercase text-[10px]">Waktu Penjualan</span>
                <span class="font-semibold text-slate-800 text-sm">{{ $sale->transaction_date->format('d M Y, H:i') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium uppercase text-[10px]">Pembeli / Pabrik</span>
                <span class="font-bold text-slate-900 text-sm">{{ $sale->buyer_name }}</span>
                @if($sale->buyer_phone)
                    <div class="text-[11px] text-slate-500">{{ $sale->buyer_phone }}</div>
                @endif
            </div>
            <div>
                <span class="text-slate-400 block font-medium uppercase text-[10px]">Metode Penyerahan</span>
                @if($sale->isPickup())
                    <span class="inline-flex items-center px-2 py-0.5 rounded font-bold text-xs bg-emerald-100 text-emerald-800 border border-emerald-200 mt-0.5">
                        Ambil Sendiri di Depo
                    </span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Tanpa surat jalan</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded font-bold text-xs bg-blue-100 text-blue-800 border border-blue-200 mt-0.5">
                        🚚 Dikirim Armada Depo
                    </span>
                    @if($sale->deliveryOrder)
                        <span class="text-[10px] text-blue-700 font-mono block mt-0.5">SJ: {{ $sale->deliveryOrder->delivery_number }}</span>
                    @else
                        <span class="text-[10px] text-amber-600 block mt-0.5">Belum terbit SJ</span>
                    @endif
                @endif
            </div>
            <div>
                <span class="text-slate-400 block font-medium uppercase text-[10px]">Operator</span>
                <span class="font-semibold text-slate-800 text-sm">{{ $sale->user?->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium uppercase text-[10px]">Metode Pembayaran</span>
                <span class="font-semibold text-slate-800 text-sm">{{ $sale->paymentMethod?->name ?? 'Transfer' }}</span>
                @if($sale->isBankPayment())
                    <div class="mt-1 text-xs font-mono text-blue-900 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-200 space-y-0.5">
                        @if($sale->getBankAccountNumber())
                            <div class="font-bold">💳 No. Rek: <span class="font-black">{{ $sale->getBankAccountNumber() }}</span></div>
                        @endif
                        @if($sale->getBankAccountName())
                            <div class="text-[11px] font-semibold text-blue-800">👤 a/n: {{ $sale->getBankAccountName() }}</div>
                        @endif
                        @if(!$sale->getBankAccountNumber() && !$sale->getBankAccountName() && !empty($sale->bank_info))
                            <div class="font-bold">🏦 {{ $sale->bank_info }}</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Rincian Barang yang Dijual</h3>
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[10px] uppercase font-bold">
                        <tr>
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">Kode</th>
                            <th class="py-2.5 px-3">Nama Barang</th>
                            <th class="py-2.5 px-3 text-right">Berat (kg)</th>
                            <th class="py-2.5 px-3 text-right">Harga Jual/kg</th>
                            <th class="py-2.5 px-3 text-right font-bold">Subtotal</th>
                            <th class="py-2.5 px-3 text-right">Estimasi HPP</th>
                            <th class="py-2.5 px-3 text-right font-bold text-emerald-700">Estimasi Laba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sale->grouped_master_items as $idx => $master)
                            <!-- Master Item Row (Akumulasi Total dari Master Barang) -->
                            <tr class="{{ $master->has_variants ? 'bg-slate-50/50 font-semibold' : 'hover:bg-slate-50' }}">
                                <td class="py-2.5 px-3 text-slate-500 font-mono">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-mono font-medium text-slate-600">{{ $master->item_code }}</td>
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-slate-900">{{ $master->item_name }}</span>
                                        @if($master->has_variants)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                Master Item ({{ $master->items->count() }} Varian)
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                    {{ \App\Services\FormatHelper::weight($master->total_weight) }} kg
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono">
                                    @if($master->has_variants)
                                        <span class="text-slate-400 text-[11px] italic">Rerata Rp {{ number_format($master->avg_sell_price, 0, ',', '.') }}</span>
                                    @else
                                        Rp {{ number_format($master->items->first()->sell_price_per_kg, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                    Rp {{ number_format($master->total_subtotal, 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-500">
                                    Rp {{ number_format($master->total_cogs, 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">
                                    Rp {{ number_format($master->total_profit, 0, ',', '.') }}
                                </td>
                            </tr>

                            <!-- Rincian Jenis Varian (Bila Ada Varian) -->
                            @if($master->has_variants)
                                @foreach($master->items as $vItem)
                                    <tr class="bg-blue-50/30 hover:bg-blue-50/60 border-l-4 border-l-blue-500 text-xs transition-colors">
                                        <td class="py-2 px-3 text-right text-blue-400 font-mono text-[11px]">↳</td>
                                        <td class="py-2 px-3 text-slate-400 font-mono text-[11px]">{{ $vItem->item_code_snapshot ?: '-' }}</td>
                                        <td class="py-2 px-3 pl-6">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-white border border-blue-300 font-semibold text-blue-900 text-[11px] shadow-xs">
                                                    Varian: {{ $vItem->item_type_name ?: 'Standar' }}
                                                </span>
                                                @if($vItem->notes)
                                                    <span class="text-[11px] text-slate-500 italic">({{ $vItem->notes }})</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono text-slate-700 font-medium">
                                            {{ \App\Services\FormatHelper::weight($vItem->weight) }} kg
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono text-slate-700 font-medium">
                                            Rp {{ number_format($vItem->sell_price_per_kg, 0, ',', '.') }}
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-800">
                                            Rp {{ number_format($vItem->subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono text-slate-400 text-[11px]">
                                            Rp {{ number_format($vItem->cogs_subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="py-2 px-3 text-right font-mono font-medium text-emerald-600 text-[11px]">
                                            Rp {{ number_format($vItem->profit_subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pt-2">
            <div class="text-xs text-slate-600 max-w-sm space-y-2">
                @if($sale->buyer_address)
                    <div><strong>Alamat Tujuan:</strong> {{ $sale->buyer_address }}</div>
                @endif
                @if($sale->notes)
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <strong>Catatan Pengiriman:</strong>
                        <p class="mt-0.5">{{ $sale->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="w-full sm:w-80 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Total Berat:</span>
                    <span class="font-mono font-bold text-slate-900">{{ \App\Services\FormatHelper::weight($sale->total_weight) }} kg</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-mono">Rp {{ number_format($sale->subtotal_amount, 0, ',', '.') }}</span>
                </div>
                @if($sale->discount_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Potongan:</span>
                        <span class="font-mono text-red-600">- Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-sm font-bold text-slate-900">
                    <span>TOTAL PENJUALAN:</span>
                    <span class="font-mono text-xl font-black text-blue-700">Rp {{ number_format($sale->final_amount, 0, ',', '.') }}</span>
                </div>
                <div class="pt-1.5 border-t border-dotted border-slate-300 flex justify-between text-emerald-700 font-bold">
                    <span>ESTIMASI LABA KOTOR:</span>
                    <span class="font-mono text-sm">Rp {{ number_format($sale->estimated_profit, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Cancel -->
    <div x-show="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="showCancelModal = false">
            <h3 class="text-base font-bold text-slate-900">Batalkan Penjualan Ini?</h3>
            <p class="text-xs text-slate-500">Stok barang yang sudah terjual akan dikembalikan ke saldo gudang.</p>
            <form action="{{ route('sales.cancel', $sale) }}" method="POST" class="space-y-3">
                @csrf
                <textarea name="cancel_reason" required rows="3" placeholder="Alasan pembatalan penjualan..."
                          class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-red-500"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCancelModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-red-600 text-white text-xs font-bold">Ya, Batalkan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

