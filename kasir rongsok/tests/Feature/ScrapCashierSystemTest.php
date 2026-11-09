<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\DeliveryOrder;
use App\Models\Driver;
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
        // 1. Kasir is redirected from /dashboard directly to /purchases/create
        $this->actingAs($this->kasir)->get('/dashboard')->assertRedirect('/purchases/create');
        $this->actingAs($this->admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/dashboard')->assertStatus(200);

        // 2. Kasir and Admin can access Penerimaan Barang & Riwayat Pembelian
        $this->actingAs($this->kasir)->get('/purchases/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/purchases/create')->assertStatus(200);

        $this->actingAs($this->kasir)->get('/purchases')->assertStatus(200);
        $this->actingAs($this->admin)->get('/purchases')->assertStatus(200);

        // 3. Kasir and Admin can access Penjualan Partai & Surat Jalan
        $this->actingAs($this->kasir)->get('/sales')->assertStatus(200);
        $this->actingAs($this->kasir)->get('/sales/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/sales')->assertStatus(200);
        $this->actingAs($this->admin)->get('/sales/create')->assertStatus(200);

        $this->actingAs($this->kasir)->get('/delivery-orders')->assertStatus(200);
        $this->actingAs($this->kasir)->get('/delivery-orders/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/delivery-orders')->assertStatus(200);
        $this->actingAs($this->admin)->get('/delivery-orders/create')->assertStatus(200);

        // 4. Kasir and Admin are forbidden (403) from accessing Owner-only modules
        $restrictedUrls = [
            '/scrap-items',
            '/suppliers',
            '/debts',
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

        // 1. Kasir: Bebas edit harga beli berapapun (di atas master) -> BERHASIL
        $responseKasir = $this->actingAs($this->kasir)->post('/purchases', [
            'supplier_name' => 'Penjual Test Kasir Bebas Edit',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 50,
                    'tare_weight' => 0,
                    'price_per_kg' => $masterPrice + 2000, // BEBAS EDIT MELEBIHI HARGA MASTER
                ]
            ]
        ]);
        $responseKasir->assertSessionHas('success');

        // 2. Admin: Bebas edit harga beli berapapun -> BERHASIL
        $responseAdmin = $this->actingAs($this->admin)->post('/purchases', [
            'supplier_name' => 'Penjual Test Admin Bebas Edit',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 50,
                    'tare_weight' => 0,
                    'price_per_kg' => $masterPrice + 2000, // BEBAS EDIT MELEBIHI HARGA MASTER
                ]
            ]
        ]);
        $responseAdmin->assertSessionHas('success');

        // 3. Owner (Super Admin): Harga melebihi master -> BERHASIL
        $responseOwnerSuccess = $this->actingAs($this->superAdmin)->post('/purchases', [
            'supplier_name' => 'Penjual Test Owner Bebas Edit',
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 50,
                    'tare_weight' => 0,
                    'price_per_kg' => $masterPrice + 2000,
                ]
            ]
        ]);
        $responseOwnerSuccess->assertSessionHas('success');

        // 4. Kasir/Admin: Harga di bawah atau sama dengan harga master -> HARUS BERHASIL
        $responseKasirSuccess = $this->actingAs($this->kasir)->post('/purchases', [
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
        $responseKasirSuccess->assertSessionHas('success');
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
            'work_days' => 25,
            'daily_rate' => 120000,
            'basic_salary' => 3000000,
            'allowance_amount' => 500000,
            'bonus_amount' => 200000,
            'debt_deduction' => 200000,
            'other_deduction' => 50000,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('salary_slips', [
            'employee_name' => 'Karyawan Test Gudang',
            'work_days' => 25,
            'daily_rate' => 120000,
            'basic_salary' => 3000000,
            'total_income' => 3700000,
            'total_deduction' => 250000,
            'net_salary' => 3450000,
        ]);

        $slip = \App\Models\SalarySlip::where('employee_name', 'Karyawan Test Gudang')->latest('id')->first();
        $this->assertNotNull($slip);

        // Verify detail page contains breakdown and does NOT contain Preview/Unduh PDF button
        $showRes = $this->actingAs($this->superAdmin)->get("/salary-slips/{$slip->id}");
        $showRes->assertStatus(200);
        $showRes->assertSee('25 hari');
        $showRes->assertSee('120.000');
        $showRes->assertDontSee('Preview / Unduh PDF');

        // Verify single PDF stream has the breakdown and A4 page size
        $pdfRes = $this->actingAs($this->superAdmin)->get("/salary-slips/{$slip->id}/pdf");
        $pdfRes->assertStatus(200);

        // Verify index page does not have individual row PDF button
        $indexRes = $this->actingAs($this->superAdmin)->get('/salary-slips');
        $indexRes->assertStatus(200);
        $indexRes->assertDontSee('title="Preview / Unduh PDF Slip"', false);
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
        $response->assertSee('Rincian Sisa Stok Barang & Harga');
        $response->assertSee('Rincian Barang Masuk (Pembelian)');
        $response->assertSee('Rincian Barang Keluar (Penjualan)');
        $response->assertSee('Harga Beli (@ Rp/kg)');
        $response->assertSee('Harga Jual (@ Rp/kg)');
        $response->assertSee('Total Nilai Modal Stok');
        $response->assertSee('Rincian Slip Gaji Karyawan');
        $response->assertSee('Rincian Beban Operasional Gudang & Depo', false);
        $response->assertSee('ESTIMASI LABA BERSIH USAHA (NET PROFIT)');

        $printResponse = $this->actingAs($this->superAdmin)->get('/reports/profit-loss?format=print');
        $printResponse->assertStatus(200);
        $printResponse->assertSee('II. RINCIAN SISA STOK BARANG & HARGA DEPO');
        $printResponse->assertSee('III. RINCIAN BARANG MASUK (PEMBELIAN)');
        $printResponse->assertSee('IV. RINCIAN BARANG KELUAR (PENJUALAN PABRIK)');
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
        $printRes->assertSee('Cetak Invoice');

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
        $pickupPrint->assertSee('INVOICE');
        $pickupPrint->assertSee('Diambil Langsung Pembeli');
        $pickupPrint->assertDontSee('Sopir / Ekspedisi');

        // Check DO create page does NOT include pickup sale in dropdown
        $doCreate = $this->actingAs($this->superAdmin)->get(route('delivery-orders.create'));
        $doCreate->assertStatus(200);
        $doCreate->assertDontSee('Bpk. H. Syarif (Ambil Sendiri)');

        // 2. Delivery Sale: Input penjualan TIDAK langsung otomatis jadi surat jalan, status pending_delivery
        $deliveryRes = $this->actingAs($this->superAdmin)->post(route('sales.store'), [
            'delivery_type' => 'delivery',
            'buyer_name' => 'PT. Mega Steel Konstruksi',
            'buyer_phone' => '08138888222',
            'buyer_address' => 'Kawasan Industri Jababeka V',
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
        // Penjualan jangan langsung masuk di surat jalan
        $this->assertNull($deliverySale->delivery_order_id);

        // Surat Jalan dibuat sendiri dengan memilih data dari input penjualan yang tipenya pengiriman
        $doStoreRes = $this->actingAs($this->superAdmin)->post(route('delivery-orders.store'), [
            'sales_transaction_id' => $deliverySale->id,
            'delivery_date' => now()->toDateString(),
            'recipient_name' => $deliverySale->buyer_name,
            'recipient_address' => $deliverySale->buyer_address,
            'recipient_phone' => $deliverySale->buyer_phone,
            'driver_name' => 'Pak Joko Widodo',
            'driver_phone' => '0812345678',
            'vehicle_number' => 'B 9123 KAS',
            'vehicle_type' => 'Truk Fuso Engkel',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_name' => 'Besi Super Tebal',
                    'unit_type' => 'kg',
                    'weight' => 200,
                    'price_per_kg' => 5200,
                ],
            ],
        ]);
        $doStoreRes->assertSessionHasNoErrors();

        $deliverySale->refresh();
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

        // 3. Penjualan pengiriman dengan harga dan berat kosong: sebelum update status selesai HARUS input harga & berat
        $deliverySale2 = app(\App\Services\SalesTransactionService::class)->createSale([
            'delivery_type' => 'delivery',
            'buyer_name' => 'Pabrik Peleburan Jaya',
            'transaction_date' => now()->toDateString(),
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'weight' => 0, // saat pengiriman berat masih kosong
                    'sell_price_per_kg' => 0, // harga jual masih kosong
                ],
            ],
        ], $this->superAdmin);

        $this->assertEquals('pending_delivery', $deliverySale2->status);

        // Konfirmasi selesai tanpa input harga dan berat akan ditolak
        $failMarkRes = $this->actingAs($this->superAdmin)->post(route('sales.mark-delivered', $deliverySale2));
        $failMarkRes->assertSessionHas('error');
        $deliverySale2->refresh();
        $this->assertEquals('pending_delivery', $deliverySale2->status);

        // Konfirmasi selesai dengan input berat timbang pabrik dan harga jual
        $itemToUpdate = $deliverySale2->items->first();
        $saleMarkRes = $this->actingAs($this->superAdmin)->post(route('sales.mark-delivered', $deliverySale2), [
            'items' => [
                [
                    'id' => $itemToUpdate->id,
                    'scrap_item_id' => $itemToUpdate->scrap_item_id,
                    'weight' => 150,
                    'sell_price_per_kg' => 5500,
                ],
            ],
        ]);
        $saleMarkRes->assertSessionHasNoErrors();
        $deliverySale2->refresh();
        $this->assertEquals('completed', $deliverySale2->status);
        $this->assertEquals(150, (float) $deliverySale2->total_weight);
        $this->assertEquals(825000, (float) $deliverySale2->final_amount);
    }

    /**
     * Test Kasir creates sales as draft with sell_price disabled/0,
     * and Admin/Owner edits the draft to set sell_price and finalize.
     */
    public function test_kasir_creates_sale_as_draft_and_admin_finalizes_it(): void
    {
        // 1. Kasir creates sale without sell price (submitted as 0 or empty)
        $kasirSaleRes = $this->actingAs($this->kasir)->post(route('sales.store'), [
            'delivery_type' => 'pickup',
            'buyer_name' => 'Pabrik Peleburan Kasir Draft',
            'buyer_phone' => '081233445566',
            'transaction_date' => now()->toDateString(),
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_type_name' => 'Besi Super',
                    'weight' => 200,
                    'sell_price_per_kg' => 0,
                ],
            ],
        ]);

        $kasirSaleRes->assertSessionHasNoErrors();
        $kasirSaleRes->assertRedirect(route('sales.index'));

        $draftSale = \App\Models\SalesTransaction::where('buyer_name', 'Pabrik Peleburan Kasir Draft')->first();
        $this->assertNotNull($draftSale);
        $this->assertEquals('draft', $draftSale->status);
        $this->assertTrue($draftSale->isDraft());
        $this->assertEquals(0, (float) $draftSale->final_amount);

        // Kasir CANNOT access edit page
        $this->actingAs($this->kasir)->get(route('sales.edit', $draftSale))->assertStatus(403);

        // Admin CAN access edit page
        $adminEditRes = $this->actingAs($this->admin)->get(route('sales.edit', $draftSale));
        $adminEditRes->assertStatus(200);
        $adminEditRes->assertSee('Form Penetapan Harga Jual');

        // 2. Admin finalizes the draft sale by setting sell price
        $draftItem = $draftSale->items->first();
        $finalizeRes = $this->actingAs($this->admin)->put(route('sales.update', $draftSale), [
            'items' => [
                $draftItem->id => [
                    'sell_price_per_kg' => 6000,
                ],
            ],
        ]);

        $finalizeRes->assertSessionHasNoErrors();
        $finalizeRes->assertRedirect(route('sales.show', $draftSale));

        $draftSale->refresh();
        $this->assertEquals('completed', $draftSale->status); // pickup -> completed
        $this->assertFalse($draftSale->isDraft());
        $this->assertEquals(1200000, (float) $draftSale->final_amount); // 200kg * 6000

        $draftItem->refresh();
        $this->assertEquals(6000, (float) $draftItem->sell_price_per_kg);
        $this->assertEquals(1200000, (float) $draftItem->subtotal);

        // Once completed, accessing edit redirects to show with error
        $this->actingAs($this->admin)->get(route('sales.edit', $draftSale))->assertRedirect(route('sales.show', $draftSale));
    }

    /**
     * Test Admin can add new items to the draft sale when editing.
     */
    public function test_admin_can_add_new_item_when_editing_draft_sale(): void
    {
        $kasirSaleRes = $this->actingAs($this->kasir)->post(route('sales.store'), [
            'delivery_type' => 'pickup',
            'buyer_name' => 'Pabrik Peleburan Tambah Item',
            'transaction_date' => now()->toDateString(),
            'payment_method_id' => $this->cashMethod->id,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'weight' => 50,
                    'sell_price_per_kg' => 0,
                ],
            ],
        ]);
        $kasirSaleRes->assertSessionHasNoErrors();
        $draftSale = \App\Models\SalesTransaction::where('buyer_name', 'Pabrik Peleburan Tambah Item')->first();

        // Admin edits draft sale: fills price for Besi (50kg @ 6000) AND adds a second item
        $secondItem = \App\Models\ScrapItem::where('id', '!=', $this->besiItem->id)->first();
        // Ensure secondItem has stock
        $secondStock = \App\Models\StockBalance::firstOrCreate(['scrap_item_id' => $secondItem->id], ['current_stock' => 100, 'total_in' => 100, 'total_out' => 0]);
        if ($secondStock->current_stock < 50) {
            $secondStock->update(['current_stock' => 100]);
        }

        $draftItem = $draftSale->items->first();
        $updateRes = $this->actingAs($this->admin)->put(route('sales.update', $draftSale), [
            'buyer_name' => 'Pabrik Peleburan Tambah Item',
            'delivery_type' => 'pickup',
            'items' => [
                [
                    'id' => $draftItem->id,
                    'scrap_item_id' => $this->besiItem->id,
                    'weight' => 50,
                    'sell_price_per_kg' => 6000,
                ],
                [
                    'id' => null, // NEW ITEM added by Admin
                    'scrap_item_id' => $secondItem->id,
                    'weight' => 10,
                    'sell_price_per_kg' => 20000,
                ],
            ],
        ]);

        $updateRes->assertSessionHasNoErrors();
        $draftSale->refresh();

        $this->assertEquals('completed', $draftSale->status);
        $this->assertCount(2, $draftSale->items);
        // Besi 50*6000 = 300,000 + SecondItem 10*20000 = 200,000 = 500,000
        $this->assertEquals(500000, (float) $draftSale->final_amount);
        $this->assertEquals(60, (float) $draftSale->total_weight);
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
        $cleanBorrower = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($debt->borrower_name ?: 'Peminjam'));
        $this->assertStringContainsString("Riwayat_Hutang_{$cleanBorrower}.pdf", $initialPdfRes->headers->get('Content-Disposition'));

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

    public function test_receipt_shows_bank_account_number_and_name_under_payment_method(): void
    {
        $bcaMethod = PaymentMethod::where('code', 'transfer_bca')->first() ?? PaymentMethod::firstOrCreate(
            ['code' => 'transfer_bca'],
            ['name' => 'Transfer Bank BCA', 'bank_name' => 'BCA', 'account_number' => '8830123456', 'account_holder' => 'PT PENATA PRIMA JAYA', 'is_active' => true]
        );

        // 1. Penerimaan Barang dengan Metode Bank & Custom Rekening
        $purchaseService = app(\App\Services\PurchaseTransactionService::class);
        $purchase = $purchaseService->createPurchase([
            'supplier_name' => 'Pemasok Rekening Test',
            'payment_method_id' => $bcaMethod->id,
            'custom_bank' => 'BCA',
            'custom_account_no' => '9988776655',
            'custom_account_name' => 'H. Suwandi Rek Bank',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 20,
                    'tare_weight' => 0,
                    'price_per_kg' => (float) $this->besiItem->buy_price_per_kg,
                ]
            ]
        ], $this->superAdmin);

        $printRes = $this->actingAs($this->superAdmin)->get("/purchases/{$purchase->id}/print");
        $printRes->assertOk();
        $printRes->assertSee('Metode Bayar');
        $printRes->assertSee('No. Rekening');
        $printRes->assertSee('9988776655');
        $printRes->assertSee('Nama Rekening');
        $printRes->assertSee('H. Suwandi Rek Bank');

        // Cek juga pada batch print dan show
        $showRes = $this->actingAs($this->superAdmin)->get("/purchases/{$purchase->id}");
        $showRes->assertOk();
        $showRes->assertSee('9988776655');
        $showRes->assertSee('H. Suwandi Rek Bank');

        // 2. Penjualan Barang dengan Metode Bank
        $salesService = app(\App\Services\SalesTransactionService::class);
        $sale = $salesService->createSale([
            'buyer_name' => 'Pabrik Pembeli Rekening Test',
            'delivery_type' => \App\Models\SalesTransaction::DELIVERY_TYPE_PICKUP,
            'payment_method_id' => $bcaMethod->id,
            'custom_bank' => 'BCA',
            'custom_account_no' => '1122334455',
            'custom_account_name' => 'PT PENATA REKENING',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'weight' => 5,
                    'sell_price_per_kg' => 6500,
                ]
            ]
        ], $this->superAdmin);

        $salePrintRes = $this->actingAs($this->superAdmin)->get("/sales/{$sale->id}/print");
        $salePrintRes->assertOk();
        $salePrintRes->assertSee('INVOICE');
        $salePrintRes->assertDontSee('Metode Bayar');
        $salePrintRes->assertDontSee('No. Rekening');
    }

    public function test_user_management_supports_bank_details(): void
    {
        $uniqueEmail = 'operator.' . uniqid() . '@kasirrongsok.com';

        // 1. Create User with Bank Details
        $createRes = $this->actingAs($this->superAdmin)->post(route('users.store'), [
            'name' => 'Operator Baru Bank',
            'email' => $uniqueEmail,
            'password' => 'rahasia123',
            'role' => User::ROLE_KASIR,
            'phone' => '089911223344',
            'bank_name' => 'BCA',
            'bank_account_number' => '7766554433',
            'bank_account_name' => 'Operator Baru Bank',
        ]);

        $createRes->assertSessionHasNoErrors();
        $createRes->assertRedirect();

        $newUser = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('BCA', $newUser->bank_name);
        $this->assertEquals('7766554433', $newUser->bank_account_number);
        $this->assertEquals('Operator Baru Bank', $newUser->bank_account_name);
        $this->assertTrue($newUser->hasBankDetails());

        // 2. View in Users Index
        $indexRes = $this->actingAs($this->superAdmin)->get(route('users.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('Operator Baru Bank');
        $indexRes->assertSee('7766554433');
        $indexRes->assertSee('BCA');

        // 3. Update User Bank Details
        $updateRes = $this->actingAs($this->superAdmin)->put(route('users.update', $newUser), [
            'name' => 'Operator Baru Bank Updated',
            'email' => $uniqueEmail,
            'role' => User::ROLE_KASIR,
            'phone' => '089911223344',
            'bank_name' => 'Mandiri',
            'bank_account_number' => '1370011223344',
            'bank_account_name' => 'Operator Baru Updated',
        ]);

        $updateRes->assertSessionHasNoErrors();
        $newUser->refresh();
        $this->assertEquals('Mandiri', $newUser->bank_name);
        $this->assertEquals('1370011223344', $newUser->bank_account_number);
        $this->assertEquals('Operator Baru Updated', $newUser->bank_account_name);
    }

    public function test_supplier_management_supports_bank_details(): void
    {
        // 1. Create Supplier with Bank Details
        $createRes = $this->actingAs($this->superAdmin)->post(route('suppliers.store'), [
            'name' => 'Juragan Rongsok Bank Mandiri',
            'phone' => '081234567890',
            'bank_name' => 'BCA',
            'bank_account_number' => '8899001122',
            'bank_account_name' => 'Juragan Rongsok',
            'address' => 'Jl. Pengepul No. 10',
        ]);

        $createRes->assertSessionHasNoErrors();
        $supplier = Supplier::where('name', 'Juragan Rongsok Bank Mandiri')->firstOrFail();
        $this->assertEquals('BCA', $supplier->bank_name);
        $this->assertEquals('8899001122', $supplier->bank_account_number);
        $this->assertEquals('Juragan Rongsok', $supplier->bank_account_name);
        $this->assertTrue($supplier->hasBankDetails());

        // 2. View in Index Table
        $indexRes = $this->actingAs($this->superAdmin)->get(route('suppliers.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('BCA');
        $indexRes->assertSee('8899001122');

        // 3. Update Supplier Bank Details
        $updateRes = $this->actingAs($this->superAdmin)->put(route('suppliers.update', $supplier), [
            'name' => 'Juragan Rongsok Bank Mandiri',
            'phone' => '081234567890',
            'bank_name' => 'BRI',
            'bank_account_number' => '5544332211',
            'bank_account_name' => 'Juragan Rongsok Cabang',
        ]);

        $updateRes->assertSessionHasNoErrors();
        $supplier->refresh();
        $this->assertEquals('BRI', $supplier->bank_name);
        $this->assertEquals('5544332211', $supplier->bank_account_number);
        $this->assertEquals('Juragan Rongsok Cabang', $supplier->bank_account_name);
    }

    public function test_purchase_transaction_syncs_bank_details_to_supplier_and_user(): void
    {
        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'is_active' => true,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-TESTBANK',
            'name' => 'Pak Budi Scrap Logam',
            'phone' => '081399887766',
        ]);

        $item = ScrapItem::first();

        $postData = [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'scrap_item_id' => $item->id,
                    'gross_weight' => 20,
                    'tare_weight' => 0,
                    'price_per_kg' => (float) $item->buy_price_per_kg,
                ]
            ],
            'payment_method_id' => 1,
            'custom_bank' => 'Bank Mandiri',
            'custom_account_no' => '123000998877',
            'custom_account_name' => 'Pak Budi Scrap',
            'save_to_supplier_bank' => '1',
            'save_to_user_bank' => '1',
        ];

        $res = $this->actingAs($cashier)->post(route('purchases.store'), $postData);
        $res->assertSessionHasNoErrors();

        // Verify supplier got updated
        $supplier->refresh();
        $this->assertEquals('Bank Mandiri', $supplier->bank_name);
        $this->assertEquals('123000998877', $supplier->bank_account_number);
        $this->assertEquals('Pak Budi Scrap', $supplier->bank_account_name);

        // Verify cashier user got updated
        $cashier->refresh();
        $this->assertEquals('Bank Mandiri', $cashier->bank_name);
        $this->assertEquals('123000998877', $cashier->bank_account_number);
        $this->assertEquals('Pak Budi Scrap', $cashier->bank_account_name);
    }

    public function test_sales_transaction_syncs_bank_details_to_user(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
        ]);

        $item = ScrapItem::first();

        $postData = [
            'delivery_type' => 'pickup',
            'buyer_name' => 'Pabrik Peleburan Besi Baja',
            'buyer_phone' => '081299998888',
            'payment_method_id' => 1,
            'custom_bank' => 'Bank BCA',
            'custom_account_no' => '8877665544',
            'custom_account_name' => 'Kasir Rongsok Depo',
            'save_to_user_bank' => '1',
            'items' => [
                [
                    'scrap_item_id' => $item->id,
                    'weight' => 50,
                    'sell_price_per_kg' => (float) $item->sell_price_per_kg,
                ]
            ],
        ];

        $res = $this->actingAs($owner)->post(route('sales.store'), $postData);
        $res->assertSessionHasNoErrors();

        $owner->refresh();
        $this->assertEquals('Bank BCA', $owner->bank_name);
        $this->assertEquals('8877665544', $owner->bank_account_number);
        $this->assertEquals('Kasir Rongsok Depo', $owner->bank_account_name);
    }

    public function test_debt_creation_syncs_bank_details_to_supplier(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-DEBTBANK',
            'name' => 'Pengepul Binaan Depo',
            'phone' => '081288776655',
        ]);

        $postData = [
            'borrower_name' => $supplier->name,
            'borrower_phone' => $supplier->phone,
            'borrower_type' => 'supplier',
            'loan_category' => 'modal',
            'supplier_id' => $supplier->id,
            'date' => now()->format('Y-m-d'),
            'initial_amount' => 500000,
            'bank_name' => 'Bank BNI',
            'bank_account_number' => '0192837465',
            'bank_account_name' => 'Pengepul Binaan',
            'save_to_supplier_bank' => 1,
        ];

        $res = $this->actingAs($this->superAdmin)->post(route('debts.store'), $postData);
        $res->assertSessionHasNoErrors();

        $supplier->refresh();
        $this->assertEquals('Bank BNI', $supplier->bank_name);
        $this->assertEquals('0192837465', $supplier->bank_account_number);
        $this->assertEquals('Pengepul Binaan', $supplier->bank_account_name);
    }

    public function test_driver_management_supports_bank_details(): void
    {
        // 1. Create Driver with Bank Details
        $createRes = $this->actingAs($this->superAdmin)->post(route('drivers.store'), [
            'name' => 'Sopir Truk Budi Santoso',
            'phone' => '081233445566',
            'vehicle_number' => 'B 9988 KAS',
            'vehicle_type' => 'Truk Colt Diesel',
            'bank_name' => 'BCA',
            'bank_account_number' => '9988776655',
            'bank_account_name' => 'Budi Santoso',
            'address' => 'Pangkalan Truk Karawang',
        ]);

        $createRes->assertSessionHasNoErrors();
        $driver = Driver::where('name', 'Sopir Truk Budi Santoso')->firstOrFail();
        $this->assertEquals('BCA', $driver->bank_name);
        $this->assertEquals('9988776655', $driver->bank_account_number);
        $this->assertEquals('Budi Santoso', $driver->bank_account_name);
        $this->assertEquals('B 9988 KAS', $driver->vehicle_number);
        $this->assertTrue($driver->hasBankDetails());

        // 2. View in Index Table
        $indexRes = $this->actingAs($this->superAdmin)->get(route('drivers.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('Sopir Truk Budi Santoso');
        $indexRes->assertSee('9988776655');

        // 3. Update Driver Bank Details
        $updateRes = $this->actingAs($this->superAdmin)->put(route('drivers.update', $driver), [
            'name' => 'Sopir Truk Budi Santoso Updated',
            'phone' => '081233445566',
            'vehicle_number' => 'B 9988 KAS',
            'vehicle_type' => 'Truk Fuso',
            'bank_name' => 'BRI',
            'bank_account_number' => '1122334455',
            'bank_account_name' => 'Budi Santoso Ekspedisi',
        ]);

        $updateRes->assertSessionHasNoErrors();
        $driver->refresh();
        $this->assertEquals('BRI', $driver->bank_name);
        $this->assertEquals('1122334455', $driver->bank_account_number);
        $this->assertEquals('Budi Santoso Ekspedisi', $driver->bank_account_name);
        $this->assertEquals('Truk Fuso', $driver->vehicle_type);
    }

    public function test_delivery_order_creation_syncs_driver_bank_details(): void
    {
        $postData = [
            'delivery_date' => now()->format('Y-m-d'),
            'recipient_name' => 'PT. Krakatau Steelindo',
            'recipient_address' => 'Cilegon, Banten',
            'recipient_phone' => '0819998877',
            'driver_name' => 'Pak Ahmad Fuso',
            'driver_phone' => '081255667788',
            'vehicle_number' => 'B 7788 FSO',
            'vehicle_type' => 'Truk Fuso',
            'driver_bank_name' => 'Mandiri',
            'driver_bank_account_number' => '137000889900',
            'driver_bank_account_name' => 'Ahmad Ekspedisi',
            'save_to_driver_bank' => 1,
            'shipping_cost' => 1500000,
            'loading_cost' => 300000,
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_name' => $this->besiItem->name,
                    'unit_type' => 'kg',
                    'weight' => 1200,
                    'price_per_kg' => 5000,
                    'subtotal' => 6000000,
                ]
            ],
        ];

        $res = $this->actingAs($this->superAdmin)->post(route('delivery-orders.store'), $postData);
        $res->assertSessionHasNoErrors();

        // Verify driver was created in drivers table
        $driver = Driver::where('name', 'Pak Ahmad Fuso')->firstOrFail();
        $this->assertEquals('Mandiri', $driver->bank_name);
        $this->assertEquals('137000889900', $driver->bank_account_number);
        $this->assertEquals('Ahmad Ekspedisi', $driver->bank_account_name);

        // Verify Delivery Order
        $order = DeliveryOrder::where('recipient_name', 'PT. Krakatau Steelindo')->firstOrFail();
        $this->assertEquals($driver->id, $order->driver_id);
        $this->assertEquals('Mandiri', $order->driver_bank_name);
        $this->assertEquals('137000889900', $order->driver_bank_account_number);
        $this->assertEquals('Ahmad Ekspedisi', $order->driver_bank_account_name);
        $this->assertTrue($order->hasDriverBankDetails());

        // Verify PDF output contains bank details
        $pdfRes = $this->actingAs($this->superAdmin)->get(route('delivery-orders.pdf', $order));
        $pdfRes->assertOk();
    }

    public function test_bank_account_remains_empty_without_default_and_populates_only_when_entity_has_bank(): void
    {
        // 1. Transaction without bank details must return null (no default fake account or company bank fallback)
        $purchaseService = app(\App\Services\PurchaseTransactionService::class);
        $cashierNoBank = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
        ]);
        $supplierNoBank = Supplier::create([
            'code' => 'SPL-NOBANK',
            'name' => 'Supplier Tanpa Bank',
            'phone' => '0899999999',
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
            'type' => 'lapak',
        ]);

        $purchase = $purchaseService->createPurchase([
            'supplier_id' => $supplierNoBank->id,
            'supplier_name' => $supplierNoBank->name,
            'payment_method_id' => $this->cashMethod->id,
            'custom_bank' => '',
            'custom_account_no' => '',
            'custom_account_name' => '',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 10,
                    'tare_weight' => 0,
                    'price_per_kg' => (float) $this->besiItem->buy_price_per_kg,
                ]
            ]
        ], $cashierNoBank);

        $this->assertNull($purchase->getBankAccountNumber());
        $this->assertNull($purchase->getBankAccountName());

        // 2. Transaction with supplier that has bank details returns supplier bank
        $supplierWithBank = Supplier::create([
            'code' => 'SPL-WITHBANK',
            'name' => 'Supplier Ber-Rekening',
            'phone' => '0888888888',
            'bank_name' => 'BCA',
            'bank_account_number' => '5544332211',
            'bank_account_name' => 'Bambang Rekening',
            'type' => 'lapak',
        ]);

        $purchaseWithSupplierBank = $purchaseService->createPurchase([
            'supplier_id' => $supplierWithBank->id,
            'supplier_name' => $supplierWithBank->name,
            'payment_method_id' => $this->cashMethod->id,
            'custom_bank' => '',
            'custom_account_no' => '',
            'custom_account_name' => '',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 10,
                    'tare_weight' => 0,
                    'price_per_kg' => (float) $this->besiItem->buy_price_per_kg,
                ]
            ]
        ], $cashierNoBank);

        $this->assertEquals('5544332211', $purchaseWithSupplierBank->getBankAccountNumber());
        $this->assertEquals('Bambang Rekening', $purchaseWithSupplierBank->getBankAccountName());
        $this->assertEquals('BCA', $purchaseWithSupplierBank->getBankName());

        // 3. Driver without bank hasDriverBankDetails returns false
        $driverNoBank = Driver::create([
            'name' => 'Driver Tanpa Bank',
            'code' => 'DRV-NB-001',
            'vehicle_number' => 'B 1000 NB',
            'vehicle_type' => 'Pick Up',
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
        ]);
        $this->assertFalse($driverNoBank->hasBankDetails());
        $this->assertNull($driverNoBank->bankDisplay());
    }

    public function test_create_purchase_with_new_payment_method_and_bank_details(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $service = app(PurchaseTransactionService::class);

        // Test creating purchase with a completely new payment method and bank details
        $trx = $service->createPurchase([
            'supplier_name' => 'Supplier Baru Testing',
            'payment_method_id' => '__new__',
            'new_payment_method_name' => 'QRIS Bank Jago',
            'custom_bank' => 'Bank Jago',
            'custom_account_no' => '1029384756',
            'custom_account_name' => 'Depo Rongsok Jaya',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 20,
                    'tare_weight' => 0,
                    'price_per_kg' => (float) $this->besiItem->buy_price_per_kg,
                ]
            ]
        ], $cashier);

        // Verify that PaymentMethod was created in the database with bank fields
        $pm = PaymentMethod::where('name', 'QRIS Bank Jago')->first();
        $this->assertNotNull($pm);
        $this->assertEquals('Bank Jago', $pm->bank_name);
        $this->assertEquals('1029384756', $pm->account_number);
        $this->assertEquals('Depo Rongsok Jaya', $pm->account_holder);
        $this->assertEquals($pm->id, $trx->payment_method_id);
        $this->assertStringContainsString('Bank Jago', $trx->bank_info);
        $this->assertStringContainsString('1029384756', $trx->bank_info);
        $this->assertStringContainsString('Depo Rongsok Jaya', $trx->bank_info);
    }

    public function test_whatsapp_captions_strip_comma_zero_zero_for_whole_numbers(): void
    {
        // 1. Direct helper test
        $raw = "Total: 10,00 kg (Kotor: 15,00 kg - Pot: 0,00 kg). Subtotal: Rp 50.000,00. Pcs: 5,00 pcs.";
        $cleaned = \App\Services\FormatHelper::cleanCaption($raw);
        $this->assertEquals("Total: 10 kg (Kotor: 15 kg - Pot: 0 kg). Subtotal: Rp 50.000. Pcs: 5 pcs.", $cleaned);

        // Keep real decimals intact
        $rawDecimal = "Berat: 12,5 kg dan 10,75 kg";
        $this->assertEquals("Berat: 12,5 kg dan 10,75 kg", \App\Services\FormatHelper::cleanCaption($rawDecimal));

        // 2. Test Purchase Receipt WhatsApp Message builder
        $cashier = User::factory()->create(['role' => 'cashier']);
        $service = app(PurchaseTransactionService::class);
        $trx = $service->createPurchase([
            'supplier_name' => 'Pak Budi Supplier',
            'supplier_phone' => '08123456789',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 50,
                    'tare_weight' => 0,
                    'price_per_kg' => (float) $this->besiItem->buy_price_per_kg,
                ]
            ]
        ], $cashier);

        $waService = app(\App\Services\WhatsAppGatewayService::class);
        $message = $waService->buildPurchaseReceiptMessage($trx);

        // Ensure no ",00" or ".00" appears in the message
        $this->assertStringNotContainsString(',00', $message);
        $this->assertStringNotContainsString('.00 kg', $message);
        $this->assertStringContainsString('50 kg', $message);

        // 3. Test Preview endpoint
        $this->actingAs($cashier);
        $res = $this->getJson(route('whatsapp.purchases.preview', $trx));
        $res->assertOk();
        $this->assertStringNotContainsString(',00', $res->json('message'));
        $this->assertEquals('50', $res->json('receipt_data.totalNetWeight'));
        $this->assertEquals('50', $res->json('receipt_data.totalGrossWeight'));
        $this->assertEquals('0', $res->json('receipt_data.totalTareWeight'));
    }

    public function test_purchase_preview_pdf_data_contains_bank_info_for_bank_payments(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $bankMethod = PaymentMethod::firstOrCreate(
            ['code' => 'transfer_bca'],
            [
                'name' => 'Transfer Bank BCA',
                'is_active' => true,
                'bank_name' => 'BCA',
            ]
        );

        $service = app(PurchaseTransactionService::class);
        $trx = $service->createPurchase([
            'supplier_name' => 'Totong Test',
            'supplier_phone' => '0870707077777',
            'payment_method_id' => $bankMethod->id,
            'bank_info' => 'BCA - Rek: 777777777 - a/n Totong',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'gross_weight' => 35,
                    'tare_weight' => 0,
                    'price_per_kg' => (float) $this->besiItem->buy_price_per_kg,
                ]
            ]
        ], $cashier);

        $this->actingAs($cashier);
        $res = $this->getJson(route('whatsapp.purchases.preview', $trx));
        $res->assertOk();

        $receiptData = $res->json('receipt_data');
        $this->assertTrue($receiptData['isBankPayment']);
        $this->assertEquals('777777777', $receiptData['bankAccountNumber']);
        $this->assertEquals('Totong', $receiptData['bankAccountName']);
        $this->assertEquals('BCA', $receiptData['bankName']);
        $this->assertEquals('BCA - Rek: 777777777 - a/n Totong', $receiptData['bankInfo']);
    }

    public function test_non_bank_sales_does_not_show_bank_account_in_detail_pdf_and_nota(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $item = ScrapItem::first();

        // 1. Buat Penjualan dengan Metode Tunai (Cash) tapi terisi data rekening di form request
        $salesService = app(SalesTransactionService::class);
        $cashSale = $salesService->createSale([
            'delivery_type' => 'pickup',
            'buyer_name' => 'PT. Pabrik Logam Tunai',
            'buyer_phone' => '081234567890',
            'payment_method_id' => $this->cashMethod->id,
            'custom_bank' => 'BRI',
            'custom_account_no' => '012301054321508',
            'custom_account_name' => 'Rian Pratama',
            'items' => [
                [
                    'scrap_item_id' => $item->id,
                    'weight' => 20,
                    'sell_price_per_kg' => 15000,
                ]
            ],
        ], $admin);

        // Pastikan model sales mendeteksi bukan bank payment
        $this->assertFalse($cashSale->isBankPayment());
        $this->assertNull($cashSale->getBankAccountNumber());
        $this->assertNull($cashSale->getBankAccountName());
        $this->assertNull($cashSale->bank_info);

        $this->actingAs($admin);

        // Cek halaman preview detail (sales.show): tidak ada info rekening bank
        $detailRes = $this->get(route('sales.show', $cashSale));
        $detailRes->assertOk();
        $detailRes->assertDontSee('012301054321508');
        $detailRes->assertDontSee('Rian Pratama');
        $detailRes->assertDontSee('No. Rek:');

        // Cek halaman cetak nota (sales.print): tidak ada info rekening bank
        $printRes = $this->get(route('sales.print', $cashSale));
        $printRes->assertOk();
        $printRes->assertDontSee('012301054321508');
        $printRes->assertDontSee('Rian Pratama');
        $printRes->assertDontSee('No. Rek:');

        // Cek preview PDF invoice (sales.pdf): tidak ada info rekening bank
        $pdfHtml = view('sales.invoice_pdf', [
            'sale' => $cashSale,
            'settings' => [],
        ])->render();
        $this->assertStringNotContainsString('012301054321508', $pdfHtml);
        $this->assertStringNotContainsString('Rian Pratama', $pdfHtml);
        $this->assertStringNotContainsString('No. Rek', $pdfHtml);

        // 2. Bandingkan dengan Penjualan Metode Bank Transfer
        $bankMethod = PaymentMethod::where('code', 'transfer_bca')->first() ?? PaymentMethod::firstOrCreate(
            ['code' => 'transfer_bca'],
            ['name' => 'Transfer Bank BCA', 'bank_name' => 'BCA', 'account_number' => '8830123456', 'account_holder' => 'PT PENATA PRIMA JAYA', 'is_active' => true]
        );

        $bankSale = $salesService->createSale([
            'delivery_type' => 'pickup',
            'buyer_name' => 'PT. Pabrik Transfer Bank',
            'buyer_phone' => '081234567891',
            'payment_method_id' => $bankMethod->id,
            'custom_bank' => 'BCA',
            'custom_account_no' => '9988776655',
            'custom_account_name' => 'PT Penata Prima',
            'items' => [
                [
                    'scrap_item_id' => $item->id,
                    'weight' => 25,
                    'sell_price_per_kg' => 15000,
                ]
            ],
        ], $admin);

        $this->assertTrue($bankSale->isBankPayment());
        $this->assertEquals('9988776655', $bankSale->getBankAccountNumber());
        $this->assertEquals('PT Penata Prima', $bankSale->getBankAccountName());

        $bankDetailRes = $this->get(route('sales.show', $bankSale));
        $bankDetailRes->assertOk();
        $bankDetailRes->assertSee('9988776655');
        $bankDetailRes->assertSee('PT Penata Prima');

        $bankPrintRes = $this->get(route('sales.print', $bankSale));
        $bankPrintRes->assertOk();
        $bankPrintRes->assertSee('9988776655');
        $bankPrintRes->assertSee('PT Penata Prima');

        $bankPdfHtml = view('sales.invoice_pdf', [
            'sale' => $bankSale,
            'settings' => [],
        ])->render();
        $this->assertStringContainsString('9988776655', $bankPdfHtml);
        $this->assertStringContainsString('PT Penata Prima', $bankPdfHtml);
    }

    public function test_cashier_and_admin_can_preview_and_send_member_card_wa(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-MBR-01',
            'member_number' => 'MBR-2026-001',
            'name' => 'Pak Budi Pemasok',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $cashier = User::factory()->create([
            'role' => User::ROLE_KASIR,
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        // 1. Cek halaman penerimaan barang memiliki tombol cetak & kirim wa kartu member
        $createRes = $this->get(route('purchases.create'));
        $createRes->assertOk();
        $createRes->assertSee('openMemberCardWa()', false);
        $createRes->assertSee('Cetak Kartu');

        // 2. Kasir dapat mengakses tampilan kartu member
        $cardRes = $this->get(route('suppliers.member-card', $supplier));
        $cardRes->assertOk();
        $cardRes->assertSee('MBR-2026-001');
        $cardRes->assertSee('Pak Budi Pemasok');

        // 3. Kasir dapat mengakses PDF kartu member
        $pdfRes = $this->get(route('suppliers.member-card-pdf', $supplier));
        $pdfRes->assertOk();

        // 4. Kasir dapat mengakses preview WhatsApp kartu member (JSON)
        $previewRes = $this->getJson(route('suppliers.member-card-wa-preview', $supplier));
        $previewRes->assertOk();
        $previewRes->assertJsonFragment([
            'phone' => '081234567890',
        ]);
        $this->assertStringContainsString('MBR-2026-001', $previewRes->json('message'));

        // 5. GET ke member-card-wa redirect dengan aman ke kartu member
        $getWaRes = $this->get(route('suppliers.member-card-wa', $supplier));
        $getWaRes->assertRedirect(route('suppliers.member-card', $supplier));

        // 6. Kasir dapat mengirim kartu member via WhatsApp (JSON POST)
        $postWaRes = $this->postJson(route('suppliers.member-card-wa', $supplier), [
            'phone' => '081234567890',
            'send_pdf' => false,
            'message' => 'Halo ini kartu member Anda',
        ]);
        $postWaRes->assertOk();
    }

    public function test_operational_costs_can_be_zero_or_dash_and_weight_or_price_can_be_empty(): void
    {
        // 1. Buat stock awal besi
        $this->besiItem->stockBalance->update(['current_stock' => 100]);

        // 2. Sales dengan shipping_cost = '-', loading_cost = '0', weight kosong, sell_price_per_kg kosong
        $salesRes = $this->actingAs($this->kasir)->post('/sales', [
            'buyer_name' => 'PT. Pabrik Mitra Test',
            'delivery_type' => 'delivery',
            'shipping_cost' => '-',
            'loading_cost' => '0',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'unit_type' => 'kg',
                    'weight' => null,
                    'sell_price_per_kg' => null,
                ]
            ]
        ]);
        $salesRes->assertSessionHas('success');

        $sale = \App\Models\SalesTransaction::latest()->first();
        $this->assertNotNull($sale);
        $this->assertEquals(0, (float) ($sale->deliveryOrder?->shipping_cost ?? 0));
        $this->assertEquals(0, (float) ($sale->deliveryOrder?->loading_cost ?? 0));

        // 3. Delivery Order dengan shipping_cost = '-', weight = null
        $doRes = $this->actingAs($this->admin)->post('/delivery-orders', [
            'delivery_date' => now()->toDateString(),
            'recipient_name' => 'PT. Logam Nusantara Jaya',
            'driver_name' => 'Sopir Budi',
            'vehicle_number' => 'B 9999 XYZ',
            'shipping_cost' => '-',
            'loading_cost' => '-',
            'items' => [
                [
                    'scrap_item_id' => $this->besiItem->id,
                    'item_name' => $this->besiItem->name,
                    'unit_type' => 'kg',
                    'weight' => null,
                    'package_qty' => 5,
                ]
            ]
        ]);
        $doRes->assertSessionHas('success');
        $do = \App\Models\DeliveryOrder::latest()->first();
        $this->assertEquals(0, (float) $do->shipping_cost);
        $this->assertEquals(0, (float) $do->loading_cost);
    }

    public function test_delivery_order_views_exclude_costs_and_sales_views_include_driver_and_costs(): void
    {
        $this->besiItem->stockBalance->update(['current_stock' => 500]);

        // Buat DO dengan ongkos
        $do = \App\Models\DeliveryOrder::create([
            'delivery_number' => 'SJ-TEST-001',
            'delivery_date' => now(),
            'recipient_name' => 'PT. Pabrik Besi Baja',
            'driver_name' => 'Pak Supir Anto',
            'driver_phone' => '08987654321',
            'driver_bank_account_number' => '1122334455',
            'vehicle_number' => 'B 1111 AAA',
            'vehicle_type' => 'Truk Fuso',
            'shipping_cost' => 350000,
            'loading_cost' => 150000,
            'total_weight' => 200,
            'total_packages' => 2,
            'status' => 'delivered',
            'user_id' => $this->admin->id,
        ]);

        $sale = \App\Models\SalesTransaction::create([
            'invoice_number' => 'PPJ-JL-XI-26-0099',
            'buyer_name' => 'PT. Pabrik Besi Baja',
            'delivery_type' => 'delivery',
            'delivery_order_id' => $do->id,
            'user_id' => $this->kasir->id,
            'payment_method_id' => $this->cashMethod->id,
            'transaction_date' => now(),
            'total_weight' => 200,
            'subtotal_amount' => 1000000,
            'final_amount' => 1000000,
            'status' => 'completed',
        ]);

        // 1. Sales detail view: Menampilkan detail supir dan ongkos armada
        $saleRes = $this->actingAs($this->admin)->get(route('sales.show', $sale));
        $saleRes->assertOk();
        $saleRes->assertSee('Pak Supir Anto');
        $saleRes->assertSee('350.000');
        $saleRes->assertSee('150.000');

        // 2. Sales PDF view: Menampilkan detail supir dan biaya kirim
        $salePdfRes = $this->actingAs($this->admin)->get(route('sales.pdf', $sale));
        $salePdfRes->assertOk();

        // 3. Delivery Order show & print views: HAPUS biaya operasional & rekening supir
        $doRes = $this->actingAs($this->admin)->get(route('delivery-orders.show', $do));
        $doRes->assertOk();
        $doRes->assertDontSee('Biaya Operasional Pengiriman (Tercatat di Kas Operasional)');
        $doRes->assertDontSee('Rekening Bank Sopir (Pencairan Ongkos):');

        $doPrintRes = $this->actingAs($this->admin)->get(route('delivery-orders.print', $do));
        $doPrintRes->assertOk();
        $doPrintRes->assertDontSee('Biaya Operasional Pengiriman (Arsip Owner/Gudang):');
    }

    public function test_salary_slip_payment_method_bank_details_and_pdf_clean_labels(): void
    {
        // 1. Submit slip with __new__ payment method, bank details, and Rupiah-masked formatted inputs
        $response = $this->actingAs($this->superAdmin)->post('/salary-slips', [
            'employee_name' => 'Karyawan Bank Test',
            'period_month' => 'November 2026',
            'payment_date' => now()->toDateString(),
            'payment_method_id' => '__new__',
            'new_payment_method_name' => 'Transfer Bank Permata',
            'bank_name' => 'Bank Permata',
            'bank_account_number' => '987654321',
            'bank_account_name' => 'Karyawan Permata Rekening',
            'work_days' => '20',
            'daily_rate' => 'Rp 120.000',
            'basic_salary' => 'Rp 2.400.000',
            'allowance_amount' => 'Rp 100.000',
            'bonus_amount' => 'Rp 50.000',
            'debt_deduction' => 'Rp 50.000',
            'other_deduction' => 'Rp 0',
        ]);

        $response->assertSessionHas('success');

        // Verify Payment Method created
        $this->assertDatabaseHas('payment_methods', [
            'name' => 'Transfer Bank Permata',
        ]);

        // Verify Salary Slip created with bank details and clean numbers
        $slip = \App\Models\SalarySlip::where('employee_name', 'Karyawan Bank Test')->latest('id')->first();
        $this->assertNotNull($slip);
        $this->assertEquals('Bank Permata', $slip->bank_name);
        $this->assertEquals('987654321', $slip->bank_account_number);
        $this->assertEquals(20, $slip->work_days);
        $this->assertEquals(120000, $slip->daily_rate);
        $this->assertEquals(2400000, $slip->basic_salary);
        $this->assertEquals(2500000, $slip->net_salary);

        // 2. Verify Single PDF view clean labels (no "A." / "B." / "(NETTO = A - B)")
        $pdfRes = $this->actingAs($this->superAdmin)->get(route('salary-slips.pdf', $slip));
        $pdfRes->assertOk();
        $pdfHtml = view('salary_slips.pdf', [
            'salarySlip' => $slip,
            'settings' => [],
        ])->render();

        $this->assertStringNotContainsString('A. Penghasilan', $pdfHtml);
        $this->assertStringNotContainsString('B. Potongan', $pdfHtml);
        $this->assertStringNotContainsString('(A - B)', $pdfHtml);
        $this->assertStringNotContainsString('(NETTO = A - B)', $pdfHtml);
        $this->assertStringContainsString('Penghasilan / Penerimaan', $pdfHtml);
        $this->assertStringContainsString('Potongan Gaji &amp; Kasbon', $pdfHtml);
        $this->assertStringContainsString('GAJI BERSIH DITERIMA', $pdfHtml);
        $this->assertStringContainsString('Bank Permata', $pdfHtml);
        $this->assertStringContainsString('987654321', $pdfHtml);

        // 3. Verify Print view clean labels
        $printRes = $this->actingAs($this->superAdmin)->get(route('salary-slips.print', $slip));
        $printRes->assertOk();
        $printRes->assertDontSee('A. Penghasilan');
        $printRes->assertDontSee('B. Potongan');
        $printRes->assertDontSee('(A - B)');
        $printRes->assertDontSee('(NETTO = A - B)');
        $printRes->assertSee('Penghasilan / Penerimaan');
        $printRes->assertSee('Potongan Gaji');
        $printRes->assertSee('Gaji Bersih Diterima');
        $printRes->assertSee('Bank Permata');
        $printRes->assertSee('987654321');

        // 4. Verify Batch PDF is Landscape and contains nowrap single-line headers
        $batchPdfRes = $this->actingAs($this->superAdmin)->get(route('salary-slips.batch-pdf'));
        $batchPdfRes->assertOk();
        $batchHtml = view('salary_slips.batch_pdf', [
            'slips' => collect([$slip]),
            'settings' => [],
            'period' => 'November 2026',
            'displayPeriod' => 'November 2026',
            'totalNetSalary' => 2500000,
        ])->render();
        $this->assertStringContainsString('size: a4 landscape', $batchHtml);
        $this->assertStringContainsString('white-space: nowrap', $batchHtml);
        $this->assertStringContainsString('page-break-inside: avoid', $batchHtml);
    }
}

