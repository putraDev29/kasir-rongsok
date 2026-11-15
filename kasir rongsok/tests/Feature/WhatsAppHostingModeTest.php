<?php

namespace Tests\Feature;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\PaymentMethod;
use App\Models\PurchaseTransaction;
use App\Models\SalesTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\WhatsAppGatewayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WhatsAppHostingModeTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
        $this->cashier = User::where('role', User::ROLE_KASIR)->first() ?? $this->superAdmin;
    }

    /**
     * Test that WhatsApp Receipt modal is rendered cleanly on purchases pages when gateway is disabled (hosting mode)
     */
    public function test_whatsapp_modal_renders_in_hosting_mode_without_gateway(): void
    {
        config(['services.whatsapp_gateway.enabled' => false]);

        $purchase = PurchaseTransaction::first();
        if (!$purchase) {
            $this->markTestSkipped('No purchase transaction available for test.');
        }

        $response = $this->actingAs($this->cashier)->get(route('purchases.show', $purchase));

        $response->assertStatus(200);
        // Verify modal component is included
        $response->assertSee('$store.waGateway.receiptModalOpen', false);
        // Verify hosting notice is present
        $response->assertSee('Mode Web / Hosting: Kirim pesan & berkas PDF langsung via', false);
        // Verify wa.me button is present
        $response->assertSee('$store.waGateway.openWaMe(true)', false);
        $response->assertSee('Kirim ke WhatsApp (wa.me)', false);
    }

    /**
     * Test that WhatsApp preview endpoints work 100% without any WhatsApp gateway running
     */
    public function test_whatsapp_preview_endpoints_work_without_gateway(): void
    {
        config(['services.whatsapp_gateway.enabled' => false]);

        $purchase = PurchaseTransaction::first();
        if ($purchase) {
            $res = $this->actingAs($this->cashier)->getJson(route('whatsapp.purchases.preview', $purchase));
            $res->assertStatus(200)
                ->assertJsonStructure([
                    'phone',
                    'formatted_phone',
                    'message',
                    'invoice_number',
                    'filename',
                    'pdf_url',
                    'print_url',
                    'receipt_data',
                ]);
            $this->assertEquals($purchase->invoice_number, $res->json('invoice_number'));
            $this->assertNotEmpty($res->json('message'));
        }

        $sale = SalesTransaction::first();
        if ($sale) {
            $res = $this->actingAs($this->cashier)->getJson(route('whatsapp.sales.preview', $sale));
            $res->assertStatus(200)
                ->assertJsonStructure([
                    'phone',
                    'formatted_phone',
                    'message',
                    'invoice_number',
                    'filename',
                    'pdf_url',
                    'print_url',
                    'receipt_data',
                ]);
            $this->assertEquals($sale->invoice_number, $res->json('invoice_number'));
        }

        $payment = DebtPayment::first();
        if ($payment) {
            $res = $this->actingAs($this->superAdmin)->getJson(route('whatsapp.debt-payments.preview', $payment));
            $res->assertStatus(200)
                ->assertJsonStructure([
                    'phone',
                    'formatted_phone',
                    'message',
                    'filename',
                    'pdf_url',
                    'print_url',
                    'receipt_data',
                ]);
        }
    }

    /**
     * Test status endpoint returns DISABLED immediately when gateway is disabled
     */
    public function test_status_endpoint_returns_disabled_instantly_on_hosting(): void
    {
        config(['services.whatsapp_gateway.enabled' => false]);

        $res = $this->actingAs($this->cashier)->getJson(route('whatsapp.status'));

        $res->assertStatus(200);
        $res->assertJson([
            'status' => 'DISABLED',
            'connected' => false,
            'enabled' => false,
        ]);
    }

    /**
     * Test status endpoint caches OFFLINE status when gateway port is unreachable
     */
    public function test_status_endpoint_caches_offline_when_port_unreachable(): void
    {
        Cache::forget('wa_gateway_offline_status');

        // Set to unreachable port to test offline handling
        config([
            'services.whatsapp_gateway.enabled' => true,
            'services.whatsapp_gateway.url' => 'http://127.0.0.1:21999',
        ]);

        $start = microtime(true);
        $res1 = $this->actingAs($this->cashier)->getJson(route('whatsapp.status'));
        $duration1 = microtime(true) - $start;

        $res1->assertStatus(200);
        $res1->assertJson([
            'status' => 'OFFLINE',
            'connected' => false,
        ]);

        // Second call must return from cache instantly (< 50ms)
        $start2 = microtime(true);
        $res2 = $this->actingAs($this->cashier)->getJson(route('whatsapp.status'));
        $duration2 = microtime(true) - $start2;

        $res2->assertStatus(200);
        $res2->assertJson([
            'status' => 'OFFLINE',
            'connected' => false,
        ]);
        $this->assertLessThan(0.1, $duration2, 'Second call must be served from cache instantly');

        Cache::forget('wa_gateway_offline_status');
    }
}
