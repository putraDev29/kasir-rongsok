@extends('layouts.app', ['header' => 'Hutang Piutang (Pinjaman Modal & Bon)'])

@section('content')
<div x-data="{ 
    showCreateModal: false, 
    showPayModal: false,
    showSuccessModal: {{ !empty($justCreatedDebt) ? 'true' : 'false' }},
    payDebtId: null,
    payDebtNumber: '',
    payBorrowerName: '',
    payRemainingAmount: 0,
    payAmount: 0,
    borrowerType: 'supplier',
    supplierId: '',
    borrowerName: '',
    borrowerPhone: '',

    openPayModal(id, number, name, remaining) {
        this.payDebtId = id;
        this.payDebtNumber = number;
        this.payBorrowerName = name;
        this.payRemainingAmount = remaining;
        this.payAmount = remaining;
        this.showPayModal = true;
    },

    onSupplierSelect(e) {
        const opt = e.target.selectedOptions[0];
        if (opt && opt.value) {
            this.borrowerName = opt.dataset.name || '';
            this.borrowerPhone = opt.dataset.phone || '';
        } else {
            this.borrowerName = '';
            this.borrowerPhone = '';
        }
    }
}" class="space-y-6">

    <!-- Top KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pinjaman Modal</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1 font-mono">
                    Rp {{ number_format($totalInitial, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi modal dipinjamkan</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="handshake" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Telah Diangsur</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-600 mt-1 font-mono">
                    Rp {{ number_format($totalPaid, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Dari kasir timbang & setor tunai</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle-2" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/30 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-amber-800 uppercase tracking-wider">Sisa Hutang Piutang Aktif</p>
                <h3 class="text-xl sm:text-2xl font-black text-amber-600 mt-1 font-mono">
                    Rp {{ number_format($totalRemaining, 0, ',', '.') }}
                </h3>
                <p class="text-[11px] text-amber-700 mt-0.5">Modal beredar belum kembali</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                <i data-lucide="hourglass" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Action Header -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Filter Form -->
        <form method="GET" action="{{ route('debts.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari peminjam / No. Pinjaman..." 
                       class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <select name="status" onchange="this.form.submit()" 
                    class="py-1.5 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 text-slate-700">
                <option value="">-- Semua Status --</option>
                <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Belum Diangsur</option>
                <option value="partially_paid" {{ $status === 'partially_paid' ? 'selected' : '' }}>Diangsur Sebagian</option>
                <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Lunas</option>
            </select>

            <select name="borrower_type" onchange="this.form.submit()" 
                    class="py-1.5 px-3 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 text-slate-700">
                <option value="">-- Semua Tipe Peminjam --</option>
                <option value="supplier" {{ $type === 'supplier' ? 'selected' : '' }}>Pemasok / Pengepul</option>
                <option value="karyawan" {{ $type === 'karyawan' ? 'selected' : '' }}>Karyawan / Kasbon</option>
                <option value="lainnya" {{ $type === 'lainnya' ? 'selected' : '' }}>Pihak Lain</option>
            </select>

            @if($search || $status || $type)
                <a href="{{ route('debts.index') }}" class="px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 bg-slate-100 rounded-lg">
                    Reset
                </a>
            @endif
        </form>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('debts.batch-pdf', request()->all()) }}" target="_blank"
               class="px-2.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors"
               title="Unduh Rekap PDF Hutang Modal">
                <i data-lucide="file-text" class="w-4 h-4 text-rose-600"></i>
                <span class="hidden sm:inline">PDF</span>
            </a>

            <a href="{{ route('debts.export-excel', request()->all()) }}" 
               class="px-2.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors"
               title="Export Excel">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                <span class="hidden sm:inline">Excel</span>
            </a>

            <button type="button" 
                    @click="openWaPdfModal({
                        title: 'Kirim Rekapitulasi Hutang Piutang Modal PDF',
                        subtitle: 'Kirim berkas dokumen PDF rekapitulasi buku hutang piutang modal ke nomor WhatsApp',
                        filename: 'Rekap_Hutang_Modal_' + ('{{ date("Ymd") }}') + '.pdf',
                        fileDescription: 'Dokumen PDF Rekap Buku Hutang Piutang & Pinjaman Modal',
                        pdfUrl: '{{ route('debts.batch-pdf', request()->all()) }}',
                        previewUrl: '{{ route('debts.wa-global.preview', request()->all()) }}',
                        sendUrl: '{{ route('debts.send-wa-global') }}',
                        sendPayload: {
                            search: '{{ addslashes($search) }}',
                            status: '{{ $status }}',
                            borrower_type: '{{ $type }}'
                        },
                        submitButtonText: 'Kirim Rekap Hutang PDF via WA'
                    })"
                    class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm transition-colors"
                    title="Kirim Rekap Hutang Piutang PDF via WhatsApp">
                <x-whatsapp-icon class="w-3.5 h-3.5" />
                <span>Kirim WA Rekap (PDF)</span>
            </button>

            <button type="button" @click="showCreateModal = true" 
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold flex items-center justify-center gap-2 shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Pinjaman Modal Baru</span>
            </button>
        </div>
    </div>

    <!-- Debts Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="py-3 px-4">No. Pinjaman & Tanggal</th>
                        <th class="py-3 px-4">Peminjam</th>
                        <th class="py-3 px-4">Tipe</th>
                        <th class="py-3 px-4 text-right">Nominal Pinjaman</th>
                        <th class="py-3 px-4 text-right">Sudah Diangsur</th>
                        <th class="py-3 px-4 text-right">Sisa Hutang</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($debts as $debt)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4">
                                <a href="{{ route('debts.show', $debt) }}" class="font-bold text-emerald-600 hover:underline font-mono">
                                    {{ $debt->debt_number }}
                                </a>
                                <div class="text-[11px] text-slate-400">
                                    {{ $debt->date->format('d/m/Y') }}
                                    @if($debt->due_date)
                                        <span class="text-amber-600">(JT: {{ $debt->due_date->format('d/m/Y') }})</span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $debt->borrower_name }}</div>
                                @if($debt->borrower_phone)
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $debt->borrower_phone }}</div>
                                @endif
                                @if($debt->supplier)
                                    <div class="text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded w-max mt-0.5">
                                        Pemasok: {{ $debt->supplier->code }}
                                    </div>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                @if($debt->borrower_type === 'supplier')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Pemasok</span>
                                @elseif($debt->borrower_type === 'karyawan')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">Kasbon Karyawan</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">Lainnya</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">
                                Rp {{ number_format($debt->initial_amount, 0, ',', '.') }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono font-semibold text-emerald-600">
                                Rp {{ number_format($debt->paid_amount, 0, ',', '.') }}
                                @if($debt->initial_amount > 0)
                                    <div class="text-[10px] text-slate-400">
                                        {{ round(($debt->paid_amount / $debt->initial_amount) * 100) }}%
                                    </div>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right font-mono font-black {{ $debt->remaining_amount > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                                Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }}
                            </td>

                            <td class="py-3 px-4 text-center">
                                @if($debt->status === 'paid')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                        LUNAS
                                    </span>
                                @elseif($debt->status === 'partially_paid')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        SEBAGIAN
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                        BELUM LUNAS
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($debt->remaining_amount > 0)
                                        <button type="button" 
                                                @click="openPayModal({{ $debt->id }}, '{{ $debt->debt_number }}', '{{ addslashes($debt->borrower_name) }}', {{ $debt->remaining_amount }})"
                                                class="px-2.5 py-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[11px] border border-emerald-200 flex items-center gap-1">
                                            <i data-lucide="badge-dollar-sign" class="w-3.5 h-3.5"></i>
                                            <span>Bayar</span>
                                        </button>
                                    @endif

                                    <a href="{{ route('debts.agreement', $debt) }}" 
                                       target="_blank"
                                       class="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded" title="Cetak Surat Perjanjian">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
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
                                            class="p-1 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded" 
                                            title="Kirim Surat Perjanjian PDF ke WhatsApp">
                                        <x-whatsapp-icon class="w-4 h-4" />
                                    </button>

                                    <a href="{{ route('debts.show', $debt) }}" 
                                       class="p-1 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded" title="Lihat Detail & Riwayat Cicilan">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i data-lucide="handshake" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                <p class="text-sm font-medium">Belum ada data pinjaman modal / hutang piutang.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($debts->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $debts->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: Catat Pinjaman Modal Baru -->
    <div x-show="showCreateModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200 space-y-4" @click.away="showCreateModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <i data-lucide="handshake" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Catat Pinjaman Modal / Bon</h3>
                        <p class="text-[11px] text-slate-500">Berikan pinjaman modal kepada pengepul, karyawan, atau mitra.</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('debts.store') }}" method="POST" class="space-y-3.5">
                @csrf

                <!-- Borrower Type -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Peminjam</label>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:border-emerald-500 font-medium"
                               :class="borrowerType === 'supplier' ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : ''">
                            <input type="radio" name="borrower_type" value="supplier" x-model="borrowerType" class="text-emerald-600">
                            <span>Pemasok</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:border-emerald-500 font-medium"
                               :class="borrowerType === 'karyawan' ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : ''">
                            <input type="radio" name="borrower_type" value="karyawan" x-model="borrowerType" class="text-emerald-600">
                            <span>Karyawan</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:border-emerald-500 font-medium"
                               :class="borrowerType === 'lainnya' ? 'bg-emerald-50 border-emerald-500 text-emerald-900' : ''">
                            <input type="radio" name="borrower_type" value="lainnya" x-model="borrowerType" class="text-emerald-600">
                            <span>Lainnya</span>
                        </label>
                    </div>
                </div>

                <!-- If Supplier, choose from master -->
                <div x-show="borrowerType === 'supplier'">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Dari Master Pemasok</label>
                    <select name="supplier_id" x-model="supplierId" @change="onSupplierSelect($event)"
                            class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                        <option value="">-- Pilih Pemasok / Atau ketik manual di bawah --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" data-name="{{ $sup->name }}" data-phone="{{ $sup->phone }}">
                                {{ $sup->name }} [{{ $sup->member_number }}]
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Borrower Name & Phone -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Peminjam <span class="text-red-500">*</span></label>
                        <input type="text" name="borrower_name" x-model="borrowerName" required placeholder="Nama lengkap / panggilan"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor HP</label>
                        <input type="text" name="borrower_phone" x-model="borrowerPhone" placeholder="08..."
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>
                </div>

                <!-- Nominal Pinjaman -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Pinjaman Modal (Rp) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-slate-400 text-xs">Rp</span>
                        <input type="number" name="initial_amount" step="1000" min="1000" required placeholder="0"
                               class="w-full pl-9 pr-3 py-2 text-sm font-mono font-bold rounded-lg border border-slate-300 focus:ring-emerald-500 text-slate-900">
                    </div>
                </div>

                <!-- Tanggal & Jatuh Tempo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pinjaman <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jatuh Tempo (Opsional)</label>
                        <input type="date" name="due_date"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- Jaminan / Agunan & Saksi (Opsional untuk Surat Perjanjian) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jaminan / Agunan (Opsional)</label>
                        <input type="text" name="guarantee_info" placeholder="Contoh: BPKB Motor, KTP Asli, dll"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Saksi (Opsional)</label>
                        <input type="text" name="witness_name" placeholder="Nama saksi kesepakatan"
                               class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- Catatan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Keperluan Pinjaman</label>
                    <textarea name="notes" rows="2" placeholder="Contoh: Tambahan modal keliling cari besi, beli timbangan, kasbon mingguan..."
                              class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showCreateModal = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm flex items-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Simpan & Terbitkan SPH</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(!empty($justCreatedDebt))
    <!-- MODAL SUKSES INPUT DATA PINJAMAN MODAL (CETAK PDF & SHARE WA) -->
    <div x-show="showSuccessModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4" @click.away="showSuccessModal = false">
            <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 shadow-xs">
                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900">Pinjaman Modal Berhasil Dicatat!</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                SPH RESMI
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500">Surat Perjanjian Hutang Piutang (SPH) telah dibuat lengkap tanda tangan Owner & Peminjam.</p>
                    </div>
                </div>
                <button type="button" @click="showSuccessModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Detail Ringkasan Pinjaman Modal -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-2 text-xs">
                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200">
                    <span class="text-slate-500">Nomor Referensi:</span>
                    <span class="font-mono font-bold text-slate-800 bg-white px-2 py-0.5 rounded border border-slate-200">{{ $justCreatedDebt->debt_number }}</span>
                </div>
                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200">
                    <span class="text-slate-500">No. Surat Perjanjian (SPH):</span>
                    <span class="font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">{{ $justCreatedDebt->agreement_number }}</span>
                </div>
                <div class="flex justify-between items-center pb-1.5 border-b border-slate-200">
                    <span class="text-slate-500">Nama Peminjam:</span>
                    <span class="font-bold text-slate-900">{{ $justCreatedDebt->borrower_name }} @if($justCreatedDebt->borrower_phone) <span class="font-mono font-normal text-slate-500">({{ $justCreatedDebt->borrower_phone }})</span> @endif</span>
                </div>
                <div class="flex justify-between items-center pt-0.5">
                    <span class="text-slate-500">Nominal Pinjaman Modal:</span>
                    <span class="font-mono font-black text-sm text-emerald-700">
                        Rp {{ number_format($justCreatedDebt->initial_amount, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Tombol Aksi: Cetak PDF & Kirim via WhatsApp -->
            <div class="space-y-2">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aksi Surat Perjanjian Hutang Modal:</div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('debts.agreement-pdf', $justCreatedDebt) }}" target="_blank"
                       class="px-3.5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm transition-colors text-center"
                       title="Preview & Unduh Berkas PDF Surat Perjanjian">
                        <i data-lucide="file-text" class="w-4 h-4 text-emerald-400"></i>
                        <span>Preview PDF SPH</span>
                    </a>

                    <a href="{{ route('debts.agreement', $justCreatedDebt) }}" target="_blank"
                       class="px-3.5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-800 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-xs transition-colors text-center"
                       title="Cetak Print Lembar Surat Perjanjian">
                        <i data-lucide="printer" class="w-4 h-4 text-slate-600"></i>
                        <span>Cetak Lembar</span>
                    </a>
                </div>

                <button type="button"
                        @click="showSuccessModal = false; openWaPdfModal({
                            title: 'Kirim Surat Perjanjian Pinjaman PDF',
                            subtitle: 'Kirim berkas dokumen PDF Surat Perjanjian Hutang Modal (SPH) ke WhatsApp peminjam',
                            phone: '{{ $justCreatedDebt->borrower_phone }}',
                            filename: 'SPH_' + ('{{ str_replace(['/', ' '], '_', $justCreatedDebt->agreement_number ?: $justCreatedDebt->debt_number) }}') + '.pdf',
                            fileDescription: 'Dokumen PDF Resmi Surat Perjanjian Hutang Piutang Modal',
                            pdfUrl: '{{ route('debts.agreement-pdf', $justCreatedDebt) }}',
                            previewUrl: '{{ route('debts.agreement-wa-preview', $justCreatedDebt) }}',
                            sendUrl: '{{ route('debts.agreement-wa', $justCreatedDebt) }}',
                            submitButtonText: 'Kirim SPH PDF via WA'
                        })"
                        class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-sm transition-colors"
                        title="Kirim Surat Perjanjian via WhatsApp">
                    <x-whatsapp-icon class="w-4 h-4" />
                    <span>Share Surat Perjanjian via WhatsApp</span>
                </button>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <a href="{{ route('debts.show', $justCreatedDebt) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 hover:underline flex items-center gap-1">
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    <span>Buka Rincian Pinjaman</span>
                </a>
                <button type="button" @click="showSuccessModal = false" class="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL 2: Pembayaran / Angsuran Langsung -->
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
                        <p class="text-[11px] text-slate-500 font-mono" x-text="payDebtNumber + ' - ' + payBorrowerName"></p>
                    </div>
                </div>
                <button type="button" @click="showPayModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Pay Form -->
            <form :action="'{{ url('debts') }}/' + payDebtId + '/pay'" method="POST" class="space-y-3.5">
                @csrf

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 flex justify-between items-center text-xs">
                    <span class="text-amber-800 font-semibold">Sisa Saldo Hutang:</span>
                    <span class="font-mono font-black text-amber-900 text-sm" x-text="'Rp ' + (payRemainingAmount || 0).toLocaleString('id-ID')">Rp 0</span>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-xs font-semibold text-slate-700">Nominal Pembayaran (Rp) <span class="text-red-500">*</span></label>
                        <button type="button" @click="payAmount = payRemainingAmount" class="text-[10px] text-emerald-600 font-bold hover:underline">
                            Bayar Lunas Semua
                        </button>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-slate-400 text-xs">Rp</span>
                        <input type="number" name="amount" x-model.number="payAmount" step="500" min="500" :max="payRemainingAmount" required
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
                    <input type="text" name="notes" placeholder="Contoh: Setor tunai ke kantor, angsuran ke-2, dsb."
                           class="w-full text-xs p-2 rounded-lg border border-slate-300 focus:ring-emerald-500">
                </div>

                <div class="p-2.5 bg-slate-50 rounded-lg text-[11px] text-slate-500 flex items-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4 text-slate-400 shrink-0"></i>
                    <span>Setelah disimpan, sistem akan otomatis membuka halaman cetak nota bukti pembayaran.</span>
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

