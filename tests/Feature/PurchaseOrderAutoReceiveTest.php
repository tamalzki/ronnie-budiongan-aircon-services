<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Brand;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseOrderAutoReceiveTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function seedCatalog(): array
    {
        $supplier = Supplier::create(['name' => 'AutoRecv Supplier', 'is_active' => true]);
        $brand     = Brand::create(['name' => 'AutoRecv Brand']);
        $product   = Product::create([
            'name'     => 'Unit AC',
            'brand_id' => $brand->id,
            'model'    => 'AR-1',
            'price'    => 5000,
            'cost'     => 3000,
            'is_active' => true,
        ]);

        return [$supplier, $product];
    }

    public function test_store_receives_stock_and_puts_serials_in_stock_on_creation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$supplier, $product] = $this->seedCatalog();

        // Even with a future expected delivery date, stock is received immediately.
        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => now()->addWeek()->toDateString(),
            'payment_type'           => 'full',
            'notes'                  => null,
            'items'                  => [
                [
                    'product_id'        => $product->id,
                    'quantity'          => 2,
                    'unit_cost'         => 100,
                    'discount_percent'  => 0,
                    'discount_amount'   => 0,
                    'serials'           => ['AUTO-SN-01', 'AUTO-SN-02'],
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $po = PurchaseOrder::query()->latest('id')->first();
        $this->assertNotNull($po);
        $this->assertSame('received', $po->status);
        $this->assertNotNull($po->received_date);

        $item = $po->items()->first();
        $this->assertSame(2, $item->quantity_received);

        $this->assertSame(2, ProductSerial::where('purchase_order_id', $po->id)->where('status', 'in_stock')->count());
        $this->assertSame(0, ProductSerial::where('purchase_order_id', $po->id)->where('status', 'pending')->count());

        // An inventory stock-in movement is logged for the received quantity.
        $movement = InventoryMovement::where('reference_type', 'PurchaseOrder')
            ->where('reference_id', $po->id)
            ->where('type', 'stock_in')
            ->first();
        $this->assertNotNull($movement);
        $this->assertSame(2, (int) $movement->quantity);
    }

    public function test_store_without_serials_creates_pending_po_for_later_receiving(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$supplier, $product] = $this->seedCatalog();

        // 'test' may already exist as a supplier PO No on another order — duplicates are allowed.
        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->subDays(5)->toDateString(),
            'expected_delivery_date' => now()->subDay()->toDateString(),
            'payment_type'           => 'full',
            'supplier_po_number'     => 'test',
            'notes'                  => null,
            'items'                  => [
                [
                    'product_id'        => $product->id,
                    'quantity'          => 3,
                    'unit_cost'         => 100,
                    'discount_percent'  => 0,
                    'discount_amount'   => 0,
                    'serials'           => [],
                ],
            ],
        ]);

        // No serials yet — the PO is created as pending and awaits Order Receive.
        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $po = PurchaseOrder::query()->latest('id')->first();
        $this->assertNotNull($po);
        $this->assertSame('pending', $po->status);
        $this->assertNull($po->received_date);

        // Supplier PO No is stored separately; the internal number stays auto-generated.
        $this->assertSame('test', $po->supplier_po_number);
        $this->assertStringStartsWith('PO-', $po->po_number);

        $item = $po->items()->first();
        $this->assertSame(3, (int) $item->quantity_ordered);
        $this->assertSame(0, (int) $item->quantity_received);

        $this->assertSame(0, ProductSerial::where('product_id', $product->id)->count());
        $this->assertSame(
            0,
            InventoryMovement::where('reference_type', 'PurchaseOrder')->where('reference_id', $po->id)->count()
        );
    }

    public function test_receive_pending_po_with_serials_stocks_in_units(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$supplier, $product] = $this->seedCatalog();

        // Create a pending PO (no serials)
        $this->post(route('purchase-orders.store'), [
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => now()->addWeek()->toDateString(),
            'payment_type'           => 'full',
            'notes'                  => null,
            'items'                  => [
                [
                    'product_id'        => $product->id,
                    'quantity'          => 2,
                    'unit_cost'         => 100,
                    'discount_percent'  => 0,
                    'discount_amount'   => 0,
                ],
            ],
        ]);

        $po   = PurchaseOrder::query()->latest('id')->first();
        $item = $po->items()->first();
        $this->assertSame('pending', $po->status);

        // Order Receive: input serials now
        $response = $this->post(route('purchase-orders.receive', $po), [
            'received_date'   => now()->toDateString(),
            'delivery_number' => 'DR-001',
            'items'           => [
                [
                    'id'                => $item->id,
                    'quantity_received' => 2,
                    'serials'           => ['RCV-SN-01', 'RCV-SN-02'],
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $po->refresh();
        $this->assertSame('received', $po->status);
        $this->assertNotNull($po->received_date);
        $this->assertSame(2, (int) $po->items()->first()->quantity_received);

        $this->assertSame(2, ProductSerial::where('purchase_order_id', $po->id)->where('status', 'in_stock')->count());
    }

    public function test_store_requires_unit_cost(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$supplier, $product] = $this->seedCatalog();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => now()->addWeek()->toDateString(),
            'payment_type'           => 'full',
            'notes'                  => null,
            'items'                  => [
                [
                    'product_id'       => $product->id,
                    'quantity'         => 1,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
                    'serials'          => ['NOCOST-SN-1'],
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.unit_cost');
    }

    public function test_store_45day_downpayment_receives_and_records_partial_payment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$supplier, $product] = $this->seedCatalog();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => now()->addWeek()->toDateString(),
            'payment_type'           => '45days',
            'downpayment_amount'     => 1000,
            'downpayment_date'       => now()->toDateString(),
            'downpayment_method'     => 'gcash',
            'downpayment_reference'  => 'GC-REF-1',
            'notes'                  => null,
            'items'                  => [
                [
                    'product_id'        => $product->id,
                    'quantity'          => 1,
                    'unit_cost'         => 5000,
                    'discount_percent'  => 0,
                    'discount_amount'   => 0,
                    'serials'           => ['DP-SN-1'],
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $po = PurchaseOrder::query()->latest('id')->first();
        $this->assertNotNull($po);
        $this->assertSame('received', $po->status);
        $this->assertSame('partial', $po->payment_status);
        $this->assertSame(5000.0, (float) $po->total);
        $this->assertSame(1000.0, (float) $po->amount_paid);
        $this->assertSame(4000.0, (float) $po->balance);
        $this->assertNotNull($po->payment_due_date);

        // Serial is in stock immediately.
        $this->assertTrue(
            ProductSerial::where('purchase_order_id', $po->id)->where('serial_number', 'DP-SN-1')->where('status', 'in_stock')->exists()
        );

        // Downpayment recorded.
        $payment = SupplierPayment::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(1000.0, (float) $payment->amount);
        $this->assertSame('gcash', $payment->payment_method);
        $this->assertSame('GC-REF-1', $payment->reference_number);
    }
}
