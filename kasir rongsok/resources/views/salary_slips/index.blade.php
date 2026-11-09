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
                    {{ ($startDate && $endDate) ? "Periode {$startDate} s/d {$endDate}" : ($period ? "Periode {$period}" : "Semua periode penggajian") }}
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
                    Kertas A4 Portrait
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">210mm × 297mm (Standar Resmi)</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <i data-lucide="printer" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('salary-slips.index') }}" class="space-y-3">
            <!-- Row 1: Cari Karyawan/No. Slip, Bulan, Tahun -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cari Karyawan / No. Slip</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama karyawan / No. Slip..." 
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

                <x-month-year-filter :selected-month="$selectedMonth ?? request('month')" :selected-year="$selectedYear ?? request('year')" />
            </div>

            <!-- Row 2: Dari Tanggal, Sampai Tanggal, Terapkan Filter & Reset -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Dari Tanggal Bayar</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                           class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sampai Tanggal Bayar</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                           class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Terapkan Filter</span>
                    </button>
                    <a href="{{ route('salary-slips.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Slips Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Daftar Slip Gaji Karyawan</span>
                <span class="text-[11px] text-slate-500">Total ditampilkan: {{ $slips->total() }} berkas slip</span>
            </div>

            <!-- Action Buttons on Table Card Header -->
            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                   onclick="openPdfPreview('{{ route('salary-slips.batch-pdf', request()->query()) }}', 'Rekap Penggajian Karyawan', '', '', 'Berikut rekapitulasi penggajian karyawan')"
                   class="px-2.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm transition-colors cursor-pointer"
                   title="Pratinjau PDF Rekap Penggajian">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                    <span>PDF Rekap</span>
                </button>

                <a href="{{ route('salary-slips.excel', request()->query()) }}" 
                   class="px-2.5 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs flex items-center gap-1 shadow-sm transition-colors"
                   title="Export Data ke Excel">
                    <i data-lucide="sheet" class="w-3.5 h-3.5"></i>
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
                        class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1 shadow-sm transition-colors"
                        title="Kirim Rekap Penggajian PDF via WhatsApp">
                    <x-whatsapp-icon class="w-3.5 h-3.5" />
                    <span>Kirim WA Rekap (PDF)</span>
                </button>

                <a href="{{ route('salary-slips.create') }}" 
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>Buat Slip Baru</span>
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-3 px-4 whitespace-nowrap">No. Slip & Tanggal</th>
                        <th class="py-3 px-4 min-w-[150px]">Nama Karyawan</th>
                        <th class="py-3 px-4 whitespace-nowrap">Periode</th>
                        <th class="py-3 px-4 text-right whitespace-nowrap">Gaji Pokok</th>
                        <th class="py-3 px-4 text-right whitespace-nowrap">Tunjangan/Bonus</th>
                        <th class="py-3 px-4 text-right whitespace-nowrap">Potongan (Kasbon)</th>
                        <th class="py-3 px-4 text-right whitespace-nowrap">Gaji Bersih (THP)</th>
                        <th class="py-3 px-4 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($slips as $slip)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 whitespace-nowrap">
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

                            <td class="py-3 px-4 font-medium text-slate-700 whitespace-nowrap">
                                {{ $slip->period_month }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-slate-700 whitespace-nowrap">
                                <div>{{ \App\Services\FormatHelper::rupiah($slip->basic_salary) }}</div>
                                @if($slip->work_days && $slip->daily_rate)
                                    <div class="text-[10px] text-emerald-700 font-sans font-medium">
                                        {{ $slip->work_days }} hr &times; {{ \App\Services\FormatHelper::rupiah($slip->daily_rate) }}
                                    </div>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-emerald-600 font-semibold whitespace-nowrap">
                                + {{ \App\Services\FormatHelper::rupiah($slip->allowance_amount + $slip->bonus_amount) }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-red-600 font-semibold whitespace-nowrap">
                                - {{ \App\Services\FormatHelper::rupiah($slip->total_deduction) }}
                                @if($slip->debt_deduction > 0)
                                    <div class="text-[10px] text-slate-400">Kasbon: {{ \App\Services\FormatHelper::rupiah($slip->debt_deduction) }}</div>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right font-mono font-black text-slate-900 text-sm whitespace-nowrap">
                                {{ \App\Services\FormatHelper::rupiah($slip->net_salary) }}
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                            @click="openWaPdfModal({
                                                title: 'Kirim Slip Gaji Karyawan PDF',
                                                subtitle: 'Kirim berkas dokumen PDF resmi slip gaji ke WhatsApp karyawan',
                                                phone: '{{ $slip->employeeUser?->phone ?? $slip->employee_phone }}',
                                                filename: 'Slip_Gaji_{{ $slip->slip_number }}.pdf',
                                                fileDescription: 'Dokumen PDF Resmi Slip Gaji & Honor Karyawan',
                                                printUrl: '{{ route('salary-slips.print', $slip) }}',
                                                pdfUrl: '{{ route('salary-slips.pdf', $slip) }}',
                                                previewUrl: '{{ route('salary-slips.wa-preview', $slip) }}',
                                                sendUrl: '{{ route('salary-slips.send-wa', $slip) }}',
                                                submitButtonText: 'Kirim Slip PDF via WA'
                                            })"
                                            class="p-1 rounded text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 transition-colors" 
                                            title="Kirim Slip Gaji PDF ke Karyawan">
                                        <x-whatsapp-icon class="w-4 h-4" />
                                    </button>

                                    <a href="{{ route('salary-slips.print', $slip) }}" target="_blank"
                                       class="p-1 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded transition-colors"
                                       title="Print Slip Gaji (A4)">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </a>

                                    <a href="{{ route('salary-slips.show', $slip) }}" 
                                       class="p-1 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded transition-colors" title="Lihat Detail Slip">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
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

