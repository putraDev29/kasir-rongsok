@extends('layouts.app', ['header' => 'Detail Nota Penerimaan Barang'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ showCancelModal: false }">

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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($purchase->items as $idx => $item)
                            <tr class="hover:bg-slate-50/80">
                                <td class="py-2.5 px-3 text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-mono font-medium text-slate-600">{{ $item->item_code_snapshot }}</td>
                                <td class="py-2.5 px-3 font-bold text-slate-800">{{ $item->item_name_snapshot }}</td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600">
                                    @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                        {{ number_format($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight), 0, ',', '.') }} {{ $item->unit_type ?: 'pcs' }}
                                    @else
                                        {{ number_format($item->gross_weight, 2, ',', '.') }} kg
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600">
                                    @if($item->tare_weight > 0)
                                        @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                            {{ number_format($item->tare_weight, 0, ',', '.') }} {{ $item->unit_type ?: 'pcs' }}
                                            @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight > 0 ? $item->gross_weight : ($item->quantity + $item->tare_weight), $item->tare_weight))
                                                <span class="text-[11px] text-slate-400 font-normal">({{ $pct }})</span>
                                            @endif
                                        @else
                                            {{ number_format($item->tare_weight, 2, ',', '.') }} kg
                                            @if($pct = \App\Services\FormatHelper::tarePercent($item->gross_weight, $item->tare_weight))
                                                <span class="text-[11px] text-slate-400 font-normal">({{ $pct }})</span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700 bg-emerald-50/30">
                                    @if($item->unit_type === 'pcs' || $item->unit_type === 'satuan')
                                        {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit_type ?: 'pcs' }}
                                    @else
                                        {{ number_format($item->net_weight, 2, ',', '.') }} kg
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-700">Rp {{ number_format($item->price_per_kg, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
                    <span class="font-mono">{{ number_format($purchase->total_gross_weight, 2) }} kg</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Total Potongan Tara:</span>
                    <span class="font-mono">{{ number_format($purchase->total_tare_weight, 2) }} kg</span>
                </div>
                <div class="flex justify-between font-bold text-slate-800 pb-1 border-b border-slate-200">
                    <span>Total Berat Bersih:</span>
                    <span class="font-mono text-emerald-700">{{ number_format($purchase->total_net_weight, 2) }} kg</span>
                </div>

                <div class="flex justify-between text-slate-600 pt-1">
                    <span>Subtotal:</span>
                    <span class="font-mono font-semibold">Rp {{ number_format($purchase->subtotal_amount, 0, ',', '.') }}</span>
                </div>
                @if($purchase->discount_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Potongan Diskon:</span>
                        <span class="font-mono text-red-600">- Rp {{ number_format($purchase->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($purchase->rounding_amount != 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Pembulatan:</span>
                        <span class="font-mono">Rp {{ number_format($purchase->rounding_amount, 0, ',', '.') }}</span>
                    </div>
                @endif

                <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-xs font-bold text-slate-800">
                    <span>Total Harga Barang:</span>
                    <span class="font-mono text-sm font-bold text-slate-900">
                        Rp {{ number_format($purchase->final_amount, 0, ',', '.') }}
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
                            <span>Rp {{ number_format($debtBefore, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-800 font-semibold">
                            <span>Potongan Hutang Transaksi Ini:</span>
                            <span>- Rp {{ number_format($purchase->debt_deduction_amount ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-amber-900 pt-1 border-t border-amber-200">
                            <span>Sisa Saldo Hutang:</span>
                            <span>Rp {{ number_format($debtAfter, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @endif

                <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-sm font-bold text-slate-900">
                    <div>
                        <span class="block">Uang Bersih Diterima Penjual:</span>
                        <span class="text-[10px] font-normal text-slate-500 font-sans">Net tunai setelah potong hutang</span>
                    </div>
                    <span class="font-mono text-xl font-black text-emerald-600">
                        Rp {{ number_format($purchase->net_cash_paid ?? ($purchase->final_amount - ($purchase->debt_deduction_amount ?? 0)), 0, ',', '.') }}
                    </span>
                </div>
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

