<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Tests\Support\RefreshDatabaseWithForce;
use Tests\TestCase;

class PurchaseOrderPaymentTest extends TestCase
{
    use RefreshDatabaseWithForce;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function makePurchaseOrder(User $user): PurchaseOrder
    {
        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'is_active' => true,
        ]);

        return PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'subtotal' => 1000,
            'tax' => 0,
            'total' => 1000,
            'payment_type' => '45days',
            'payment_due_date' => now()->addDays(45)->toDateString(),
            'amount_paid' => 0,
            'balance' => 1000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);
    }

    public function test_guest_is_redirected_when_recording_po_payment(): void
    {
        $user = User::factory()->create();
        $po = $this->makePurchaseOrder($user);

        $response = $this->post(route('purchase-orders.payment', $po), [
            'amount' => 100,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_record_po_payment_within_balance(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $po = $this->makePurchaseOrder($user);

        $response = $this->post(route('purchase-orders.payment', $po), [
            'amount' => 250,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $po->refresh();
        $this->assertEquals(250.0, (float) $po->amount_paid);
        $this->assertEquals(750.0, (float) $po->balance);
        $this->assertSame('partial', $po->payment_status);
    }

    public function test_po_payment_rejected_when_exceeding_balance(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $po = $this->makePurchaseOrder($user);

        $response = $this->post(route('purchase-orders.payment', $po), [
            'amount' => 1000.01,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $po->refresh();
        $this->assertEquals(0.0, (float) $po->amount_paid);
        $this->assertEquals(1000.0, (float) $po->balance);
    }
}
