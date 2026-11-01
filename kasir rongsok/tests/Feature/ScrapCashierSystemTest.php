<?php

namespace Tests\Feature;

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

    public function test_sale_deducts_stock_and_prevents_negative_stock(): void
    {
        $salesService = app(SalesTransactionService::class);
        $this->besiItem->refresh();
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

        // Cancel by Admin
        $cancelled = $service->cancelPurchase($tx, $this->admin, 'Salah timbang');
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
        // Kasir attempts to open User Management or Settings
        $response = $this->actingAs($this->kasir)->get('/users');
        $response->assertStatus(403);

        $response = $this->actingAs($this->kasir)->get('/settings');
        $response->assertStatus(403);

        // Super Admin can access Settings
        $response = $this->actingAs($this->superAdmin)->get('/settings');
        $response->assertStatus(200);
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
        $response = $this->actingAs($this->admin)->post('/salary-slips', [
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
        $response = $this->actingAs($this->admin)->post('/delivery-orders', [
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
    }

    public function test_create_pages_render_without_source_code_leak(): void
    {
        $response = $this->actingAs($this->admin)->get('/delivery-orders/create');
        $response->assertStatus(200);
        $response->assertSee('x-data="deliveryOrderForm()"', false);

        $responseSalary = $this->actingAs($this->admin)->get('/salary-slips/create');
        $responseSalary->assertStatus(200);
        $responseSalary->assertSee('x-data="salarySlipForm()"', false);
    }

    public function test_expense_can_be_stored_with_manual_category_name(): void
    {
        $uniqueCatName = 'Kategori Manual ' . uniqid();
        $response = $this->actingAs($this->admin)->post('/expenses', [
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
        $response = $this->actingAs($this->admin)->get('/reports/profit-loss');
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
        $response = $this->actingAs($this->admin)->get('/reports/purchases');
        $response->assertStatus(200);
        $response->assertSee('Daftar Harga Beli (PDF)');
        $response->assertSee(route('scrap-items.print-price-list'));

        // 2. Check Price List Print/PDF view renders item names and prices for sellers
        $priceListResponse = $this->actingAs($this->admin)->get('/scrap-items/print-price-list');
        $priceListResponse->assertStatus(200);
        $priceListResponse->assertSee('DAFTAR HARGA BELI RONGSOK');
        $priceListResponse->assertSee('Nama Jenis Barang Rongsok');
        $priceListResponse->assertSee('Harga Beli per Kg');
        $priceListResponse->assertSee('TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI');
    }
}

