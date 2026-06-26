<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Brand;
use App\Models\Part;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseOrderSplitReceiveTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->actingAs(User::factory()->create());
    }

    private function seedCatalog(): array
    {
        $supplier = Supplier::create(['name' => 'Split Supplier', 'is_active' => true]);
        $brand    = Brand::create(['name' => 'Split Brand']);
        $product  = Product::create([
            'name' => 'Unit AC', 'brand_id' => $brand->id, 'model' => 'SPLIT-1',
            'price' => 9000, 'cost' => 0, 'is_active' => true,
        ]);

        return [$supplier, $product];
    }

    public function test_receiving_part_of_a_product_line_with_split_creates_new_pending_po(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'ORIG-1',
            'supplier_id'        => $supplier->id,
            'order_date'         => now()->toDateString(),
            'payment_type'       => 'full',
            'items'              => [[
                'item_type'        => 'product',
                'product_id'       => $product->id,
                'quantity'         => 3,
                'unit_cost'        => 100,
                'discount_percent' => 0,
                'discount_amount'  => 0,
            ]],
        ])->assertRedirect();

        $po       = PurchaseOrder::query()->latest('id')->first();
        $poItem   = $po->items()->first();

        $response = $this->post(route('purchase-orders.receive', $po), [
            'received_date'   => now()->toDateString(),
            'delivery_number' => 'DR-ORIG-1',
            'items'           => [
                [
                    'id'                => $poItem->id,
                    'quantity_received' => 1,
                    'serials'           => ['SPLIT-A'],
                    'split_remainder'   => 1,
                ],
            ],
            'new_po_supplier_po_number'     => 'ORIG-1-B',
            'new_po_expected_delivery_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertRedirect(route('purchase-orders.show', $po));
        $response->assertSessionMissing('error');

        $po->refresh();
        $originalItem = $po->items()->first();
        $this->assertSame(1, (int) $originalItem->quantity_ordered);
        $this->assertSame(1, (int) $originalItem->quantity_received);
        $this->assertSame('received', $po->status);
        $this->assertSame(100.0, (float) $po->total);

        $newPo = PurchaseOrder::where('supplier_po_number', 'ORIG-1-B')->first();
        $this->assertNotNull($newPo);
        $this->assertSame('pending', $newPo->status);
        $this->assertSame($supplier->id, $newPo->supplier_id);
        $this->assertSame(200.0, (float) $newPo->total);

        $newItem = $newPo->items()->first();
        $this->assertSame($product->id, $newItem->product_id);
        $this->assertSame(2, (int) $newItem->quantity_ordered);
        $this->assertSame(0, (int) $newItem->quantity_received);
    }

    public function test_split_proportionally_carries_over_payment_for_45_day_term(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        // Total = 300 (3 x 100). Downpayment of 90 = 30% paid.
        $this->post(route('purchase-orders.store'), [
            'supplier_po_number'  => 'PAY-1',
            'supplier_id'         => $supplier->id,
            'order_date'          => now()->toDateString(),
            'payment_type'        => '45days',
            'downpayment_amount'  => 90,
            'downpayment_date'    => now()->toDateString(),
            'downpayment_method'  => 'cash',
            'items'               => [[
                'item_type'        => 'product',
                'product_id'       => $product->id,
                'quantity'         => 3,
                'unit_cost'        => 100,
                'discount_percent' => 0,
                'discount_amount'  => 0,
            ]],
        ])->assertRedirect();

        $po     = PurchaseOrder::query()->latest('id')->first();
        $poItem = $po->items()->first();

        $this->assertSame(300.0, (float) $po->total);
        $this->assertSame(90.0, (float) $po->amount_paid);

        // Receive 1 of 3 now; split the remaining 2 (worth 200, two-thirds of the order value).
        $this->post(route('purchase-orders.receive', $po), [
            'received_date'   => now()->toDateString(),
            'delivery_number' => 'DR-PAY-1',
            'items'           => [
                [
                    'id'                => $poItem->id,
                    'quantity_received' => 1,
                    'serials'           => ['PAY-A'],
                    'split_remainder'   => 1,
                ],
            ],
            'new_po_supplier_po_number' => 'PAY-1-B',
        ])->assertRedirect();

        $po->refresh();
        $newPo = PurchaseOrder::where('supplier_po_number', 'PAY-1-B')->first();
        $this->assertNotNull($newPo);

        // Original keeps 1/3 of the value and 1/3 of what was paid; new PO gets the other 2/3.
        $this->assertSame(100.0, (float) $po->total);
        $this->assertSame(30.0, (float) $po->amount_paid);
        $this->assertSame(70.0, (float) $po->balance);

        $this->assertSame(200.0, (float) $newPo->total);
        $this->assertSame(60.0, (float) $newPo->amount_paid);
        $this->assertSame(140.0, (float) $newPo->balance);
        $this->assertSame('partial', $newPo->payment_status);

        $this->assertSame(
            (float) $po->amount_paid + (float) $newPo->amount_paid,
            90.0
        );
    }

    public function test_split_requires_new_supplier_po_number(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'NOPO-1',
            'supplier_id'        => $supplier->id,
            'order_date'         => now()->toDateString(),
            'payment_type'       => 'full',
            'items'              => [[
                'item_type'        => 'product',
                'product_id'       => $product->id,
                'quantity'         => 2,
                'unit_cost'        => 100,
                'discount_percent' => 0,
                'discount_amount'  => 0,
            ]],
        ])->assertRedirect();

        $po     = PurchaseOrder::query()->latest('id')->first();
        $poItem = $po->items()->first();

        $response = $this->post(route('purchase-orders.receive', $po), [
            'received_date'   => now()->toDateString(),
            'delivery_number' => 'DR-NOPO-1',
            'items'           => [
                [
                    'id'                => $poItem->id,
                    'quantity_received' => 1,
                    'serials'           => ['NOPO-A'],
                    'split_remainder'   => 1,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase-orders.show', $po));
        $response->assertSessionHasErrors('new_po_supplier_po_number');

        $po->refresh();
        $this->assertSame(2, (int) $po->items()->first()->quantity_ordered);
        $this->assertSame(0, (int) $po->items()->first()->quantity_received);
        $this->assertSame(0, PurchaseOrder::where('supplier_po_number', 'like', 'NOPO-1%')->where('id', '!=', $po->id)->count());
    }

    public function test_splitting_an_unreceived_part_line_moves_it_to_new_po(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $part = Part::create(['name' => 'Drain Hose', 'cost' => 50, 'is_active' => true]);

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PART-1',
            'supplier_id'        => $supplier->id,
            'order_date'         => now()->toDateString(),
            'payment_type'       => 'full',
            'items'              => [[
                'item_type'        => 'part',
                'part_id'          => $part->id,
                'quantity'         => 5,
                'unit_cost'        => 50,
                'discount_percent' => 0,
                'discount_amount'  => 0,
            ]],
        ])->assertRedirect();

        $po     = PurchaseOrder::query()->latest('id')->first();
        $poItem = $po->items()->first();

        $this->post(route('purchase-orders.receive', $po), [
            'received_date'   => now()->toDateString(),
            'delivery_number' => 'DR-PART-1',
            'items'           => [
                [
                    'id'                => $poItem->id,
                    'quantity_received' => 2,
                    'split_remainder'   => 1,
                ],
            ],
            'new_po_supplier_po_number' => 'PART-1-B',
        ])->assertRedirect();

        $this->assertSame(2, $part->fresh()->stock_quantity);

        $po->refresh();
        $originalItem = $po->items()->first();
        $this->assertSame(2, (int) $originalItem->quantity_ordered);
        $this->assertSame(2, (int) $originalItem->quantity_received);
        $this->assertSame('received', $po->status);

        $newPo = PurchaseOrder::where('supplier_po_number', 'PART-1-B')->first();
        $newItem = $newPo->items()->first();
        $this->assertSame($part->id, $newItem->part_id);
        $this->assertSame(3, (int) $newItem->quantity_ordered);
        $this->assertSame(0, (int) $newItem->quantity_received);
    }
}
