<?php

namespace Tests\Feature;

use App\Models\Debt;
use App\Models\DeliveryOrder;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\SalarySlip;
use App\Models\SalesTransaction;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PdfPreviewStreamTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
    }

    public function test_all_pdf_endpoints_preview_inline_by_default_and_support_download_param(): void
    {
        $supplier = Supplier::first() ?: Supplier::create([
            'code' => 'SPL-TEST',
            'name' => 'Supplier Test PDF',
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 1',
            'type' => 'lapak',
        ]);

        $pm = PaymentMethod::where('code', 'cash')->first() ?: PaymentMethod::first();

        // 1. Debt Agreement PDF & Debt History PDF
        $debt = Debt::first() ?: Debt::create([
            'debt_number' => 'DBT-TEST-001',
            'borrower_name' => 'Peminjam Test',
            'borrower_phone' => '081234567890',
            'borrower_type' => 'supplier',
            'supplier_id' => $supplier->id,
            'amount' => 500000,
            'remaining_amount' => 500000,
            'date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(30),
            'status' => 'unpaid',
            'user_id' => $this->superAdmin->id,
            'agreement_number' => 'SPH/TEST/001',
        ]);

        $res = $this->actingAs($this->superAdmin)->get(route('debts.agreement-pdf', $debt));
        $res->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $res->headers->get('content-type'));
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // Test ?download=1 forces attachment
        $resDownload = $this->actingAs($this->superAdmin)->get(route('debts.agreement-pdf', [$debt, 'download' => 1]));
        $resDownload->assertStatus(200);
        $this->assertStringContainsString('attachment', $resDownload->headers->get('content-disposition'));

        // 1b. Debt History PDF (sesuai status saat itu & nama file peminjam)
        $cleanBorrower = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($debt->borrower_name ?: 'Peminjam'));
        $cleanBorrower = trim(preg_replace('/_+/', '_', $cleanBorrower), '_');
        $resHistoryPdf = $this->actingAs($this->superAdmin)->get(route('debts.pdf', $debt));
        $resHistoryPdf->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $resHistoryPdf->headers->get('content-type'));
        $this->assertStringContainsString('inline', $resHistoryPdf->headers->get('content-disposition'));
        $this->assertStringContainsString("Riwayat_Hutang_{$cleanBorrower}.pdf", $resHistoryPdf->headers->get('content-disposition'));

        $resHistoryPdfDownload = $this->actingAs($this->superAdmin)->get(route('debts.pdf', [$debt, 'download' => 1]));
        $resHistoryPdfDownload->assertStatus(200);
        $this->assertStringContainsString('attachment', $resHistoryPdfDownload->headers->get('content-disposition'));
        $this->assertStringContainsString("Riwayat_Hutang_{$cleanBorrower}.pdf", $resHistoryPdfDownload->headers->get('content-disposition'));

        // 1c. Debt History WA Preview
        $resWaPreview = $this->actingAs($this->superAdmin)->get(route('debts.wa-preview', $debt));
        $resWaPreview->assertStatus(200);
        $resWaPreview->assertJson(['success' => true]);
        $this->assertEquals("Riwayat_Hutang_{$cleanBorrower}.pdf", $resWaPreview->json('pdf_filename'));

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
        $slip = SalarySlip::first() ?: SalarySlip::create([
            'slip_number' => 'SLIP-TEST-001',
            'employee_user_id' => $this->superAdmin->id,
            'employee_name' => $this->superAdmin->name,
            'period_month' => date('Y-m'),
            'payment_date' => Carbon::today(),
            'base_salary' => 3000000,
            'allowances' => 0,
            'deductions' => 0,
            'net_salary' => 3000000,
            'payment_method_id' => $pm->id,
            'status' => 'paid',
            'created_by' => $this->superAdmin->id,
        ]);
        $res = $this->actingAs($this->superAdmin)->get(route('salary-slips.pdf', $slip));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        $res = $this->actingAs($this->superAdmin)->get(route('salary-slips.batch-pdf'));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 6. Expense PDF
        $expCat = ExpenseCategory::first() ?: ExpenseCategory::create(['name' => 'Operasional Test']);
        Expense::create([
            'expense_number' => 'EXP-TEST-001',
            'expense_category_id' => $expCat->id,
            'user_id' => $this->superAdmin->id,
            'amount' => 50000,
            'expense_date' => Carbon::today(),
            'description' => 'Test Biaya',
        ]);
        $res = $this->actingAs($this->superAdmin)->get(route('expenses.pdf'));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 7. Sales Invoice Single & Batch PDF
        $sale = SalesTransaction::first() ?: SalesTransaction::create([
            'invoice_number' => 'SL-TEST-001',
            'buyer_name' => 'Pabrik Peleburan Test',
            'buyer_phone' => '081234567890',
            'transaction_date' => Carbon::today(),
            'total_weight' => 100,
            'total_amount' => 500000,
            'final_amount' => 500000,
            'total_cogs' => 300000,
            'gross_profit' => 200000,
            'payment_method_id' => $pm->id,
            'status' => 'completed',
            'user_id' => $this->superAdmin->id,
        ]);
        $res = $this->actingAs($this->superAdmin)->get(route('sales.pdf', $sale));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        $res = $this->actingAs($this->superAdmin)->get(route('sales.batch-pdf'));
        $res->assertStatus(200);
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        // 8. Delivery Order PDF
        $do = DeliveryOrder::first() ?: DeliveryOrder::create([
            'delivery_number' => 'DO-TEST-001',
            'user_id' => $this->superAdmin->id,
            'sales_transaction_id' => $sale->id,
            'recipient_name' => 'PT Test Daur Ulang',
            'delivery_date' => Carbon::today(),
            'driver_name' => 'Supir Test',
            'vehicle_number' => 'B 1234 ABC',
            'status' => 'draft',
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
