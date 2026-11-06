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
        $superAdmin = User::firstOrCreate(['email' => 'owner@kasirrongsok.com'], [
            'name' => 'Bapak Hartono (Owner)',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SUPER_ADMIN,
            'phone' => '081122334455',
            'bank_name' => 'BCA',
            'bank_account_number' => '8830123456',
            'bank_account_name' => 'Bapak Hartono',
            'is_active' => true,
        ]);
        if (empty($superAdmin->bank_account_number)) {
            $superAdmin->update([
                'bank_name' => 'BCA',
                'bank_account_number' => '8830123456',
                'bank_account_name' => 'Bapak Hartono',
            ]);
        }

        $admin = User::firstOrCreate(['email' => 'admin@kasirrongsok.com'], [
            'name' => 'Dewi Safitri (Admin)',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
            'phone' => '081233445566',
            'bank_name' => 'Mandiri',
            'bank_account_number' => '1370098765432',
            'bank_account_name' => 'Dewi Safitri',
            'is_active' => true,
        ]);
        if (empty($admin->bank_account_number)) {
            $admin->update([
                'bank_name' => 'Mandiri',
                'bank_account_number' => '1370098765432',
                'bank_account_name' => 'Dewi Safitri',
            ]);
        }

        $kasir1 = User::firstOrCreate(['email' => 'kasir1@kasirrongsok.com'], [
            'name' => 'Rian Pratama (Kasir 1)',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_KASIR,
            'phone' => '081344556677',
            'bank_name' => 'BRI',
            'bank_account_number' => '012301054321508',
            'bank_account_name' => 'Rian Pratama',
            'is_active' => true,
        ]);
        if (empty($kasir1->bank_account_number)) {
            $kasir1->update([
                'bank_name' => 'BRI',
                'bank_account_number' => '012301054321508',
                'bank_account_name' => 'Rian Pratama',
            ]);
        }

        $kasir2 = User::firstOrCreate(['email' => 'kasir2@kasirrongsok.com'], [
            'name' => 'Agus Santoso (Kasir 2)',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_KASIR,
            'phone' => '081455667788',
            'bank_name' => 'BNI',
            'bank_account_number' => '0876543210',
            'bank_account_name' => 'Agus Santoso',
            'is_active' => true,
        ]);
        if (empty($kasir2->bank_account_number)) {
            $kasir2->update([
                'bank_name' => 'BNI',
                'bank_account_number' => '0876543210',
                'bank_account_name' => 'Agus Santoso',
            ]);
        }

        // 2. Business Settings
        $settings = [
            'company_name' => 'UD. Berkah Rongsok Makmur',
            'company_tagline' => 'Pusat Pembelian & Pengelolaan Daur Ulang Terpercaya',
            'company_phone' => '0812-3456-7890 / (021) 555-8989',
            'company_address' => 'Jl. Industri Daur Ulang No. 88, Kel. Sukamaju, Kec. Cikarang',
            'receipt_header' => 'Nota Resmi Pembelian Material Scrap & Daur Ulang',
            'receipt_footer' => 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali. Terima kasih atas kerja samanya.',
            'default_printer_type' => 'thermal_58', // thermal_58, thermal_80, a4
            'currency_symbol' => 'Rp',
            'delivery_order_notes' => 'Surat jalan ini dibuat 2 (dua) rangkap: Lembar 1 dipegang oleh gudang dan Lembar 2 dipegang oleh supir. Dokumen sah penyerahan material scrap & daur ulang.',
            'company_bank_name' => 'BCA',
            'company_bank_account_number' => '8830123456',
            'company_bank_account_name' => 'PT PENATA PRIMA JAYA',
        ];

        foreach ($settings as $key => $val) {
            BusinessSetting::updateOrCreate(['key' => $key], ['value' => $val, 'group' => 'general']);
        }

        // 3. Payment Methods
        $cash = PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Tunai / Cash', 'is_active' => true]);
        $bca = PaymentMethod::updateOrCreate(['code' => 'transfer_bca'], [
            'name' => 'Transfer Bank BCA',
            'bank_name' => 'BCA',
            'account_number' => '8830123456',
            'account_holder' => 'PT PENATA PRIMA JAYA',
            'is_active' => true
        ]);
        $bri = PaymentMethod::updateOrCreate(['code' => 'transfer_bri'], [
            'name' => 'Transfer Bank BRI',
            'bank_name' => 'BRI',
            'account_number' => '012301000123508',
            'account_holder' => 'PT PENATA PRIMA JAYA',
            'is_active' => true
        ]);
        $mandiri = PaymentMethod::updateOrCreate(['code' => 'transfer_mandiri'], [
            'name' => 'Transfer Bank Mandiri',
            'bank_name' => 'Mandiri',
            'account_number' => '1370012345678',
            'account_holder' => 'PT PENATA PRIMA JAYA',
            'is_active' => true
        ]);
        $bni = PaymentMethod::updateOrCreate(['code' => 'transfer_bni'], [
            'name' => 'Transfer Bank BNI',
            'bank_name' => 'BNI',
            'account_number' => '0987654321',
            'account_holder' => 'PT PENATA PRIMA JAYA',
            'is_active' => true
        ]);
        $qris = PaymentMethod::firstOrCreate(['code' => 'qris'], ['name' => 'QRIS / Digital', 'is_active' => true]);

        // 4. Expense Categories
        $expTransport = ExpenseCategory::firstOrCreate(['name' => 'Biaya Transportasi & Armada'], ['description' => 'BBM truk, tol, sewa pick up']);
        $expLabor = ExpenseCategory::firstOrCreate(['name' => 'Biaya Bongkar Muat / Tenaga Angkut'], ['description' => 'Upah harian kuli bongkar muat rongsok']);
        $expMeals = ExpenseCategory::firstOrCreate(['name' => 'Uang Makan Karyawan'], ['description' => 'Konsumsi makan & minum operator timbang']);
        $expMaintenance = ExpenseCategory::firstOrCreate(['name' => 'Perawatan Kendaraan & Alat Timbang'], ['description' => 'Kalibrasi timbangan duduk, servis armada']);
        $expUtility = ExpenseCategory::firstOrCreate(['name' => 'Listrik, Air & Operasional Kantor'], ['description' => 'Token PLN gudang, PDAM, ATK kertas nota']);

        // 5. Item Categories
        $catTembagaKuningan = ItemCategory::firstOrCreate(['slug' => 'tembaga-kuningan'], ['name' => 'Tembaga & Kuningan', 'description' => 'Tembaga murni, dangdang, kuningan bersih, kuningan radiator, dan gram kuningan', 'is_active' => true]);
        $catAlumunium = ItemCategory::firstOrCreate(['slug' => 'alumunium'], ['name' => 'Alumunium', 'description' => 'Aluminium siku/kawat, blok mesin, panci/plat, kuali, babet, prodo, eleman, AC, dan part aluminium', 'is_active' => true]);
        $catBesi = ItemCategory::firstOrCreate(['slug' => 'besi'], ['name' => 'Besi', 'description' => 'Besi tua balok tebal (BTG), besi tipis behel (BTK), seng pasir, dan spandek tipis', 'is_active' => true]);
        $catTimahAkiStanlis = ItemCategory::firstOrCreate(['slug' => 'timah-aki-stanlis'], ['name' => 'Timah, Aki & Stainless', 'description' => 'Timah batangan/pancing, aki basah/kering motor & mobil bekas, dan stainless steel', 'is_active' => true]);
        $catPlastic = ItemCategory::firstOrCreate(['slug' => 'plastik'], ['name' => 'Plastik', 'description' => 'Botol Aqua susun bening, galon bekas, emberan warna campur, dan plastik kerasan/impek', 'is_active' => true]);
        $catPaper = ItemCategory::firstOrCreate(['slug' => 'kertas-karton'], ['name' => 'Kertas & Karton', 'description' => 'Kertas putih HVS/swl arsip, kardus boks kering & lembap, buku cetak, kertas buram, dan duplek kones', 'is_active' => true]);
        $catGembel = ItemCategory::firstOrCreate(['slug' => 'kain-gembel'], ['name' => 'Kain Majun & Gembel', 'description' => 'Kain majun perca katun gembel kasar dan gembel alus', 'is_active' => true]);

        // 6. Scrap Items with initial stock (35 Items Sesuai Standar Lapak / Depo)
        $itemsData = [
            // TEMBAGA & KUNINGAN (5 items)
            [
                'category_id' => $catTembagaKuningan->id,
                'code' => 'TBG-01',
                'name' => 'Tembaga',
                'unit' => 'kg',
                'buy_price' => 195000,
                'sell_price' => 215000,
                'min_stock' => 50,
                'stock' => 120.00,
                'desc' => 'Tembaga murni / kabel tembaga kupas merah bersih',
                'is_active' => true,
            ],
            [
                'category_id' => $catTembagaKuningan->id,
                'code' => 'TBG-02',
                'name' => 'Dangdang',
                'unit' => 'kg',
                'buy_price' => 170000,
                'sell_price' => 188000,
                'min_stock' => 30,
                'stock' => 50.00,
                'desc' => 'Tembaga dandang / perkakas masak tembaga kuno',
                'is_active' => true,
            ],
            [
                'category_id' => $catTembagaKuningan->id,
                'code' => 'KNG-01',
                'name' => 'KN bersih',
                'unit' => 'kg',
                'buy_price' => 130000,
                'sell_price' => 145000,
                'min_stock' => 40,
                'stock' => 80.00,
                'desc' => 'Kuningan bersih tanpa besi/kotoran',
                'is_active' => true,
            ],
            [
                'category_id' => $catTembagaKuningan->id,
                'code' => 'KNG-02',
                'name' => 'KN Radiator',
                'unit' => 'kg',
                'buy_price' => 110000,
                'sell_price' => 125000,
                'min_stock' => 40,
                'stock' => 60.00,
                'desc' => 'Kuningan radiator mesin & kendaraan',
                'is_active' => true,
            ],
            [
                'category_id' => $catTembagaKuningan->id,
                'code' => 'KNG-03',
                'name' => 'Gram kn',
                'unit' => 'kg',
                'buy_price' => 92000,
                'sell_price' => 105000,
                'min_stock' => 30,
                'stock' => 45.00,
                'desc' => 'Gram / serbuk bubut kuningan kering',
                'is_active' => true,
            ],

            // ALUMUNIUM (11 items)
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-01',
                'name' => 'AL-Siku/kawat',
                'unit' => 'kg',
                'buy_price' => 38000,
                'sell_price' => 43000,
                'min_stock' => 100,
                'stock' => 180.00,
                'desc' => 'Aluminium profil siku kusen, kawat aluminium bersih',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-02',
                'name' => 'AL-Blok',
                'unit' => 'kg',
                'buy_price' => 36500,
                'sell_price' => 41500,
                'min_stock' => 100,
                'stock' => 220.00,
                'desc' => 'Aluminium blok mesin, pelek velg motor/mobil',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-03',
                'name' => 'AL-Panci/plat',
                'unit' => 'kg',
                'buy_price' => 35500,
                'sell_price' => 40500,
                'min_stock' => 100,
                'stock' => 250.00,
                'desc' => 'Aluminium panci, perabot rumah tangga, plat lembaran',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-04',
                'name' => 'AL-Kere/Krai',
                'unit' => 'kg',
                'buy_price' => 31000,
                'sell_price' => 35500,
                'min_stock' => 50,
                'stock' => 85.00,
                'desc' => 'Aluminium kere / krai tirai jendela',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-05',
                'name' => 'AL-Kuali',
                'unit' => 'kg',
                'buy_price' => 31000,
                'sell_price' => 35500,
                'min_stock' => 50,
                'stock' => 95.00,
                'desc' => 'Aluminium kuali / wajan cor tebal',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-06',
                'name' => 'AL-Babet',
                'unit' => 'kg',
                'buy_price' => 26000,
                'sell_price' => 30000,
                'min_stock' => 50,
                'stock' => 110.00,
                'desc' => 'Aluminium babet / cor campuran',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-07',
                'name' => 'AL-Prodo',
                'unit' => 'kg',
                'buy_price' => 21000,
                'sell_price' => 24500,
                'min_stock' => 50,
                'stock' => 70.00,
                'desc' => 'Aluminium prodo / part cetakan industri',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-08',
                'name' => 'AL-Eleman',
                'unit' => 'kg',
                'buy_price' => 21000,
                'sell_price' => 24500,
                'min_stock' => 50,
                'stock' => 65.00,
                'desc' => 'Aluminium elemen pemanas / part pendingin',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-09',
                'name' => 'AL-ac/auning',
                'unit' => 'kg',
                'buy_price' => 17000,
                'sell_price' => 20500,
                'min_stock' => 50,
                'stock' => 90.00,
                'desc' => 'Aluminium pipa AC, auning kanopi',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-10',
                'name' => 'AL-bakar',
                'unit' => 'kg',
                'buy_price' => 6000,
                'sell_price' => 8000,
                'min_stock' => 50,
                'stock' => 50.00,
                'desc' => 'Aluminium bekas bakaran / kerak leburan',
                'is_active' => true,
            ],
            [
                'category_id' => $catAlumunium->id,
                'code' => 'ALM-11',
                'name' => 'AL-kopling',
                'unit' => 'kg',
                'buy_price' => 6000,
                'sell_price' => 8000,
                'min_stock' => 50,
                'stock' => 50.00,
                'desc' => 'Aluminium rumah kopling / part kotor',
                'is_active' => true,
            ],

            // TIMAH, AKI, STANLIS (3 items)
            [
                'category_id' => $catTimahAkiStanlis->id,
                'code' => 'TMH-01',
                'name' => 'Timah',
                'unit' => 'kg',
                'buy_price' => 22000,
                'sell_price' => 26000,
                'min_stock' => 30,
                'stock' => 60.00,
                'desc' => 'Timah batangan, timah pancing, segel',
                'is_active' => true,
            ],
            [
                'category_id' => $catTimahAkiStanlis->id,
                'code' => 'AKI-01',
                'name' => 'Aki',
                'unit' => 'kg',
                'buy_price' => 16000,
                'sell_price' => 19500,
                'min_stock' => 100,
                'stock' => 260.00,
                'desc' => 'Aki bekas basah dan kering motor/mobil',
                'is_active' => true,
            ],
            [
                'category_id' => $catTimahAkiStanlis->id,
                'code' => 'SS-01',
                'name' => 'Stanlis',
                'unit' => 'kg',
                'buy_price' => 7000,
                'sell_price' => 9000,
                'min_stock' => 100,
                'stock' => 210.00,
                'desc' => 'Stainless steel rongsok / pipa / plat',
                'is_active' => true,
            ],

            // BESI (4 items)
            [
                'category_id' => $catBesi->id,
                'code' => 'BSI-01',
                'name' => 'BTG',
                'unit' => 'kg',
                'buy_price' => 5500,
                'sell_price' => 6600,
                'min_stock' => 500,
                'stock' => 2450.00,
                'desc' => 'Besi Tua Balok / Tebal / Super / Rel Padat (BTG)',
                'is_active' => true,
            ],
            [
                'category_id' => $catBesi->id,
                'code' => 'BSI-02',
                'name' => 'BTK',
                'unit' => 'kg',
                'buy_price' => 3100,
                'sell_price' => 3900,
                'min_stock' => 500,
                'stock' => 1820.00,
                'desc' => 'Besi Tua Tipis / Campur / Potongan Pipa / Behel (BTK)',
                'is_active' => true,
            ],
            [
                'category_id' => $catBesi->id,
                'code' => 'BSI-03',
                'name' => 'BS-Seng/pasir',
                'unit' => 'kg',
                'buy_price' => 1200,
                'sell_price' => 1700,
                'min_stock' => 200,
                'stock' => 500.00,
                'desc' => 'Besi seng berkarat, pasir besi, drum tipis',
                'is_active' => true,
            ],
            [
                'category_id' => $catBesi->id,
                'code' => 'BSI-04',
                'name' => 'Bs-spandek tipis',
                'unit' => 'kg',
                'buy_price' => 1200,
                'sell_price' => 1700,
                'min_stock' => 200,
                'stock' => 450.00,
                'desc' => 'Besi spandek / atap seng tipis potongan',
                'is_active' => true,
            ],

            // PLASTIK (4 items)
            [
                'category_id' => $catPlastic->id,
                'code' => 'PLS-01',
                'name' => 'Aqua susun',
                'unit' => 'kg',
                'buy_price' => 4500,
                'sell_price' => 5800,
                'min_stock' => 300,
                'stock' => 1100.00,
                'desc' => 'Botol Aqua / PET bening susun rapi bersih',
                'is_active' => true,
            ],
            [
                'category_id' => $catPlastic->id,
                'code' => 'PLS-02',
                'name' => 'Galon',
                'unit' => 'kg',
                'buy_price' => 4000,
                'sell_price' => 5500,
                'min_stock' => 100,
                'stock' => 350.00,
                'desc' => 'Galon air mineral bekas (PC / PET)',
                'is_active' => true,
            ],
            [
                'category_id' => $catPlastic->id,
                'code' => 'PLS-03',
                'name' => 'Gabruk/Emberan warna',
                'unit' => 'kg',
                'buy_price' => 2200,
                'sell_price' => 3000,
                'min_stock' => 200,
                'stock' => 450.00,
                'desc' => 'Plastik gabruk / emberan warna campur (PP/PE)',
                'is_active' => true,
            ],
            [
                'category_id' => $catPlastic->id,
                'code' => 'PLS-04',
                'name' => 'Impek/kerasan',
                'unit' => 'kg',
                'buy_price' => 600,
                'sell_price' => 1000,
                'min_stock' => 100,
                'stock' => 280.00,
                'desc' => 'Plastik impek / kerasan (ABS, PP hitam keras)',
                'is_active' => true,
            ],

            // KERTAS & KARTON (6 items)
            [
                'category_id' => $catPaper->id,
                'code' => 'KRT-01',
                'name' => 'HVS/swl',
                'unit' => 'kg',
                'buy_price' => 2100,
                'sell_price' => 2800,
                'min_stock' => 500,
                'stock' => 1250.00,
                'desc' => 'Kertas putih HVS / SWL arsip kantor bersih',
                'is_active' => true,
            ],
            [
                'category_id' => $catPaper->id,
                'code' => 'KDS-01',
                'name' => 'Kardus kering',
                'unit' => 'kg',
                'buy_price' => 1800,
                'sell_price' => 2400,
                'min_stock' => 1000,
                'stock' => 4200.00,
                'desc' => 'Kardus boks cokelat kering dan bersih',
                'is_active' => true,
            ],
            [
                'category_id' => $catPaper->id,
                'code' => 'KDS-02',
                'name' => 'kardus lembap',
                'unit' => 'kg',
                'buy_price' => 1600,
                'sell_price' => 2100,
                'min_stock' => 500,
                'stock' => 1500.00,
                'desc' => 'Kardus boks kondisi basah / lembap',
                'is_active' => true,
            ],
            [
                'category_id' => $catPaper->id,
                'code' => 'KRT-02',
                'name' => 'Buku/cetak',
                'unit' => 'kg',
                'buy_price' => 1000,
                'sell_price' => 1500,
                'min_stock' => 300,
                'stock' => 650.00,
                'desc' => 'Buku pelajaran, majalah, kertas cetak',
                'is_active' => true,
            ],
            [
                'category_id' => $catPaper->id,
                'code' => 'KRT-03',
                'name' => 'Burem kering',
                'unit' => 'kg',
                'buy_price' => 1000,
                'sell_price' => 1500,
                'min_stock' => 200,
                'stock' => 400.00,
                'desc' => 'Kertas buram / CD kering',
                'is_active' => true,
            ],
            [
                'category_id' => $catPaper->id,
                'code' => 'KRT-04',
                'name' => 'Duplek/kones',
                'unit' => 'kg',
                'buy_price' => 500,
                'sell_price' => 800,
                'min_stock' => 300,
                'stock' => 800.00,
                'desc' => 'Kertas duplek box makanan / kones benang pabrik',
                'is_active' => true,
            ],

            // KAIN MAJUN & GEMBEL (2 items)
            [
                'category_id' => $catGembel->id,
                'code' => 'GMB-01',
                'name' => 'Gembel kasar',
                'unit' => 'kg',
                'buy_price' => 10000,
                'sell_price' => 12500,
                'min_stock' => 50,
                'stock' => 150.00,
                'desc' => 'Kain perca / majun gembel kasar',
                'is_active' => true,
            ],
            [
                'category_id' => $catGembel->id,
                'code' => 'GMB-02',
                'name' => 'Gembel alus',
                'unit' => 'kg',
                'buy_price' => 7000,
                'sell_price' => 9000,
                'min_stock' => 50,
                'stock' => 120.00,
                'desc' => 'Kain perca / majun katun gembel halus',
                'is_active' => true,
            ],
        ];

        $createdScrapItems = [];
        foreach ($itemsData as $row) {
            $item = ScrapItem::where('code', $row['code'])->first();
            if (!$item) {
                $item = ScrapItem::create([
                    'category_id' => $row['category_id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'unit' => $row['unit'],
                    'unit_type' => $row['unit'],
                    'buy_price_per_kg' => $row['buy_price'],
                    'sell_price_per_kg' => $row['sell_price'],
                    'min_stock_alert' => $row['min_stock'],
                    'description' => $row['desc'],
                    'is_active' => $row['is_active'] ?? true,
                ]);
            }

            // Create initial Stock Balance
            StockBalance::firstOrCreate(
                ['scrap_item_id' => $item->id],
                [
                    'current_stock' => $row['stock'],
                    'total_in' => $row['stock'] + 200,
                    'total_out' => 200,
                    'average_buy_price' => $row['buy_price'],
                ]
            );

            // Create initial price tier stock
            \App\Models\ItemPriceStock::firstOrCreate(
                ['scrap_item_id' => $item->id, 'price_per_kg' => $row['buy_price']],
                [
                    'current_stock' => $row['stock'],
                    'total_in' => $row['stock'] + 200,
                    'total_out' => 200,
                ]
            );

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
            'total_gross_weight' => 219.50,
            'total_tare_weight' => 7.50,
            'total_net_weight' => 212.00,
            'subtotal_amount' => 673000.00,
            'discount_amount' => 0.00,
            'rounding_amount' => 0.00,
            'final_amount' => 673000.00,
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
            'scrap_item_id' => $createdScrapItems['PLS-02']->id,
            'item_name_snapshot' => $createdScrapItems['PLS-02']->name,
            'item_code_snapshot' => $createdScrapItems['PLS-02']->code,
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
            'price_per_kg' => 35500,
            'subtotal' => 142000.00,
            'notes' => 'Panci plat bersih',
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
            'total_gross_weight' => 356.20,
            'total_tare_weight' => 15.20,
            'total_net_weight' => 341.00,
            'subtotal_amount' => 3960000.00,
            'discount_amount' => 0.00,
            'final_amount' => 3960000.00,
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
            'price_per_kg' => 5500,
            'subtotal' => 1815000.00,
            'notes' => 'Besi tebal padat BTG',
        ]);

        PurchaseTransactionItem::create([
            'purchase_transaction_id' => $tx2->id,
            'scrap_item_id' => $createdScrapItems['TBG-01']->id,
            'item_name_snapshot' => $createdScrapItems['TBG-01']->name,
            'item_code_snapshot' => $createdScrapItems['TBG-01']->code,
            'gross_weight' => 11.20,
            'tare_weight' => 0.20,
            'net_weight' => 11.00,
            'price_per_kg' => 195000,
            'subtotal' => 2145000.00,
            'notes' => 'Tembaga murni',
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
            'total_gross_weight' => 46.00,
            'total_tare_weight' => 0.30,
            'total_net_weight' => 45.70,
            'subtotal_amount' => 966600.00,
            'discount_amount' => 0.00,
            'final_amount' => 966600.00,
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
            'price_per_kg' => 16000,
            'subtotal' => 560000.00,
        ]);

        PurchaseTransactionItem::create([
            'purchase_transaction_id' => $tx3->id,
            'scrap_item_id' => $createdScrapItems['ALM-01']->id,
            'item_name_snapshot' => $createdScrapItems['ALM-01']->name,
            'item_code_snapshot' => $createdScrapItems['ALM-01']->code,
            'gross_weight' => 11.00,
            'tare_weight' => 0.30,
            'net_weight' => 10.70,
            'price_per_kg' => 38000,
            'subtotal' => 406600.00,
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
            'subtotal_amount' => 9900000.00,
            'discount_amount' => 0.00,
            'final_amount' => 9900000.00,
            'estimated_cogs' => 8250000.00,
            'estimated_profit' => 1650000.00,
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
            'sell_price_per_kg' => 6600.00,
            'buy_price_snapshot' => 5500.00,
            'subtotal' => 9900000.00,
            'cogs_subtotal' => 8250000.00,
            'profit_subtotal' => 1650000.00,
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
