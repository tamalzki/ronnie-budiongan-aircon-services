<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Brand;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SaleCreateSerialOptionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /** @return array{0: Product, 1: ProductSerial} */
    private function productWithOneSerial(): array
    {
        $brand = Brand::create(['name' => 'SaleTest Brand']);
        $product = Product::create([
            'name'      => 'SaleTest Unit',
            'brand_id'  => $brand->id,
            'model'     => 'ST-1',
            'price'     => 10000,
            'cost'      => 7000,
            'is_active' => true,
        ]);
        $serial = ProductSerial::create([
            'product_id'         => $product->id,
            'purchase_order_id'  => null,
            'serial_number'      => 'STOCK-SN-001',
            'status'             => 'in_stock',
            'received_date'      => now()->toDateString(),
        ]);

        return [$product, $serial];
    }

    private function basePayload(User $user, array $item): array
    {
        return [
            'customer_name'    => 'Walk-in Customer',
            'customer_contact' => null,
            'customer_address' => null,
            'sale_date'        => now()->toDateString(),
            'payment_type'     => 'cash',
            'payment_method'   => 'cash',
            'discount'         => 0,
            'notes'            => null,
            'items'            => [$item],
        ];
    }

    public function test_sale_with_existing_serial_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$product, $serial] = $this->productWithOneSerial();

        $response = $this->post(route('sales.store'), $this->basePayload($user, [
            'type'             => 'product',
            'id'               => $product->id,
            'quantity'         => 1,
            'price'            => (float) $product->price,
            'serial_ids'       => [$serial->id],
            'new_serials_raw'  => '',
        ]));

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionMissing('error');

        $serial->refresh();
        $this->assertSame('sold', $serial->status);

        $this->assertSame(1, InventoryMovement::where('product_id', $product->id)->where('type', 'stock_out')->count());
    }

    public function test_sale_with_new_serial_registers_and_records_movements(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$product] = $this->productWithOneSerial();

        $response = $this->post(route('sales.store'), $this->basePayload($user, [
            'type'             => 'product',
            'id'               => $product->id,
            'quantity'         => 1,
            'price'            => (float) $product->price,
            'serial_ids'       => [],
            'new_serials_raw'  => "FRESH-SN-777\n",
        ]));

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionMissing('error');

        $sn = ProductSerial::where('product_id', $product->id)->where('serial_number', 'FRESH-SN-777')->first();
        $this->assertNotNull($sn);
        $this->assertSame('sold', $sn->status);

        $this->assertSame(1, InventoryMovement::where('product_id', $product->id)->where('type', 'stock_in')->count());
        $this->assertSame(1, InventoryMovement::where('product_id', $product->id)->where('type', 'stock_out')->count());
    }

    public function test_sale_without_serials_when_qty_positive(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$product] = $this->productWithOneSerial();

        $response = $this->post(route('sales.store'), $this->basePayload($user, [
            'type'             => 'product',
            'id'               => $product->id,
            'quantity'         => 2,
            'price'            => (float) $product->price,
            'serial_ids'       => [],
            'new_serials_raw'  => '',
        ]));

        $response->assertRedirect(route('sales.index'));
        $sale = Sale::query()->latest('id')->first();
        $this->assertNotNull($sale);
        $item = $sale->items()->where('product_id', $product->id)->first();
        $this->assertSame(2, (int) $item->quantity);
        $this->assertSame(0, ProductSerial::where('sale_id', $sale->id)->count());
    }

    /** @return Product */
    private function productWithNoSerials(): Product
    {
        $brand = Brand::create(['name' => 'NoSerial Brand']);

        return Product::create([
            'name'      => 'NoSerial Unit',
            'brand_id'  => $brand->id,
            'model'     => 'NS-1',
            'price'     => 5000,
            'cost'      => 3000,
            'is_active' => true,
        ]);
    }

    public function test_sale_requires_serial_when_product_has_no_recorded_serials(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->productWithNoSerials();
        $salesBefore = Sale::query()->count();

        $response = $this->post(route('sales.store'), $this->basePayload($user, [
            'type'             => 'product',
            'id'               => $product->id,
            'quantity'         => 1,
            'price'            => (float) $product->price,
            'serial_ids'       => [],
            'new_serials_raw'  => '',
        ]));

        $response->assertSessionHasErrors('items');
        $this->assertSame($salesBefore, Sale::query()->count());
    }

    public function test_sale_accepts_new_serial_when_product_has_no_recorded_serials(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = $this->productWithNoSerials();

        $response = $this->post(route('sales.store'), $this->basePayload($user, [
            'type'             => 'product',
            'id'               => $product->id,
            'quantity'         => 1,
            'price'            => (float) $product->price,
            'serial_ids'       => [],
            'new_serials_raw'  => "ENCODED-AT-SALE-01\n",
        ]));

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionMissing('error');

        $sn = ProductSerial::where('product_id', $product->id)
            ->where('serial_number', 'ENCODED-AT-SALE-01')
            ->first();
        $this->assertNotNull($sn);
        $this->assertSame('sold', $sn->status);
    }

    public function test_sale_rejects_partial_serial_coverage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$product, $serial] = $this->productWithOneSerial();

        $response = $this->post(route('sales.store'), $this->basePayload($user, [
            'type'             => 'product',
            'id'               => $product->id,
            'quantity'         => 2,
            'price'            => (float) $product->price,
            'serial_ids'       => [$serial->id],
            'new_serials_raw'  => '',
        ]));

        $response->assertSessionHasErrors('items');
    }
}
