<?php

use App\Models\ItemCategory;
use App\Models\ScrapItem;
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
        // 1. Categories
        $categories = [
            'besi' => ItemCategory::firstOrCreate(
                ['slug' => 'besi-ferrous'],
                ['name' => 'Besi & Logam Ferrous', 'description' => 'Segala jenis besi tua, plat, siku, kawat, spandek, seng', 'is_active' => true]
            ),
            'tembaga' => ItemCategory::firstOrCreate(
                ['slug' => 'logam-non-besi'],
                ['name' => 'Tembaga & Logam Non-Besi', 'description' => 'Tembaga, kuningan, timah, stainless steel', 'is_active' => true]
            ),
            'alumunium' => ItemCategory::firstOrCreate(
                ['slug' => 'alumunium'],
                ['name' => 'Alumunium', 'description' => 'Aneka jenis rongsokan aluminium batangan, cor, plat, siku, kuali, dan perkakas', 'is_active' => true]
            ),
            'plastik' => ItemCategory::firstOrCreate(
                ['slug' => 'plastik-botol'],
                ['name' => 'Plastik & Botol', 'description' => 'Botol PET bening, aqua susun, galon, emberan warna, impek kerasan', 'is_active' => true]
            ),
            'kertas' => ItemCategory::firstOrCreate(
                ['slug' => 'kertas-karton'],
                ['name' => 'Kertas & Karton', 'description' => 'Kardus boks kering & lembap, kertas HVS/swl, buku cetak, buram, duplek kones', 'is_active' => true]
            ),
            'aki' => ItemCategory::firstOrCreate(
                ['slug' => 'aki-baterai'],
                ['name' => 'Aki & Baterai Bekas', 'description' => 'Aki bekas basah dan kering motor & mobil', 'is_active' => true]
            ),
            'gembel' => ItemCategory::firstOrCreate(
                ['slug' => 'lain-lain'],
                ['name' => 'Gembel / Karung / Lainnya', 'description' => 'Kain majun perca gembel kasar & alus, karung goni, rongsok lain', 'is_active' => true]
            ),
        ];

        // 2. Master Scrap Items & Prices
        $items = [
            // TEMBAGA & KUNINGAN
            [
                'code' => 'TBG-01',
                'name' => 'Tembaga',
                'category_id' => $categories['tembaga']->id,
                'unit' => 'kg',
                'buy_price' => 195000,
                'sell_price' => 215000,
                'desc' => 'Tembaga murni / kabel tembaga kupas bersih',
                'is_active' => true,
            ],
            [
                'code' => 'TBG-02',
                'name' => 'Dangdang',
                'category_id' => $categories['tembaga']->id,
                'unit' => 'kg',
                'buy_price' => 170000,
                'sell_price' => 188000,
                'desc' => 'Tembaga dandang / perkakas masak tembaga',
                'is_active' => true,
            ],
            [
                'code' => 'KNG-01',
                'name' => 'KN bersih',
                'category_id' => $categories['tembaga']->id,
                'unit' => 'kg',
                'buy_price' => 130000,
                'sell_price' => 145000,
                'desc' => 'Kuningan bersih tanpa besi/kotoran',
                'is_active' => true,
            ],
            [
                'code' => 'KNG-02',
                'name' => 'KN Radiator',
                'category_id' => $categories['tembaga']->id,
                'unit' => 'kg',
                'buy_price' => 110000,
                'sell_price' => 125000,
                'desc' => 'Kuningan radiator mesin & kendaraan',
                'is_active' => true,
            ],
            [
                'code' => 'KNG-03',
                'name' => 'Gram kn',
                'category_id' => $categories['tembaga']->id,
                'unit' => 'kg',
                'buy_price' => 92000,
                'sell_price' => 105000,
                'desc' => 'Gram / serbuk bubut kuningan',
                'is_active' => true,
            ],

            // ALUMUNIUM
            [
                'code' => 'ALM-01',
                'name' => 'AL-Siku/kawat',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 38000,
                'sell_price' => 43000,
                'desc' => 'Aluminium profil siku kusen, kawat aluminium bersih',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-02',
                'name' => 'AL-Blok',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 36500,
                'sell_price' => 41500,
                'desc' => 'Aluminium blok mesin, pelek velg motor/mobil',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-03',
                'name' => 'AL-Panci/plat',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 35500,
                'sell_price' => 40500,
                'desc' => 'Aluminium panci, perabot rumah tangga, plat lembaran',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-04',
                'name' => 'AL-Kere/Krai',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 31000,
                'sell_price' => 35500,
                'desc' => 'Aluminium kere / krai tirai jendela',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-05',
                'name' => 'AL-Kuali',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 31000,
                'sell_price' => 35500,
                'desc' => 'Aluminium kuali / wajan cor tebal',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-06',
                'name' => 'AL-Babet',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 26000,
                'sell_price' => 30000,
                'desc' => 'Aluminium babet / cor campuran',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-07',
                'name' => 'AL-Prodo',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 21000,
                'sell_price' => 24500,
                'desc' => 'Aluminium prodo / part cetakan',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-08',
                'name' => 'AL-Eleman',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 21000,
                'sell_price' => 24500,
                'desc' => 'Aluminium elemen pemanas / part pendingin',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-09',
                'name' => 'AL-ac/auning',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 17000,
                'sell_price' => 20500,
                'desc' => 'Aluminium pipa AC, auning kanopi',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-10',
                'name' => 'AL-bakar',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 6000,
                'sell_price' => 8000,
                'desc' => 'Aluminium bekas bakaran / kerak leburan',
                'is_active' => true,
            ],
            [
                'code' => 'ALM-11',
                'name' => 'AL-kopling',
                'category_id' => $categories['alumunium']->id,
                'unit' => 'kg',
                'buy_price' => 6000,
                'sell_price' => 8000,
                'desc' => 'Aluminium rumah kopling / part kotor',
                'is_active' => true,
            ],

            // TIMAH, AKI, STANLIS
            [
                'code' => 'TMH-01',
                'name' => 'Timah',
                'category_id' => $categories['tembaga']->id,
                'unit' => 'kg',
                'buy_price' => 22000,
                'sell_price' => 26000,
                'desc' => 'Timah batangan, timah pancing, segel',
                'is_active' => true,
            ],
            [
                'code' => 'AKI-01',
                'name' => 'Aki',
                'category_id' => $categories['aki']->id,
                'unit' => 'kg',
                'buy_price' => 16000,
                'sell_price' => 19500,
                'desc' => 'Aki bekas basah dan kering motor/mobil',
                'is_active' => true,
            ],
            [
                'code' => 'SS-01',
                'name' => 'Stanlis',
                'category_id' => $categories['tembaga']->id,
                'unit' => 'kg',
                'buy_price' => 7000,
                'sell_price' => 9000,
                'desc' => 'Stainless steel rongsok / pipa / plat',
                'is_active' => true,
            ],

            // BESI
            [
                'code' => 'BSI-01',
                'name' => 'BTG',
                'category_id' => $categories['besi']->id,
                'unit' => 'kg',
                'buy_price' => 5500,
                'sell_price' => 6600,
                'desc' => 'Besi Tua Tebal / Super / Balok / Rel Padat',
                'is_active' => true,
            ],
            [
                'code' => 'BSI-02',
                'name' => 'BTK',
                'category_id' => $categories['besi']->id,
                'unit' => 'kg',
                'buy_price' => 3100,
                'sell_price' => 3900,
                'desc' => 'Besi Tipis / Campur / Potongan Pipa / Behel',
                'is_active' => true,
            ],
            [
                'code' => 'BSI-03',
                'name' => 'BS-Seng/pasir',
                'category_id' => $categories['besi']->id,
                'unit' => 'kg',
                'buy_price' => 1200,
                'sell_price' => 1700,
                'desc' => 'Besi seng, pasir besi, besi rongsok tipis berkarat',
                'is_active' => true,
            ],
            [
                'code' => 'BSI-04',
                'name' => 'Bs-spandek tipis',
                'category_id' => $categories['besi']->id,
                'unit' => 'kg',
                'buy_price' => 1200,
                'sell_price' => 1700,
                'desc' => 'Besi spandek / atap seng tipis',
                'is_active' => true,
            ],
            [
                'code' => 'BSI-05',
                'name' => 'Springbet (Tidak Beli)',
                'category_id' => $categories['besi']->id,
                'unit' => 'kg',
                'buy_price' => 0,
                'sell_price' => 0,
                'desc' => 'Per kasur springbed (Status: Tidak beli)',
                'is_active' => false,
            ],

            // PLASTIK
            [
                'code' => 'PLS-01',
                'name' => 'Gabruk/Emberan warna',
                'category_id' => $categories['plastik']->id,
                'unit' => 'kg',
                'buy_price' => 2200,
                'sell_price' => 3000,
                'desc' => 'Plastik gabruk / emberan warna campur',
                'is_active' => true,
            ],
            [
                'code' => 'PLS-02',
                'name' => 'Aqua susun',
                'category_id' => $categories['plastik']->id,
                'unit' => 'kg',
                'buy_price' => 4500,
                'sell_price' => 5800,
                'desc' => 'Botol Aqua / PET bening susun rapi bersih',
                'is_active' => true,
            ],
            [
                'code' => 'PLS-03',
                'name' => 'Galon',
                'category_id' => $categories['plastik']->id,
                'unit' => 'kg',
                'buy_price' => 4000,
                'sell_price' => 5500,
                'desc' => 'Galon air mineral bekas',
                'is_active' => true,
            ],
            [
                'code' => 'PLS-04',
                'name' => 'Impek/kerasan',
                'category_id' => $categories['plastik']->id,
                'unit' => 'kg',
                'buy_price' => 600,
                'sell_price' => 1000,
                'desc' => 'Plastik impek / kerasan (ABS, PP hitam keras)',
                'is_active' => true,
            ],

            // KERTAS
            [
                'code' => 'KRT-01',
                'name' => 'HVS/swl',
                'category_id' => $categories['kertas']->id,
                'unit' => 'kg',
                'buy_price' => 2100,
                'sell_price' => 2800,
                'desc' => 'Kertas putih HVS / SWL arsip bersih',
                'is_active' => true,
            ],
            [
                'code' => 'KDS-01',
                'name' => 'Kardus kering',
                'category_id' => $categories['kertas']->id,
                'unit' => 'kg',
                'buy_price' => 1800,
                'sell_price' => 2400,
                'desc' => 'Kardus boks cokelat kering dan bersih',
                'is_active' => true,
            ],
            [
                'code' => 'KDS-02',
                'name' => 'kardus lembap',
                'category_id' => $categories['kertas']->id,
                'unit' => 'kg',
                'buy_price' => 1600,
                'sell_price' => 2100,
                'desc' => 'Kardus boks kondisi basah / lembap',
                'is_active' => true,
            ],
            [
                'code' => 'KRT-02',
                'name' => 'Buku/cetak',
                'category_id' => $categories['kertas']->id,
                'unit' => 'kg',
                'buy_price' => 1000,
                'sell_price' => 1500,
                'desc' => 'Buku pelajaran, majalah, kertas cetak',
                'is_active' => true,
            ],
            [
                'code' => 'KRT-03',
                'name' => 'Burem kering',
                'category_id' => $categories['kertas']->id,
                'unit' => 'kg',
                'buy_price' => 1000,
                'sell_price' => 1500,
                'desc' => 'Kertas buram / CD kering',
                'is_active' => true,
            ],
            [
                'code' => 'KRT-04',
                'name' => 'Duplek/kones',
                'category_id' => $categories['kertas']->id,
                'unit' => 'kg',
                'buy_price' => 500,
                'sell_price' => 800,
                'desc' => 'Kertas duplek box makanan / kones benang pabrik',
                'is_active' => true,
            ],

            // GEMBEL
            [
                'code' => 'GMB-01',
                'name' => 'Gembel kasar',
                'category_id' => $categories['gembel']->id,
                'unit' => 'kg',
                'buy_price' => 10000,
                'sell_price' => 12500,
                'desc' => 'Kain perca / majun gembel kasar',
                'is_active' => true,
            ],
            [
                'code' => 'GMB-02',
                'name' => 'Gembel alus',
                'category_id' => $categories['gembel']->id,
                'unit' => 'kg',
                'buy_price' => 7000,
                'sell_price' => 9000,
                'desc' => 'Kain perca / majun katun gembel halus',
                'is_active' => true,
            ],
        ];

        foreach ($items as $data) {
            $item = ScrapItem::updateOrCreate(
                ['code' => $data['code']],
                [
                    'category_id' => $data['category_id'],
                    'name' => $data['name'],
                    'unit' => $data['unit'],
                    'unit_type' => $data['unit'],
                    'buy_price_per_kg' => $data['buy_price'],
                    'sell_price_per_kg' => $data['sell_price'],
                    'min_stock_alert' => 50.00,
                    'description' => $data['desc'],
                    'is_active' => $data['is_active'],
                ]
            );

            StockBalance::firstOrCreate(
                ['scrap_item_id' => $item->id],
                [
                    'current_stock' => 0.00,
                    'total_in' => 0.00,
                    'total_out' => 0.00,
                    'average_buy_price' => $data['buy_price'],
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe to leave data intact
    }
};

