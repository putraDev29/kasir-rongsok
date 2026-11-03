@extends('layouts.app', ['header' => 'Slip Gaji Karyawan'])

@section('content')
<div class="space-y-6">

    <!-- Top KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Gaji Dibayarkan</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1 font-mono">
                    Rp {{ number_format($totalPayroll, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    {{ $period ? "Periode {$period}" : "Semua periode penggajian" }}
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="banknote" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jumlah Dokumen Slip</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-800 mt-1 font-mono">
                    {{ $slips->total() }} Slip
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Tercatat dalam sistem</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="file-text" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ukuran Cetak Standar</p>
                <h3 class="text-lg font-black text-slate-800 mt-1">
                    HVS Landscape ÷ 2
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">210mm × 148mm (Setengah Folio)</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <i data-lucide="printer" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <form method="GET" action="{{ route('salary-slips.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama karyawan / No. Slip..." 
                       class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <select name="period_month" onchange="this.form.submit()" 
                    class="py-1.5 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 text-slate-700">
                <option value="">-- Semua Periode --</option>
                @foreach($periods as $p)
                    <option value="{{ $p }}" {{ $period === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>

            @if($search || $period)
                <a href="{{ route('salary-slips.index') }}" class="px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 bg-slate-100 rounded-lg">
                    Reset
                </a>
            @endif
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('salary-slips.batch-pdf', request()->query()) }}" target="_blank"
               class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors"
               title="Unduh Berkas PDF Rekap Penggajian">
                <i data-lucide="file-text" class="w-4 h-4 text-rose-600"></i>
                <span>PDF Rekap</span>
            </a>

            <a href="{{ route('salary-slips.excel', request()->query()) }}" 
               class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors"
               title="Export Data ke Excel">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                <span>Excel</span>
            </a>

            <button type="button"
                    @click="openWaPdfModal({
                        title: 'Kirim Rekapitulasi Gaji Karyawan PDF',
                        subtitle: 'Kirim berkas dokumen PDF ringkasan penggajian karyawan ke WhatsApp',
                        filename: 'Rekap_Penggajian_Karyawan_' + ('{{ $period ?: date("Ymd") }}') + '.pdf',
                        fileDescription: 'Dokumen PDF Rekap Penggajian, Tunjangan & Kasbon Karyawan',
                        pdfUrl: '{{ route('salary-slips.batch-pdf', request()->query()) }}',
                        previewUrl: '{{ route('salary-slips.wa-global.preview', request()->query()) }}',
                        sendUrl: '{{ route('salary-slips.send-wa-global') }}',
                        sendPayload: {
                            search: '{{ addslashes($search) }}',
                            period_month: '{{ $period }}'
                        },
                        submitButtonText: 'Kirim Rekap Gaji PDF via WA'
                    })"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm"
                    title="Kirim Rekap Penggajian PDF via WhatsApp">
                <x-whatsapp-icon class="w-3.5 h-3.5" />
                <span>Kirim WA Rekap (PDF)</span>
            </button>

            <a href="{{ route('salary-slips.create') }}" 
               class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold flex items-center justify-center gap-2 shadow-sm shrink-0">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Buat Slip Baru</span>
            </a>
        </div>
    </div>

    <!-- Slips Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-3 px-4">No. Slip & Tanggal</th>
                        <th class="py-3 px-4">Nama Karyawan</th>
                        <th class="py-3 px-4">Periode</th>
                        <th class="py-3 px-4 text-right">Gaji Pokok</th>
                        <th class="py-3 px-4 text-right">Tunjangan/Bonus</th>
                        <th class="py-3 px-4 text-right">Potongan (Kasbon)</th>
                        <th class="py-3 px-4 text-right">Gaji Bersih (THP)</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($slips as $slip)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4">
                                <a href="{{ route('salary-slips.show', $slip) }}" class="font-bold text-emerald-600 hover:underline font-mono">
                                    {{ $slip->slip_number }}
                                </a>
                                <div class="text-[11px] text-slate-400">
                                    {{ $slip->payment_date->format('d/m/Y') }}
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $slip->employee_name }}</div>
                                @if($slip->employeeUser)
                                    <div class="text-[10px] text-slate-500 capitalize">
                                        Role: {{ $slip->employeeUser->role }}
                                    </div>
                                @endif
                            </td>

                            <td class="py-3 px-4 font-medium text-slate-700">
                                {{ $slip->period_month }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-slate-700">
                                {{ \App\Services\FormatHelper::rupiah($slip->basic_salary) }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-emerald-600 font-semibold">
                                + {{ \App\Services\FormatHelper::rupiah($slip->allowance_amount + $slip->bonus_amount) }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-red-600 font-semibold">
                                - {{ \App\Services\FormatHelper::rupiah($slip->total_deduction) }}
                                @if($slip->debt_deduction > 0)
                                    <div class="text-[10px] text-slate-400">Kasbon: {{ \App\Services\FormatHelper::rupiah($slip->debt_deduction) }}</div>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right font-mono font-black text-slate-900 text-sm">
                                {{ \App\Services\FormatHelper::rupiah($slip->net_salary) }}
                            </td>

                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('salary-slips.pdf', $slip) }}"
                                       class="px-2 py-1 rounded bg-slate-900 hover:bg-slate-800 text-white font-bold text-[10px] flex items-center gap-1 shadow-sm"
                                       title="Download PDF Slip">
                                        <i data-lucide="download" class="w-3 h-3"></i>
                                        <span>PDF</span>
                                    </a>

                                    <button type="button"
                                            @click="openWaPdfModal({
                                                title: 'Kirim Slip Gaji Karyawan PDF',
                                                subtitle: 'Kirim berkas dokumen PDF resmi slip gaji ke WhatsApp karyawan',
                                                phone: '{{ $slip->employeeUser?->phone ?? $slip->employee_phone }}',
                                                filename: 'Slip_Gaji_{{ $slip->slip_number }}.pdf',
                                                fileDescription: 'Dokumen PDF Resmi Slip Gaji & Honor Karyawan',
                                                pdfUrl: '{{ route('salary-slips.pdf', $slip) }}',
                                                previewUrl: '{{ route('salary-slips.wa-preview', $slip) }}',
                                                sendUrl: '{{ route('salary-slips.send-wa', $slip) }}',
                                                submitButtonText: 'Kirim Slip PDF via WA'
                                            })"
                                            class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50" 
                                            title="Kirim Slip Gaji PDF ke Karyawan">
                                        <x-whatsapp-icon class="w-4 h-4" />
                                    </button>

                                    <a href="{{ route('salary-slips.print', $slip) }}" target="_blank"
                                       class="p-1 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded"
                                       title="Print Preview">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    </a>

                                    <a href="{{ route('salary-slips.show', $slip) }}" 
                                       class="p-1 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded" title="Lihat Detail">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i data-lucide="banknote" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                <p class="text-sm font-medium">Belum ada data slip gaji karyawan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($slips->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $slips->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

