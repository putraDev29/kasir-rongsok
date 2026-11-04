@extends('layouts.app', ['header' => 'Slip Gaji #' . $salarySlip->slip_number])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('salary-slips.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-slate-900 font-mono">{{ $salarySlip->slip_number }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 uppercase">
                        {{ $salarySlip->status }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Periode: <strong class="text-slate-800">{{ $salarySlip->period_month }}</strong> | Dibayar: {{ $salarySlip->payment_date->format('d/m/Y') }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('salary-slips.pdf', $salarySlip) }}" target="_blank"
               class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm"
               title="Preview & Unduh Berkas PDF Slip Gaji">
                <i data-lucide="file-text" class="w-4 h-4 text-rose-400"></i>
                <span>Preview / Unduh PDF</span>
            </a>

            <button type="button"
                    @click="openWaPdfModal({
                        title: 'Kirim Slip Gaji Karyawan PDF',
                        subtitle: 'Kirim berkas dokumen PDF resmi slip gaji ke WhatsApp karyawan',
                        phone: '{{ $salarySlip->employeeUser?->phone ?? $salarySlip->employee_phone }}',
                        filename: 'Slip_Gaji_{{ $salarySlip->slip_number }}.pdf',
                        fileDescription: 'Dokumen PDF Resmi Slip Gaji & Honor Karyawan',
                        pdfUrl: '{{ route('salary-slips.pdf', $salarySlip) }}',
                        previewUrl: '{{ route('salary-slips.wa-preview', $salarySlip) }}',
                        sendUrl: '{{ route('salary-slips.send-wa', $salarySlip) }}',
                        submitButtonText: 'Kirim Slip PDF via WA'
                    })"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm"
                    title="Kirim Slip Gaji PDF ke Karyawan">
                <x-whatsapp-icon class="w-4 h-4" />
                <span>Kirim WA (PDF)</span>
            </button>

            <a href="{{ route('salary-slips.print', $salarySlip) }}" target="_blank"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold flex items-center gap-1.5 border border-slate-300">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Print</span>
            </a>

            <form action="{{ route('salary-slips.destroy', $salarySlip) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus slip gaji ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg border border-red-200" title="Hapus Slip">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Slip Overview Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        
        <!-- Header Info -->
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Karyawan Penerima</span>
                <h3 class="text-lg font-black text-slate-900">{{ $salarySlip->employee_name }}</h3>
                @if($salarySlip->employeeUser)
                    <p class="text-xs text-slate-500">Jabatan / Role: <strong class="capitalize">{{ $salarySlip->employeeUser->role }}</strong></p>
                @endif
            </div>

            <div class="sm:text-right">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Metode Pembayaran</span>
                <h4 class="text-sm font-bold text-slate-800">{{ $salarySlip->paymentMethod?->name ?? 'Tunai Langsung' }}</h4>
                <p class="text-[11px] text-slate-400">Dibuat oleh: {{ $salarySlip->creator?->name ?? 'Admin' }}</p>
            </div>
        </div>

        <!-- Breakdown Grid -->
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Earnings Column -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-wider pb-2 border-b border-emerald-100 flex items-center justify-between">
                    <span>A. Komponen Penerimaan</span>
                    <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-600"></i>
                </h4>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Gaji Pokok:</span>
                        <span class="font-mono font-bold text-slate-900">Rp {{ number_format($salarySlip->basic_salary, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Tunjangan Operasional:</span>
                        <span class="font-mono font-medium text-slate-800">Rp {{ number_format($salarySlip->allowance_amount, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Bonus & Lembur:</span>
                        <span class="font-mono font-medium text-slate-800">Rp {{ number_format($salarySlip->bonus_amount, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between pt-2 border-t border-slate-200 font-bold text-emerald-700">
                        <span>Total Penghasilan Kotor:</span>
                        <span class="font-mono">Rp {{ number_format($salarySlip->total_income, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Deductions Column -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-red-800 uppercase tracking-wider pb-2 border-b border-red-100 flex items-center justify-between">
                    <span>B. Komponen Potongan</span>
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                </h4>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Potongan Bon Modal / Kasbon:</span>
                        <span class="font-mono font-bold text-red-600">Rp {{ number_format($salarySlip->debt_deduction, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Potongan Lainnya:</span>
                        <span class="font-mono font-medium text-red-600">Rp {{ number_format($salarySlip->other_deduction, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between pt-2 border-t border-slate-200 font-bold text-red-700">
                        <span>Total Potongan Gaji:</span>
                        <span class="font-mono">- Rp {{ number_format($salarySlip->total_deduction, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Net Take Home Pay Foot -->
        <div class="p-5 bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs text-slate-400 uppercase tracking-wider">GAJI BERSIH DITERIMA (TAKE HOME PAY)</span>
                <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono mt-0.5">
                    Rp {{ number_format($salarySlip->net_salary, 0, ',', '.') }}
                </div>
                @if($salarySlip->notes)
                    <p class="text-xs text-slate-400 mt-1 italic">Catatan: {{ $salarySlip->notes }}</p>
                @endif
            </div>

            <a href="{{ route('salary-slips.print', $salarySlip) }}" target="_blank"
               class="px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Lembar Slip</span>
            </a>
        </div>

    </div>

</div>
@endsection

