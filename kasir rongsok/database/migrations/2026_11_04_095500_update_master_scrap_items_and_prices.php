<?php

use App\Models\ItemCategory;
use App\Models\ScrapItem;
use App\Models\ScrapItemPriceHistory;
use App\Models\StockBalance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations to synchronize user-requested scrap items and price updates.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Nullify foreign keys on transaction items before clearing items
        DB::table('purchase_transaction_items')->update(['scrap_item_id' => null]);
        DB::table('sales_transaction_items')->update(['scrap_item_id' => null]);
        DB::table('delivery_order_items')->update(['scrap_item_id' => null]);

        // Wipe old stock movements, balances, price histories, scrap items, and categories
        if (\Illuminate\Support\Facades\Schema::hasTable('stock_movements')) {
            DB::table('stock_movements')->truncate();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('stock_balances')) {
            DB::table('stock_balances')->truncate();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('scrap_item_price_histories')) {
            DB::table('scrap_item_price_histories')->truncate();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('scrap_items')) {
            DB::table('scrap_items')->truncate();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('item_categories')) {
            DB::table('item_categories')->truncate();
        }

        // 1. Kategori Master Sesuai Pengelompokan Scrap & Daur Ulang
        $categories = [
            'tembaga-kuningan' => ItemCategory::create([
                'name' => 'Tembaga & Kuningan',
                'slug' => 'tembaga-kuningan',
                'description' => 'Tembaga murni, dangdang, kuningan bersih, kuningan radiator, dan gram bubut kuningan',
                'is_active' => true,
            ]),
            'alumunium' => ItemCategory::create([
                'name' => 'Alumunium',
                'slug' => 'alumunium',
                'description' => 'Aluminium siku/kawat, blok mesin, panci/plat, kuali, babet, prodo, eleman, AC, dan part aluminium lainnya',
                'is_active' => true,
            ]),
            'besi' => ItemCategory::create([
                'name' => 'Besi',
                'slug' => 'besi',
                'description' => 'Besi tua balok tebal (BTG), besi tipis behel (BTK), seng pasir, dan spandek tipis',
                'is_active' => true,
            ]),
            'timah-aki-stanlis' => ItemCategory::create([
                'name' => 'Timah, Aki & Stainless',
                'slug' => 'timah-aki-stanlis',
                'description' => 'Timah batangan/pancing, aki basah/kering motor & mobil bekas, dan stainless steel rongsok',
                'is_active' => true,
            ]),
            'plastik' => ItemCategory::create([
                'name' => 'Plastik',
                'slug' => 'plastik',
                'description' => 'Botol Aqua susun bening, galon bekas, emberan warna campur, dan plastik kerasan/impek',
                'is_active' => true,
            ]),
            'kertas-karton' => ItemCategory::create([
                'name' => 'Kertas & Karton',
                'slug' => 'kertas-karton',
                'description' => 'Kertas putih HVS/swl arsip, kardus boks kering & lembap, buku cetak, kertas buram, dan duplek kones',
                'is_active' => true,
            ]),
            'kain-gembel' => ItemCategory::create([
                'name' => 'Kain Majun & Gembel',
                'slug' => 'kain-gembel',
                'description' => 'Kain majun perca katun gembel kasar dan gembel alus',
                'is_active' => true,
            ]),
        ];

        // 2. Daftar 35 Master Barang dan Harga Sesuai Permintaan
        $items = [
            // ==========================================
            // KELOMPOK 1: TEMBAGA & KUNINGAN (5 items)
            // ==========================================
            [
                'category_slug' => 'tembaga-kuningan',
                'code' => 'TBG-01',
                'name' => 'Tembaga',
                'unit' => 'kg',
                'buy_price' => 195000,
                'sell_price' => 215000,
                'stock' => 120.00,
                'desc' => 'Tembaga murni / kabel tembaga kupas merah bersih',
            ],
            [
                'category_slug' => 'tembaga-kuningan',
                'code' => 'TBG-02',
                'name' => 'Dangdang',
                'unit' => 'kg',
                'buy_price' => 170000,
                'sell_price' => 188000,
                'stock' => 50.00,
                'desc' => 'Tembaga dandang / perkakas masak tembaga kuno',
            ],
            [
                'category_slug' => 'tembaga-kuningan',
                'code' => 'KNG-01',
                'name' => 'KN bersih',
                'unit' => 'kg',
                'buy_price' => 130000,
                'sell_price' => 145000,
                'stock' => 80.00,
                'desc' => 'Kuningan bersih tanpa besi/kotoran (kran, pipa kuningan)',
            ],
            [
                'category_slug' => 'tembaga-kuningan',
                'code' => 'KNG-02',
                'name' => 'KN Radiator',
                'unit' => 'kg',
                'buy_price' => 110000,
                'sell_price' => 125000,
                'stock' => 60.00,
                'desc' => 'Kuningan radiator mesin & kendaraan bermotor',
            ],
            [
                'category_slug' => 'tembaga-kuningan',
                'code' => 'KNG-03',
                'name' => 'Gram kn',
                'unit' => 'kg',
                'buy_price' => 92000,
                'sell_price' => 105000,
                'stock' => 45.00,
                'desc' => 'Gram / serbuk bubut kuningan kering',
            ],

            // ==========================================
            // KELOMPOK 2: ALUMUNIUM (11 items)
            // ==========================================
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-01',
                'name' => 'AL-Siku/kawat',
                'unit' => 'kg',
                'buy_price' => 38000,
                'sell_price' => 43000,
                'stock' => 180.00,
                'desc' => 'Aluminium profil siku kusen, kawat aluminium bersih',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-02',
                'name' => 'AL-Blok',
                'unit' => 'kg',
                'buy_price' => 36500,
                'sell_price' => 41500,
                'stock' => 220.00,
                'desc' => 'Aluminium blok mesin, pelek velg motor/mobil',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-03',
                'name' => 'AL-Panci/plat',
                'unit' => 'kg',
                'buy_price' => 35500,
                'sell_price' => 40500,
                'stock' => 250.00,
                'desc' => 'Aluminium panci, perabot dapur, plat lembaran',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-04',
                'name' => 'AL-Kere/Krai',
                'unit' => 'kg',
                'buy_price' => 31000,
                'sell_price' => 35500,
                'stock' => 85.00,
                'desc' => 'Aluminium kere / krai tirai jendela',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-05',
                'name' => 'AL-Kuali',
                'unit' => 'kg',
                'buy_price' => 31000,
                'sell_price' => 35500,
                'stock' => 95.00,
                'desc' => 'Aluminium kuali / wajan cor tebal',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-06',
                'name' => 'AL-Babet',
                'unit' => 'kg',
                'buy_price' => 26000,
                'sell_price' => 30000,
                'stock' => 110.00,
                'desc' => 'Aluminium babet / cor campuran zinc',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-07',
                'name' => 'AL-Prodo',
                'unit' => 'kg',
                'buy_price' => 21000,
                'sell_price' => 24500,
                'stock' => 70.00,
                'desc' => 'Aluminium prodo / part cetakan industri',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-08',
                'name' => 'AL-Eleman',
                'unit' => 'kg',
                'buy_price' => 21000,
                'sell_price' => 24500,
                'stock' => 65.00,
                'desc' => 'Aluminium elemen pemanas / part pendingin',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-09',
                'name' => 'AL-ac/auning',
                'unit' => 'kg',
                'buy_price' => 17000,
                'sell_price' => 20500,
                'stock' => 90.00,
                'desc' => 'Aluminium pipa AC, kisi radiator AC, auning kanopi',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-10',
                'name' => 'AL-bakar',
                'unit' => 'kg',
                'buy_price' => 6000,
                'sell_price' => 8000,
                'stock' => 50.00,
                'desc' => 'Aluminium bekas bakaran / kerak leburan kotor',
            ],
            [
                'category_slug' => 'alumunium',
                'code' => 'ALM-11',
                'name' => 'AL-kopling',
                'unit' => 'kg',
                'buy_price' => 6000,
                'sell_price' => 8000,
                'stock' => 50.00,
                'desc' => 'Aluminium rumah kopling / part motor kotor',
            ],

            // ==========================================
            // KELOMPOK 3: TIMAH, AKI & STAINLESS (3 items)
            // ==========================================
            [
                'category_slug' => 'timah-aki-stanlis',
                'code' => 'TMH-01',
                'name' => 'Timah',
                'unit' => 'kg',
                'buy_price' => 22000,
                'sell_price' => 26000,
                'stock' => 60.00,
                'desc' => 'Timah batangan, timah pancing, segel timah',
            ],
            [
                'category_slug' => 'timah-aki-stanlis',
                'code' => 'AKI-01',
                'name' => 'Aki',
                'unit' => 'kg',
                'buy_price' => 16000,
                'sell_price' => 19500,
                'stock' => 260.00,
                'desc' => 'Aki bekas basah dan kering motor & mobil',
            ],
            [
                'category_slug' => 'timah-aki-stanlis',
                'code' => 'SS-01',
                'name' => 'Stanlis',
                'unit' => 'kg',
                'buy_price' => 7000,
                'sell_price' => 9000,
                'stock' => 210.00,
                'desc' => 'Stainless steel rongsok / pipa / plat stainless',
            ],

            // ==========================================
            // KELOMPOK 4: BESI (4 items)
            // ==========================================
            [
                'category_slug' => 'besi',
                'code' => 'BSI-01',
                'name' => 'BTG',
                'unit' => 'kg',
                'buy_price' => 5500,
                'sell_price' => 6600,
                'stock' => 2450.00,
                'desc' => 'Besi Tua Balok / Tebal / Super / Rel Padat (BTG)',
            ],
            [
                'category_slug' => 'besi',
                'code' => 'BSI-02',
                'name' => 'BTK',
                'unit' => 'kg',
                'buy_price' => 3100,
                'sell_price' => 3900,
                'stock' => 1820.00,
                'desc' => 'Besi Tua Tipis / Campur / Potongan Pipa / Behel (BTK)',
            ],
            [
                'category_slug' => 'besi',
                'code' => 'BSI-03',
                'name' => 'BS-Seng/pasir',
                'unit' => 'kg',
                'buy_price' => 1200,
                'sell_price' => 1700,
                'stock' => 500.00,
                'desc' => 'Besi seng berkarat, pasir besi, drum tipis',
            ],
            [
                'category_slug' => 'besi',
                'code' => 'BSI-04',
                'name' => 'Bs-spandek tipis',
                'unit' => 'kg',
                'buy_price' => 1200,
                'sell_price' => 1700,
                'stock' => 450.00,
                'desc' => 'Besi spandek / atap seng tipis potongan',
            ],

            // ==========================================
            // KELOMPOK 5: PLASTIK (4 items)
            // ==========================================
            [
                'category_slug' => 'plastik',
                'code' => 'PLS-01',
                'name' => 'Aqua susun',
                'unit' => 'kg',
                'buy_price' => 4500,
                'sell_price' => 5800,
                'stock' => 1100.00,
                'desc' => 'Botol Aqua / PET bening susun rapi bersih tanpa tutup/label',
            ],
            [
                'category_slug' => 'plastik',
                'code' => 'PLS-02',
                'name' => 'Galon',
                'unit' => 'kg',
                'buy_price' => 4000,
                'sell_price' => 5500,
                'stock' => 350.00,
                'desc' => 'Galon air mineral bekas (PC / PET)',
            ],
            [
                'category_slug' => 'plastik',
                'code' => 'PLS-03',
                'name' => 'Gabruk/Emberan warna',
                'unit' => 'kg',
                'buy_price' => 2200,
                'sell_price' => 3000,
                'stock' => 450.00,
                'desc' => 'Plastik gabruk / emberan warna campur (PP/PE)',
            ],
            [
                'category_slug' => 'plastik',
                'code' => 'PLS-04',
                'name' => 'Impek/kerasan',
                'unit' => 'kg',
                'buy_price' => 600,
                'sell_price' => 1000,
                'stock' => 280.00,
                'desc' => 'Plastik impek / kerasan (ABS, PP hitam keras, bodi TV/elektronik)',
            ],

            // ==========================================
            // KELOMPOK 6: KERTAS & KARTON (6 items)
            // ==========================================
            [
                'category_slug' => 'kertas-karton',
                'code' => 'KRT-01',
                'name' => 'HVS/swl',
                'unit' => 'kg',
                'buy_price' => 2100,
                'sell_price' => 2800,
                'stock' => 1250.00,
                'desc' => 'Kertas putih HVS / SWL arsip kantor bersih tanpa cover',
            ],
            [
                'category_slug' => 'kertas-karton',
                'code' => 'KDS-01',
                'name' => 'Kardus kering',
                'unit' => 'kg',
                'buy_price' => 1800,
                'sell_price' => 2400,
                'stock' => 4200.00,
                'desc' => 'Kardus boks cokelat kering dan bersih',
            ],
            [
                'category_slug' => 'kertas-karton',
                'code' => 'KDS-02',
                'name' => 'kardus lembap',
                'unit' => 'kg',
                'buy_price' => 1600,
                'sell_price' => 2100,
                'stock' => 1500.00,
                'desc' => 'Kardus boks kondisi basah / lembap',
            ],
            [
                'category_slug' => 'kertas-karton',
                'code' => 'KRT-02',
                'name' => 'Buku/cetak',
                'unit' => 'kg',
                'buy_price' => 1000,
                'sell_price' => 1500,
                'stock' => 650.00,
                'desc' => 'Buku pelajaran, majalah, kertas cetak warna',
            ],
            [
                'category_slug' => 'kertas-karton',
                'code' => 'KRT-03',
                'name' => 'Burem kering',
                'unit' => 'kg',
                'buy_price' => 1000,
                'sell_price' => 1500,
                'stock' => 400.00,
                'desc' => 'Kertas buram / kertas koran / CD kering',
            ],
            [
                'category_slug' => 'kertas-karton',
                'code' => 'KRT-04',
                'name' => 'Duplek/kones',
                'unit' => 'kg',
                'buy_price' => 500,
                'sell_price' => 800,
                'stock' => 800.00,
                'desc' => 'Kertas duplek box makanan / kones benang pabrik',
            ],

            // ==========================================
            // KELOMPOK 7: KAIN MAJUN & GEMBEL (2 items)
            // ==========================================
            [
                'category_slug' => 'kain-gembel',
                'code' => 'GMB-01',
                'name' => 'Gembel kasar',
                'unit' => 'kg',
                'buy_price' => 10000,
                'sell_price' => 12500,
                'stock' => 150.00,
                'desc' => 'Kain perca / majun gembel kasar',
            ],
            [
                'category_slug' => 'kain-gembel',
                'code' => 'GMB-02',
                'name' => 'Gembel alus',
                'unit' => 'kg',
                'buy_price' => 7000,
                'sell_price' => 9000,
                'stock' => 120.00,
                'desc' => 'Kain perca / majun katun gembel halus',
            ],
        ];

        foreach ($items as $data) {
            $catId = $categories[$data['category_slug']]->id;

            $item = ScrapItem::create([
                'category_id' => $catId,
                'code' => $data['code'],
                'name' => $data['name'],
                'unit' => $data['unit'],
                'unit_type' => $data['unit'],
                'buy_price_per_kg' => $data['buy_price'],
                'sell_price_per_kg' => $data['sell_price'],
                'min_stock_alert' => 50.00,
                'description' => $data['desc'],
                'is_active' => true,
            ]);

            StockBalance::create([
                'scrap_item_id' => $item->id,
                'current_stock' => $data['stock'],
                'total_in' => $data['stock'],
                'total_out' => 0.00,
                'average_buy_price' => $data['buy_price'],
            ]);

            if (\Illuminate\Support\Facades\Schema::hasTable('scrap_item_price_histories')) {
                ScrapItemPriceHistory::create([
                    'scrap_item_id' => $item->id,
                    'buy_price_per_kg' => $data['buy_price'],
                    'sell_price_per_kg' => $data['sell_price'],
                    'notes' => 'Penyesuaian standar harga master barang',
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration
    }
};
