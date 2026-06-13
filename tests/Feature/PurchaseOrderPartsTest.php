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

    public function test_store_with_new_part_creates_part_and_stocks_it_in(): void
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
        $this->assertSame(5, $part->stock_quantity);

        $po = PurchaseOrder::query()->latest('id')->first();
        $partItem = $po->items()->whereNotNull('part_id')->first();
        $this->assertNotNull($partItem);
        $this->assertSame($part->id, $partItem->part_id);
        $this->assertNull($partItem->product_id);
        $this->assertSame(5, (int) $partItem->quantity_ordered);
        $this->assertSame(5, (int) $partItem->quantity_received);

        $this->assertTrue(
            InventoryMovement::where('part_id', $part->id)
                ->where('type', 'stock_in')
                ->where('reference_type', 'PurchaseOrder')
                ->where('reference_id', $po->id)
                ->where('quantity', 5)
                ->exists()
        );
    }

    public function test_store_with_existing_part_increments_stock_and_updates_cost(): void
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
                    'item_type'        => 'part',
                    'part_id'          => $part->id,
                    'quantity'         => 3,
                    'unit_cost'        => 80,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $part->refresh();
        $this->assertSame(3, $part->stock_quantity);
        $this->assertSame(80.0, (float) $part->cost);
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
                    'item_type'        => 'part',
                    'part_id'          => $part->id,
                    'quantity'         => 2,
                    'unit_cost'        => 200,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
                ],
            ],
        ])->assertRedirect();

        $po = PurchaseOrder::query()->latest('id')->first();

        $response = $this->get(route('purchase-orders.show', $po));

        $response->assertOk();
        $response->assertSee('Outdoor Bracket');
        $response->assertSee('🔧 Part');
    }

    public function test_update_recomputes_part_stock_after_quantity_change(): void
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
                    'item_type'        => 'part',
                    'part_id'          => $part->id,
                    'quantity'         => 4,
                    'unit_cost'        => 100,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(4, $part->fresh()->stock_quantity);

        $po = PurchaseOrder::query()->latest('id')->first();

        $this->put(route('purchase-orders.update', $po), [
            'supplier_po_number' => 'PO-PARTS-4',
            'supplier_id'  => $supplier->id,
            'order_date'   => now()->toDateString(),
            'payment_type' => 'full',
            'items'        => [
                [
                    'item_type'        => 'part',
                    'part_id'          => $part->id,
                    'quantity'         => 7,
                    'unit_cost'        => 110,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(7, $part->fresh()->stock_quantity);
        $this->assertSame(110.0, (float) $part->fresh()->cost);
    }

    public function test_destroy_reverts_part_stock(): void
    {
        [$supplier, $product] = $this->seedCatalog();

        $part = Part::create([
            'name' => 'Fan Motor', 'product_id' => $product->id, 'cost' => 500, 'is_active' => true,
        ]);

        $this->post(route('purchase-orders.store'), [
            'supplier_po_number' => 'PO-PARTS-5',
            'supplier_id'  => $supplier->id,
            'order_date'   => now()->toDateString(),
            'payment_type' => 'full',
            'items'        => [
                [
                    'item_type'        => 'part',
                    'part_id'          => $part->id,
                    'quantity'         => 2,
                    'unit_cost'        => 500,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(2, $part->fresh()->stock_quantity);

        $po = PurchaseOrder::query()->latest('id')->first();

        $this->delete(route('purchase-orders.destroy', $po))->assertRedirect();

        $this->assertSame(0, $part->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::where('part_id', $part->id)->count());
        $this->assertSame(0, PurchaseOrderItem::where('part_id', $part->id)->count());
    }
}
