<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Brand;
use App\Models\InstallmentPayment;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SaleUpdateTest extends TestCase
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
        $brand = Brand::create(['name' => 'UpdateTest Brand']);
        $product = Product::create([
            'name'      => 'UpdateTest Unit',
            'brand_id'  => $brand->id,
            'model'     => 'UT-1',
            'price'     => 10000,
            'cost'      => 7000,
            'is_active' => true,
        ]);
        $serial = ProductSerial::create([
            'product_id'        => $product->id,
            'purchase_order_id' => null,
            'serial_number'     => 'UT-SN-001',
            'status'            => 'in_stock',
            'received_date'     => now()->toDateString(),
        ]);

        return [$product, $serial];
    }

    private function createSaleWithSerial(User $user, Product $product, ProductSerial $serial): Sale
    {
        $response = $this->actingAs($user)->post(route('sales.store'), [
            'customer_name'    => 'Original Customer',
            'customer_contact' => '09171234567',
            'customer_address' => null,
            'sale_date'        => now()->toDateString(),
            'payment_type'     => 'cash',
            'payment_method'   => 'cash',
            'discount'         => 0,
            'notes'            => null,
            'items'            => [[
                'type'            => 'product',
                'id'              => $product->id,
                'quantity'        => 1,
                'price'           => (float) $product->price,
                'serial_ids'      => [$serial->id],
                'new_serials_raw' => '',
            ]],
        ]);

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionMissing('error');

        return Sale::query()->latest('id')->firstOrFail();
    }

    public function test_edit_uses_create_form_with_prefill_data(): void
    {
        $user = User::factory()->create();
        [$product, $serial] = $this->productWithOneSerial();
        $sale = $this->createSaleWithSerial($user, $product, $serial);

        $response = $this->actingAs($user)->get(route('sales.edit', $sale));

        $response->assertOk();
        $response->assertSee('Edit Sale', false);
        $response->assertSee('Sale Items', false);
        $response->assertSee('Original Customer', false);
        $response->assertSee($sale->invoice_number, false);
        $response->assertDontSee('Items, prices, serials', false);
    }

    public function test_update_changes_customer_and_totals(): void
    {
        $user = User::factory()->create();
        [$product, $serial] = $this->productWithOneSerial();
        $sale = $this->createSaleWithSerial($user, $product, $serial);
        $itemsBefore = SaleItem::where('sale_id', $sale->id)->count();

        $response = $this->actingAs($user)->put(route('sales.update', $sale), [
            'customer_name'    => 'Updated Customer',
            'customer_contact' => '09998887777',
            'customer_address' => 'New address',
            'sale_date'        => now()->toDateString(),
            'payment_type'     => 'cash',
            'payment_method'   => 'gcash',
            'discount'         => 500,
            'notes'            => 'Updated notes',
            'items'            => [[
                'type'            => 'product',
                'id'              => $product->id,
                'quantity'        => 1,
                'price'           => 12000,
                'serial_ids'      => [$serial->id],
                'new_serials_raw' => '',
            ]],
        ]);

        $response->assertRedirect(route('sales.show', $sale));
        $response->assertSessionMissing('error');

        $sale->refresh();
        $this->assertSame('Updated Customer', $sale->customer_name);
        $this->assertSame('09998887777', $sale->customer_contact);
        $this->assertSame(12000.0, (float) $sale->subtotal);
        $this->assertSame(500.0, (float) $sale->discount);
        $this->assertSame(11500.0, (float) $sale->total);
        $this->assertSame(1, SaleItem::where('sale_id', $sale->id)->count());
        $this->assertNotSame($itemsBefore, 0);
    }

    public function test_update_reusing_same_serial_does_not_duplicate_stock_out_movements(): void
    {
        $user = User::factory()->create();
        [$product, $serial] = $this->productWithOneSerial();
        $sale = $this->createSaleWithSerial($user, $product, $serial);

        $stockOutBefore = InventoryMovement::where('product_id', $product->id)
            ->where('type', 'stock_out')
            ->count();

        $response = $this->actingAs($user)->put(route('sales.update', $sale), [
            'customer_name'    => 'Same Serial Customer',
            'customer_contact' => null,
            'customer_address' => null,
            'sale_date'        => now()->toDateString(),
            'payment_type'     => 'cash',
            'payment_method'   => 'cash',
            'discount'         => 0,
            'notes'            => null,
            'items'            => [[
                'type'            => 'product',
                'id'              => $product->id,
                'quantity'        => 1,
                'price'           => (float) $product->price,
                'serial_ids'      => [$serial->id],
                'new_serials_raw' => '',
            ]],
        ]);

        $response->assertRedirect(route('sales.show', $sale));
        $response->assertSessionMissing('error');

        $serial->refresh();
        $this->assertSame('sold', $serial->status);
        $this->assertSame($sale->id, $serial->sale_id);

        $stockOutAfter = InventoryMovement::where('product_id', $product->id)
            ->where('type', 'stock_out')
            ->count();
        $this->assertSame($stockOutBefore, $stockOutAfter);
    }

    public function test_update_removing_serial_restores_it_to_stock(): void
    {
        $user = User::factory()->create();
        [$product, $serial] = $this->productWithOneSerial();
        $sale = $this->createSaleWithSerial($user, $product, $serial);

        $response = $this->actingAs($user)->put(route('sales.update', $sale), [
            'customer_name'    => 'No Serial Customer',
            'customer_contact' => null,
            'customer_address' => null,
            'sale_date'        => now()->toDateString(),
            'payment_type'     => 'cash',
            'payment_method'   => 'cash',
            'discount'         => 0,
            'notes'            => null,
            'items'            => [[
                'type'            => 'product',
                'id'              => $product->id,
                'quantity'        => 1,
                'price'           => (float) $product->price,
                'serial_ids'      => [],
                'new_serials_raw' => '',
            ]],
        ]);

        $response->assertRedirect(route('sales.show', $sale));
        $response->assertSessionMissing('error');

        $serial->refresh();
        $this->assertSame('in_stock', $serial->status);
        $this->assertNull($serial->sale_id);
        $this->assertSame(0, ProductSerial::where('sale_id', $sale->id)->count());
    }

    public function test_update_rebuilds_installment_schedule(): void
    {
        $user = User::factory()->create();
        [$product, $serial] = $this->productWithOneSerial();
        $sale = $this->createSaleWithSerial($user, $product, $serial);

        $response = $this->actingAs($user)->put(route('sales.update', $sale), [
            'customer_name'       => 'Installment Customer',
            'customer_contact'    => null,
            'customer_address'    => null,
            'sale_date'           => now()->toDateString(),
            'payment_type'        => 'installment',
            'payment_method'      => 'cash',
            'discount'            => 0,
            'down_payment'        => 2000,
            'down_payment_method' => 'gcash',
            'installment_months'  => 6,
            'notes'               => null,
            'items'               => [[
                'type'            => 'product',
                'id'              => $product->id,
                'quantity'        => 1,
                'price'           => (float) $product->price,
                'serial_ids'      => [$serial->id],
                'new_serials_raw' => '',
            ]],
        ]);

        $response->assertRedirect(route('sales.show', $sale));
        $response->assertSessionMissing('error');

        $sale->refresh();
        $this->assertSame('installment', $sale->payment_type);
        $this->assertSame(6, (int) $sale->installment_months);
        $this->assertSame(2000.0, (float) $sale->paid_amount);
        $this->assertSame(8000.0, (float) $sale->balance);

        $payments = InstallmentPayment::where('sale_id', $sale->id)->orderBy('installment_number')->get();
        $this->assertGreaterThanOrEqual(2, $payments->count());
        $this->assertSame('paid', $payments->first()->status);
        $this->assertSame(2000.0, (float) $payments->first()->amount_paid);
    }

    public function test_sales_index_search_by_serial(): void
    {
        $user = User::factory()->create();
        [$product, $serial] = $this->productWithOneSerial();
        $this->createSaleWithSerial($user, $product, $serial);

        $response = $this->actingAs($user)->get(route('sales.index', ['search' => 'UT-SN-001']));

        $response->assertOk();
        $response->assertSee('Original Customer', false);
        $response->assertSee('UT-SN-001', false);
    }

    public function test_serial_lookup_returns_customer_for_sold_serial(): void
    {
        $user = User::factory()->create();
        [$product, $serial] = $this->productWithOneSerial();
        $sale = $this->createSaleWithSerial($user, $product, $serial);

        $response = $this->actingAs($user)->getJson(route('sales.serial-lookup', [
            'q'          => 'UT-SN-001',
            'product_id' => $product->id,
        ]));

        $response->assertOk();
        $response->assertJsonFragment([
            'serial_number'  => 'UT-SN-001',
            'customer_name'  => 'Original Customer',
            'invoice_number' => $sale->invoice_number,
        ]);
    }
}
