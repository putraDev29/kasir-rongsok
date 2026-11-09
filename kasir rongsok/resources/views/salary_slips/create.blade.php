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
                <span>Data Karyawan & Periode Gaji</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih User / Operator Terdaftar (Opsional)</label>
                    <select name="user_id" x-model="userId" @change="onUserSelect($event)"
                            class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                        <option value="">-- Pilih User Sistem (Atau ketik manual di samping) --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" 
                                    data-name="{{ $u->name }}" 
                                    data-bank-name="{{ $u->bank_name }}" 
                                    data-bank-number="{{ $u->bank_account_number }}" 
                                    data-bank-holder="{{ $u->bank_account_name }}"
                                    data-bank="{{ $u->bankDisplay() }}">
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

                <!-- Payment Method (Dropdown + Tambah Metode Baru + Form Bank) -->
                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700">Metode Pembayaran</label>
                        <button type="button" @click="togglePaymentMethodMode()" 
                                class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline cursor-pointer">
                            <span x-text="isManualPaymentMethod ? '← Pilih dari Daftar Metode' : '+ Tambah Metode Baru'"></span>
                        </button>
                    </div>

                    <!-- Mode 1: Searchable Dropdown Metode Pembayaran Tersimpan -->
                    <div x-show="!isManualPaymentMethod" class="relative" @click.outside="paymentMethodDropdownOpen = false">
                        <input type="hidden" name="payment_method_id" :value="paymentMethodId" :disabled="isManualPaymentMethod">

                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                            </span>

                            <input type="text"
                                   x-ref="paymentMethodInput"
                                   x-model="paymentMethodSearchQuery"
                                   @focus="paymentMethodDropdownOpen = true; $event.target.select()"
                                   @click="paymentMethodDropdownOpen = true"
                                   @input="paymentMethodDropdownOpen = true"
                                   placeholder="Cari metode pembayaran (Tunai, BCA, BRI, Mandiri...)"
                                   class="w-full pl-8 pr-14 py-2.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-900 bg-white">

                            <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-1">
                                <button type="button" 
                                        x-show="paymentMethodSearchQuery"
                                        @click="paymentMethodSearchQuery = ''; paymentMethodDropdownOpen = true; $refs.paymentMethodInput?.focus()" 
                                        class="text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer"
                                        title="Reset Pencarian">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button" 
                                        @click="paymentMethodDropdownOpen = !paymentMethodDropdownOpen"
                                        class="text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer"
                                        title="Buka / Tutup Daftar Metode">
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': paymentMethodDropdownOpen }"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Floating Filtered List -->
                        <div x-show="paymentMethodDropdownOpen"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute z-50 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white rounded-xl border border-slate-200 shadow-xl divide-y divide-slate-100"
                             style="display: none;">
                            
                            <template x-for="pm in filteredPaymentMethods()" :key="pm.id">
                                <button type="button" 
                                        @click="selectPaymentMethod(pm)"
                                        class="w-full text-left px-3 py-2.5 hover:bg-emerald-50 flex items-center justify-between transition-colors cursor-pointer group"
                                        :class="{ 'bg-emerald-50/80 font-bold text-emerald-950': paymentMethodId == pm.id }">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-slate-900 group-hover:text-emerald-900" x-text="pm.name"></span>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase border"
                                              :class="isTransferMethod(pm) ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                              x-text="isTransferMethod(pm) ? 'Bank / Transfer' : 'Kas Tunai'"></span>
                                    </div>
                                    <template x-if="paymentMethodId == pm.id">
                                        <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    </template>
                                </button>
                            </template>

                            <template x-if="filteredPaymentMethods().length === 0">
                                <div class="p-3 text-center space-y-2">
                                    <p class="text-xs text-slate-500">Tidak ada metode pembayaran "<span class="font-bold text-slate-800" x-text="paymentMethodSearchQuery"></span>"</p>
                                    <button type="button" 
                                            @click="isManualPaymentMethod = true; newPaymentMethodName = paymentMethodSearchQuery; paymentMethodDropdownOpen = false; refreshIcons();"
                                            class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs inline-flex items-center gap-1 cursor-pointer">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span x-text="'+ Tambah &quot;' + paymentMethodSearchQuery + '&quot; sebagai Metode Baru'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Mode 2: Input Manual Metode Pembayaran Baru -->
                    <div x-show="isManualPaymentMethod" style="display: none;" class="space-y-1">
                        <input type="text" name="new_payment_method_name" x-model="newPaymentMethodName"
                               placeholder="Ketik metode pembayaran baru (contoh: QRIS BCA, Transfer Mandiri, Bank Jatim)..."
                               :required="isManualPaymentMethod"
                               class="w-full py-2 px-3 text-xs rounded-lg border border-emerald-500 focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-900 bg-emerald-50/30">
                        <p class="text-[10px] text-emerald-600 font-medium">Metode pembayaran baru otomatis tersimpan ke master database saat slip disimpan.</p>
                        <input type="hidden" name="payment_method_id" value="__new__" :disabled="!isManualPaymentMethod">
                    </div>

                    <!-- Form Akun Bank (Tampil otomatis jika pilih Bank / Transfer) -->
                    <div x-show="isTransferPayment()" style="display: none;" class="mt-2.5 p-3 bg-blue-50 border border-blue-200 rounded-lg space-y-2">
                        <div class="text-[11px] font-bold text-blue-900 flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <i data-lucide="building" class="w-3.5 h-3.5 text-blue-600"></i>
                                <span>Informasi Rekening Bank Karyawan (Dicetak di Dokumen Slip):</span>
                            </div>
                            <span class="text-[10px] text-blue-700 font-medium">Bisa auto-fill dari user atau diisi manual</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-blue-900 mb-0.5">Nama Bank</label>
                                <input type="text" name="custom_bank" x-model="customBank" placeholder="BCA / BRI / Mandiri"
                                       class="w-full py-1.5 px-2 text-xs rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-500 bg-white font-medium text-slate-800">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-blue-900 mb-0.5">Nomor Rekening</label>
                                <input type="text" name="custom_account_no" x-model="customAccountNo" placeholder="No. Rekening Bank"
                                       class="w-full py-1.5 px-2 text-xs rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-500 bg-white font-mono text-slate-800">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-blue-900 mb-0.5">Nama Pemilik (A/N)</label>
                                <input type="text" name="custom_account_name" x-model="customAccountName" placeholder="Nama Pemilik Rekening"
                                       class="w-full py-1.5 px-2 text-xs rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-500 bg-white font-medium text-slate-800">
                            </div>
                        </div>

                        <!-- Opsi Simpan ke Data Profil User Karyawan -->
                        <div class="pt-1 border-t border-blue-200 flex flex-wrap items-center gap-3 text-[10.5px]">
                            <label class="inline-flex items-center gap-1.5 text-blue-900 font-medium cursor-pointer">
                                <input type="checkbox" name="save_to_user_bank" value="1" checked class="rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                                <span>Simpan / perbarui rekening ke profil user karyawan</span>
                            </label>
                        </div>
                    </div>
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
                        <span>Penghasilan / Penerimaan</span>
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
                                    <input type="number" name="work_days" x-model="workDays" @input="calcDailySalary()" step="0.5" min="0" placeholder="Contoh: 25"
                                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-semibold">
                                    <span class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-xs text-slate-400 pointer-events-none">Hari</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Gaji Per Hari (Rp)</label>
                                <div class="relative">
                                    <input type="text"
                                           x-model="dailyRateDisplay"
                                           @input="onDailyRateInput($event)"
                                           placeholder="Rp 120.000"
                                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold text-slate-900">
                                    <input type="hidden" name="daily_rate" :value="dailyRate">
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
                        <input type="text"
                               x-model="basicSalaryDisplay"
                               @input="onBasicSalaryInput($event)"
                               placeholder="Rp 0"
                               required
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold text-slate-900 bg-white">
                        <input type="hidden" name="basic_salary" :value="basicSalary">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Tunjangan (Transport, Makan, Kehadiran)</label>
                        <input type="text"
                               x-model="allowanceDisplay"
                               @input="onAllowanceInput($event)"
                               placeholder="Rp 0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                        <input type="hidden" name="allowance_amount" :value="allowanceAmount">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Bonus Kinerja / Lembur / Target Tonase</label>
                        <input type="text"
                               x-model="bonusDisplay"
                               @input="onBonusInput($event)"
                               placeholder="Rp 0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                        <input type="hidden" name="bonus_amount" :value="bonusAmount">
                    </div>
                </div>
            </div>

            <!-- Deductions (Potongan) -->
            <div class="bg-white p-5 rounded-xl border border-red-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-red-100">
                    <h3 class="text-xs font-bold text-red-900 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                        <span>Potongan Gaji</span>
                    </h3>
                    <span class="text-xs font-mono font-bold text-red-700" x-text="formatRp(totalDeduction())">Rp 0</span>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Potongan Kasbon / Hutang Modal</label>
                        <input type="text"
                               x-model="debtDisplay"
                               @input="onDebtInput($event)"
                               placeholder="Rp 0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-red-500 font-mono">
                        <input type="hidden" name="debt_deduction" :value="debtDeduction">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Potongan Lainnya (BPJS / Terlambat / Absen)</label>
                        <input type="text"
                               x-model="otherDisplay"
                               @input="onOtherInput($event)"
                               placeholder="Rp 0"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-red-500 font-mono">
                        <input type="hidden" name="other_deduction" :value="otherDeduction">
                    </div>
                </div>
            </div>

        </div>

        <!-- Section 3: Notes & Final Take Home Pay Summary -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan / Keterangan Slip</label>
                <textarea name="notes" rows="2" placeholder="Contoh: Gaji ditransfer via BCA, bonus sortir besi..."
                          class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500"></textarea>
            </div>

            <div class="p-4 bg-slate-900 text-white rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Total Gaji Bersih (Take Home Pay)</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono mt-0.5" x-text="formatRp(netSalary())">Rp 0</h2>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" 
                            class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer">
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
        
        dailyRate: 0,
        dailyRateDisplay: '',
        
        basicSalary: 0,
        basicSalaryDisplay: '',
        
        allowanceAmount: 0,
        allowanceDisplay: '',
        
        bonusAmount: 0,
        bonusDisplay: '',
        
        debtDeduction: 0,
        debtDisplay: '',
        
        otherDeduction: 0,
        otherDisplay: '',

        // Payment Method state
        isManualPaymentMethod: false,
        newPaymentMethodName: '',
        paymentMethodId: '{{ $paymentMethods->first()?->id ?? 1 }}',
        paymentMethodSearchQuery: '{{ $paymentMethods->first()?->name ?? "Tunai / Cash" }}',
        paymentMethodDropdownOpen: false,
        paymentMethodsList: @json($paymentMethods),

        // Bank fields
        customBank: '',
        customAccountNo: '',
        customAccountName: '',

        init() {
            this.refreshIcons();
        },

        refreshIcons() {
            this.$nextTick(() => {
                if (window.createIcons && window.lucideIcons) {
                    window.createIcons({ icons: window.lucideIcons });
                }
            });
        },

        formatRupiahString(num) {
            if (num === null || num === undefined || num === '') return '';
            const n = parseInt(String(num).replace(/\D/g, ''), 10);
            return isNaN(n) ? '' : 'Rp ' + n.toLocaleString('id-ID');
        },

        parseRupiahNumber(val) {
            if (!val && val !== 0) return 0;
            const n = parseInt(String(val).replace(/\D/g, ''), 10);
            return isNaN(n) ? 0 : n;
        },

        onDailyRateInput(e) {
            const num = this.parseRupiahNumber(e.target.value);
            this.dailyRate = num;
            this.dailyRateDisplay = num > 0 ? this.formatRupiahString(num) : '';
            e.target.value = this.dailyRateDisplay;
            this.calcDailySalary();
        },

        onBasicSalaryInput(e) {
            const num = this.parseRupiahNumber(e.target.value);
            this.basicSalary = num;
            this.basicSalaryDisplay = num > 0 ? this.formatRupiahString(num) : '';
            e.target.value = this.basicSalaryDisplay;
        },

        onAllowanceInput(e) {
            const num = this.parseRupiahNumber(e.target.value);
            this.allowanceAmount = num;
            this.allowanceDisplay = num > 0 ? this.formatRupiahString(num) : '';
            e.target.value = this.allowanceDisplay;
        },

        onBonusInput(e) {
            const num = this.parseRupiahNumber(e.target.value);
            this.bonusAmount = num;
            this.bonusDisplay = num > 0 ? this.formatRupiahString(num) : '';
            e.target.value = this.bonusDisplay;
        },

        onDebtInput(e) {
            const num = this.parseRupiahNumber(e.target.value);
            this.debtDeduction = num;
            this.debtDisplay = num > 0 ? this.formatRupiahString(num) : '';
            e.target.value = this.debtDisplay;
        },

        onOtherInput(e) {
            const num = this.parseRupiahNumber(e.target.value);
            this.otherDeduction = num;
            this.otherDisplay = num > 0 ? this.formatRupiahString(num) : '';
            e.target.value = this.otherDisplay;
        },

        onUserSelect(e) {
            const opt = e.target.selectedOptions[0];
            if (opt && opt.value) {
                this.employeeName = opt.dataset.name || '';
                if (opt.dataset.bankName) {
                    this.customBank = opt.dataset.bankName;
                }
                if (opt.dataset.bankNumber) {
                    this.customAccountNo = opt.dataset.bankNumber;
                }
                if (opt.dataset.bankHolder) {
                    this.customAccountName = opt.dataset.bankHolder;
                } else if (this.employeeName) {
                    this.customAccountName = this.employeeName;
                }
            }
        },

        calcDailySalary() {
            const days = parseFloat(this.workDays) || 0;
            const rate = parseFloat(this.dailyRate) || 0;
            if (days > 0 && rate > 0) {
                this.basicSalary = Math.round(days * rate);
                this.basicSalaryDisplay = this.formatRupiahString(this.basicSalary);
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
        },

        // Payment Method methods
        filteredPaymentMethods() {
            if (!this.paymentMethodSearchQuery || this.paymentMethodSearchQuery.trim() === '') {
                return this.paymentMethodsList;
            }
            const q = this.paymentMethodSearchQuery.toLowerCase();
            return this.paymentMethodsList.filter(pm => 
                (pm.name && pm.name.toLowerCase().includes(q)) || 
                (pm.code && pm.code.toLowerCase().includes(q))
            );
        },

        selectPaymentMethod(pm) {
            this.paymentMethodId = pm.id;
            this.paymentMethodSearchQuery = pm.name;
            this.paymentMethodDropdownOpen = false;
            
            // If the selected method has bank details stored, pre-fill if customBank is empty
            if (pm.bank_name && !this.customBank) {
                this.customBank = pm.bank_name;
            }
            if (pm.account_number && !this.customAccountNo) {
                this.customAccountNo = pm.account_number;
            }
            if (pm.account_holder && !this.customAccountName) {
                this.customAccountName = pm.account_holder;
            }

            this.refreshIcons();
        },

        isTransferMethod(pm) {
            if (!pm || !pm.name) return false;
            const name = pm.name.toLowerCase();
            const code = (pm.code || '').toLowerCase();
            return name.includes('transfer') || name.includes('bank') || name.includes('bca') || name.includes('bri') || name.includes('mandiri') || name.includes('bni') || code.includes('transfer') || Boolean(pm.bank_name || pm.account_number);
        },

        isTransferPayment() {
            if (this.isManualPaymentMethod) {
                return true;
            }
            const method = this.paymentMethodsList.find(m => m.id == this.paymentMethodId);
            if (!method) {
                const query = (this.paymentMethodSearchQuery || '').toLowerCase();
                return query.includes('transfer') || query.includes('bank') || query.includes('bca') || query.includes('bri') || query.includes('mandiri');
            }
            return this.isTransferMethod(method);
        },

        togglePaymentMethodMode() {
            this.isManualPaymentMethod = !this.isManualPaymentMethod;
            if (this.isManualPaymentMethod) {
                this.paymentMethodId = '__new__';
                this.newPaymentMethodName = (this.paymentMethodSearchQuery && !this.paymentMethodsList.find(m => m.name === this.paymentMethodSearchQuery)) ? this.paymentMethodSearchQuery : '';
            } else {
                const first = this.paymentMethodsList[0];
                this.paymentMethodId = first?.id || 1;
                this.paymentMethodSearchQuery = first?.name || 'Tunai / Cash';
                this.newPaymentMethodName = '';
            }
            this.paymentMethodDropdownOpen = false;
            this.refreshIcons();
        }
    };
}
</script>
@endpush
