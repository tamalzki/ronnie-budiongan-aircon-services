<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Brand;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PartTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function makeProduct(): Product
    {
        $brand = Brand::create(['name' => 'Part Brand']);

        return Product::create([
            'name' => 'Unit AC', 'brand_id' => $brand->id, 'model' => 'PT-1',
            'price' => 9000, 'cost' => 0, 'is_active' => true,
        ]);
    }

    public function test_index_lists_parts_with_stock(): void
    {
        $part = Part::create([
            'name' => 'Capacitor 35uF', 'cost' => 150, 'is_active' => true,
        ]);

        $response = $this->get(route('parts.index'));

        $response->assertOk();
        $response->assertSee('Capacitor 35uF');
        $response->assertSee('Parts');
    }

    public function test_can_create_part_linked_to_a_product(): void
    {
        $product = $this->makeProduct();

        $response = $this->post(route('parts.store'), [
            'name'        => 'Outdoor Bracket',
            'product_id'  => $product->id,
            'description' => 'Heavy duty bracket',
            'cost'        => 250,
            'is_active'   => '1',
        ]);

        $response->assertRedirect(route('parts.index'));

        $this->assertDatabaseHas('parts', [
            'name'       => 'Outdoor Bracket',
            'product_id' => $product->id,
            'cost'       => 250,
            'is_active'  => true,
        ]);
    }

    public function test_can_update_and_delete_part(): void
    {
        $part = Part::create(['name' => 'Drain Hose', 'cost' => 50, 'is_active' => true]);

        $updateResponse = $this->put(route('parts.update', $part), [
            'name'      => 'Drain Hose (Long)',
            'cost'      => 75,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('parts.index'));
        $this->assertDatabaseHas('parts', ['id' => $part->id, 'name' => 'Drain Hose (Long)', 'cost' => 75]);

        $deleteResponse = $this->delete(route('parts.destroy', $part));

        $deleteResponse->assertRedirect(route('parts.index'));
        $this->assertDatabaseMissing('parts', ['id' => $part->id]);
    }

    public function test_stock_quantity_reflects_movements(): void
    {
        $part = Part::create(['name' => 'Copper Pipe 1/4', 'cost' => 100, 'is_active' => true]);

        $this->assertSame(0, $part->stock_quantity);

        InventoryMovement::create([
            'part_id'        => $part->id,
            'type'           => 'stock_in',
            'quantity'       => 10,
            'stock_before'   => 0,
            'stock_after'    => 10,
            'reference_type' => 'PurchaseOrder',
            'reference_id'   => 1,
            'notes'          => 'Test stock in',
            'user_id'        => $this->user->id,
        ]);

        $this->assertSame(10, $part->fresh()->stock_quantity);

        InventoryMovement::create([
            'part_id'        => $part->id,
            'type'           => 'stock_out',
            'quantity'       => 3,
            'stock_before'   => 10,
            'stock_after'    => 7,
            'reference_type' => 'DailyCustomer',
            'reference_id'   => 1,
            'notes'          => 'Test stock out',
            'user_id'        => $this->user->id,
        ]);

        $this->assertSame(7, $part->fresh()->stock_quantity);
    }
}
