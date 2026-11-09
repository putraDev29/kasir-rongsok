@extends('layouts.app', ['header' => 'Buat Slip Gaji Karyawan'])

@section('content')
<div x-data="salarySlipForm()" class="max-w-4xl mx-auto space-y-6">

    <!-- Top Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('salary-slips.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Form Penggajian Karyawan</h2>
                <p class="text-xs text-slate-500">Buat slip gaji dengan format dokumen PDF standar A4 resmi.</p>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <form action="{{ route('salary-slips.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Section 1: Employee & Meta -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                <span>1. Data Karyawan & Periode Gaji</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih User / Operator Terdaftar (Opsional)</label>
                    <select name="user_id" x-model="userId" @change="onUserSelect($event)"
                            class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                        <option value="">-- Pilih User Sistem (Atau ketik manual di samping) --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" data-name="{{ $u->name }}" data-bank="{{ $u->bankDisplay() }}">
                                {{ $u->name }} ({{ ucfirst($u->role) }}) {{ $u->hasBankDetails() ? '— [' . $u->bank_name . ': ' . $u->bank_account_number . ']' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Karyawan <span class="text-red-500">*</span></label>
                    <input type="text" name="employee_name" x-model="employeeName" required placeholder="Contoh: Budi Santoso / Operator Timbangan"
                           class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Periode Bulan Gaji <span class="text-red-500">*</span></label>
                    <input type="text" name="period_month" value="{{ $currentMonth }}" required placeholder="Contoh: November 2026"
                           class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pembayaran Gaji <span class="text-red-500">*</span></label>
                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                           class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Pembayaran</label>
                    <select name="payment_method_id" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 2: Earnings & Deductions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Earnings (Penerimaan) -->
            <div class="bg-white p-5 rounded-xl border border-emerald-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-emerald-100">
                    <h3 class="text-xs font-bold text-emerald-900 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-600"></i>
                        <span>A. Penghasilan / Penerimaan</span>
                    </h3>
                    <span class="text-xs font-mono font-bold text-emerald-700" x-text="formatRp(totalIncome())">Rp 0</span>
                </div>

                <div class="space-y-3">
                    <!-- Perhitungan Hari Kerja x Gaji Per Hari -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                            Hitung Gaji Harian (Jumlah Hari Kerja &times; Gaji / Hari)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Jumlah Hari Kerja</label>
                                <div class="relative">
                                    <input type="number" name="work_days" x-model.number="workDays" @input="calcDailySalary()" step="0.5" min="0" placeholder="Contoh: 25"
                                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                                    <span class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-xs text-slate-400 pointer-events-none">Hari</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Gaji Per Hari (Rp)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs text-slate-400 pointer-events-none font-mono">Rp</span>
                                    <input type="number" name="daily_rate" x-model.number="dailyRate" @input="calcDailySalary()" step="1000" min="0" placeholder="Contoh: 100000"
                                           class="w-full text-xs pl-8 pr-2.5 py-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                                </div>
                            </div>
                        </div>
                        <template x-if="workDays > 0 && dailyRate > 0">
                            <div class="text-[11px] text-emerald-700 font-semibold flex items-center justify-between pt-1 border-t border-slate-200">
                                <span>Rincian: <span x-text="workDays"></span> hari &times; <span x-text="formatRp(dailyRate)"></span></span>
                                <span class="font-mono font-bold" x-text="formatRp(workDays * dailyRate)"></span>
                            </div>
                        </template>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">Total Gaji Pokok (Rp) <span class="text-red-500">*</span></label>
                            <span class="text-[11px] text-emerald-600 font-medium" x-show="workDays > 0 && dailyRate > 0">Otomatis terisi dari perkalian hari</span>
                        </div>
                        <input type="number" name="basic_salary" x-model.number="basicSalary" step="1000" min="0" required placeholder="0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold text-slate-900 bg-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Tunjangan (Transport, Makan, Kehadiran)</label>
                        <input type="number" name="allowance_amount" x-model.number="allowanceAmount" step="1000" min="0" placeholder="0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Bonus Kinerja / Lembur / Target Tonase</label>
                        <input type="number" name="bonus_amount" x-model.number="bonusAmount" step="1000" min="0" placeholder="0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>
                </div>
            </div>

            <!-- Deductions (Potongan) -->
            <div class="bg-white p-5 rounded-xl border border-red-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-red-100">
                    <h3 class="text-xs font-bold text-red-900 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                        <span>B. Potongan Gaji</span>
                    </h3>
                    <span class="text-xs font-mono font-bold text-red-700" x-text="formatRp(totalDeduction())">Rp 0</span>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Potongan Kasbon / Hutang Modal</label>
                        <input type="number" name="debt_deduction" x-model.number="debtDeduction" step="1000" min="0" placeholder="0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-red-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Potongan Lainnya (BPJS / Terlambat / Absen)</label>
                        <input type="number" name="other_deduction" x-model.number="otherDeduction" step="1000" min="0" placeholder="0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-red-500 font-mono">
                    </div>
                </div>
            </div>

        </div>

        <!-- Section 3: Notes & Final Take Home Pay Summary -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan / Keterangan Slip</label>
                <textarea name="notes" rows="2" placeholder="Contoh: Gaji dibayarkan tunai, bonus lembur sortir besi 10 jam..."
                          class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500"></textarea>
            </div>

            <div class="p-4 bg-slate-900 text-white rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Total Gaji Bersih (Take Home Pay)</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono mt-0.5" x-text="formatRp(netSalary())">Rp 0</h2>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" 
                            class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-md transition-all flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Simpan & Terbitkan Slip</span>
                    </button>
                </div>
            </div>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function salarySlipForm() {
    return {
        employeeName: '',
        userId: '',
        workDays: '',
        dailyRate: '',
        basicSalary: 0,
        allowanceAmount: 0,
        bonusAmount: 0,
        debtDeduction: 0,
        otherDeduction: 0,

        onUserSelect(e) {
            const opt = e.target.selectedOptions[0];
            if (opt && opt.value) {
                this.employeeName = opt.dataset.name || '';
            }
        },

        calcDailySalary() {
            const days = parseFloat(this.workDays) || 0;
            const rate = parseFloat(this.dailyRate) || 0;
            if (days > 0 && rate > 0) {
                this.basicSalary = Math.round(days * rate);
            }
        },

        totalIncome() {
            return (parseFloat(this.basicSalary) || 0) + (parseFloat(this.allowanceAmount) || 0) + (parseFloat(this.bonusAmount) || 0);
        },

        totalDeduction() {
            return (parseFloat(this.debtDeduction) || 0) + (parseFloat(this.otherDeduction) || 0);
        },

        netSalary() {
            return Math.max(0, this.totalIncome() - this.totalDeduction());
        },

        formatRp(val) {
            return 'Rp ' + (val || 0).toLocaleString('id-ID');
        }
    };
}
</script>
@endpush

