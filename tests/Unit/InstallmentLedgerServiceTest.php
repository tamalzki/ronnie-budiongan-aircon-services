<?php

namespace Tests\Unit;

use App\Models\InstallmentPayment;
use App\Models\Sale;
use App\Models\User;
use App\Services\InstallmentLedgerService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InstallmentLedgerServiceTest extends TestCase
{
    use DatabaseTransactions;

    private InstallmentLedgerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InstallmentLedgerService();
    }

    public function test_builds_running_balances_and_summary(): void
    {
        $user = User::factory()->create();

        $sale = Sale::create([
            'invoice_number'     => 'INV-LEDGER-1',
            'customer_name'      => 'Ledger Test Customer',
            'customer_contact'   => '09123456789',
            'customer_address'   => 'Test Address',
            'sale_type'          => 'both',
            'subtotal'           => 10000,
            'discount'           => 500,
            'total'              => 9500,
            'payment_type'       => 'installment',
            'payment_method'     => 'cash',
            'installment_months' => 4,
            'installment_amount' => 2000,
            'paid_amount'        => 3500,
            'balance'            => 6000,
            'status'             => 'pending',
            'sale_date'          => now()->subMonths(2)->toDateString(),
            'user_id'            => $user->id,
        ]);

        InstallmentPayment::create([
            'sale_id'            => $sale->id,
            'installment_number' => 1,
            'amount'             => 1500,
            'amount_paid'        => 1500,
            'due_date'           => now()->subMonths(2)->toDateString(),
            'paid_date'          => now()->subMonths(2)->toDateString(),
            'status'             => 'paid',
            'payment_method'     => 'cash',
            'notes'              => 'Downpayment',
        ]);

        InstallmentPayment::create([
            'sale_id'            => $sale->id,
            'installment_number' => 2,
            'amount'             => 2000,
            'amount_paid'        => 2000,
            'due_date'           => now()->subMonth()->toDateString(),
            'paid_date'          => now()->subMonth()->toDateString(),
            'status'             => 'paid',
            'payment_method'     => 'cash',
        ]);

        InstallmentPayment::create([
            'sale_id'            => $sale->id,
            'installment_number' => 3,
            'amount'             => 2000,
            'amount_paid'        => 0,
            'due_date'           => now()->subDays(10)->toDateString(),
            'status'             => 'unpaid',
        ]);

        InstallmentPayment::create([
            'sale_id'            => $sale->id,
            'installment_number' => 4,
            'amount'             => 2000,
            'amount_paid'        => 0,
            'due_date'           => now()->addMonth()->toDateString(),
            'status'             => 'unpaid',
        ]);

        $sales = Sale::with(['items', 'user'])->whereKey($sale->id)->get();
        $installments = InstallmentPayment::where('sale_id', $sale->id)
            ->with('sale.user')
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->get();

        $ledger = $this->service->build($sales, $installments);

        $this->assertCount(4, $ledger['rows']);
        $this->assertEquals(9500.0, $ledger['summary']['original_contract_amount']);
        $this->assertEquals(1500.0, $ledger['summary']['down_payment']);
        $this->assertEquals(8000.0, $ledger['summary']['net_financed_amount']);
        $this->assertEquals(3500.0, $ledger['summary']['total_paid']);
        $this->assertEquals(6000.0, $ledger['summary']['current_balance']);
        $this->assertEquals(1, $ledger['summary']['installments_paid']);
        $this->assertEquals(2, $ledger['summary']['installments_remaining']);

        $lastRow = $ledger['rows'][3];
        $this->assertEquals(3500.0, $lastRow['running_total_paid']);
        $this->assertEquals(3500.0, $lastRow['total_credit']);
        $this->assertEquals(6000.0, $lastRow['remaining_balance']);
        $this->assertSame('Full DP', $ledger['rows'][0]['bill_no']);
        $this->assertArrayHasKey('header', $ledger);

        $overdueRow = $ledger['rows'][2];
        $this->assertSame('overdue', $overdueRow['status']);
        $this->assertGreaterThan(0, $ledger['aging']['days_1_30']);

        $this->assertCount(2, $ledger['paymentHistory']);
    }
}
