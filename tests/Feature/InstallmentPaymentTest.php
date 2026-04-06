<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\InstallmentPayment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function paymentPayload(float $amount): array
    {
        return [
            'amount_paid' => $amount,
            'paid_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ];
    }

    public function test_guest_is_redirected_when_recording_installment_payment(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create([
            'user_id' => $user->id,
            'total' => 1000,
            'paid_amount' => 500,
            'balance' => 500,
        ]);
        $installment = InstallmentPayment::factory()->create([
            'sale_id' => $sale->id,
            'amount' => 500,
            'amount_paid' => 0,
        ]);

        $response = $this->post(route('installments.pay', $installment), $this->paymentPayload(100));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_record_partial_installment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $sale = Sale::factory()->create([
            'user_id' => $user->id,
            'total' => 1000,
            'paid_amount' => 500,
            'balance' => 500,
        ]);
        $installment = InstallmentPayment::factory()->create([
            'sale_id' => $sale->id,
            'installment_number' => 1,
            'amount' => 300,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->from(route('installments.show', $sale))
            ->post(route('installments.pay', $installment), $this->paymentPayload(200));

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $sale->refresh();
        $this->assertEquals(300.0, (float) $sale->balance);

        $installment->refresh();
        $this->assertEquals(200.0, (float) $installment->amount_paid);
        $this->assertSame('partial', $installment->status);
    }

    public function test_payment_rejected_when_exceeding_sale_balance(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $sale = Sale::factory()->create([
            'user_id' => $user->id,
            'total' => 1000,
            'paid_amount' => 900,
            'balance' => 100,
        ]);
        $installment = InstallmentPayment::factory()->create([
            'sale_id' => $sale->id,
            'amount' => 500,
            'amount_paid' => 400,
            'status' => 'partial',
        ]);

        $response = $this->from(route('installments.show', $sale))
            ->post(route('installments.pay', $installment), $this->paymentPayload(150));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $sale->refresh();
        $this->assertEquals(100.0, (float) $sale->balance);
        $installment->refresh();
        $this->assertEquals(400.0, (float) $installment->amount_paid);
    }

    public function test_payment_rejected_when_overflow_cannot_be_applied_to_schedule(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $sale = Sale::create([
            'invoice_number' => 'INV-SCHED-1',
            'customer_name' => 'Test',
            'sale_type' => 'both',
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_type' => 'installment',
            'payment_method' => 'cash',
            'paid_amount' => 500,
            'balance' => 500,
            'status' => 'completed',
            'sale_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        InstallmentPayment::create([
            'sale_id' => $sale->id,
            'installment_number' => 1,
            'amount' => 400,
            'amount_paid' => 0,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'unpaid',
        ]);
        InstallmentPayment::create([
            'sale_id' => $sale->id,
            'installment_number' => 2,
            'amount' => 400,
            'amount_paid' => 350,
            'due_date' => now()->addMonths(2)->toDateString(),
            'status' => 'partial',
        ]);

        $first = InstallmentPayment::where('sale_id', $sale->id)->where('installment_number', 1)->firstOrFail();

        $response = $this->from(route('installments.show', $sale))
            ->post(route('installments.pay', $first), $this->paymentPayload(500));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $sale->refresh();
        $this->assertEquals(500.0, (float) $sale->balance);
    }
}
