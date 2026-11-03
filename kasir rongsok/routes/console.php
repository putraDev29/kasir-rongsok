<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('app:reset-transactions {--force : Paksa reset tanpa konfirmasi}', function () {
    if (!$this->option('force') && !$this->confirm('Apakah Anda yakin ingin menghapus SEMUA data transaksi dan menyisakan data User, Master Barang & Harga?')) {
        $this->warn('Operasi dibatalkan.');
        return 1;
    }

    $this->info('Memulai pembersihan data transaksi...');

    DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

    // 1. Transaksi Pembelian & Penjualan
    DB::table('purchase_transaction_items')->truncate();
    DB::table('purchase_transactions')->truncate();
    DB::table('sales_transaction_items')->truncate();
    DB::table('sales_transactions')->truncate();

    // 2. Surat Jalan Pengiriman
    DB::table('delivery_order_items')->truncate();
    DB::table('delivery_orders')->truncate();

    // 3. Hutang Piutang, Biaya Operasional, & Slip Gaji
    DB::table('debt_payments')->truncate();
    DB::table('debts')->truncate();
    DB::table('expenses')->truncate();
    DB::table('salary_slips')->truncate();

    // 4. Reward & Pemasok Dummy
    DB::table('supplier_rewards')->truncate();
    DB::table('suppliers')->truncate();

    // 5. Mutasi Stok Ledger, Log Aktivitas, & Sesi
    DB::table('stock_movements')->truncate();
    DB::table('activity_logs')->truncate();
    DB::table('sessions')->truncate();

    // 6. Reset Stock Balances ke 0 untuk semua master scrap items
    DB::table('stock_balances')->truncate();
    $scrapItems = DB::table('scrap_items')->get();
    foreach ($scrapItems as $item) {
        DB::table('stock_balances')->insert([
            'scrap_item_id' => $item->id,
            'current_stock' => 0.00,
            'total_in' => 0.00,
            'total_out' => 0.00,
            'average_buy_price' => $item->buy_price_per_kg,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

    $this->info('================================================================');
    $this->info('  SEMUA DATA TRANSAKSI BERHASIL DIHAPUS!');
    $this->info('================================================================');
    $this->info('Data yang tetap tersimpan aman:');
    $this->info('  [V] Data User / Akun Login : ' . DB::table('users')->count() . ' user');
    $this->info('  [V] Master Barang Rongsok  : ' . DB::table('scrap_items')->count() . ' item');
    $this->info('  [V] Kategori Barang        : ' . DB::table('item_categories')->count() . ' kategori');
    $this->info('  [V] Metode Pembayaran      : ' . DB::table('payment_methods')->count() . ' metode');
    $this->info('  [V] Pengaturan Usaha / Logo: ' . DB::table('business_settings')->count() . ' setting');
    $this->info('  [V] Kategori Pengeluaran   : ' . DB::table('expense_categories')->count() . ' kategori');
    $this->info('  [V] Saldo Stok Semua Barang: 0.00 kg / pcs (Siap pakai dari nol)');
    $this->info('================================================================');

    return 0;
})->purpose('Hapus semua data transaksi dan reset stok ke 0, menyisakan akun user dan master barang');

