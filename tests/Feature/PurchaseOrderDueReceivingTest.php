<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseOrderDueReceivingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseOrderDueReceivingTest extends TestCase
{
    use DatabaseTransactions;

    private function seedPendingPo(User $user, string $expectedDate): PurchaseOrder
    {
        $supplier = Supplier::create(['name' => 'Due Recv Supplier', 'is_active' => true]);
        $brand    = Brand::create(['name' => 'Due Recv Brand']);
        $product  = Product::create([
            'name'      => 'Due Unit',
            'brand_id'  => $brand->id,
            'model'     => 'DR-100',
            'price'     => 5000,
            'cost'      => 3000,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'user_id'                => $user->id,
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->subDay()->toDateString(),
            'expected_delivery_date' => $expectedDate,
            'payment_type'           => 'full',
            'status'                 => 'pending',
            'subtotal'               => 3000,
            'tax'                    => 0,
            'total'                  => 3000,
            'amount_paid'            => 0,
            'balance'                => 3000,
            'payment_status'         => 'unpaid',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id'        => $product->id,
            'quantity_ordered'  => 1,
            'quantity_received' => 0,
            'unit_cost'         => 3000,
            'discounted_cost'   => 3000,
            'total_cost'        => 3000,
        ]);

        return $po->fresh(['items.product']);
    }

    public function test_service_includes_pending_po_when_expected_delivery_is_today(): void
    {
        $user = User::factory()->create();
        $po   = $this->seedPendingPo($user, now()->toDateString());

        $due = app(PurchaseOrderDueReceivingService::class)->ordersDueForReceiving($user);

        $this->assertTrue($due->contains('id', $po->id));
    }

    public function test_service_excludes_po_with_future_expected_delivery(): void
    {
        $user = User::factory()->create();
        $po   = $this->seedPendingPo($user, now()->addWeek()->toDateString());

        $due = app(PurchaseOrderDueReceivingService::class)->ordersDueForReceiving($user);

        $this->assertFalse($due->contains('id', $po->id));
    }

    public function test_service_excludes_fully_received_po(): void
    {
        $user = User::factory()->create();
        $po   = $this->seedPendingPo($user, now()->toDateString());
        $po->items()->first()->update(['quantity_received' => 1]);
        $po->update(['status' => 'received']);

        $due = app(PurchaseOrderDueReceivingService::class)->ordersDueForReceiving($user);

        $this->assertFalse($due->contains('id', $po->id));
    }

    public function test_dashboard_includes_due_receive_modal_for_pending_po(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $po = $this->seedPendingPo($user, now()->toDateString());

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Order Receive — PO No:', false);
        $response->assertSee('dueReceiveModal' . $po->id, false);
        $response->assertSee('Not yet received', false);
        $response->assertSee('auto-open-due-receive', false);
    }
}
