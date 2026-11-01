<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ItemCategory;
use App\Models\PaymentMethod;
use App\Models\PurchaseTransaction;
use App\Models\PurchaseTransactionItem;
use App\Models\SalarySlip;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with comprehensive, realistic operational data.
     */
    public function run(): void
    {
        // 1. Users
        $superAdmin = User::create([
            'name' => 'Bapak Hartono (Owner)',
            'email' => 'owner@kasirrongsok.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SUPER_ADMIN,
            'phone' => '081122334455',
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Dewi Safitri (Admin)',
            'email' => 'admin@kasirrongsok.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
            'phone' => '081233445566',
            'is_active' => true,
        ]);

        $kasir1 = User::create([
            'name' => 'Rian Pratama (Kasir 1)',
            'email' => 'kasir1@kasirrongsok.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_KASIR,
            'phone' => '081344556677',
            'is_active' => true,
        ]);

        $kasir2 = User::create([
            'name' => 'Agus Santoso (Kasir 2)',
            'email' => 'kasir2@kasirrongsok.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_KASIR,
            'phone' => '081455667788',
            'is_active' => true,
        ]);

        // 2. Business Settings
        $settings = [
            'company_name' => 'UD. Berkah Rongsok Makmur',
            'company_tagline' => 'Pusat Pembelian & Pengelolaan Daur Ulang Terpercaya',
            'company_phone' => '0812-3456-7890 / (021) 555-8989',
            'company_address' => 'Jl. Industri Daur Ulang No. 88, Kel. Sukamaju, Kec. Cikarang',
            'receipt_header' => 'Nota Resmi Pembelian Barang Rongsokan & Logam Bekas',
            'receipt_footer' => 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali. Terima kasih atas kerja samanya.',
            'default_printer_type' => 'thermal_58', // thermal_58, thermal_80, a4
            'currency_symbol' => 'Rp',
        ];

        foreach ($settings as $key => $val) {
            BusinessSetting::create(['key' => $key, 'value' => $val, 'group' => 'general']);
        }

        // 3. Payment Methods
        $cash = PaymentMethod::create(['name' => 'Tunai / Cash', 'code' => 'cash', 'is_active' => true]);
        $bca = PaymentMethod::create(['name' => 'Transfer Bank BCA', 'code' => 'transfer_bca', 'is_active' => true]);
        $bri = PaymentMethod::create(['name' => 'Transfer Bank BRI', 'code' => 'transfer_bri', 'is_active' => true]);
        $mandiri = PaymentMethod::create(['name' => 'Transfer Bank Mandiri', 'code' => 'transfer_mandiri', 'is_active' => true]);

        // 4. Expense Categories
        $expTransport = ExpenseCategory::create(['name' => 'Biaya Transportasi & Armada', 'description' => 'BBM truk, tol, sewa pick up']);
        $expLabor = ExpenseCategory::create(['name' => 'Biaya Bongkar Muat / Tenaga Angkut', 'description' => 'Upah harian kuli bongkar muat rongsok']);
        $expMeals = ExpenseCategory::create(['name' => 'Uang Makan Karyawan', 'description' => 'Konsumsi makan & minum operator timbang']);
        $expMaintenance = ExpenseCategory::create(['name' => 'Perawatan Kendaraan & Alat Timbang', 'description' => 'Kalibrasi timbangan duduk, servis armada']);
        $expUtility = ExpenseCategory::create(['name' => 'Listrik, Air & Operasional Kantor', 'description' => 'Token PLN gudang, PDAM, ATK kertas nota']);

        // 5. Item Categories
        $catFerrous = ItemCategory::create(['name' => 'Besi & Logam Ferrous', 'slug' => 'besi-ferrous', 'description' => 'Segala jenis besi tua, plat, siku, kawat']);
        $catNonFerrous = ItemCategory::create(['name' => 'Logam Non-Besi', 'slug' => 'logam-non-besi', 'description' => 'Tembaga, aluminium, kuningan, stainless steel']);
        $catPaper = ItemCategory::create(['name' => 'Kertas & Karton', 'slug' => 'kertas-karton', 'description' => 'Kardus boks, kertas arsip HVS, koran, majalah']);
        $catPlastic = ItemCategory::create(['name' => 'Plastik & Botol', 'slug' => 'plastik-botol', 'description' => 'Botol PET bening, jerigen HDPE, emberan campur']);
        $catBattery = ItemCategory::create(['name' => 'Aki & Baterai Bekas', 'slug' => 'aki-baterai', 'description' => 'Aki basah/kering motor dan mobil']);
        $catOther = ItemCategory::create(['name' => 'Barang Lainnya', 'slug' => 'lain-lain', 'description' => 'Kaca, karung goni, rongsok elektronik']);

        // 6. Scrap Items with initial stock
        $itemsData = [
            [
                'category_id' => $catFerrous->id,
                'code' => 'BSI-01',
                'name' => 'Besi Tua Campur',
                'unit' => 'kg',
                'buy_price' => 5000,
                'sell_price' => 6200,
                'min_stock' => 500,
                'stock' => 2450.00,
                'desc' => 'Besi campur potongan pipa, plat tipis, behel',
            ],
            [
                'category_id' => $catFerrous->id,
                'code' => 'BSI-02',
                'name' => 'Besi Super / Tebal (Padat)',
                'unit' => 'kg',
                'buy_price' => 6500,
                'sell_price' => 7800,
                'min_stock' => 500,
                'stock' => 1820.00,
                'desc' => 'Besi tebal minimal 6mm, balok H-Beam, rel, as kruk',
            ],
            [
                'category_id' => $catNonFerrous->id,
                'code' => 'TBG-01',
                'name' => 'Tembaga Merah Super (Kabel)',
                'unit' => 'kg',
                'buy_price' => 115000,
                'sell_price' => 130000,
                'min_stock' => 50,
                'stock' => 145.50,
                'desc' => 'Kupas kabel tembaga besar, bersih mengkilap tanpa lapisan',
            ],
            [
                'category_id' => $catNonFerrous->id,
                'code' => 'TBG-02',
                'name' => 'Tembaga Biasa / Bakar',
                'unit' => 'kg',
                'buy_price' => 95000,
                'sell_price' => 108000,
                'min_stock' => 50,
                'stock' => 88.00,
                'desc' => 'Tembaga rambut dinamo, bakar, ada sedikit kotoran',
            ],
            [
                'category_id' => $catNonFerrous->id,
                'code' => 'KNG-01',
                'name' => 'Kuningan Kran & Bubut',
                'unit' => 'kg',
                'buy_price' => 65000,
                'sell_price' => 75000,
                'min_stock' => 50,
                'stock' => 92.50,
                'desc' => 'Kran air kuningan, propeler, part mesin bubut kuningan',
            ],
            [
                'category_id' => $catNonFerrous->id,
                'code' => 'ALM-01',
                'name' => 'Aluminium Panci / Wajan',
                'unit' => 'kg',
                'buy_price' => 24000,
                'sell_price' => 28500,
                'min_stock' => 100,
                'stock' => 310.00,
                'desc' => 'Perabotan dapur aluminium bersih, tanpa pegangan besi',
            ],
            [
                'category_id' => $catNonFerrous->id,
                'code' => 'ALM-02',
                'name' => 'Aluminium Kusen / Profil Siku',
                'unit' => 'kg',
                'buy_price' => 28000,
                'sell_price' => 33000,
                'min_stock' => 100,
                'stock' => 450.00,
                'desc' => 'Kusen jendela, pintu etalase aluminium',
            ],
            [
                'category_id' => $catNonFerrous->id,
                'code' => 'ALM-03',
                'name' => 'Aluminium Kaleng Minuman',
                'unit' => 'kg',
                'buy_price' => 18000,
                'sell_price' => 22000,
                'min_stock' => 50,
                'stock' => 85.00,
                'desc' => 'Kaleng softdrink, sudah dipres/penyet',
            ],
            [
                'category_id' => $catPaper->id,
                'code' => 'KDS-01',
                'name' => 'Kardus Boks Cokelat (Karton)',
                'unit' => 'kg',
                'buy_price' => 1800,
                'sell_price' => 2400,
                'min_stock' => 1000,
                'stock' => 4200.00,
                'desc' => 'Kardus rokok, indomie, elektronik kering & bersih',
            ],
            [
                'category_id' => $catPaper->id,
                'code' => 'KRT-01',
                'name' => 'Kertas Koran / Majalah',
                'unit' => 'kg',
                'buy_price' => 1500,
                'sell_price' => 2000,
                'min_stock' => 300,
                'stock' => 650.00,
                'desc' => 'Koran bekas kering, majalah, kertas buram',
            ],
            [
                'category_id' => $catPaper->id,
                'code' => 'KRT-02',
                'name' => 'Kertas Putih HVS / Arsip Kantor',
                'unit' => 'kg',
                'buy_price' => 2500,
                'sell_price' => 3200,
                'min_stock' => 500,
                'stock' => 1250.00,
                'desc' => 'Kertas putih print tanpa map/plastik, kertas buku tulis',
            ],
            [
                'category_id' => $catPlastic->id,
                'code' => 'PLS-01',
                'name' => 'Botol Plastik PET Bening (Aqua)',
                'unit' => 'kg',
                'buy_price' => 4500,
                'sell_price' => 5800,
                'min_stock' => 300,
                'stock' => 1100.00,
                'desc' => 'Botol air mineral bening, bersih tutup dan label dilepas',
            ],
            [
                'category_id' => $catPlastic->id,
                'code' => 'PLS-02',
                'name' => 'Plastik Sablon / Campuran',
                'unit' => 'kg',
                'buy_price' => 2200,
                'sell_price' => 3000,
                'min_stock' => 200,
                'stock' => 320.00,
                'desc' => 'Plastik warna-warni, botol teh pucuk, botol berwarna',
            ],
            [
                'category_id' => $catPlastic->id,
                'code' => 'PLS-03',
                'name' => 'Jerigen & Emberan HDPE',
                'unit' => 'kg',
                'buy_price' => 3500,
                'sell_price' => 4500,
                'min_stock' => 200,
                'stock' => 480.00,
                'desc' => 'Jerigen minyak, ember cat pecah, bak mandi plastik',
            ],
            [
                'category_id' => $catBattery->id,
                'code' => 'AKI-01',
                'name' => 'Aki Bekas Mobil / Motor',
                'unit' => 'kg',
                'buy_price' => 14000,
                'sell_price' => 17500,
                'min_stock' => 100,
                'stock' => 260.00,
                'desc' => 'Aki timbal bekas basah & kering dengan air/tanpa air',
            ],
            [
                'category_id' => $catNonFerrous->id,
                'code' => 'SS-01',
                'name' => 'Stainless Steel 304/Campur',
                'unit' => 'kg',
                'buy_price' => 16000,
                'sell_price' => 20000,
                'min_stock' => 100,
                'stock' => 210.00,
                'desc' => 'Pipa stainless, bak cuci piring (sink), panci stainless',
            ],
        ];

        $createdScrapItems = [];
        foreach ($itemsData as $row) {
            $item = ScrapItem::create([
                'category_id' => $row['category_id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'unit' => $row['unit'],
                'buy_price_per_kg' => $row['buy_price'],
                'sell_price_per_kg' => $row['sell_price'],
                'min_stock_alert' => $row['min_stock'],
                'description' => $row['desc'],
                'is_active' => true,
            ]);

            // Create initial Stock Balance
            StockBalance::create([
                'scrap_item_id' => $item->id,
                'current_stock' => $row['stock'],
                'total_in' => $row['stock'] + 200,
                'total_out' => 200,
                'average_buy_price' => $row['buy_price'],
            ]);

            // Initial movement
            StockMovement::create([
                'scrap_item_id' => $item->id,
                'user_id' => $superAdmin->id,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'weight_qty' => $row['stock'],
                'unit_price' => $row['buy_price'],
                'balance_after' => $row['stock'],
                'notes' => 'Stok awal sistem pembukaan kasir rongsok',
            ]);

            $createdScrapItems[$row['code']] = $item;
        }

        // 7. Suppliers
        $sup1 = Supplier::create([
            'code' => 'SUP-001',
            'name' => 'Pak Slamet Riyadi',
            'phone' => '0857-1122-3344',
            'address' => 'Kampung Seng, Cikarang Timur (Pengepul Gerobak)',
            'notes' => 'Rutin antar kardus dan botol plastik tiap sore',
            'is_active' => true,
        ]);

        $sup2 = Supplier::create([
            'code' => 'SUP-002',
            'name' => 'Mas Budi Rongsokan',
            'phone' => '0878-9988-7766',
            'address' => 'Jl. Kali Baru RT 03/05 (Armada Tossa)',
            'notes' => 'Pengepul besi tua bengkel & tembaga',
            'is_active' => true,
        ]);

        $sup3 = Supplier::create([
            'code' => 'SUP-003',
            'name' => 'Toko Logam Sinar Jaya',
            'phone' => '0812-4455-6677',
            'address' => 'Kawasan Industri Jababeka (Limbah Pabrik)',
            'notes' => 'Potongan drum, plat sisa stamping pres',
            'is_active' => true,
        ]);

        $sup4 = Supplier::create([
            'code' => 'SUP-004',
            'name' => 'Haji Mansur',
            'phone' => '0813-7788-9900',
            'address' => 'Pasar Lemahabang (Kardus Toko)',
            'notes' => 'Kardus boks bersih partai sedang',
            'is_active' => true,
        ]);

        // 8. Sample Purchase Transactions (Hari Ini & Kemarin)
        // Tx 1: Pembelian Hari Ini oleh Kasir 1
        $tx1Date = Carbon::now()->subHours(2);
        $tx1 = PurchaseTransaction::create([
            'invoice_number' => 'KB-' . $tx1Date->format('ymd') . '-0001',
            'supplier_id' => $sup1->id,
            'supplier_name' => $sup1->name,
            'supplier_phone' => $sup1->phone,
            'user_id' => $kasir1->id,
            'payment_method_id' => $cash->id,
            'transaction_date' => $tx1Date,
            'total_gross_weight' => 215.00,
            'total_tare_weight' => 7.00,
            'total_net_weight' => 208.00,
            'subtotal_amount' => 610000.00,
            'discount_amount' => 0.00,
            'rounding_amount' => 0.00,
            'final_amount' => 610000.00,
            'payment_status' => 'paid',
            'status' => 'completed',
            'notes' => 'Kardus dan botol bersih dari langganan',
        ]);

        PurchaseTransactionItem::create([
            'purchase_transaction_id' => $tx1->id,
            'scrap_item_id' => $createdScrapItems['KDS-01']->id,
            'item_name_snapshot' => $createdScrapItems['KDS-01']->name,
            'item_code_snapshot' => $createdScrapItems['KDS-01']->code,
            'gross_weight' => 155.00,
            'tare_weight' => 5.00,
            'net_weight' => 150.00,
            'price_per_kg' => 1800,
            'subtotal' => 270000.00,
            'notes' => 'Kardus ikat kencang',
        ]);

        PurchaseTransactionItem::create([
            'purchase_transaction_id' => $tx1->id,
            'scrap_item_id' => $createdScrapItems['PLS-01']->id,
            'item_name_snapshot' => $createdScrapItems['PLS-01']->name,
            'item_code_snapshot' => $createdScrapItems['PLS-01']->code,
            'gross_weight' => 60.00,
            'tare_weight' => 2.00,
            'net_weight' => 58.00,
            'price_per_kg' => 4500,
            'subtotal' => 261000.00,
            'notes' => 'Karung besar',
        ]);

        PurchaseTransactionItem::create([
            'purchase_transaction_id' => $tx1->id,
            'scrap_item_id' => $createdScrapItems['ALM-03']->id,
            'item_name_snapshot' => $createdScrapItems['ALM-03']->name,
            'item_code_snapshot' => $createdScrapItems['ALM-03']->code,
            'gross_weight' => 4.50,
            'tare_weight' => 0.50,
            'net_weight' => 4.00,
            'price_per_kg' => 19750, // negosiasi
            'subtotal' => 79000.00,
            'notes' => 'Kaleng minuman bersih',
        ]);

        // Tx 2: Pembelian Logam Berat dari Mas Budi
        $tx2Date = Carbon::now()->subHours(4);
        $tx2 = PurchaseTransaction::create([
            'invoice_number' => 'KB-' . $tx2Date->format('ymd') . '-0002',
            'supplier_id' => $sup2->id,
            'supplier_name' => $sup2->name,
            'supplier_phone' => $sup2->phone,
            'user_id' => $kasir1->id,
            'payment_method_id' => $cash->id,
            'transaction_date' => $tx2Date,
            'total_gross_weight' => 365.00,
            'total_tare_weight' => 15.00,
            'total_net_weight' => 350.00,
            'subtotal_amount' => 2925000.00,
            'discount_amount' => 0.00,
            'final_amount' => 2925000.00,
            'payment_status' => 'paid',
            'status' => 'completed',
            'notes' => 'Besi tua dan tembaga kabel',
        ]);

        PurchaseTransactionItem::create([
            'purchase_transaction_id' => $tx2->id,
            'scrap_item_id' => $createdScrapItems['BSI-01']->id,
            'item_name_snapshot' => $createdScrapItems['BSI-01']->name,
            'item_code_snapshot' => $createdScrapItems['BSI-01']->code,
            'gross_weight' => 345.00,
            'tare_weight' => 15.00,
            'net_weight' => 330.00,
            'price_per_kg' => 5000,
            'subtotal' => 1650000.00,
            'notes' => 'Besi campur potongan bengkel',
        ]);

        PurchaseTransactionItem::create([
            'purchase_transaction_id' => $tx2->id,
            'scrap_item_id' => $createdScrapItems['TBG-01']->id,
            'item_name_snapshot' => $createdScrapItems['TBG-01']->name,
            'item_code_snapshot' => $createdScrapItems['TBG-01']->code,
            'gross_weight' => 11.20,
            'tare_weight' => 0.20,
            'net_weight' => 11.00,
            'price_per_kg' => 115000,
            'subtotal' => 1265000.00,
            'notes' => 'Tembaga merah super kabel PLN',
        ]);

        // Tx 3: Pembelian Kemarin oleh Kasir 2
        $tx3Date = Carbon::yesterday()->setHour(14);
        $tx3 = PurchaseTransaction::create([
            'invoice_number' => 'KB-' . $tx3Date->format('ymd') . '-0001',
            'supplier_id' => null,
            'supplier_name' => 'Pak Joko (Warga Umum)',
            'supplier_phone' => '0899-2345-6789',
            'user_id' => $kasir2->id,
            'payment_method_id' => $cash->id,
            'transaction_date' => $tx3Date,
            'total_gross_weight' => 84.00,
            'total_tare_weight' => 2.00,
            'total_net_weight' => 82.00,
            'subtotal_amount' => 746000.00,
            'discount_amount' => 0.00,
            'final_amount' => 746000.00,
            'payment_status' => 'paid',
            'status' => 'completed',
            'notes' => 'Bongkaran gudang rumah',
        ]);

        PurchaseTransactionItem::create([
            'purchase_transaction_id' => $tx3->id,
            'scrap_item_id' => $createdScrapItems['AKI-01']->id,
            'item_name_snapshot' => $createdScrapItems['AKI-01']->name,
            'item_code_snapshot' => $createdScrapItems['AKI-01']->code,
            'gross_weight' => 35.00,
            'tare_weight' => 0.00,
            'net_weight' => 35.00,
            'price_per_kg' => 14000,
            'subtotal' => 490000.00,
        ]);

        PurchaseTransactionItem::create([
            'purchase_transaction_id' => $tx3->id,
            'scrap_item_id' => $createdScrapItems['ALM-01']->id,
            'item_name_snapshot' => $createdScrapItems['ALM-01']->name,
            'item_code_snapshot' => $createdScrapItems['ALM-01']->code,
            'gross_weight' => 11.00,
            'tare_weight' => 0.30,
            'net_weight' => 10.70,
            'price_per_kg' => 24000,
            'subtotal' => 256800.00,
        ]);

        // 9. Sample Sales Transactions (Penjualan Partai Besar ke Pabrik Peleburan)
        $saleDate = Carbon::yesterday()->setHour(16);
        $sale1 = SalesTransaction::create([
            'invoice_number' => 'PJ-' . $saleDate->format('ymd') . '-0001',
            'buyer_name' => 'PT. Krakatau Scrap Metal',
            'buyer_phone' => '021-8989-1234',
            'buyer_address' => 'Kawasan Industri Cilegon, Banten',
            'user_id' => $admin->id,
            'payment_method_id' => $bca->id,
            'transaction_date' => $saleDate,
            'total_weight' => 1500.00,
            'subtotal_amount' => 9300000.00,
            'discount_amount' => 0.00,
            'final_amount' => 9300000.00,
            'estimated_cogs' => 7500000.00,
            'estimated_profit' => 1800000.00,
            'payment_status' => 'paid',
            'status' => 'completed',
            'notes' => 'Pengiriman armada truk fuso no. pol B 9812 UI',
        ]);

        SalesTransactionItem::create([
            'sales_transaction_id' => $sale1->id,
            'scrap_item_id' => $createdScrapItems['BSI-01']->id,
            'item_name_snapshot' => $createdScrapItems['BSI-01']->name,
            'item_code_snapshot' => $createdScrapItems['BSI-01']->code,
            'weight' => 1500.00,
            'sell_price_per_kg' => 6200.00,
            'buy_price_snapshot' => 5000.00,
            'subtotal' => 9300000.00,
            'cogs_subtotal' => 7500000.00,
            'profit_subtotal' => 1800000.00,
        ]);

        // 10. Sample Expenses
        Expense::create([
            'expense_number' => 'EXP-' . Carbon::today()->format('ymd') . '-0001',
            'expense_category_id' => $expTransport->id,
            'user_id' => $admin->id,
            'payment_method_id' => $cash->id,
            'expense_date' => Carbon::today()->toDateString(),
            'amount' => 180000.00,
            'description' => 'Solar truk pick up L300 angkut kardus',
            'recipient' => 'SPBU Cikarang Barat',
        ]);

        Expense::create([
            'expense_number' => 'EXP-' . Carbon::today()->format('ymd') . '-0002',
            'expense_category_id' => $expMeals->id,
            'user_id' => $kasir1->id,
            'payment_method_id' => $cash->id,
            'expense_date' => Carbon::today()->toDateString(),
            'amount' => 75000.00,
            'description' => 'Makan siang 3 orang pekerja timbang rongsok',
            'recipient' => 'Warung Nasi Padang Sejahtera',
        ]);

        Expense::create([
            'expense_number' => 'EXP-' . Carbon::yesterday()->format('ymd') . '-0001',
            'expense_category_id' => $expLabor->id,
            'user_id' => $admin->id,
            'payment_method_id' => $cash->id,
            'expense_date' => Carbon::yesterday()->toDateString(),
            'amount' => 250000.00,
            'description' => 'Upah borongan kuli muat truk besi ke Cilegon',
            'recipient' => 'Pak Joko & Team Bongkar',
        ]);

        // 11. Sample Pinjaman Modal / Hutang Piutang
        $debt1 = Debt::create([
            'debt_number' => 'BON-' . Carbon::today()->subDays(5)->format('ymd') . '-001',
            'supplier_id' => $sup1->id,
            'borrower_name' => $sup1->name,
            'borrower_phone' => $sup1->phone,
            'borrower_type' => 'supplier',
            'date' => Carbon::today()->subDays(5),
            'due_date' => Carbon::today()->addDays(25),
            'initial_amount' => 1500000.00,
            'paid_amount' => 500000.00,
            'remaining_amount' => 1000000.00,
            'status' => 'partially_paid',
            'notes' => 'Pinjaman modal keliling beli rongsokan ke perumahan',
            'user_id' => $admin->id,
        ]);

        DebtPayment::create([
            'payment_number' => 'BAYAR-' . Carbon::today()->subDays(2)->format('ymd') . '-001',
            'debt_id' => $debt1->id,
            'payment_date' => Carbon::today()->subDays(2),
            'amount' => 500000.00,
            'remaining_before' => 1500000.00,
            'remaining_after' => 1000000.00,
            'payment_method_id' => $cash->id,
            'user_id' => $kasir1->id,
            'notes' => 'Setor cicilan tunai ke kantor kasir',
        ]);

        $debt2 = Debt::create([
            'debt_number' => 'BON-' . Carbon::today()->subDays(3)->format('ymd') . '-001',
            'supplier_id' => $sup2->id,
            'borrower_name' => $sup2->name,
            'borrower_phone' => $sup2->phone,
            'borrower_type' => 'supplier',
            'date' => Carbon::today()->subDays(3),
            'due_date' => Carbon::today()->addDays(27),
            'initial_amount' => 2000000.00,
            'paid_amount' => 0.00,
            'remaining_amount' => 2000000.00,
            'status' => 'unpaid',
            'notes' => 'Pinjaman modal borongan besi bongkaran pabrik',
            'user_id' => $superAdmin->id,
        ]);

        $debt3 = Debt::create([
            'debt_number' => 'BON-' . Carbon::today()->subDays(10)->format('ymd') . '-001',
            'borrower_name' => $kasir1->name,
            'borrower_phone' => $kasir1->phone,
            'borrower_type' => 'karyawan',
            'date' => Carbon::today()->subDays(10),
            'due_date' => Carbon::today()->endOfMonth(),
            'initial_amount' => 500000.00,
            'paid_amount' => 500000.00,
            'remaining_amount' => 0.00,
            'status' => 'paid',
            'notes' => 'Kasbon keperluan mendadak anak sekolah (Lunas dipotong slip gaji)',
            'user_id' => $admin->id,
        ]);

        DebtPayment::create([
            'payment_number' => 'BAYAR-' . Carbon::today()->subDays(1)->format('ymd') . '-001',
            'debt_id' => $debt3->id,
            'payment_date' => Carbon::today()->subDays(1),
            'amount' => 500000.00,
            'remaining_before' => 500000.00,
            'remaining_after' => 0.00,
            'payment_method_id' => $cash->id,
            'user_id' => $admin->id,
            'notes' => 'Pelunasan potong gaji bulanan',
        ]);

        // 12. Sample Salary Slips
        SalarySlip::create([
            'slip_number' => 'SLIP-' . Carbon::today()->format('ymd') . '-001',
            'employee_name' => $kasir1->name,
            'user_id' => $kasir1->id,
            'created_by' => $admin->id,
            'payment_method_id' => $cash->id,
            'period_month' => Carbon::now()->isoFormat('MMMM Y'),
            'payment_date' => Carbon::today(),
            'basic_salary' => 3500000.00,
            'allowance_amount' => 500000.00,
            'bonus_amount' => 300000.00,
            'total_income' => 4300000.00,
            'debt_deduction' => 500000.00,
            'other_deduction' => 0.00,
            'total_deduction' => 500000.00,
            'net_salary' => 3800000.00,
            'status' => 'paid',
            'notes' => 'Gaji operator kasir & timbangan. Bonus target volume masuk 20 ton.',
        ]);

        SalarySlip::create([
            'slip_number' => 'SLIP-' . Carbon::today()->format('ymd') . '-002',
            'employee_name' => $kasir2->name,
            'user_id' => $kasir2->id,
            'created_by' => $admin->id,
            'payment_method_id' => $bca->id,
            'period_month' => Carbon::now()->isoFormat('MMMM Y'),
            'payment_date' => Carbon::today(),
            'basic_salary' => 3300000.00,
            'allowance_amount' => 450000.00,
            'bonus_amount' => 150000.00,
            'total_income' => 3900000.00,
            'debt_deduction' => 0.00,
            'other_deduction' => 50000.00,
            'total_deduction' => 50000.00,
            'net_salary' => 3850000.00,
            'status' => 'paid',
            'notes' => 'Transfer payroll bank BCA. Potongan iuran paguyuban Rp 50.000.',
        ]);

        // 13. Sample Delivery Orders (Surat Jalan Pengiriman ke Pabrik)
        $do1 = DeliveryOrder::create([
            'delivery_number' => 'SJ-' . Carbon::today()->format('ymd') . '-001',
            'sales_transaction_id' => $sale1->id,
            'delivery_date' => Carbon::today(),
            'recipient_name' => 'PT. Krakatau Baja Peleburan Mandiri',
            'recipient_address' => 'Kawasan Industri Krakatau Steel Kav. 42, Cilegon, Banten',
            'recipient_phone' => '0254-391200 / 0812-8899-7711',
            'driver_name' => 'Pak Wawan Gunawan',
            'driver_phone' => '0877-6655-4433',
            'vehicle_number' => 'B 9812 UI',
            'vehicle_type' => 'Truk Fuso Long Chasis',
            'total_weight' => 1500.00,
            'total_packages' => 0,
            'status' => 'sent',
            'notes' => 'Muatan besi super padat, ditutup terpal ganda, segel gudang #0981.',
            'user_id' => $admin->id,
        ]);

        DeliveryOrderItem::create([
            'delivery_order_id' => $do1->id,
            'scrap_item_id' => $createdScrapItems['BSI-01']->id,
            'item_name' => 'Besi Tua Campur & Balok Pres',
            'weight' => 1500.00,
            'package_qty' => 0,
            'notes' => 'Segel muatan No. 0981 - 0982',
        ]);
    }
}
