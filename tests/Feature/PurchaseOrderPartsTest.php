<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Brand;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseOrderPartsTest extends TestCase
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
        $supplier = Supplier::create(['name' => 'Parts Supplier', 'is_active' => true]);
        $brand    = Brand::create(['name' => 'Parts Brand']);
        $product  = Product::create([
            'name' => 'Unit AC', 'brand_id' => $brand->id, 'model' => 'PT-1',
            'price' => 9000, 'cost' => 0, 'is_active' => true,
        ]);

        return [$supplier, $product];
    }

    public function test_store_with_new_part_creates_part_linked_to_model_without_stocking_it_in_yet(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_po_number'     => 'PO-PARTS-1',
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => now()->addWeek()->toDateString(),
            'payment_type'           => 'full',
            'items'                  => [
                [
                    'item_type'        => 'product',
                    'product_id'       => $product->id,
                    'quantity'         => 1,
                    'unit_cost'        => 9000,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
                ],
                [
                    'item_type'           => 'part',
                    'new_part_name'       => 'Capacitor 35uF',
                    'new_part_product_id' => $product->id,
                    'quantity'            => 5,
                    'unit_cost'           => 120,
                    'discount_percent'    => 0,
                    'discount_amount'     => 0,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $part = Part::where('name', 'Capacitor 35uF')->first();
        $this->assertNotNull($part);
        $this->assertSame($product->id, $part->product_id);
        $this->assertSame(120.0, (float) $part->cost);
        $this->assertSame(0, $part->stock_quantity);

        $po = PurchaseOrder::query()->latest('id')->first();
        $partItem = $po->items()->whereNotNull('part_id')->first();
        $this->assertNotNull($partItem);
        $this->assertSame($part->id, $partItem->part_id);
        $this->assertNull($partItem->product_id);
        $this->assertSame(5, (int) $partItem->quantity_ordered);
        $this->assertSame(0, (int) $partItem->quantity_received);

        $this->assertSame(0, InventoryMovement::where('part_id', $part->id)->count());
    }

    public function test_store_with_new_part_and_no_model_creates_general_unlinked_part(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PO-PARTS-1C',
            'supplier_id'        => $supplier->id,
            'order_date'         => now()->toDateString(),
            'payment_type'       => 'full',
            'items'              => [
                [
                    'item_type'        => 'part',
                    'new_part_name'    => 'Universal Remote',
                    'quantity'         => 2,
                    'unit_cost'        => 90,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
                ],
            ],
        ])->assertRedirect();

        $part = Part::where('name', 'Universal Remote')->first();
        $this->assertNotNull($part);
        $this->assertNull($part->product_id);
    }

    public function test_store_reuses_existing_part_with_same_name_and_model_case_insensitively(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $existing = Part::create(['name' => 'Drain Hose', 'product_id' => $product->id, 'cost' => 50, 'is_active' => true]);

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PO-PARTS-1B',
            'supplier_id'  => $supplier->id,
            'order_date'   => now()->toDateString(),
            'payment_type' => 'full',
            'items'        => [
                [
                    'item_type'           => 'part',
                    'new_part_name'       => 'drain hose',
                    'new_part_product_id' => $product->id,
                    'quantity'            => 3,
                    'unit_cost'           => 80,
                    'discount_percent'    => 0,
                    'discount_amount'     => 0,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $this->assertSame(1, Part::where('name', 'Drain Hose')->count());
        $existing->refresh();
        $this->assertSame(80.0, (float) $existing->cost);
        $this->assertSame(0, $existing->stock_quantity);
    }

    public function test_store_with_same_part_name_under_a_different_model_creates_a_separate_part(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $brand2   = Brand::create(['name' => 'Parts Brand 2']);
        $product2 = Product::create([
            'name' => 'Unit AC 2', 'brand_id' => $brand2->id, 'model' => 'PT-2',
            'price' => 9000, 'cost' => 0, 'is_active' => true,
        ]);

        Part::create(['name' => 'Filter', 'product_id' => $product->id, 'cost' => 30, 'is_active' => true]);

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PO-PARTS-1D',
            'supplier_id'        => $supplier->id,
            'order_date'         => now()->toDateString(),
            'payment_type'       => 'full',
            'items'              => [
                [
                    'item_type'           => 'part',
                    'new_part_name'       => 'Filter',
                    'new_part_product_id' => $product2->id,
                    'quantity'            => 1,
                    'unit_cost'           => 35,
                    'discount_percent'    => 0,
                    'discount_amount'     => 0,
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(2, Part::where('name', 'Filter')->count());
        $this->assertNotNull(Part::where('name', 'Filter')->where('product_id', $product->id)->first());
        $this->assertNotNull(Part::where('name', 'Filter')->where('product_id', $product2->id)->first());
    }

    public function test_store_with_existing_part_id_updates_cost_without_stocking_in(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $part = Part::create([
            'name' => 'Drain Hose', 'product_id' => $product->id, 'cost' => 50, 'is_active' => true,
        ]);

        $response = $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PO-PARTS-2',
            'supplier_id'  => $supplier->id,
            'order_date'   => now()->toDateString(),
            'payment_type' => 'full',
            'items'        => [
                [
                    'item_type'           => 'part',
                    'part_id'             => $part->id,
                    'new_part_product_id' => $product->id,
                    'quantity'            => 3,
                    'unit_cost'           => 80,
                    'discount_percent'    => 0,
                    'discount_amount'     => 0,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $part->refresh();
        $this->assertSame(0, $part->stock_quantity);
        $this->assertSame(80.0, (float) $part->cost);
        $this->assertSame($product->id, $part->product_id);
    }

    public function test_show_page_renders_part_name_and_badge(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $part = Part::create([
            'name' => 'Outdoor Bracket', 'product_id' => $product->id, 'cost' => 200, 'is_active' => true,
        ]);

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PO-PARTS-3',
            'supplier_id'  => $supplier->id,
            'order_date'   => now()->toDateString(),
            'payment_type' => 'full',
            'items'        => [
                [
                    'item_type'           => 'part',
                    'part_id'             => $part->id,
                    'new_part_product_id' => $product->id,
                    'quantity'            => 2,
                    'unit_cost'           => 200,
                    'discount_percent'    => 0,
                    'discount_amount'     => 0,
                ],
            ],
        ])->assertRedirect();

        $po = PurchaseOrder::query()->latest('id')->first();

        $response = $this->get(route('purchase-orders.show', $po));

        $response->assertOk();
        $response->assertSee('Outdoor Bracket');
        $response->assertSee('🔧 Aircon Part');
    }

    public function test_receiving_a_part_stocks_it_in(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $part = Part::create([
            'name' => 'Copper Pipe 1/4', 'product_id' => $product->id, 'cost' => 100, 'is_active' => true,
        ]);

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PO-PARTS-4',
            'supplier_id'  => $supplier->id,
            'order_date'   => now()->toDateString(),
            'payment_type' => 'full',
            'items'        => [
                [
                    'item_type'           => 'part',
                    'part_id'             => $part->id,
                    'new_part_product_id' => $product->id,
                    'quantity'            => 4,
                    'unit_cost'           => 100,
                    'discount_percent'    => 0,
                    'discount_amount'     => 0,
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(0, $part->fresh()->stock_quantity);

        $po = PurchaseOrder::query()->latest('id')->first();
        $partItem = $po->items()->whereNotNull('part_id')->first();

        $this->post(route('purchase-orders.receive', $po), [
            'received_date'   => now()->toDateString(),
            'delivery_number' => 'DR-1001',
            'items'           => [
                ['id' => $partItem->id, 'quantity_received' => 4],
            ],
        ])->assertRedirect();

        $this->assertSame(4, $part->fresh()->stock_quantity);
        $this->assertSame(4, (int) $partItem->fresh()->quantity_received);
        $this->assertSame('received', $po->fresh()->status);

        $this->assertTrue(
            InventoryMovement::where('part_id', $part->id)
                ->where('type', 'stock_in')
                ->where('reference_type', 'PurchaseOrder')
                ->where('reference_id', $po->id)
                ->where('quantity', 4)
                ->exists()
        );
    }

    public function test_update_carries_over_previously_received_part_quantity(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $part = Part::create([
            'name' => 'Fan Motor', 'product_id' => $product->id, 'cost' => 100, 'is_active' => true,
        ]);

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PO-PARTS-5',
            'supplier_id'  => $supplier->id,
            'order_date'   => now()->toDateString(),
            'payment_type' => 'full',
            'items'        => [
                [
                    'item_type'           => 'part',
                    'part_id'             => $part->id,
                    'new_part_product_id' => $product->id,
                    'quantity'            => 4,
                    'unit_cost'           => 100,
                    'discount_percent'    => 0,
                    'discount_amount'     => 0,
                ],
            ],
        ])->assertRedirect();

        $po = PurchaseOrder::query()->latest('id')->first();
        $partItem = $po->items()->whereNotNull('part_id')->first();

        $this->post(route('purchase-orders.receive', $po), [
            'received_date'   => now()->toDateString(),
            'delivery_number' => 'DR-1002',
            'items'           => [
                ['id' => $partItem->id, 'quantity_received' => 4],
            ],
        ])->assertRedirect();

        $this->assertSame(4, $part->fresh()->stock_quantity);

        // Editing the PO to raise the ordered quantity should keep the 4 already received in stock.
        $this->put(route('purchase-orders.update', $po), [
            'supplier_po_number' => 'PO-PARTS-5',
            'supplier_id'  => $supplier->id,
            'order_date'   => now()->toDateString(),
            'payment_type' => 'full',
            'items'        => [
                [
                    'item_type'           => 'part',
                    'part_id'             => $part->id,
                    'new_part_product_id' => $product->id,
                    'quantity'            => 7,
                    'unit_cost'           => 110,
                    'discount_percent'    => 0,
                    'discount_amount'     => 0,
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(4, $part->fresh()->stock_quantity);
        $this->assertSame(110.0, (float) $part->fresh()->cost);

        $updatedItem = $po->fresh()->items()->whereNotNull('part_id')->first();
        $this->assertSame(7, (int) $updatedItem->quantity_ordered);
        $this->assertSame(4, (int) $updatedItem->quantity_received);
    }

    public function test_destroy_reverts_part_stock(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $part = Part::create([
            'name' => 'Blower Wheel', 'product_id' => $product->id, 'cost' => 500, 'is_active' => true,
        ]);

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PO-PARTS-6',
            'supplier_id'  => $supplier->id,
            'order_date'   => now()->toDateString(),
            'payment_type' => 'full',
            'items'        => [
                [
                    'item_type'           => 'part',
                    'part_id'             => $part->id,
                    'new_part_product_id' => $product->id,
                    'quantity'            => 2,
                    'unit_cost'           => 500,
                    'discount_percent'    => 0,
                    'discount_amount'     => 0,
                ],
            ],
        ])->assertRedirect();

        $po = PurchaseOrder::query()->latest('id')->first();
        $partItem = $po->items()->whereNotNull('part_id')->first();

        $this->post(route('purchase-orders.receive', $po), [
            'received_date'   => now()->toDateString(),
            'delivery_number' => 'DR-1003',
            'items'           => [
                ['id' => $partItem->id, 'quantity_received' => 2],
            ],
        ])->assertRedirect();

        $this->assertSame(2, $part->fresh()->stock_quantity);

        $this->delete(route('purchase-orders.destroy', $po))->assertRedirect();

        $this->assertSame(0, $part->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::where('part_id', $part->id)->count());
        $this->assertSame(0, PurchaseOrderItem::where('part_id', $part->id)->count());
    }
}
