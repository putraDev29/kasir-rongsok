@extends('layouts.app', ['header' => 'Detail Pinjaman Modal #' . $debt->debt_number])

@section('content')
<div x-data="{ 
    showPayModal: false,
    payAmount: {{ $debt->remaining_amount }},
    maxAmount: {{ $debt->remaining_amount }}
}" class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('debts.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-slate-900 font-mono">{{ $debt->debt_number }}</h2>
                    @if($debt->status === 'paid')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800">LUNAS</span>
                    @elseif($debt->status === 'partially_paid')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">SEBAGIAN</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">BELUM LUNAS</span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Dicatat pada {{ $debt->date->format('d/m/Y') }} oleh <span class="font-semibold text-slate-700">{{ $debt->user?->name ?? 'Sistem' }}</span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('debts.agreement', $debt) }}" target="_blank"
               class="px-3 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-800 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors">
                <i data-lucide="file-text" class="w-4 h-4 text-blue-600"></i>
                <span>Surat Perjanjian</span>
            </a>

            <a href="{{ route('debts.agreement-pdf', $debt) }}" target="_blank"
               class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors"
               title="Preview & Unduh Berkas PDF Surat Perjanjian">
                <i data-lucide="file-text" class="w-4 h-4 text-rose-400"></i>
                <span>Preview / Unduh PDF</span>
            </a>

            <button type="button"
                    @click="openWaPdfModal({
                        title: 'Kirim Surat Perjanjian Pinjaman PDF',
                        subtitle: 'Kirim berkas dokumen PDF Surat Perjanjian Hutang Modal (SPH) ke WhatsApp peminjam',
                        phone: '{{ $debt->borrower_phone }}',
                        filename: 'SPH_' + ('{{ str_replace(['/', ' '], '_', $debt->agreement_number ?: $debt->debt_number) }}') + '.pdf',
                        fileDescription: 'Dokumen PDF Resmi Surat Perjanjian Hutang Piutang Modal',
                        pdfUrl: '{{ route('debts.agreement-pdf', $debt) }}',
                        previewUrl: '{{ route('debts.agreement-wa-preview', $debt) }}',
                        sendUrl: '{{ route('debts.agreement-wa', $debt) }}',
                        submitButtonText: 'Kirim SPH PDF via WA'
                    })"
                    class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors"
                    title="Kirim Surat Perjanjian PDF ke WhatsApp">
                <x-whatsapp-icon class="w-3.5 h-3.5" />
                <span>Kirim WA (PDF)</span>
            </button>

            @if($debt->remaining_amount > 0)
                <button type="button" @click="showPayModal = true" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center gap-2 shadow-sm shrink-0">
                    <i data-lucide="badge-dollar-sign" class="w-4 h-4"></i>
                    <span>Bayar / Angsur</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Overview Financial Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase">Nominal Pinjaman Awal</span>
            <div class="text-2xl font-black text-slate-900 mt-1 font-mono">
                Rp {{ number_format($debt->initial_amount, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-0.5">Modal yang dipinjamkan</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase">Total Sudah Diangsur</span>
            <div class="text-2xl font-black text-emerald-600 mt-1 font-mono">
                Rp {{ number_format($debt->paid_amount, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-0.5">
                {{ $debt->initial_amount > 0 ? round(($debt->paid_amount / $debt->initial_amount) * 100) : 0 }}% terbayar
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/30 shadow-sm">
            <span class="text-xs font-semibold text-amber-800 uppercase">Sisa Saldo Hutang</span>
            <div class="text-2xl font-black text-amber-600 mt-1 font-mono">
                Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-amber-700 mt-0.5">Wajib dilunasi</div>
        </div>
    </div>

    <!-- Borrower Information & Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Borrower Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-emerald-600"></i>
                <span>Data Peminjam</span>
            </h3>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Nama Peminjam:</span>
                    <span class="font-bold text-slate-900 text-sm">{{ $debt->borrower_name }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Tipe:</span>
                    <span class="font-semibold text-slate-700 capitalize">{{ $debt->borrower_type }}</span>
                    @if($debt->supplier)
                        <span class="ml-1 text-[11px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">
                            Master: {{ $debt->supplier->code }}
                        </span>
                    @endif
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Nomor Telepon:</span>
                    <span class="font-mono text-slate-800">{{ $debt->borrower_phone ?? '-' }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Jatuh Tempo:</span>
                    <span class="font-medium text-slate-800">
                        {{ $debt->due_date ? $debt->due_date->format('d F Y') : 'Tidak ditentukan' }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">Keterangan:</span>
                    <p class="text-slate-700 mt-0.5 leading-relaxed bg-slate-50 p-2 rounded border border-slate-100">
                        {{ $debt->notes ?: 'Tidak ada catatan khusus.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Right: Payment History Ledger (Riwayat Cicilan) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-emerald-600"></i>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        Riwayat Angsuran & Pelunasan
                    </h3>
                </div>
                <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-[11px] font-bold">
                    {{ $debt->payments->count() }} Kali Pembayaran
                </span>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <th class="py-2.5 px-4">No. Bukti / Tanggal</th>
                            <th class="py-2.5 px-4">Sumber / Metode</th>
                            <th class="py-2.5 px-4 text-right">Nominal Bayar</th>
                            <th class="py-2.5 px-4 text-right">Sisa Hutang</th>
                            <th class="py-2.5 px-4">Kasir / Operator</th>
                            <th class="py-2.5 px-4 text-center">Nota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-mono">
                        @forelse($debt->payments as $payment)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $payment->payment_number }}</div>
                                    <div class="text-[11px] text-slate-400 font-sans">{{ $payment->payment_date->format('d/m/Y H:i') }}</div>
                                </td>

                                <td class="py-3 px-4 font-sans">
                                    @if($payment->purchase_transaction_id)
                                        <a href="{{ route('purchases.show', $payment->purchase_transaction_id) }}" 
                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[11px] font-bold hover:bg-emerald-100">
                                            <i data-lucide="scale" class="w-3 h-3"></i>
                                            <span>Potong Timbangan: {{ $payment->purchaseTransaction?->invoice_number }}</span>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[11px] font-bold">
                                            <i data-lucide="wallet" class="w-3 h-3"></i>
                                            <span>Setor: {{ $payment->paymentMethod?->name ?? 'Tunai' }}</span>
                                        </span>
                                    @endif
                                    @if($payment->notes)
                                        <div class="text-[11px] text-slate-500 mt-0.5 italic">{{ $payment->notes }}</div>
                                    @endif
                                </td>

                                <td class="py-3 px-4 text-right font-bold text-emerald-600">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                </td>

                                <td class="py-3 px-4 text-right font-bold text-slate-700">
                                    Rp {{ number_format($payment->remaining_balance, 0, ',', '.') }}
                                </td>

                                <td class="py-3 px-4 font-sans text-slate-600">
                                    {{ $payment->user?->name ?? '-' }}
                                </td>

                                <td class="py-3 px-4 text-center font-sans">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('debts.print-payment', $payment) }}" target="_blank"
                                           class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold inline-flex items-center gap-1"
                                           title="Cetak Bukti Pembayaran">
                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                            <span>Cetak</span>
                                        </a>
                                        <button type="button"
                                                @click="$store.waGateway.openReceipt('debt_payment', {{ $payment->id }}, '{{ $debt->borrower_phone }}')"
                                                class="px-2 py-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-bold inline-flex items-center gap-1"
                                                title="Kirim Bukti Pembayaran via WhatsApp">
                                            <x-whatsapp-icon class="w-3.5 h-3.5" />
                                            <span>WA</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 font-sans">
                                    Belum ada catatan pembayaran / angsuran untuk pinjaman ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL: Pembayaran / Angsuran -->
    <div x-show="showPayModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="showPayModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <i data-lucide="badge-dollar-sign" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Pembayaran Hutang Piutang</h3>
                        <p class="text-[11px] text-slate-500 font-mono">{{ $debt->debt_number }} - {{ $debt->borrower_name }}</p>
                    </div>
                </div>
                <button type="button" @click="showPayModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('debts.pay', $debt) }}" method="POST" class="space-y-3.5">
                @csrf

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 flex justify-between items-center text-xs">
                    <span class="text-amber-800 font-semibold">Sisa Saldo Hutang:</span>
                    <span class="font-mono font-black text-amber-900 text-sm">Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}</span>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-xs font-semibold text-slate-700">Nominal Pembayaran (Rp) <span class="text-red-500">*</span></label>
                        <button type="button" @click="payAmount = maxAmount" class="text-[10px] text-emerald-600 font-bold hover:underline">
                            Bayar Lunas Semua
                        </button>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-slate-400 text-xs">Rp</span>
                        <input type="number" name="amount" x-model.number="payAmount" step="500" min="500" :max="maxAmount" required
                               class="w-full pl-9 pr-3 py-2 text-sm font-mono font-bold rounded-lg border border-slate-300 focus:ring-emerald-500 text-slate-900">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Pembayaran</label>
                    <select name="payment_method_id" class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Catatan</label>
                    <input type="text" name="notes" placeholder="Contoh: Setor tunai ke kantor, pelunasan sisa bon..."
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showPayModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm">
                        Simpan & Cetak Bukti Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

