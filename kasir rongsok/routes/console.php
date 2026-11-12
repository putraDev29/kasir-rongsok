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

    // 2. Surat Jalan Pengiriman & Sopir Armada
    DB::table('delivery_order_items')->truncate();
    DB::table('delivery_orders')->truncate();
    DB::table('drivers')->truncate();

    // 3. Hutang Piutang, Biaya Operasional, & Slip Gaji
    DB::table('debt_payments')->truncate();
    DB::table('debts')->truncate();
    DB::table('expenses')->truncate();
    DB::table('salary_slips')->truncate();

    // 4. Reward & Pemasok Dummy
    DB::table('supplier_rewards')->truncate();
    DB::table('suppliers')->truncate();

    // 5. Mutasi Stok Ledger, Log Aktivitas, Sesi & Riwayat Harga
    DB::table('stock_movements')->truncate();
    DB::table('item_price_stocks')->truncate();
    DB::table('scrap_item_price_histories')->truncate();
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

Artisan::command('app:generate-icon', function () {
    $this->info('Menghasilkan file icon ICO Windows secara dinamis dari logo usaha...');
    $success = \App\Services\IconGeneratorService::generateCurrentOrFallbackIco();
    if ($success) {
        $path = \App\Services\IconGeneratorService::resolveActiveIconPath();
        $this->info("[V] Icon berhasil diperbarui di: {$path} (Ukuran: " . filesize($path) . " bytes)");
        return 0;
    } else {
        $this->error('[X] Gagal menghasilkan icon.');
        return 1;
    }
})->purpose('Generate icon Windows ICO secara dinamis untuk pintasan desktop dan favicon');

Artisan::command('app:ensure-system-ready', function () {
    $this->info('============================================================');
    $this->info('  MEMERIKSA KESIAPAN SISTEM KASIR RONGSOK (AUTO-INITIALIZE)');
    $this->info('============================================================');

    $dbConnection = config('database.default', 'mysql');
    $dbHost = config("database.connections.{$dbConnection}.host", '127.0.0.1');
    $dbPort = config("database.connections.{$dbConnection}.port", '3306');
    $dbName = config("database.connections.{$dbConnection}.database", 'kasir_rongsok_2');
    $dbUser = config("database.connections.{$dbConnection}.username", 'root');
    $dbPass = config("database.connections.{$dbConnection}.password", '');

    // 1. Buat Database MySQL jika belum ada di PC / Laptop ini
    if ($dbConnection === 'mysql') {
        try {
            $pdo = new \PDO("mysql:host={$dbHost};port={$dbPort}", $dbUser, $dbPass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $this->info("[V] Database '{$dbName}' siap di MySQL ({$dbHost}:{$dbPort}).");
        } catch (\Throwable $e) {
            $this->warn("[!] Peringatan koneksi server MySQL awal: " . $e->getMessage());
        }
    }

    // 2. Jalankan Migrasi Database jika tabel belum ada atau ada migrasi pending
    try {
        $this->info('Memeriksa migrasi skema database...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info('[V] Migrasi skema tabel database siap.');
    } catch (\Throwable $e) {
        $this->error('[X] Gagal migrasi: ' . $e->getMessage());
    }

    // 3. Seed Akun Default & Master Barang jika tabel user kosong
    try {
        $userCount = \Illuminate\Support\Facades\Schema::hasTable('users') 
            ? \Illuminate\Support\Facades\DB::table('users')->count() 
            : 0;

        if ($userCount === 0) {
            $this->info('Mengisi data akun awal (Owner, Admin, Kasir) & master barang rongsok...');
            Artisan::call('db:seed', ['--force' => true]);
            $this->info('[V] Seeder data bawaan berhasil diselesaikan.');
        } else {
            $this->info("[V] Database sudah berisi {$userCount} pengguna terdaftar.");
        }
    } catch (\Throwable $e) {
        $this->warn('[!] Gagal seeding data awal: ' . $e->getMessage());
    }

    // 4. Sinkronisasi Logo, Tanda Tangan, & Stempel dari direktori uploads pada disk
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('business_settings')) {
            \App\Models\BusinessSetting::syncUploadedMediaFromDisk();
            $this->info('[V] Sinkronisasi media usaha (logo, stempel, tanda tangan) selesai.');
        }
    } catch (\Throwable $e) {
        $this->warn('[!] Gagal sinkronisasi media usaha: ' . $e->getMessage());
    }

    // 5. Generate Icon ICO Aktif & Sinkronkan ke Desktop Shortcut
    try {
        $iconUpdated = \App\Services\IconGeneratorService::generateCurrentOrFallbackIco();
        if ($iconUpdated) {
            $icoPath = \App\Services\IconGeneratorService::resolveActiveIconPath();
            $this->info("[V] Icon aplikasi berhasil diperbarui dari logo usaha: {$icoPath}");
        }
    } catch (\Throwable $e) {
        $this->warn('[!] Peringatan generate icon: ' . $e->getMessage());
    }

    // 6. Buat / Perbarui Pintasan Desktop di PC / Laptop ini
    try {
        $projectRoot = \App\Services\IconGeneratorService::resolveProjectRoot();
        $psScript = $projectRoot . DIRECTORY_SEPARATOR . 'setup_shortcuts.ps1';
        if (file_exists($psScript)) {
            @pclose(@popen("start /B powershell.exe -NoProfile -ExecutionPolicy Bypass -File \"" . $psScript . "\"", "r"));
            $this->info('[V] Pintasan desktop & folder proyek berhasil diperbarui.');
        }
    } catch (\Throwable $e) {
        // Abaikan jika PowerShell tidak bisa dipanggil
    }

    $this->info('============================================================');
    $this->info('  KASIR RONGSOK SYSTEM SUDAH SIAP BERJALAN!');
    $this->info('============================================================');
    return 0;
})->purpose('Memastikan database MySQL, migrasi, data default, dan icon usaha siap pakai di PC baru');



