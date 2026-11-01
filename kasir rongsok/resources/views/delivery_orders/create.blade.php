@extends('layouts.app', ['header' => 'Buat Surat Jalan Pengiriman'])

@section('content')
<div x-data="deliveryOrderForm()" class="max-w-5xl mx-auto space-y-6">

    <!-- Top Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('delivery-orders.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Form Surat Jalan Pengiriman Rongsok</h2>
                <p class="text-xs text-slate-500">Format cetak/ekspor PDF resmi ukuran HVS Landscape ÷ 2.</p>
            </div>
        </div>
    </div>

    @if($recentSales->count() > 0 && !$salesTx)
        <div class="p-3 bg-blue-50 rounded-xl border border-blue-200 text-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-blue-900">
                <i data-lucide="info" class="w-4 h-4 text-blue-600 shrink-0"></i>
                <span>Tersedia transaksi penjualan partai grosir terbaru yang belum dibuatkan surat jalan.</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="text-[11px] text-blue-700">Tarik dari penjualan:</span>
                <select onchange="if(this.value) window.location.href='{{ route('delivery-orders.create') }}?sales_transaction_id=' + this.value"
                        class="text-xs py-1 px-2 rounded border border-blue-300 bg-white font-medium">
                    <option value="">-- Pilih Transaksi Penjualan --</option>
                    @foreach($recentSales as $rs)
                        <option value="{{ $rs->id }}">{{ $rs->invoice_number }} - {{ $rs->buyer_name }} ({{ number_format($rs->total_weight, 1) }}kg)</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <form action="{{ route('delivery-orders.store') }}" method="POST" class="space-y-6">
        @csrf
        @if($salesTx)
            <input type="hidden" name="sales_transaction_id" value="{{ $salesTx->id }}">
        @endif

        <!-- Card 1: Tujuan Pabrik & Armada Transportasi -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="truck" class="w-4 h-4 text-emerald-600"></i>
                <span>1. Data Pabrik Penerima & Armada Ekspedisi</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Recipient Info -->
                <div class="space-y-3 p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                    <span class="text-xs font-bold text-slate-700 uppercase">Pabrik / Mitra Penerima</span>
                    
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Perusahaan / Pabrik Tujuan <span class="text-red-500">*</span></label>
                        <input type="text" name="recipient_name" x-model="recipientName" required placeholder="Contoh: PT. Master Baja Peleburan / Depo Cilegon"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Alamat Tujuan Pengiriman</label>
                        <input type="text" name="recipient_address" placeholder="Kawasan Industri / Alamat Depo Pembongkaran"
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nomor Telepon / Kontak Penerima</label>
                        <input type="text" name="recipient_phone" x-model="recipientPhone" placeholder="08..."
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                    </div>
                </div>

                <!-- Vehicle Info -->
                <div class="space-y-3 p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                    <span class="text-xs font-bold text-slate-700 uppercase">Armada Kendaraan & Pengemudi</span>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">No. Polisi Armada <span class="text-red-500">*</span></label>
                            <input type="text" name="vehicle_number" x-model="vehicleNumber" required placeholder="Contoh: B 9123 TYZ"
                                   class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono font-bold uppercase">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Jenis Kendaraan</label>
                            <select name="vehicle_type" x-model="vehicleType" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                                <option value="Truk Colt Diesel">Truk Colt Diesel (CDD)</option>
                                <option value="Truk Engkel (CDE)">Truk Engkel (CDE)</option>
                                <option value="Truk Fuso">Truk Fuso</option>
                                <option value="Truk Tronton / Wingbox">Truk Tronton / Wingbox</option>
                                <option value="Pick Up L300 / Gran Max">Pick Up L300 / Gran Max</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Sopir / Driver <span class="text-red-500">*</span></label>
                            <input type="text" name="driver_name" x-model="driverName" required placeholder="Nama sopir"
                                   class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-medium">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nomor HP Sopir</label>
                            <input type="text" name="driver_phone" x-model="driverPhone" placeholder="08..."
                                   class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500 font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tanggal Keberangkatan <span class="text-red-500">*</span></label>
                        <input type="date" name="delivery_date" value="{{ date('Y-m-d') }}" required
                               class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500">
                    </div>
                </div>

            </div>
        </div>

        <!-- Card 2: Cargo / Items Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden space-y-4">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-emerald-600"></i>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        2. Rincian Muatan Barang Rongsok
                    </h3>
                </div>
                <div class="text-xs text-slate-600">
                    Total Berat: <strong class="text-emerald-700 font-mono text-sm" x-text="(totalWeight() || 0).toLocaleString('id-ID') + ' kg'"></strong>
                </div>
            </div>

            <div class="p-4 overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <th class="pb-2 w-10">No</th>
                            <th class="pb-2 w-48">Pilih Dari Master</th>
                            <th class="pb-2">Nama Barang / Spesifikasi Muatan <span class="text-red-500">*</span></th>
                            <th class="pb-2 w-32 text-right">Berat Bersih (kg) <span class="text-red-500">*</span></th>
                            <th class="pb-2 w-28 text-right">Jml Koli / Ikat</th>
                            <th class="pb-2 w-48">Keterangan / No. Segel</th>
                            <th class="pb-2 w-10 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr>
                                <td class="py-2.5 text-slate-400 font-mono" x-text="idx + 1"></td>
                                
                                <td class="py-2.5 pr-2">
                                    <select :name="'items['+idx+'][scrap_item_id]'" x-model="row.scrap_item_id" @change="onItemSelect(row)"
                                            class="w-full text-xs p-1.5 rounded border border-slate-300">
                                        <option value="">-- Manual --</option>
                                        <template x-for="item in masterItems" :key="item.id">
                                            <option :value="item.id" x-text="item.name"></option>
                                        </template>
                                    </select>
                                </td>

                                <td class="py-2.5 pr-2">
                                    <input type="text" :name="'items['+idx+'][item_name]'" x-model="row.item_name" required placeholder="Contoh: Besi Super / Kardus Press..."
                                           class="w-full text-xs p-1.5 rounded border border-slate-300 font-medium">
                                </td>

                                <td class="py-2.5 pr-2">
                                    <input type="number" step="0.1" min="0.1" :name="'items['+idx+'][weight]'" x-model.number="row.weight" required placeholder="0.0"
                                           class="w-full text-xs p-1.5 rounded border border-slate-300 text-right font-mono font-bold">
                                </td>

                                <td class="py-2.5 pr-2">
                                    <input type="number" min="0" :name="'items['+idx+'][package_qty]'" x-model.number="row.package_qty" placeholder="0"
                                           class="w-full text-xs p-1.5 rounded border border-slate-300 text-right font-mono">
                                </td>

                                <td class="py-2.5 pr-2">
                                    <input type="text" :name="'items['+idx+'][notes]'" x-model="row.notes" placeholder="Segel / keterangan..."
                                           class="w-full text-xs p-1.5 rounded border border-slate-300">
                                </td>

                                <td class="py-2.5 text-center">
                                    <button type="button" @click="removeRow(idx)" class="text-slate-400 hover:text-red-600">
                                        <i data-lucide="trash" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div class="pt-3 flex items-center justify-between">
                    <button type="button" @click="addRow()" 
                            class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200 flex items-center gap-1.5">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-600"></i>
                        <span>Tambah Baris Muatan</span>
                    </button>

                    <div class="text-xs text-slate-600 space-x-4">
                        <span>Total Koli: <strong class="font-mono text-slate-800" x-text="totalPackages()"></strong></span>
                        <span>Total Muatan: <strong class="font-mono text-emerald-700 text-sm" x-text="(totalWeight() || 0).toLocaleString('id-ID') + ' kg'"></strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Notes & Submit -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Pengiriman / Instruksi Khusus</label>
                <textarea name="notes" rows="2" placeholder="Contoh: Muatan diikat terpal rapi, bongkar pagi pukul 08:00, dokumen diserahkan security..."
                          class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-emerald-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <a href="{{ route('delivery-orders.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200">
                    Batal
                </a>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan & Terbitkan Surat Jalan</span>
                </button>
            </div>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function deliveryOrderForm() {
    return {
        masterItems: @json($scrapItems),
        salesTx: @json($salesTx),
        recipientName: '{{ $salesTx ? $salesTx->buyer_name : '' }}',
        recipientPhone: '{{ $salesTx ? $salesTx->buyer_phone : '' }}',
        vehicleNumber: '',
        driverName: '',
        driverPhone: '',
        vehicleType: 'Truk Colt Diesel',
        rows: [
            @if($salesTx && $salesTx->items->count() > 0)
                @foreach($salesTx->items as $si)
                    {
                        scrap_item_id: '{{ $si->scrap_item_id }}',
                        item_name: '{{ addslashes($si->item_name_snapshot) }}',
                        weight: {{ (float) $si->weight }},
                        package_qty: 0,
                        notes: ''
                    },
                @endforeach
            @else
                { scrap_item_id: '', item_name: '', weight: '', package_qty: '', notes: '' }
            @endif
        ],

        addRow() {
            this.rows.push({ scrap_item_id: '', item_name: '', weight: '', package_qty: '', notes: '' });
            if (window.refreshIcons) window.refreshIcons();
        },

        removeRow(idx) {
            if (this.rows.length > 1) {
                this.rows.splice(idx, 1);
            }
        },

        onItemSelect(row) {
            const found = this.masterItems.find(i => i.id == row.scrap_item_id);
            if (found) {
                row.item_name = found.name;
            }
        },

        totalWeight() {
            return this.rows.reduce((acc, r) => acc + (parseFloat(r.weight) || 0), 0);
        },

        totalPackages() {
            return this.rows.reduce((acc, r) => acc + (parseInt(r.package_qty) || 0), 0);
        }
    };
}
</script>
@endpush

