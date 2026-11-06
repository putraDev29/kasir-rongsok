<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\ItemPriceStock;
use App\Models\PaymentMethod;
use App\Models\PurchaseTransaction;
use App\Models\SalesTransaction;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\PurchaseTransactionService;
use App\Services\SalesTransactionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PriceTieredTransactionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $cashier;
    protected ScrapItem $besiItem;
    protected PaymentMethod $cashMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
        $this->cashier = User::factory()->create(['role' => User::ROLE_KASIR, 'is_active' => true]);

        $category = ItemCategory::firstOrCreate(
            ['slug' => 'besi-logam'],
            [
                'name' => 'Besi Logam',
                'is_active' => true,
            ]
        );

        $this->besiItem = ScrapItem::firstOrCreate(
            ['code' => 'BESI-TEST-01'],
            [
                'category_id' => $category->id,
                'name' => 'Besi Campur Test',
                'unit' => 'kg',
                'unit_type' => 'kg',
                'buy_price_per_kg' => 4000,
                'sell_price_per_kg' => 6000,
                'min_stock_alert' => 10,
                'is_active' => true,
            ]
        );

        StockBalance::firstOrCreate(
            ['scrap_item_id' => $this->besiItem->id],
            [
                'current_stock' => 0,
                'total_in' => 0,
                'total_out' => 0,
                'average_buy_price' => 4000,
            ]
        );

        $this->cashMethod = PaymentMethod::firstOrCreate(
            ['code' => 'cash'],
            [
                'name' => 'Tunai',
                'is_active' => true,
            ]
        );
    }

    public function test_purchase_with_same_price_merges_and_different_price_splits()
    {
        $service = app(PurchaseTransactionService::class);

        // Kasir memasukkan 3 timbangan:
        // 1. Besi @ Rp 4.000 (net 50 kg)
        // 2. Besi @ Rp 4.000 (net 30 kg) -> harga sama, harus masuk ke tier harga 4.000 (akumulasi jadi 80 kg)
        // 3. Besi @ Rp 3.500 (net 20 kg) -> harga beda, harus ada entri harga beda (20 kg @ Rp 3.500)
        $purchaseData = [
            'supplier_name' => 'Pemasok Budi',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'unit_type' => 'kg',
                    'gross_weight' => 55,
                    'tare_weight' => 5,
                    'price_per_kg' => 4000,
                ],
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'unit_type' => 'kg',
                    'gross_weight' => 33,
                    'tare_weight' => 3,
                    'price_per_kg' => 4000,
                ],
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'unit_type' => 'kg',
                    'gross_weight' => 22,
                    'tare_weight' => 2,
                    'price_per_kg' => 3500,
                ],
            ],
        ];

        $transaction = $service->createPurchase($purchaseData, $this->admin);

        // Item harus terkonsolidasi menjadi 2 baris (bukan 3 baris):
        // 1 baris @ 4.000 dengan net 80 kg
        // 1 baris @ 3.500 dengan net 20 kg
        $this->assertCount(2, $transaction->items);

        $tier4000 = $transaction->items->firstWhere('price_per_kg', 4000);
        $this->assertNotNull($tier4000);
        $this->assertEquals(80, (float) $tier4000->net_weight);
        $this->assertEquals(320000, (float) $tier4000->subtotal);

        $tier3500 = $transaction->items->firstWhere('price_per_kg', 3500);
        $this->assertNotNull($tier3500);
        $this->assertEquals(20, (float) $tier3500->net_weight);
        $this->assertEquals(70000, (float) $tier3500->subtotal);

        // Total akumulasi berat transaksi adalah 100 kg (80 + 20)
        $this->assertEquals(100, (float) $transaction->total_net_weight);
        $this->assertEquals(390000, (float) $transaction->final_amount);

        // Cek ItemPriceStock tercatat untuk masing-masing harga
        $priceStock4000 = ItemPriceStock::where('scrap_item_id', $this->besiItem->id)
            ->where('price_per_kg', 4000)
            ->first();
        $this->assertNotNull($priceStock4000);
        $this->assertEquals(80, (float) $priceStock4000->current_stock);

        $priceStock3500 = ItemPriceStock::where('scrap_item_id', $this->besiItem->id)
            ->where('price_per_kg', 3500)
            ->first();
        $this->assertNotNull($priceStock3500);
        $this->assertEquals(20, (float) $priceStock3500->current_stock);

        // Cek total stock balance terakumulasi 100 kg
        $this->besiItem->refresh();
        $this->assertEquals(100, (float) $this->besiItem->stockBalance->current_stock);
    }

    public function test_sales_and_delivery_orders_have_distinct_options_per_price()
    {
        // Siapkan 2 tier stok untuk Besi Campur: 80 kg @ 4.000 dan 20 kg @ 3.500
        ItemPriceStock::updateOrCreate(
            ['scrap_item_id' => $this->besiItem->id, 'price_per_kg' => 4000],
            ['current_stock' => 80, 'total_in' => 80, 'total_out' => 0]
        );
        ItemPriceStock::updateOrCreate(
            ['scrap_item_id' => $this->besiItem->id, 'price_per_kg' => 3500],
            ['current_stock' => 20, 'total_in' => 20, 'total_out' => 0]
        );
        $this->besiItem->stockBalance->update(['current_stock' => 100]);

        // Cek helper getPriceTierOptions
        $options = $this->besiItem->getPriceTierOptions(onlyWithStock: true);
        $this->assertCount(2, $options);
        $this->assertEquals('Besi Campur Test - Rp 3.500', $options[0]['name']);
        $this->assertEquals(20, (float) $options[0]['current_stock']);
        $this->assertEquals('Besi Campur Test - Rp 4.000', $options[1]['name']);
        $this->assertEquals(80, (float) $options[1]['current_stock']);

        // Uji halaman Penjualan menampilkan kedua opsi harga
        $response = $this->actingAs($this->admin)->get(route('sales.create'));
        $response->assertOk();
        $response->assertSee('Besi Campur Test - Rp 3.500');
        $response->assertSee('Besi Campur Test - Rp 4.000');

        // Uji halaman Surat Jalan menampilkan kedua opsi harga
        $responseDo = $this->actingAs($this->admin)->get(route('delivery-orders.create'));
        $responseDo->assertOk();
        $responseDo->assertSee('Besi Campur Test - Rp 3.500');
        $responseDo->assertSee('Besi Campur Test - Rp 4.000');
    }

    public function test_sale_from_specific_price_tier_deducts_tier_stock_and_records_cogs()
    {
        $salesService = app(SalesTransactionService::class);

        ItemPriceStock::updateOrCreate(
            ['scrap_item_id' => $this->besiItem->id, 'price_per_kg' => 4000],
            ['current_stock' => 80, 'total_in' => 80, 'total_out' => 0]
        );
        ItemPriceStock::updateOrCreate(
            ['scrap_item_id' => $this->besiItem->id, 'price_per_kg' => 3500],
            ['current_stock' => 20, 'total_in' => 20, 'total_out' => 0]
        );
        $this->besiItem->stockBalance->update(['current_stock' => 100]);

        // Jual 15 kg dari tier harga Rp 3.500 dengan harga jual Rp 5.500
        $saleData = [
            'buyer_name' => 'Pabrik Peleburan Baja',
            'delivery_type' => SalesTransaction::DELIVERY_TYPE_PICKUP,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'buy_price_snapshot' => 3500,
                    'weight' => 15,
                    'unit_type' => 'kg',
                    'sell_price_per_kg' => 5500,
                ],
            ],
        ];

        $sale = $salesService->createSale($saleData, $this->admin);

        $this->assertEquals(15 * 5500, (float) $sale->final_amount);
        $this->assertEquals(15 * 3500, (float) $sale->estimated_cogs); // 52.500
        $this->assertEquals((15 * 5500) - (15 * 3500), (float) $sale->estimated_profit); // 30.000

        // Stok tier 3.500 berkurang dari 20 jadi 5 kg
        $tier3500 = ItemPriceStock::where('scrap_item_id', $this->besiItem->id)
            ->where('price_per_kg', 3500)
            ->first();
        $this->assertEquals(5, (float) $tier3500->current_stock);

        // Stok tier 4.000 tetap 80 kg
        $tier4000 = ItemPriceStock::where('scrap_item_id', $this->besiItem->id)
            ->where('price_per_kg', 4000)
            ->first();
        $this->assertEquals(80, (float) $tier4000->current_stock);

        // Total stock balance berkurang 15 kg (dari 100 jadi 85 kg)
        $this->besiItem->refresh();
        $this->assertEquals(85, (float) $this->besiItem->stockBalance->current_stock);
    }
}
