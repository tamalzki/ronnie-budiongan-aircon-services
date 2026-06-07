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

class PurchaseOrderEditDeleteTest extends TestCase
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
        $supplier = Supplier::create(['name' => 'Edit Supplier', 'is_active' => true]);
        $brand    = Brand::create(['name' => 'Edit Brand']);
        $product  = Product::create([
            'name' => 'Unit AC', 'brand_id' => $brand->id, 'model' => 'ED-1',
            'price' => 9000, 'cost' => 0, 'is_active' => true,
        ]);

        return [$supplier, $product];
    }

    private function createReceivedPo(Supplier $supplier, Product $product, array $serials, int $qty, float $cost = 100): PurchaseOrder
    {
        $this->post(route('purchase-orders.store'), [
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => now()->addWeek()->toDateString(),
            'payment_type'           => 'full',
            'items'                  => [[
                'product_id'       => $product->id,
                'quantity'         => $qty,
                'unit_cost'        => $cost,
                'discount_percent' => 0,
                'discount_amount'  => 0,
                'serials'          => $serials,
            ]],
        ])->assertRedirect();

        return PurchaseOrder::query()->latest('id')->first();
    }

    public function test_update_resyncs_serials_and_inventory(): void
    {
        [$supplier, $product] = $this->seedCatalog();
        $po = $this->createReceivedPo($supplier, $product, ['EDIT-A', 'EDIT-B'], 2, 100);

        $this->assertSame(2, $product->fresh()->inStockSerials()->count());

        $response = $this->put(route('purchase-orders.update', $po), [
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => now()->addWeek()->toDateString(),
            'payment_type'           => 'full',
            'items'                  => [[
                'product_id'       => $product->id,
                'quantity'         => 1,
                'unit_cost'        => 150,
                'discount_percent' => 0,
                'discount_amount'  => 0,
                'serials'          => ['EDIT-C'],
            ]],
        ]);

        $response->assertRedirect(route('purchase-orders.show', $po));
        $po->refresh();

        // Item re-created with new qty/cost/total
        $item = $po->items()->first();
        $this->assertSame(1, (int) $item->quantity_ordered);
        $this->assertSame(1, (int) $item->quantity_received);
        $this->assertSame(150.0, (float) $item->unit_cost);
        $this->assertSame(150.0, (float) $po->total);

        // Old serials gone, new serial in stock
        $this->assertFalse(ProductSerial::where('serial_number', 'EDIT-A')->exists());
        $this->assertFalse(ProductSerial::where('serial_number', 'EDIT-B')->exists());
        $this->assertTrue(
            ProductSerial::where('purchase_order_id', $po->id)->where('serial_number', 'EDIT-C')->where('status', 'in_stock')->exists()
        );

        // Inventory reflects 1 unit now; product cost updated
        $this->assertSame(1, $product->fresh()->inStockSerials()->count());
        $this->assertSame(150.0, (float) $product->fresh()->cost);

        // Full payment re-synced to new total
        $this->assertSame(150.0, (float) $po->amount_paid);
        $this->assertSame(0.0, (float) $po->balance);
        $this->assertSame('paid', $po->payment_status);
        $this->assertSame(1, SupplierPayment::where('purchase_order_id', $po->id)->count());
    }

    public function test_update_blocked_when_a_unit_already_sold(): void
    {
        [$supplier, $product] = $this->seedCatalog();
        $po = $this->createReceivedPo($supplier, $product, ['SOLD-1'], 1, 100);

        // Simulate a sale of that serial
        ProductSerial::where('serial_number', 'SOLD-1')->update(['status' => 'sold']);

        $response = $this->put(route('purchase-orders.update', $po), [
            'supplier_id'            => $supplier->id,
            'order_date'             => now()->toDateString(),
            'expected_delivery_date' => now()->addWeek()->toDateString(),
            'payment_type'           => 'full',
            'items'                  => [[
                'product_id'       => $product->id,
                'quantity'         => 1,
                'unit_cost'        => 999,
                'discount_percent' => 0,
                'discount_amount'  => 0,
                'serials'          => ['SOLD-2'],
            ]],
        ]);

        $response->assertSessionHas('error');
        // Nothing changed
        $this->assertTrue(ProductSerial::where('serial_number', 'SOLD-1')->where('status', 'sold')->exists());
        $this->assertFalse(ProductSerial::where('serial_number', 'SOLD-2')->exists());
        $this->assertSame(100.0, (float) $po->fresh()->items()->first()->unit_cost);
    }

    public function test_destroy_reverts_inventory_and_dependents(): void
    {
        [$supplier, $product] = $this->seedCatalog();
        $po = $this->createReceivedPo($supplier, $product, ['DEL-1', 'DEL-2'], 2, 100);

        $this->assertSame(2, $product->fresh()->inStockSerials()->count());

        $response = $this->delete(route('purchase-orders.destroy', $po));

        $response->assertRedirect(route('purchase-orders.index'));
        $this->assertNull(PurchaseOrder::find($po->id));
        $this->assertSame(0, ProductSerial::where('purchase_order_id', $po->id)->count());
        $this->assertSame(0, $product->fresh()->inStockSerials()->count());
        $this->assertSame(0, InventoryMovement::where('reference_type', 'PurchaseOrder')->where('reference_id', $po->id)->count());
        $this->assertSame(0, SupplierPayment::where('purchase_order_id', $po->id)->count());
    }

    public function test_destroy_blocked_when_a_unit_already_sold(): void
    {
        [$supplier, $product] = $this->seedCatalog();
        $po = $this->createReceivedPo($supplier, $product, ['DSOLD-1'], 1, 100);

        ProductSerial::where('serial_number', 'DSOLD-1')->update(['status' => 'sold']);

        $response = $this->delete(route('purchase-orders.destroy', $po));

        $response->assertSessionHas('error');
        $this->assertNotNull(PurchaseOrder::find($po->id));
        $this->assertTrue(ProductSerial::where('serial_number', 'DSOLD-1')->exists());
    }
}
