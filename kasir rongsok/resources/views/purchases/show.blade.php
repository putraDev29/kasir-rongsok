@extends('layouts.app', ['header' => 'Detail Nota Penerimaan Barang'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ showCancelModal: false, showItemCancelModal: false, cancelItemAction: '', cancelItemName: '' }">

    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchases.index') }}" class="text-xs text-slate-500 hover:text-slate-800 flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Kembali ke Riwayat</span>
                </a>
            </div>
            <h2 class="text-xl font-bold text-slate-900 mt-1 flex items-center gap-2">
                <span>Nota: {{ $purchase->invoice_number }}</span>
                @if($purchase->isCancelled())
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">
                        DIBATALKAN
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                        SELESAI & DIBAYAR
                    </span>
                @endif
            </h2>
        </div>

        <!-- Print Actions & Void -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Kirim Nota via WhatsApp -->
            <button type="button" 
                    @click="$store.waGateway.openReceipt('purchase', {{ $purchase->id }}, '{{ $purchase->supplier_phone }}')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-colors">
                <x-whatsapp-icon class="w-3.5 h-3.5" />
                <span>Kirim Nota WA</span>
            </button>

            <a href="{{ route('purchases.print', ['purchase' => $purchase, 'format' => 'thermal_58']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Thermal 58mm</span>
            </a>
            <a href="{{ route('purchases.print', ['purchase' => $purchase, 'format' => 'thermal_80']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold border border-slate-300">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Thermal 80mm</span>
            </a>
            <a href="{{ route('purchases.print', ['purchase' => $purchase, 'format' => 'a4']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold border border-slate-300">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                <span>Format A4</span>
            </a>

            @if(Auth::user()->canCancelTransactions() && !$purchase->isCancelled())
                <button type="button" @click="showCancelModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold border border-red-200 transition-colors">
                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                    <span>Batalkan Transaksi</span>
                </button>
            @endif
        </div>
    </div>

    @if($purchase->isCancelled())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-sm">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-red-600"></i>
                <span>Transaksi Ini Telah Dibatalkan / Void</span>
            </div>
            <p>Dibatalkan oleh: <strong>{{ $purchase->cancelledByUser?->name ?? 'Admin' }}</strong> pada {{ $purchase->cancelled_at?->format('d/m/Y H:i') }}</p>
            <p>Alasan: <em class="text-red-800">{{ $purchase->cancel_reason }}</em></p>
            <p class="text-[11px] text-red-700">Pergerakan stok telah dibalikkan secara otomatis ke dalam kartu stok gudang.</p>
        </div>
    @endif

    <!-- Invoice Details Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
        
        <!-- Header Info Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pb-4 border-b border-slate-200 text-xs">
            <div>
                <span class="text-slate-400 block font-medium uppercase text-[10px]">Waktu Transaksi</span>
                <span class="font-semibold text-slate-800 text-sm">{{ $purchase->transaction_date->format('d M Y, H:i') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium uppercase text-[10px]">Pemasok / Penjual</span>
                <span class="font-semibold text-slate-800 text-sm">{{ $purchase->supplier_name ?: '-' }}</span>
                @if($purchase->supplier_phone)
                    <div class="text-[11px] text-slate-500">{{ $purchase->supplier_phone }}</div>
                @endif
            </div>
            <div>
                <span class="text-slate-400 block font-medium uppercase text-[10px]">Operator Kasir</span>
                <span class="font-semibold text-slate-800 text-sm">{{ $purchase->user?->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-medium uppercase text-[10px]">Metode Pembayaran</span>
                <span class="font-semibold text-slate-800 text-sm">{{ $purchase->paymentMethod?->name ?? 'Tunai' }}</span>
                @if($purchase->isBankPayment())
                    <div class="mt-1 text-xs font-mono text-blue-900 bg-blue-50 px-2.5 py-1.5 rounded-lg border border-blue-200 space-y-0.5">
                        @if($purchase->getBankAccountNumber())
                            <div class="font-bold">💳 No. Rek: <span class="font-black">{{ $purchase->getBankAccountNumber() }}</span></div>
                        @endif
                        @if($purchase->getBankAccountName())
                            <div class="text-[11px] font-semibold text-blue-800">👤 a/n: {{ $purchase->getBankAccountName() }}</div>
                        @endif
                        @if(!$purchase->getBankAccountNumber() && !$purchase->getBankAccountName() && !empty($purchase->bank_info))
                            <div class="font-bold">🏦 {{ $purchase->bank_info }}</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Rincian Barang Timbangan</h3>
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[10px] uppercase font-bold">
                        <tr>
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">Kode</th>
                            <th class="py-2.5 px-3">Nama Material Scrap & Daur Ulang</th>
                            <th class="py-2.5 px-3 text-right">Berat Kotor</th>
                            <th class="py-2.5 px-3 text-right">Pot./Tara</th>
                            <th class="py-2.5 px-3 text-right font-bold text-slate-900 bg-emerald-50/50">Berat Bersih</th>
                            <th class="py-2.5 px-3 text-right">Harga Beli/kg</th>
                            <th class="py-2.5 px-3 text-right font-bold">Subtotal</th>
                            <th class="py-2.5 px-3 text-center">Status / Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($purchase->items as $idx => $item)
                            <tr class="hover:bg-slate-50/80 transition-colors {{ $item->is_cancelled ? 'bg-red-50/40 text-slate-400' : '' }}">
                                <td class="py-2.5 px-3 text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-mono font-medium {{ $item->is_cancelled ? 'line-through text-slate-400' : 'text-slate-600' }}">{{ $item->item_code_snapshot }}</td>
                                <td class="py-2.5 px-3 font-bold {{ $item->is_cancelled ? 'line-through text-slate-400' : 'text-slate-800' }}">
                                    <div>{{ $item->item_name_snapshot }}</div>
                                    @if($item->item_type_name)
                                        <div class="text-[10px] text-blue-700 font-normal">Jenis: {{ $item->item_type_name }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono {{ $item->is_cancelled ? 'line-through text-slate-400' : 'text-slate-600' }}">
                                    @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                        {{ \App\Services\FormatHelper::number($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight)) }} {{ $item->unit_type ?: 'pcs' }}
                                    @else
                                        {{ \App\Services\FormatHelper::weight($item->gross_weight) }} kg
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono {{ $item->is_cancelled ? 'line-through text-slate-400' : 'text-slate-600' }}">
                                    @if($item->tare_weight > 0)
                                        @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                            {{ \App\Services\FormatHelper::number($item->tare_weight) }} {{ $item->unit_type ?: 'pcs' }}
                                            @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight), $item->tare_weight))
                                                <span class="text-[11px] text-slate-400 font-normal">({{ $pct }})</span>
                                            @endif
                                        @else
                                            {{ \App\Services\FormatHelper::weight($item->tare_weight) }} kg
                                            @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight, $item->tare_weight))
                                                <span class="text-[11px] text-slate-400 font-normal">({{ $pct }})</span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold {{ $item->is_cancelled ? 'line-through text-slate-400' : 'text-emerald-700 bg-emerald-50/30' }}">
                                    @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                        {{ \App\Services\FormatHelper::number($item->quantity) }} {{ $item->unit_type ?: 'pcs' }}
                                    @else
                                        {{ \App\Services\FormatHelper::weight($item->net_weight) }} kg
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono {{ $item->is_cancelled ? 'line-through text-slate-400' : 'text-slate-700' }}">{{ \App\Services\FormatHelper::rupiah($item->price_per_kg) }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold {{ $item->is_cancelled ? 'line-through text-slate-400' : 'text-slate-900' }}">{{ \App\Services\FormatHelper::rupiah($item->subtotal) }}</td>
                                <td class="py-2.5 px-3 text-center">
                                    @if($item->is_cancelled)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200"
                                              title="Dibatalkan: {{ $item->cancel_reason ?? 'Tidak ada keterangan' }}">
                                            Dibatalkan
                                        </span>
                                    @elseif(!$purchase->isCancelled() && Auth::user()->canCancelTransactions())
                                        <button type="button" 
                                                @click="cancelItemAction = '{{ route('purchases.items.cancel', [$purchase, $item]) }}'; cancelItemName = '{{ addslashes($item->item_name_snapshot) }}'; showItemCancelModal = true;"
                                                class="px-2 py-1 rounded bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-800 text-[11px] font-bold border border-red-200 transition-colors inline-flex items-center gap-1 shadow-2xs"
                                                title="Batalkan hanya barang ini">
                                            <i data-lucide="trash-2" class="w-3 h-3 text-red-600"></i>
                                            <span>Batal Barang</span>
                                        </button>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @php
                $groupedCommodities = $purchase->items->groupBy('item_name_snapshot');
                $hasMultiPrice = $groupedCommodities->contains(fn($grp) => $grp->count() > 1);
            @endphp

            @if($hasMultiPrice)
                <div class="mt-3 p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-900 mb-1.5 flex items-center gap-1.5">
                        <i data-lucide="layers" class="w-3.5 h-3.5 text-emerald-700"></i>
                        <span>Akumulasi Total Berat per Komoditas (Gabungan Semua Daftar Harga)</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($groupedCommodities as $commodityName => $groupItems)
                            @php
                                $totWeight = $groupItems->where('is_cancelled', false)->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? $i->quantity : $i->net_weight);
                                $unit = $groupItems->first()->unit_type ?: 'kg';
                                $totSub = $groupItems->where('is_cancelled', false)->sum('subtotal');
                                $priceCount = $groupItems->pluck('price_per_kg')->unique()->count();
                            @endphp
                            <div class="p-2.5 bg-white rounded-lg border border-emerald-200 shadow-2xs">
                                <div class="text-xs font-bold text-slate-900">{{ $commodityName }}</div>
                                <div class="text-[11px] text-slate-600 flex justify-between mt-1">
                                    <span>Total Akumulasi:</span>
                                    <strong class="font-mono text-emerald-700">
                                        {{ \App\Services\FormatHelper::weight($totWeight) }} {{ $unit }}
                                    </strong>
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono text-right mt-0.5">
                                    {{ \App\Services\FormatHelper::rupiah($totSub) }} @if($priceCount > 1) <span class="text-blue-600 font-semibold">({{ $priceCount }} variasi harga)</span> @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Total Breakdown Box -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pt-2">
            <div class="text-xs text-slate-500 max-w-sm space-y-3">
                @if($purchase->notes)
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <strong class="text-slate-700 block mb-1">Catatan Transaksi:</strong>
                        <p>{{ $purchase->notes }}</p>
                    </div>
                @endif

                <div class="p-3 bg-red-50/70 rounded-xl border border-red-200 text-xs text-red-900 flex items-start gap-2.5">
                    <i data-lucide="shield-alert" class="w-4 h-4 text-red-600 shrink-0 mt-0.5"></i>
                    <div>
                        <strong class="font-bold uppercase tracking-wider block text-[11px] text-red-950">
                            {{ \App\Models\BusinessSetting::get('receipt_disclaimer', 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI') }}
                        </strong>
                        <p class="text-[10px] text-red-800 mt-0.5">Barang yang dijual wajib milik sah pribadi penjual. Depo tidak melayani barang curian / ilegal.</p>
                    </div>
                </div>
            </div>

            <div class="w-full sm:w-80 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Total Berat Kotor:</span>
                    <span class="font-mono">{{ \App\Services\FormatHelper::weight($purchase->total_gross_weight) }} kg</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Total Potongan Tara:</span>
                    <span class="font-mono">{{ \App\Services\FormatHelper::weight($purchase->total_tare_weight) }} kg</span>
                </div>
                <div class="flex justify-between font-bold text-slate-800 pb-1 border-b border-slate-200">
                    <span>Total Berat Bersih:</span>
                    <span class="font-mono text-emerald-700">{{ \App\Services\FormatHelper::weight($purchase->total_net_weight) }} kg</span>
                </div>

                <div class="flex justify-between text-slate-600 pt-1">
                    <span>Subtotal:</span>
                    <span class="font-mono font-semibold">{{ \App\Services\FormatHelper::rupiah($purchase->subtotal_amount) }}</span>
                </div>
                @if($purchase->discount_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Potongan Diskon:</span>
                        <span class="font-mono text-red-600">- {{ \App\Services\FormatHelper::rupiah($purchase->discount_amount) }}</span>
                    </div>
                @endif
                @if($purchase->rounding_amount != 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Pembulatan:</span>
                        <span class="font-mono">{{ \App\Services\FormatHelper::rupiah($purchase->rounding_amount) }}</span>
                    </div>
                @endif

                <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-xs font-bold text-slate-800">
                    <span>Total Harga Barang:</span>
                    <span class="font-mono text-sm font-bold text-slate-900">
                        {{ \App\Services\FormatHelper::rupiah($purchase->final_amount) }}
                    </span>
                </div>

                @php
                    $hasDebtInfo = ($purchase->debt_deduction_amount > 0) || (($purchase->remaining_debt_after ?? 0) > 0) || ($purchase->supplier && $purchase->supplier->debts->where('remaining_amount', '>', 0)->count() > 0);
                    $debtBefore = ($purchase->remaining_debt_after ?? 0) + ($purchase->debt_deduction_amount ?? 0);
                    $debtAfter = $purchase->remaining_debt_after ?? ($purchase->supplier ? $purchase->supplier->debts->sum('remaining_amount') : 0);
                @endphp

                @if($hasDebtInfo)
                    <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-xs space-y-1.5 font-mono">
                        <div class="font-bold text-amber-900 pb-1 border-b border-amber-200 flex items-center justify-between font-sans">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="handshake" class="w-4 h-4 text-amber-700"></i>
                                Rincian Hutang / Bon Modal
                            </span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-200 text-amber-900">Pemasok</span>
                        </div>
                        <div class="flex justify-between text-slate-700 pt-1">
                            <span>Hutang Sebelum Transaksi:</span>
                            <span>{{ \App\Services\FormatHelper::rupiah($debtBefore) }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-800 font-semibold">
                            <span>Potongan Hutang Transaksi Ini:</span>
                            <span>- {{ \App\Services\FormatHelper::rupiah($purchase->debt_deduction_amount ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-amber-900 pt-1 border-t border-amber-200">
                            <span>Sisa Saldo Hutang:</span>
                            <span>{{ \App\Services\FormatHelper::rupiah($debtAfter) }}</span>
                        </div>
                    </div>
                @endif

                <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-sm font-bold text-slate-900">
                    <div>
                        <span class="block">Uang Bersih Diterima Penjual:</span>
                        <span class="text-[10px] font-normal text-slate-500 font-sans">Net tunai setelah potong hutang</span>
                    </div>
                    <span class="font-mono text-xl font-black text-emerald-600">
                        {{ \App\Services\FormatHelper::rupiah($purchase->net_cash_paid ?? ($purchase->final_amount - ($purchase->debt_deduction_amount ?? 0))) }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Cancel Single Item (Task 9) -->
    <div x-show="showItemCancelModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="showItemCancelModal = false">
            <div class="flex items-center gap-3 text-red-600">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Batalkan Barang Ini Saja?</h3>
                    <p class="text-xs text-slate-500">
                        Barang: <strong class="text-slate-800" x-text="cancelItemName"></strong><br>
                        Hanya barang terpilih yang dibatalkan & stoknya dikembalikan. Barang lain di nota ini tetap sah.
                    </p>
                </div>
            </div>

            <form :action="cancelItemAction" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alasan Pembatalan Barang *</label>
                    <textarea name="cancel_reason" required rows="3" placeholder="Contoh: Salah timbang, salah pilih komoditas..."
                              class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-red-500 focus:border-red-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showItemCancelModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm">
                        Ya, Batalkan Barang Ini
                    </button>
                </div>
            </form>
        </div>
    </div>

    </div>

    <!-- Modal Cancel / Void Transaction -->
    <div x-show="showCancelModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="showCancelModal = false">
            <div class="flex items-center gap-3 text-red-600">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Batalkan Transaksi?</h3>
                    <p class="text-xs text-slate-500">Stok barang akan otomatis dibalikkan (dikurangi).</p>
                </div>
            </div>

            <form action="{{ route('purchases.cancel', $purchase) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Alasan Pembatalan / Void</label>
                    <textarea name="cancel_reason" required rows="3" placeholder="Contoh: Salah timbang, penjual membatalkan transaksi..."
                              class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-red-500 focus:border-red-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showCancelModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm">
                        Ya, Batalkan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(session('auto_print'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const defaultFormat = "{{ \App\Models\BusinessSetting::get('default_printer_type', 'thermal_58') }}";
                const printUrl = "{{ route('purchases.print', ['purchase' => $purchase]) }}?format=" + defaultFormat + "&autoprint=1";
                window.open(printUrl, '_blank');
            });
        </script>
    @endif

</div>
@endsection

