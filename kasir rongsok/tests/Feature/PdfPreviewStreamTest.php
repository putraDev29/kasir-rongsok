<?php

namespace Tests\Feature;

use App\Models\Debt;
use App\Models\DeliveryOrder;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ItemCategory;
use App\Models\PaymentMethod;
use App\Models\PurchaseTransaction;
use App\Models\SalarySlip;
use App\Models\SalesTransaction;
use App\Models\ScrapItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfPreviewStreamTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_all_pdf_endpoints_preview_inline_by_default_and_support_download_param(): void
    {
        $supplier = Supplier::factory()->create();
        $cat = ItemCategory::factory()->create();
        $item = ScrapItem::factory()->create(['item_category_id' => $cat->id, 'current_stock' => 100]);
        $pm = PaymentMethod::factory()->create();

        // 1. Debt Agreement PDF
        $debt = Debt::factory()->create([
            'supplier_id' => $supplier->id,
            'user_id' => $this->superAdmin->id,
            'borrower_phone' => '081234567890',
        ]);
        $res = $this->actingAs($this->superAdmin)->get(route('debts.agreement-pdf', $debt));
        $res->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $res->headers->get('content-type'));
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // Test ?download=1 forces attachment
        $resDownload = $this->actingAs($this->superAdmin)->get(route('debts.agreement-pdf', [$debt, 'download' => 1]));
        $resDownload->assertStatus(200);
        $this->assertStringContainsString('attachment', $resDownload->headers->get('content-disposition'));

        // 2. Debt Batch PDF
        $res = $this->actingAs($this->superAdmin)->get(route('debts.batch-pdf'));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 3. Supplier Member Card PDF
        $res = $this->actingAs($this->superAdmin)->get(route('suppliers.member-card-pdf', $supplier));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 4. Purchase Batch PDF
        $res = $this->actingAs($this->superAdmin)->get(route('purchases.batch-pdf'));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 5. Salary Slip Single & Batch PDF
        $slip = SalarySlip::factory()->create([
            'employee_user_id' => $this->superAdmin->id,
            'created_by' => $this->superAdmin->id,
            'payment_method_id' => $pm->id,
        ]);
        $res = $this->actingAs($this->superAdmin)->get(route('salary-slips.pdf', $slip));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        $res = $this->actingAs($this->superAdmin)->get(route('salary-slips.batch-pdf'));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 6. Expense PDF
        $expCat = ExpenseCategory::factory()->create();
        Expense::factory()->create(['expense_category_id' => $expCat->id, 'user_id' => $this->superAdmin->id]);
        $res = $this->actingAs($this->superAdmin)->get(route('expenses.pdf'));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 7. Sales Invoice Single & Batch PDF
        $sale = SalesTransaction::factory()->create([
            'user_id' => $this->superAdmin->id,
            'payment_method_id' => $pm->id,
        ]);
        $res = $this->actingAs($this->superAdmin)->get(route('sales.pdf', $sale));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        $res = $this->actingAs($this->superAdmin)->get(route('sales.batch-pdf'));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 8. Delivery Order PDF
        $do = DeliveryOrder::factory()->create([
            'user_id' => $this->superAdmin->id,
            'sales_transaction_id' => $sale->id,
        ]);
        $res = $this->actingAs($this->superAdmin)->get(route('delivery-orders.pdf', $do));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 9. Scrap Item Price List PDF
        $res = $this->actingAs($this->superAdmin)->get(route('scrap-items.pdf-price-list'));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));
    }
}
