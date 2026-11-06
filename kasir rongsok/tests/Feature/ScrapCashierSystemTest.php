<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\PaymentMethod;
use App\Models\PurchaseTransaction;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseTransactionService;
use App\Services\SalesTransactionService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScrapCashierSystemTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $admin;
    protected User $kasir;
    protected ScrapItem $besiItem;
    protected PaymentMethod $cashMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->kasir = User::where('role', User::ROLE_KASIR)->first();
        $this->besiItem = ScrapItem::where('code', 'BSI-01')->first();
        $this->cashMethod = PaymentMethod::where('code', 'cash')->first();
    }

    public function test_user_can_authenticate_and_redirects_by_role(): void
    {
        // Kasir redirects to POS cashier screen
        $response = $this->post('/login', [
            'email' => $this->kasir->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect('/purchases/create');
        $this->assertAuthenticatedAs($this->kasir);

        // Logout
        $this->post('/logout');

        // Admin redirects to dashboard
        $response = $this->post('/login', [
            'email' => $this->admin->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_cashier_can_create_purchase_with_correct_math_and_stock_increase(): void
    {
        $service = app(PurchaseTransactionService::class);
        $initialStock = (float) $this->besiItem->stockBalance->current_stock;

        $gross = 105.00;
        $tare = 5.00;
        $net = 100.00; // 105 - 5 = 100 kg
        $price = 5000.00;
        $expectedSubtotal = 500000.00;

        $tx = $service->createPurchase([
            'supplier_name' => 'Pak Bambang Test',
            'supplier_phone' => '081299998888',
            'payment_method_id' => $this->cashMethod->id,
            'discount_amount' => 0,
            'rounding_amount' => 0,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => $gross,
                    'tare_weight' => $tare,
                    'price_per_kg' => $price,
                ]
            ]
        ], $this->kasir);

        $this->assertNotNull($tx);
        $this->assertEquals($net, (float) $tx->total_net_weight);
        $this->assertEquals($expectedSubtotal, (float) $tx->final_amount);
        $this->assertEquals('completed', $tx->status);

        // Verify stock balance increased by net weight
        $this->besiItem->refresh();
        $newStock = (float) $this->besiItem->stockBalance->current_stock;
        $this->assertEquals($initialStock + $net, $newStock);

        // Verify stock movement ledger recorded
        $this->assertDatabaseHas('stock_movements', [
            'scrap_item_id' => $this->besiItem->id,
            'type' => 'purchase',
            'weight_qty' => $net,
            'reference_id' => $tx->id,
        ]);
    }

    public function test_purchase_with_tare_nominal_and_percentage(): void
    {
        $service = app(PurchaseTransactionService::class);

        // 1. Nominal tara (e.g. Gross 200 kg, Tara 10 kg -> Net 190 kg)
        $txNominal = $service->createPurchase([
            'supplier_name' => 'Supplier Tara Nominal',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'unit_type' => 'kg',
                    'gross_weight' => 200.00,
                    'tare_weight' => 10.00,
                    'price_per_kg' => 5000.00,
                ]
            ]
        ], $this->kasir);

        $this->assertEquals(200.00, (float) $txNominal->total_gross_weight);
        $this->assertEquals(10.00, (float) $txNominal->total_tare_weight);
        $this->assertEquals(190.00, (float) $txNominal->total_net_weight);
        $this->assertEquals(950000.00, (float) $txNominal->final_amount);

        // FormatHelper percentage check
        $this->assertEquals('5%', \App\Services\FormatHelper::tarePercent(200, 10));

        // 2. Tara in Pcs/Satuan (e.g. Qty 50 pcs, Tara 2 pcs -> Net 48 pcs)
        $txPcs = $service->createPurchase([
            'supplier_name' => 'Supplier Tara Pcs',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'unit_type' => 'pcs',
                    'quantity' => 50,
                    'tare_weight' => 2,
                    'price_per_kg' => 5000.00,
                ]
            ]
        ], $this->kasir);

        $pcsItem = $txPcs->items->first();
        $this->assertEquals('pcs', $pcsItem->unit_type);
        $this->assertEquals(48, (float) $pcsItem->quantity);
        $this->assertEquals(2, (float) $pcsItem->tare_weight);
        $this->assertEquals(240000.00, (float) $txPcs->final_amount);
    }

    public function test_sale_deducts_stock_and_prevents_negative_stock(): void
    {
        $salesService = app(SalesTransactionService::class);
        $this->besiItem->refresh();
        if ((float) $this->besiItem->stockBalance->current_stock < 20) {
            $this->besiItem->stockBalance->update(['current_stock' => 100.00]);
            $this->besiItem->refresh();
        }
        $currentStock = (float) $this->besiItem->stockBalance->current_stock;

        // Valid sale
        $soldWeight = 20.00;
        $sellPrice = 6200.00;

        $sale = $salesService->createSale([
            'buyer_name' => 'PT. Logam Mulia Test',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'weight' => $soldWeight,
                    'sell_price_per_kg' => $sellPrice,
                ]
            ]
        ], $this->admin);

        $this->assertNotNull($sale);
        $this->besiItem->refresh();
        $this->assertEquals($currentStock - $soldWeight, (float) $this->besiItem->stockBalance->current_stock);

        // Attempt to sell more than current available stock -> must throw Exception
        $excessWeight = (float) $this->besiItem->stockBalance->current_stock + 9999.00;
        $this->expectException(Exception::class);

        $salesService->createSale([
            'buyer_name' => 'PT. Hantu Test',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'weight' => $excessWeight,
                    'sell_price_per_kg' => $sellPrice,
                ]
            ]
        ], $this->admin);
    }

    public function test_cancel_transaction_reverses_stock_accurately(): void
    {
        $service = app(PurchaseTransactionService::class);
        $this->besiItem->refresh();
        $stockBefore = (float) $this->besiItem->stockBalance->current_stock;

        $tx = $service->createPurchase([
            'supplier_name' => 'Batal Test',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 50,
                    'tare_weight' => 0,
                    'price_per_kg' => 5000,
                ]
            ]
        ], $this->kasir);

        $this->besiItem->refresh();
        $this->assertEquals($stockBefore + 50, (float) $this->besiItem->stockBalance->current_stock);

        // Cancel by Super Admin (Owner)
        $cancelled = $service->cancelPurchase($tx, $this->superAdmin, 'Salah timbang');
        $this->assertTrue($cancelled);

        $tx->refresh();
        $this->assertEquals(PurchaseTransaction::STATUS_CANCELLED, $tx->status);

        // Stock restored back to original
        $this->besiItem->refresh();
        $this->assertEquals($stockBefore, (float) $this->besiItem->stockBalance->current_stock);

        // Verify reversal movement
        $this->assertDatabaseHas('stock_movements', [
            'scrap_item_id' => $this->besiItem->id,
            'type' => 'reversal',
            'weight_qty' => -50,
        ]);
    }

    public function test_role_based_access_control(): void
    {
        // 1. Kasir & Admin can access Dashboard, Timbang Kasir, and Riwayat Pembelian
        $this->actingAs($this->kasir)->get('/dashboard')->assertStatus(200);
        $this->actingAs($this->admin)->get('/dashboard')->assertStatus(200);

        $this->actingAs($this->kasir)->get('/purchases/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/purchases/create')->assertStatus(200);

        $this->actingAs($this->kasir)->get('/purchases')->assertStatus(200);
        $this->actingAs($this->admin)->get('/purchases')->assertStatus(200);

        // 2. Kasir and Admin are forbidden (403) from accessing Owner-only modules
        $restrictedUrls = [
            '/scrap-items',
            '/suppliers',
            '/debts',
            '/sales',
            '/delivery-orders',
            '/stock',
            '/expenses',
            '/salary-slips',
            '/reports',
            '/users',
            '/settings',
        ];

        foreach ($restrictedUrls as $url) {
            $this->actingAs($this->kasir)->get($url)->assertStatus(403);
            $this->actingAs($this->admin)->get($url)->assertStatus(403);
            $this->actingAs($this->superAdmin)->get($url)->assertStatus(200);
        }
    }

    public function test_purchase_cannot_exceed_master_buy_price(): void
    {
        $masterPrice = (float) $this->besiItem->buy_price_per_kg;

        // Attempt 1: Price higher than master price -> MUST FAIL validation
        $responseFail = $this->actingAs($this->kasir)->post('/purchases', [
            'supplier_name' => 'Penjual Test Melebihi Harga',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 50,
                    'tare_weight' => 0,
                    'price_per_kg' => $masterPrice + 2000, // MELEBIHI HARGA MASTER
                ]
            ]
        ]);

        $responseFail->assertSessionHasErrors(['items.0.price_per_kg']);

        // Attempt 2: Price below or equal to master price -> MUST SUCCEED
        $responseSuccess = $this->actingAs($this->kasir)->post('/purchases', [
            'supplier_name' => 'Penjual Test Harga Wajar',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 50,
                    'tare_weight' => 0,
                    'price_per_kg' => max(100, $masterPrice - 500), // DIBAWAH HARGA MASTER
                ]
            ]
        ]);

        $responseSuccess->assertSessionHas('success');
    }

    public function test_debt_deduction_in_purchase_cashier(): void
    {
        $code = 'SUP-TEST-' . uniqid();
        $supplier = Supplier::create([
            'code' => $code,
            'name' => 'Supplier Khusus Test Hutang ' . $code,
            'phone' => '08999999999',
            'is_active' => true,
        ]);
        $debtService = app(\App\Services\DebtService::class);
        $purchaseService = app(PurchaseTransactionService::class);

        // 1. Create a loan/debt of 500.000
        $debt = $debtService->createDebt([
            'borrower_name' => $supplier->name,
            'borrower_phone' => $supplier->phone,
            'borrower_type' => 'supplier',
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'initial_amount' => 500000,
            'notes' => 'Pinjaman modal beli rongsok',
        ], $this->admin);

        $this->assertEquals(500000, (float) $debt->remaining_amount);

        // 2. Kasir buys 100 kg @ Rp 5.000 = Rp 500.000, deducts Rp 300.000
        $purchase = $purchaseService->createPurchase([
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'payment_method_id' => $this->cashMethod->id,
            'debt_deduction_amount' => 300000,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 100,
                    'tare_weight' => 0,
                    'price_per_kg' => 5000,
                ]
            ]
        ], $this->kasir);

        $this->assertEquals(500000, (float) $purchase->final_amount);
        $this->assertEquals(300000, (float) $purchase->debt_deduction_amount);
        $this->assertEquals(200000, (float) $purchase->net_cash_paid);

        $debt->refresh();
        $this->assertEquals(200000, (float) $debt->remaining_amount);
        $this->assertEquals(300000, (float) $debt->paid_amount);

        // Assert debt payment record created
        $this->assertDatabaseHas('debt_payments', [
            'debt_id' => $debt->id,
            'purchase_transaction_id' => $purchase->id,
            'amount' => 300000,
        ]);
    }

    public function test_salary_slip_creation_and_calculation(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/salary-slips', [
            'employee_name' => 'Karyawan Test Gudang',
            'period_month' => 'November 2026',
            'payment_date' => now()->toDateString(),
            'basic_salary' => 3000000,
            'allowance_amount' => 500000,
            'bonus_amount' => 200000,
            'debt_deduction' => 200000,
            'other_deduction' => 50000,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('salary_slips', [
            'employee_name' => 'Karyawan Test Gudang',
            'basic_salary' => 3000000,
            'total_income' => 3700000,
            'total_deduction' => 250000,
            'net_salary' => 3450000,
        ]);
    }

    public function test_delivery_order_creation(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/delivery-orders', [
            'delivery_date' => now()->toDateString(),
            'recipient_name' => 'PT. Daur Ulang Mandiri',
            'driver_name' => 'Sopir Joko',
            'vehicle_number' => 'B 1234 ABC',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_name' => $this->besiItem->name,
                    'weight' => 500,
                    'package_qty' => 10,
                ],
                [
                    'scrap_item_id' => null,
                    'item_name' => 'Kardus Bal Pres',
                    'weight' => 300,
                    'package_qty' => 5,
                ]
            ]
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('delivery_orders', [
            'recipient_name' => 'PT. Daur Ulang Mandiri',
            'driver_name' => 'Sopir Joko',
            'vehicle_number' => 'B 1234 ABC',
            'total_weight' => 800,
            'total_packages' => 15,
        ]);

        $deliveryOrder = \App\Models\DeliveryOrder::where('recipient_name', 'PT. Daur Ulang Mandiri')->first();
        $this->assertNotNull($deliveryOrder);

        // Verify show page displays 2 rangkap info
        $showRes = $this->actingAs($this->superAdmin)->get("/delivery-orders/{$deliveryOrder->id}");
        $showRes->assertStatus(200);
        $showRes->assertSee('Lembar 1 : Dipegang oleh Gudang');
        $showRes->assertSee('Lembar 2 : Dipegang oleh Supir');

        // Verify print view has Lembar 1 and Lembar 2
        $printRes = $this->actingAs($this->superAdmin)->get("/delivery-orders/{$deliveryOrder->id}/print");
        $printRes->assertStatus(200);
        $printRes->assertSee('LEMBAR 1 : GUDANG');
        $printRes->assertSee('LEMBAR 2 : SUPIR');
        $printRes->assertSee('Surat jalan ini dibuat <strong>2 (dua) rangkap</strong>', false);

        // Verify WA preview contains 2 rangkap notice
        $waRes = $this->actingAs($this->superAdmin)->get("/delivery-orders/{$deliveryOrder->id}/wa-preview");
        $waRes->assertStatus(200);
        $waRes->assertJsonFragment([
            'delivery_number' => $deliveryOrder->delivery_number,
        ]);
        $waData = $waRes->json();
        $this->assertStringContainsString('Lembar 1', $waData['message']);
        $this->assertStringContainsString('Lembar 2', $waData['message']);

        // Verify PDF stream returns PDF content type
        $pdfRes = $this->actingAs($this->superAdmin)->get("/delivery-orders/{$deliveryOrder->id}/pdf");
        $pdfRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));
    }

    public function test_create_pages_render_without_source_code_leak(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/delivery-orders/create');
        $response->assertStatus(200);
        $response->assertSee('x-data="deliveryOrderForm()"', false);

        $responseSalary = $this->actingAs($this->superAdmin)->get('/salary-slips/create');
        $responseSalary->assertStatus(200);
        $responseSalary->assertSee('x-data="salarySlipForm()"', false);
    }

    public function test_expense_can_be_stored_with_manual_category_name(): void
    {
        $uniqueCatName = 'Kategori Manual ' . uniqid();
        $response = $this->actingAs($this->superAdmin)->post('/expenses', [
            'new_category_name' => $uniqueCatName,
            'expense_date' => now()->toDateString(),
            'amount' => 125000,
            'description' => 'Beli oli mesin dinamo pres hidrolik',
            'recipient' => 'Toko Teknik Sejahtera',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('expense_categories', [
            'name' => $uniqueCatName,
        ]);
        $this->assertDatabaseHas('expenses', [
            'description' => 'Beli oli mesin dinamo pres hidrolik',
            'amount' => 125000,
        ]);
    }

    public function test_profit_loss_report_includes_incoming_outgoing_salaries_and_expenses(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/reports/profit-loss');
        $response->assertStatus(200);
        $response->assertSee('Rincian Barang Masuk (Pembelian)');
        $response->assertSee('Rincian Barang Keluar (Penjualan)');
        $response->assertSee('Rincian Slip Gaji Karyawan');
        $response->assertSee('Rincian Beban Operasional Gudang & Depo', false);
        $response->assertSee('ESTIMASI LABA BERSIH USAHA (NET PROFIT)');
    }

    public function test_settings_can_update_sidebar_name_logo_and_disclaimer(): void
    {
        $fakeLogo = UploadedFile::fake()->image('depo_logo.png', 100, 100);

        $response = $this->actingAs($this->superAdmin)->put('/settings', [
            'app_name' => 'DEPO BESI MAKMUR',
            'app_subtitle' => 'Sentra Daur Ulang Logam',
            'company_name' => 'PT. Daur Ulang Terpadu',
            'company_tagline' => 'Solusi Logam & Rongsok',
            'company_phone' => '0812-9988-7766',
            'company_address' => 'Jl. Pahlawan Rongsok No. 12',
            'receipt_disclaimer' => 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI',
            'receipt_footer' => 'Barang sudah ditimbang tidak dapat ditarik kembali.',
            'receipt_header' => 'Nota Pembelian Resmi',
            'default_printer_type' => 'thermal_58',
            'app_logo' => $fakeLogo,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('business_settings', [
            'key' => 'app_name',
            'value' => 'DEPO BESI MAKMUR',
        ]);
        $this->assertDatabaseHas('business_settings', [
            'key' => 'receipt_disclaimer',
            'value' => 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI',
        ]);

        // Verify Sidebar Displays the custom name
        $dashResponse = $this->actingAs($this->admin)->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('DEPO BESI MAKMUR');
        $dashResponse->assertSee('Sentra Daur Ulang Logam');

        // Verify Nota Print Displays Disclaimer
        $purchase = PurchaseTransaction::first();
        if ($purchase) {
            $printResponse = $this->actingAs($this->kasir)->get('/purchases/' . $purchase->id . '/print');
            $printResponse->assertStatus(200);
            $printResponse->assertSee('TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI');
            $printResponse->assertSee('PERHATIAN / DISCLAIMER');
        }

        // Clean up uploaded fake logo file
        $logoPath = \App\Models\BusinessSetting::get('app_logo');
        if ($logoPath && file_exists(public_path($logoPath))) {
            @unlink(public_path($logoPath));
        }
    }

    public function test_purchases_report_has_price_list_pdf_export_and_price_list_renders_cleanly(): void
    {
        // 1. Check Purchases Report page has the "Daftar Harga Beli (PDF)" button
        $response = $this->actingAs($this->superAdmin)->get('/reports/purchases');
        $response->assertStatus(200);
        $response->assertSee('Daftar Harga Beli (PDF)');
        $response->assertSee(route('scrap-items.print-price-list'));

        // 2. Check Price List Print/PDF view renders item names and prices for sellers
        $priceListResponse = $this->actingAs($this->superAdmin)->get('/scrap-items/print-price-list');
        $priceListResponse->assertStatus(200);
        $priceListResponse->assertSee('DAFTAR HARGA BELI ' . strtoupper(\App\Models\BusinessSetting::commodityTerm()), false);
        $priceListResponse->assertSee('Nama ' . \App\Models\BusinessSetting::commodityTerm(), false);
        $priceListResponse->assertSee('Harga Beli per Kg');
        $priceListResponse->assertSee('TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI');
    }

    public function test_cashier_can_input_new_item_during_purchase_and_auto_saves_to_master(): void
    {
        $uniqueItemName = 'Kabel Tembaga Rambut Spesial ' . uniqid();
        $category = \App\Models\ItemCategory::first();

        $response = $this->actingAs($this->kasir)->post('/purchases', [
            'supplier_name' => 'Pemasok Barang Baru',
            'payment_method_id' => $this->cashMethod->id,
            'discount_amount' => 0,
            'rounding_amount' => 0,
            'items' => [
                [
                    'scrap_item_id' => '__new__',
                    'new_item_name' => $uniqueItemName,
                    'category_id' => $category?->id,
                    'unit_type' => 'kg',
                    'gross_weight' => 25.00,
                    'tare_weight' => 1.00,
                    'price_per_kg' => 95000.00,
                ]
            ]
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 1. Assert ScrapItem was automatically created in master table
        $createdItem = ScrapItem::where('name', $uniqueItemName)->first();
        $this->assertNotNull($createdItem);
        $this->assertEquals(95000.00, (float) $createdItem->buy_price_per_kg);
        $this->assertEquals('kg', $createdItem->unit);
        $this->assertStringStartsWith('BRG-', $createdItem->code);

        // 2. Assert StockBalance is created and has correct 24.0 kg stock
        $createdItem->load('stockBalance');
        $this->assertNotNull($createdItem->stockBalance);
        $this->assertEquals(24.00, (float) $createdItem->stockBalance->current_stock);
        $this->assertEquals(95000.00, (float) $createdItem->stockBalance->average_buy_price);

        // 3. Assert StockMovement ledger was created
        $this->assertDatabaseHas('stock_movements', [
            'scrap_item_id' => $createdItem->id,
            'type' => 'purchase',
            'weight_qty' => 24.00,
        ]);

        // 4. Clean up test item
        $createdItem->stockMovements()->delete();
        $createdItem->stockBalance()->delete();
        $createdItem->purchaseItems()->delete();
        $createdItem->forceDelete();
    }

    public function test_cashier_can_input_new_category_during_purchase_and_auto_saves_to_master(): void
    {
        $uniqueCatName = 'Kategori Test Khusus ' . uniqid();
        $uniqueItemName = 'Material Khusus ' . uniqid();

        $response = $this->actingAs($this->kasir)->post('/purchases', [
            'supplier_name' => 'Pemasok Kategori Baru',
            'payment_method_id' => $this->cashMethod->id,
            'discount_amount' => 0,
            'rounding_amount' => 0,
            'items' => [
                [
                    'scrap_item_id' => '__new__',
                    'new_item_name' => $uniqueItemName,
                    'category_id' => '__new__',
                    'new_category_name' => $uniqueCatName,
                    'unit_type' => 'kg',
                    'gross_weight' => 15.00,
                    'tare_weight' => 1.00,
                    'price_per_kg' => 75000.00,
                ]
            ]
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 1. Assert ItemCategory was automatically created
        $createdCategory = \App\Models\ItemCategory::where('name', $uniqueCatName)->first();
        $this->assertNotNull($createdCategory);
        $this->assertTrue($createdCategory->is_active);

        // 2. Assert ScrapItem was automatically created with this new category_id
        $createdItem = ScrapItem::where('name', $uniqueItemName)->first();
        $this->assertNotNull($createdItem);
        $this->assertEquals($createdCategory->id, $createdItem->category_id);
        $this->assertEquals(75000.00, (float) $createdItem->buy_price_per_kg);

        // Clean up
        $createdItem->stockMovements()->delete();
        $createdItem->stockBalance()->delete();
        $createdItem->purchaseItems()->delete();
        $createdItem->forceDelete();
        $createdCategory->delete();
    }

    public function test_debt_categories_and_two_sheet_agreement_pdf(): void
    {
        $debtService = app(\App\Services\DebtService::class);

        // 1. Create Modal Loan
        $debtModal = $debtService->createDebt([
            'borrower_name' => 'Pak Bambang Modal',
            'borrower_phone' => '081299991111',
            'borrower_type' => 'supplier',
            'loan_category' => 'modal',
            'date' => date('Y-m-d'),
            'initial_amount' => 5000000,
            'notes' => 'Tambahan modal kerja keliling cari besi & tembaga',
        ], $this->superAdmin);

        $this->assertEquals('modal', $debtModal->loan_category);
        $this->assertEquals('Modal Usaha', $debtModal->getCategoryLabel());
        $this->assertEquals('SURAT PERJANJIAN PINJAMAN MODAL USAHA', $debtModal->getAgreementTitle());

        // 2. Create Hutang / Bon
        $debtHutang = $debtService->createDebt([
            'borrower_name' => 'Mas Rudi Kasbon',
            'borrower_phone' => '081299992222',
            'borrower_type' => 'karyawan',
            'loan_category' => 'hutang',
            'date' => date('Y-m-d'),
            'initial_amount' => 1500000,
            'notes' => 'Pinjaman dana keperluan darurat',
        ], $this->superAdmin);

        $this->assertEquals('hutang', $debtHutang->loan_category);
        $this->assertEquals('Hutang / Pinjaman', $debtHutang->getCategoryLabel());
        $this->assertEquals('SURAT PERJANJIAN HUTANG PIUTANG', $debtHutang->getAgreementTitle());

        // 3. Create Komitmen Pasokan
        $debtKomitmen = $debtService->createDebt([
            'borrower_name' => 'Juragan Agus Suplai',
            'borrower_phone' => '081299993333',
            'borrower_type' => 'supplier',
            'loan_category' => 'komitmen',
            'date' => date('Y-m-d'),
            'initial_amount' => 10000000,
            'notes' => 'Uang muka komitmen pasokan kardus 10 ton',
        ], $this->superAdmin);

        $this->assertEquals('komitmen', $debtKomitmen->loan_category);
        $this->assertEquals('Komitmen Pasokan', $debtKomitmen->getCategoryLabel());
        $this->assertEquals('SURAT PERJANJIAN KOMITMEN PASOKAN MATERIAL SCRAP', $debtKomitmen->getAgreementTitle());

        // 4. Test Web Agreement View contains 2 sheets (Owner & Peminjam)
        $viewResponse = $this->actingAs($this->superAdmin)->get(route('debts.agreement', $debtKomitmen));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('LEMBAR 1: ASLI (UNTUK OWNER');
        $viewResponse->assertSee('LEMBAR 2: SALINAN (UNTUK PEMINJAM');
        $viewResponse->assertSee('SURAT PERJANJIAN KOMITMEN PASOKAN MATERIAL SCRAP');
        $viewResponse->assertSee('Pasal 1: Dana Uang Muka Komitmen Pasokan');

        // 5. Test PDF Stream endpoint
        $pdfResponse = $this->actingAs($this->superAdmin)->get(route('debts.agreement-pdf', $debtModal));
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));

        // Clean up
        $debtModal->forceDelete();
        $debtHutang->forceDelete();
        $debtKomitmen->forceDelete();
    }

    public function test_owner_signature_upload_and_pdf_integration(): void
    {
        $oldSignature = BusinessSetting::get('owner_signature');

        // 1. Upload signature via settings update
        $fakeSignature = UploadedFile::fake()->image('ttd_owner.png', 300, 150);

        $response = $this->actingAs($this->superAdmin)->from(route('settings.index'))->put(route('settings.update'), [
            'app_name' => 'KASIR RONGSOK',
            'company_name' => 'Depo Scrap Abadi',
            'company_phone' => '08123456789',
            'company_address' => 'Jl. Daur Ulang No. 88',
            'default_printer_type' => 'thermal_58',
            'owner_signature' => $fakeSignature,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $response->assertRedirect(route('settings.index'));

        // 2. Verify setting was saved and helper methods work
        $savedPath = BusinessSetting::get('owner_signature');
        $this->assertNotNull($savedPath);
        $this->assertTrue(BusinessSetting::hasOwnerSignature());
        $base64 = BusinessSetting::ownerSignatureBase64();
        $this->assertNotNull($base64);
        $this->assertStringStartsWith('data:image/png;base64,', $base64);

        // 3. Verify PDF agreement generates successfully with signature embedded
        $debt = \App\Models\Debt::create([
            'debt_number' => 'DBT-TTD-' . time(),
            'borrower_name' => 'Mitra TTD Test',
            'borrower_phone' => '081288887777',
            'borrower_type' => 'supplier',
            'loan_category' => 'modal',
            'date' => date('Y-m-d'),
            'initial_amount' => 5000000,
            'remaining_amount' => 5000000,
            'status' => 'active',
            'user_id' => $this->superAdmin->id,
        ]);

        $pdfResponse = $this->actingAs($this->superAdmin)->get(route('debts.agreement-pdf', $debt));
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));

        // 4. Test remove signature checkbox
        $removeResponse = $this->actingAs($this->superAdmin)->from(route('settings.index'))->put(route('settings.update'), [
            'app_name' => 'KASIR RONGSOK',
            'company_name' => 'Depo Scrap Abadi',
            'company_phone' => '08123456789',
            'company_address' => 'Jl. Daur Ulang No. 88',
            'default_printer_type' => 'thermal_58',
            'remove_signature' => '1',
        ]);

        $removeResponse->assertSessionHasNoErrors();
        $removeResponse->assertSessionHas('success');
        // Signature is permanently preserved per requirement: tanda tangan filenya tetep upload jangan di hapus
        $this->assertNotNull(BusinessSetting::get('owner_signature'));
        $this->assertTrue(BusinessSetting::hasOwnerSignature());
        $this->assertNotNull(BusinessSetting::ownerSignatureBase64());

        // Restore original setting & clean up debt
        BusinessSetting::set('owner_signature', $oldSignature);
        $debt->forceDelete();
    }

    public function test_settings_can_update_commodity_term_dynamically(): void
    {
        $oldTerm = BusinessSetting::get('commodity_term');

        // 1. Update commodity term via settings page
        $response = $this->actingAs($this->superAdmin)->from(route('settings.index'))->put(route('settings.update'), [
            'app_name' => 'KASIR RONGSOK',
            'company_name' => 'Depo Scrap Abadi',
            'commodity_term' => 'Material Logam & Besi Tua',
            'company_phone' => '08123456789',
            'company_address' => 'Jl. Daur Ulang No. 88',
            'default_printer_type' => 'thermal_58',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        // 2. Assert stored setting and helper method
        $this->assertEquals('Material Logam & Besi Tua', BusinessSetting::commodityTerm());

        // 3. Assert dynamic display on Price List & Agreement PDF
        $priceListResponse = $this->actingAs($this->superAdmin)->get('/scrap-items/print-price-list');
        $priceListResponse->assertStatus(200);
        $priceListResponse->assertSee('DAFTAR HARGA BELI MATERIAL LOGAM & BESI TUA');
        $priceListResponse->assertSee('Nama Material Logam & Besi Tua');

        // 4. Agreement PDF includes updated commodity term
        $debt = \App\Models\Debt::first();
        if ($debt) {
            $pdfResponse = $this->actingAs($this->superAdmin)->get(route('debts.agreement-pdf', $debt));
            $pdfResponse->assertStatus(200);
            $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));
        }

        // Restore original setting
        BusinessSetting::set('commodity_term', $oldTerm);
    }

    public function test_sales_and_delivery_orders_show_variant_breakdown_with_master_total_aggregation_and_preview(): void
    {
        $salesService = app(SalesTransactionService::class);
        $this->besiItem->refresh();
        $this->besiItem->stockBalance->update(['current_stock' => 500.00]);
        $this->besiItem->refresh();

        // 1. Create Sale with 2 variants of same master item
        $sale = $salesService->createSale([
            'buyer_name' => 'PT. Pelat Baja Nusantara',
            'payment_method_id' => $this->cashMethod->id,
            'transaction_date' => now()->toDateString(),
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_type_name' => 'Besi Super Tebal',
                    'weight' => 100,
                    'sell_price_per_kg' => 5000,
                ],
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_type_name' => 'Besi Campur Tipis',
                    'weight' => 50,
                    'sell_price_per_kg' => 4000,
                ],
            ],
        ], $this->superAdmin);

        $this->assertEquals(150, (float) $sale->total_weight);
        $this->assertEquals(700000, (float) $sale->final_amount);

        // Grouped master items check
        $grouped = $sale->grouped_master_items;
        $this->assertCount(1, $grouped);
        $this->assertEquals(150, $grouped->first()->total_weight);
        $this->assertEquals(700000, $grouped->first()->total_subtotal);
        $this->assertTrue($grouped->first()->has_variants);
        $this->assertCount(2, $grouped->first()->items);

        // 2. Test Sales Show page has master item total and variant breakdown
        $showRes = $this->actingAs($this->superAdmin)->get(route('sales.show', $sale));
        $showRes->assertStatus(200);
        $showRes->assertSee('150 kg');
        $showRes->assertSee('700.000');
        $showRes->assertSee('Besi Super Tebal');
        $showRes->assertSee('100 kg');
        $showRes->assertSee('5.000');
        $showRes->assertSee('Besi Campur Tipis');
        $showRes->assertSee('50 kg');
        $showRes->assertSee('4.000');

        // 3. Test Sales Print view has variant breakdown and preview toolbar
        $printRes = $this->actingAs($this->superAdmin)->get(route('sales.print', $sale));
        $printRes->assertStatus(200);
        $printRes->assertSee('Besi Super Tebal');
        $printRes->assertSee('Besi Campur Tipis');
        $printRes->assertSee('Unduh PDF');
        $printRes->assertSee('Cetak Dokumen');

        // 4. Test Sales PDF preview (inline stream)
        $pdfRes = $this->actingAs($this->superAdmin)->get(route('sales.pdf', $sale));
        $pdfRes->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfRes->headers->get('Content-Type'));

        // 5. Test Delivery Order with variants
        $doRes = $this->actingAs($this->superAdmin)->post(route('delivery-orders.store'), [
            'sales_transaction_id' => $sale->id,
            'delivery_date' => now()->toDateString(),
            'recipient_name' => 'PT. Pelat Baja Nusantara',
            'driver_name' => 'Sopir Slamet',
            'vehicle_number' => 'B 9999 XYZ',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_name' => $this->besiItem->name,
                    'item_type_name' => 'Besi Super Tebal',
                    'weight' => 100,
                    'package_qty' => 5,
                    'price_per_kg' => 5000,
                ],
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_name' => $this->besiItem->name,
                    'item_type_name' => 'Besi Campur Tipis',
                    'weight' => 50,
                    'package_qty' => 3,
                    'price_per_kg' => 4000,
                ],
            ],
        ]);
        $doRes->assertSessionHasNoErrors();

        $deliveryOrder = \App\Models\DeliveryOrder::where('recipient_name', 'PT. Pelat Baja Nusantara')->first();
        $this->assertNotNull($deliveryOrder);
        $this->assertEquals(150, (float) $deliveryOrder->total_weight);
        $this->assertEquals(8, (int) $deliveryOrder->total_packages);

        // DO Show
        $doShowRes = $this->actingAs($this->superAdmin)->get(route('delivery-orders.show', $deliveryOrder));
        $doShowRes->assertStatus(200);
        $doShowRes->assertSee('Besi Super Tebal');
        $doShowRes->assertSee('Besi Campur Tipis');
        $doShowRes->assertSee('150 kg');

        // DO Print
        $doPrintRes = $this->actingAs($this->superAdmin)->get(route('delivery-orders.print', $deliveryOrder));
        $doPrintRes->assertStatus(200);
        $doPrintRes->assertSee('Besi Super Tebal');
        $doPrintRes->assertSee('Besi Campur Tipis');
        $doPrintRes->assertSee('Unduh PDF');

        // DO PDF Preview
        $doPdfRes = $this->actingAs($this->superAdmin)->get(route('delivery-orders.pdf', $deliveryOrder));
        $doPdfRes->assertStatus(200);
        $this->assertEquals('application/pdf', $doPdfRes->headers->get('Content-Type'));

        // 6. Test Reports Sales displays variant details
        $reportRes = $this->actingAs($this->superAdmin)->get(route('reports.sales', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));
        $reportRes->assertStatus(200);
        $reportRes->assertSee('Besi Super Tebal');
        $reportRes->assertSee('Besi Campur Tipis');

        // 7. Test Reports Sales Print displays variant details
        $reportPrintRes = $this->actingAs($this->superAdmin)->get(route('reports.sales', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
            'format' => 'print',
        ]));
        $reportPrintRes->assertStatus(200);
        $reportPrintRes->assertSee('Besi Super Tebal');
        $reportPrintRes->assertSee('Besi Campur Tipis');
    }

    /**
     * Test sales delivery type workflow:
     * - Pickup: Customer picks up at depot -> status immediately completed, no delivery order.
     * - Delivery: Company armada delivery -> status pending_delivery until delivery is completed.
     */
    public function test_sales_delivery_type_and_completion_workflow(): void
    {
        // 1. Pickup Sale: Immediate completion, no DO required
        $pickupRes = $this->actingAs($this->superAdmin)->post(route('sales.store'), [
            'delivery_type' => 'pickup',
            'buyer_name' => 'Bpk. H. Syarif (Ambil Sendiri)',
            'buyer_phone' => '08129999111',
            'transaction_date' => now()->toDateString(),
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_type_name' => 'Besi Super Tebal',
                    'weight' => 80,
                    'sell_price_per_kg' => 5000,
                ],
            ],
        ]);
        $pickupRes->assertSessionHasNoErrors();

        $pickupSale = \App\Models\SalesTransaction::where('buyer_name', 'Bpk. H. Syarif (Ambil Sendiri)')->first();
        $this->assertNotNull($pickupSale);
        $this->assertEquals('pickup', $pickupSale->delivery_type);
        $this->assertEquals('completed', $pickupSale->status);
        $this->assertNull($pickupSale->delivery_order_id);
        $this->assertTrue($pickupSale->isPickup());
        $this->assertTrue($pickupSale->isCompleted());

        // Check show page for pickup sale
        $pickupShow = $this->actingAs($this->superAdmin)->get(route('sales.show', $pickupSale));
        $pickupShow->assertStatus(200);
        $pickupShow->assertSee('SELESAI (DIAMBIL SENDIRI)');
        $pickupShow->assertDontSee('+ Buat Surat Jalan');

        // Check print page for pickup sale
        $pickupPrint = $this->actingAs($this->superAdmin)->get(route('sales.print', $pickupSale));
        $pickupPrint->assertStatus(200);
        $pickupPrint->assertSee('FAKTUR PENJUALAN (DIAMBIL SENDIRI)');
        $pickupPrint->assertSee('Diambil Langsung Pembeli');
        $pickupPrint->assertDontSee('Sopir / Ekspedisi');

        // Check DO create page does NOT include pickup sale in dropdown
        $doCreate = $this->actingAs($this->superAdmin)->get(route('delivery-orders.create'));
        $doCreate->assertStatus(200);
        $doCreate->assertDontSee('Bpk. H. Syarif (Ambil Sendiri)');

        // 2. Delivery Sale with driver and vehicle: Auto-creates DO, status pending_delivery
        $deliveryRes = $this->actingAs($this->superAdmin)->post(route('sales.store'), [
            'delivery_type' => 'delivery',
            'buyer_name' => 'PT. Mega Steel Konstruksi',
            'buyer_phone' => '08138888222',
            'buyer_address' => 'Kawasan Industri Jababeka V',
            'driver_name' => 'Pak Joko Widodo',
            'driver_phone' => '0812345678',
            'vehicle_number' => 'B 9123 KAS',
            'vehicle_type' => 'Truk Fuso Engkel',
            'transaction_date' => now()->toDateString(),
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_type_name' => 'Besi Super Tebal',
                    'weight' => 200,
                    'sell_price_per_kg' => 5200,
                ],
            ],
        ]);
        $deliveryRes->assertSessionHasNoErrors();

        $deliverySale = \App\Models\SalesTransaction::where('buyer_name', 'PT. Mega Steel Konstruksi')->first();
        $this->assertNotNull($deliverySale);
        $this->assertEquals('delivery', $deliverySale->delivery_type);
        $this->assertEquals('pending_delivery', $deliverySale->status);
        $this->assertTrue($deliverySale->isDelivery());
        $this->assertTrue($deliverySale->isPendingDelivery());
        $this->assertNotNull($deliverySale->delivery_order_id);

        $linkedDO = $deliverySale->deliveryOrder;
        $this->assertNotNull($linkedDO);
        $this->assertEquals('Pak Joko Widodo', $linkedDO->driver_name);
        $this->assertEquals('B 9123 KAS', $linkedDO->vehicle_number);

        // Check show page shows pending status and mark delivered action
        $deliveryShow = $this->actingAs($this->superAdmin)->get(route('sales.show', $deliverySale));
        $deliveryShow->assertStatus(200);
        $deliveryShow->assertSee('MENUNGGU PENGIRIMAN');
        $deliveryShow->assertSee('Konfirmasi Pengiriman Selesai');

        // Confirm delivery from Delivery Order endpoint (bidirectional sync)
        $markDeliveredRes = $this->actingAs($this->superAdmin)->post(route('delivery-orders.mark-delivered', $linkedDO));
        $markDeliveredRes->assertSessionHasNoErrors();

        $linkedDO->refresh();
        $deliverySale->refresh();

        $this->assertEquals('delivered', $linkedDO->status);
        $this->assertEquals('completed', $deliverySale->status);
        $this->assertTrue($deliverySale->isCompleted());

        // 3. Standalone Delivery Sale marked completed directly from Sales endpoint
        $deliverySale2 = app(\App\Services\SalesTransactionService::class)->createSale([
            'delivery_type' => 'delivery',
            'buyer_name' => 'Pabrik Peleburan Jaya',
            'transaction_date' => now()->toDateString(),
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'weight' => 100,
                    'sell_price_per_kg' => 5000,
                ],
            ],
        ], $this->superAdmin);

        $this->assertEquals('pending_delivery', $deliverySale2->status);

        $saleMarkRes = $this->actingAs($this->superAdmin)->post(route('sales.mark-delivered', $deliverySale2));
        $saleMarkRes->assertSessionHasNoErrors();
        $deliverySale2->refresh();
        $this->assertEquals('completed', $deliverySale2->status);
    }

    /**
     * Test Debt Detail page (/debts/{id}):
     * - PDF button points to debts.pdf (Debt History Statement reflecting current status, NOT agreement contract)
     * - debts.pdf reflects real-time status and payments ledger
     */
    public function test_debt_detail_page_pdf_is_debt_history_statement_and_reflects_current_status(): void
    {
        $debtService = app(\App\Services\DebtService::class);
        $debt = $debtService->createDebt([
            'borrower_name' => 'Pak Haji Komar',
            'borrower_phone' => '081233445566',
            'borrower_type' => 'supplier',
            'loan_category' => 'modal',
            'initial_amount' => 2000000,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'notes' => 'Pinjaman modal borongan besi bongkaran',
        ], $this->superAdmin);

        $this->assertEquals(2000000, (float) $debt->initial_amount);
        $this->assertEquals(2000000, (float) $debt->remaining_amount);
        $this->assertEquals('unpaid', $debt->status);

        // 1. Visit /debts/{id} show page
        $showRes = $this->actingAs($this->superAdmin)->get(route('debts.show', $debt));
        $showRes->assertStatus(200);
        // Verify it links to debts.pdf (Debt History PDF) and debts.agreement (Contract)
        $showRes->assertSee(route('debts.pdf', $debt));
        $showRes->assertSee(route('debts.agreement', $debt));
        $showRes->assertSee(route('debts.wa-preview', $debt));
        $showRes->assertSee('Surat Perjanjian');
        $showRes->assertSee('Preview / Unduh PDF');

        // 2. Initial Debt History PDF (Status: Unpaid)
        $initialPdfRes = $this->actingAs($this->superAdmin)->get(route('debts.pdf', $debt));
        $initialPdfRes->assertStatus(200);
        $this->assertEquals('application/pdf', $initialPdfRes->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $initialPdfRes->headers->get('Content-Disposition'));
        $cleanNum = str_replace(['/', ' '], '_', $debt->debt_number);
        $cleanBorrower = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($debt->borrower_name ?: 'Peminjam'));
        $this->assertStringContainsString("Riwayat_Hutang_{$cleanBorrower}_{$cleanNum}.pdf", $initialPdfRes->headers->get('Content-Disposition'));

        // 3. Make Partial Payment
        $pay1 = $this->actingAs($this->superAdmin)->post(route('debts.pay', $debt), [
            'amount' => 500000,
            'payment_method_id' => $this->cashMethod->id,
            'notes' => 'Cicilan 1 tunai',
        ]);
        $pay1->assertSessionHasNoErrors();

        $debt->refresh();
        $this->assertTrue(in_array($debt->status, ['partial', 'partially_paid']));
        $this->assertEquals(500000, (float) $debt->paid_amount);
        $this->assertEquals(1500000, (float) $debt->remaining_amount);

        // 4. Debt History PDF reflects partial repayment
        $partialPdfRes = $this->actingAs($this->superAdmin)->get(route('debts.pdf', $debt));
        $partialPdfRes->assertStatus(200);
        $this->assertEquals('application/pdf', $partialPdfRes->headers->get('Content-Type'));

        // 5. Debt History WA Preview endpoint returns updated status summary
        $waPreviewRes = $this->actingAs($this->superAdmin)->get(route('debts.wa-preview', $debt));
        $waPreviewRes->assertStatus(200);
        $waPreviewData = $waPreviewRes->json();
        $this->assertTrue($waPreviewData['success']);
        $this->assertStringContainsString($debt->debt_number, $waPreviewData['message']);
        $this->assertStringContainsString('1.500.000', $waPreviewData['message']);
        $this->assertStringContainsString('500.000', $waPreviewData['message']);
        $this->assertStringContainsString('SEBAGIAN TERBAYAR', $waPreviewData['message']);

        // 6. Final Payment to make it PAID
        $pay2 = $this->actingAs($this->superAdmin)->post(route('debts.pay', $debt), [
            'amount' => 1500000,
            'payment_method_id' => $this->cashMethod->id,
            'notes' => 'Pelunasan sisa hutang',
        ]);
        $pay2->assertSessionHasNoErrors();

        $debt->refresh();
        $this->assertEquals('paid', $debt->status);
        $this->assertEquals(0, (float) $debt->remaining_amount);
        $this->assertTrue($debt->isPaid());

        // 7. Final Debt History PDF reflecting fully paid status
        $finalPdfRes = $this->actingAs($this->superAdmin)->get(route('debts.pdf', $debt));
        $finalPdfRes->assertStatus(200);
        $this->assertEquals('application/pdf', $finalPdfRes->headers->get('Content-Type'));

        // Verify WA Preview reflects LUNAS
        $waPaidPreview = $this->actingAs($this->superAdmin)->get(route('debts.wa-preview', $debt));
        $waPaidPreview->assertStatus(200);
        $this->assertStringContainsString('LUNAS', $waPaidPreview->json('message'));
    }

    public function test_single_item_cancellation_reverses_stock_and_recalculates_totals(): void
    {
        $secondItem = ScrapItem::where('id', '!=', $this->besiItem->id)->first();
        $initialBesiStock = (float) \App\Models\StockBalance::where('scrap_item_id', $this->besiItem->id)->value('current_stock');
        $initialSecondStock = (float) \App\Models\StockBalance::where('scrap_item_id', $secondItem->id)->value('current_stock');

        // Create purchase with 2 items: 100 kg Besi and 50 kg Second Item
        $response = $this->actingAs($this->kasir)->post(route('purchases.store'), [
            'supplier_name' => 'Supplier Test Item Cancel',
            'supplier_phone' => '08123456789',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 100,
                    'tare_weight' => 0,
                    'price_per_kg' => 4000,
                ],
                [
                    'scrap_item_id' => $secondItem->id,
                    'gross_weight' => 50,
                    'tare_weight' => 0,
                    'price_per_kg' => 2000,
                ],
            ],
            'payment_method_id' => $this->cashMethod->id,
        ]);

        $response->assertSessionHasNoErrors();
        $purchase = \App\Models\PurchaseTransaction::latest('id')->first();
        $this->assertEquals(150, (float) $purchase->total_net_weight);
        $this->assertEquals(500000, (float) $purchase->final_amount);
        $this->assertCount(2, $purchase->items);

        $besiItem = $purchase->items()->where('scrap_item_id', $this->besiItem->id)->first();
        $cancelledItem = $purchase->items()->where('scrap_item_id', $secondItem->id)->first();

        // Cancel only the second item (50 kg)
        $cancelRes = $this->actingAs($this->superAdmin)->post(route('purchases.items.cancel', [$purchase, $cancelledItem]), [
            'cancel_reason' => 'Salah timbang, penjual bawa pulang barangnya',
        ]);

        $cancelRes->assertSessionHasNoErrors();
        $cancelRes->assertSessionHas('success');

        $cancelledItem->refresh();
        $this->assertTrue($cancelledItem->is_cancelled);
        $this->assertEquals('Salah timbang, penjual bawa pulang barangnya', $cancelledItem->cancel_reason);

        // Transaction remains active, but totals recalculated
        $purchase->refresh();
        $this->assertEquals('completed', $purchase->status);
        $this->assertEquals(100, (float) $purchase->total_net_weight);
        $this->assertEquals(400000, (float) $purchase->final_amount);

        // Stock for second item was reversed back, while Besi remains in stock
        $currentSecondStock = (float) \App\Models\StockBalance::where('scrap_item_id', $secondItem->id)->value('current_stock');
        $this->assertEquals($initialSecondStock, $currentSecondStock);

        $currentBesiStock = (float) \App\Models\StockBalance::where('scrap_item_id', $this->besiItem->id)->value('current_stock');
        $this->assertEquals($initialBesiStock + 100, $currentBesiStock);
    }
}

