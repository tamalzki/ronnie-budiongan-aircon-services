<?php

namespace Tests\Feature;

use App\Models\DailyCustomer;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DailyCustomerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_view_index_with_summary_cards(): void
    {
        $user = User::factory()->create();

        DailyCustomer::create([
            'customer_name' => 'Juan Dela Cruz',
            'service_type' => 'Aircon Cleaning – Split Type',
            'amount' => 1500,
            'status' => 'paid',
            'service_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        DailyCustomer::create([
            'customer_name' => 'Maria Santos',
            'service_type' => 'Others',
            'other_service' => 'Aircon gas top-up',
            'amount' => 800,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('daily-customers.index'));

        $response->assertOk();
        $response->assertSee('Daily Customers');
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('Maria Santos');
        $response->assertSee('Aircon gas top-up');
        $response->assertSee('Services Today');
        $response->assertSee('Total Unpaid');
        $response->assertSee('Total Paid');
    }

    public function test_user_can_create_entry_with_others_service(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Pedro Reyes',
            'service_type' => 'Others',
            'other_service' => 'Aircon stand fabrication',
            'amount' => 2500,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('daily-customers.index'));

        $this->assertDatabaseHas('daily_customers', [
            'customer_name' => 'Pedro Reyes',
            'service_type' => 'Aircon stand fabrication',
            'other_service' => null,
            'status' => 'unpaid',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('services', [
            'name' => 'Aircon stand fabrication',
            'default_price' => 2500,
            'is_active' => true,
        ]);
    }

    public function test_others_service_can_be_picked_directly_after_being_added(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Pedro Reyes',
            'service_type' => 'Others',
            'other_service' => 'Aircon stand fabrication',
            'amount' => 2500,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('daily-customers.index'));

        $response->assertOk();
        $response->assertSee('Aircon stand fabrication');

        $response2 = $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Another Customer',
            'service_type' => 'Aircon stand fabrication',
            'amount' => 3000,
            'status' => 'paid',
            'service_date' => now()->toDateString(),
        ]);

        $response2->assertRedirect(route('daily-customers.index'));

        $this->assertDatabaseHas('daily_customers', [
            'customer_name' => 'Another Customer',
            'service_type' => 'Aircon stand fabrication',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('services', [
            'name' => 'Aircon stand fabrication',
            'default_price' => 3000,
        ]);
    }

    public function test_service_price_updates_to_most_recent_amount(): void
    {
        $user = User::factory()->create();

        $service = \App\Models\Service::query()->orderBy('id')->first();
        $this->assertNotNull($service, 'Seeded or migrated services are required for this test database.');

        $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Customer One',
            'service_type' => $service->name,
            'amount' => 1234,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'default_price' => 1234,
        ]);

        $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Customer Two',
            'service_type' => $service->name,
            'amount' => 5678,
            'status' => 'paid',
            'service_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'default_price' => 5678,
        ]);
    }

    public function test_create_requires_other_service_when_others_selected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Ana Cruz',
            'service_type' => 'Others',
            'amount' => 500,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('other_service');
    }

    public function test_user_can_update_status_with_validation(): void
    {
        $user = User::factory()->create();

        $entry = DailyCustomer::create([
            'customer_name' => 'Liza Gomez',
            'service_type' => 'Chemical Cleaning',
            'amount' => 1200,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->patch(route('daily-customers.update-status', $entry), [
            'status' => 'paid',
        ]);

        $response->assertRedirect(route('daily-customers.index'));
        $this->assertDatabaseHas('daily_customers', [
            'id' => $entry->id,
            'status' => 'paid',
        ]);

        $invalidResponse = $this->actingAs($user)->patch(route('daily-customers.update-status', $entry), [
            'status' => 'bogus',
        ]);

        $invalidResponse->assertSessionHasErrors('status');
    }

    public function test_user_can_update_and_delete_entry(): void
    {
        $user = User::factory()->create();

        $entry = DailyCustomer::create([
            'customer_name' => 'Carlos Tan',
            'service_type' => 'Freon Refilling / Refrigerant Charging',
            'amount' => 1800,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        $updateResponse = $this->actingAs($user)->put(route('daily-customers.update', $entry), [
            'customer_name' => 'Carlos Tan Jr.',
            'service_type' => 'Freon Refilling / Refrigerant Charging',
            'amount' => 2000,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
        ]);

        $updateResponse->assertRedirect(route('daily-customers.index'));
        $this->assertDatabaseHas('daily_customers', [
            'id' => $entry->id,
            'customer_name' => 'Carlos Tan Jr.',
            'amount' => 2000,
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('daily-customers.destroy', $entry));

        $deleteResponse->assertRedirect(route('daily-customers.index'));
        $this->assertDatabaseMissing('daily_customers', ['id' => $entry->id]);
    }

    public function test_dashboard_shows_unpaid_daily_customer_alert(): void
    {
        $user = User::factory()->create();

        DailyCustomer::create([
            'customer_name' => 'Unpaid Customer',
            'service_type' => 'Outdoor Unit Cleaning',
            'amount' => 900,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Unpaid daily customers');
    }

    public function test_daily_customers_report_section_renders(): void
    {
        $user = User::factory()->create();

        DailyCustomer::create([
            'customer_name' => 'Report Customer',
            'service_type' => 'Aircon Installation',
            'amount' => 5000,
            'status' => 'paid',
            'service_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index', [
            'report' => 'daily_customers',
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('By Service Type', false);
        $response->assertSee('Report Customer');
        $response->assertSee('Collection Rate');
    }

    public function test_creating_entry_with_parts_logs_usage_and_decrements_stock(): void
    {
        $user = User::factory()->create();
        $part = Part::create(['name' => 'Capacitor 35uF', 'cost' => 150, 'is_active' => true]);

        InventoryMovement::create([
            'part_id'        => $part->id,
            'type'           => 'stock_in',
            'quantity'       => 10,
            'stock_before'   => 0,
            'stock_after'    => 10,
            'reference_type' => 'PurchaseOrder',
            'reference_id'   => 1,
            'notes'          => 'Initial stock',
            'user_id'        => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Juan Dela Cruz',
            'service_type' => 'Aircon Cleaning – Split Type',
            'amount' => 1500,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
            'parts' => [
                ['part_id' => $part->id, 'quantity' => 2],
            ],
            'parts_included_in_price' => 1,
        ]);

        $response->assertRedirect(route('daily-customers.index'));

        $entry = DailyCustomer::where('customer_name', 'Juan Dela Cruz')->first();
        $this->assertNotNull($entry);
        $this->assertTrue($entry->parts_included_in_price);

        $this->assertDatabaseHas('daily_customer_parts', [
            'daily_customer_id' => $entry->id,
            'part_id'           => $part->id,
            'quantity'          => 2,
        ]);

        $this->assertSame(8, $part->fresh()->stock_quantity);

        $this->assertTrue(
            InventoryMovement::where('part_id', $part->id)
                ->where('type', 'stock_out')
                ->where('reference_type', 'DailyCustomer')
                ->where('reference_id', $entry->id)
                ->where('quantity', 2)
                ->exists()
        );
    }

    public function test_create_requires_parts_included_in_price_when_parts_present(): void
    {
        $user = User::factory()->create();
        $part = Part::create(['name' => 'Drain Hose', 'cost' => 50, 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Pedro Reyes',
            'service_type' => 'Outdoor Unit Cleaning',
            'amount' => 1000,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
            'parts' => [
                ['part_id' => $part->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSessionHasErrors('parts_included_in_price');
    }

    public function test_editing_entry_replaces_parts_usage_and_adjusts_stock(): void
    {
        $user = User::factory()->create();
        $partA = Part::create(['name' => 'Capacitor 35uF', 'cost' => 150, 'is_active' => true]);
        $partB = Part::create(['name' => 'Drain Hose', 'cost' => 50, 'is_active' => true]);

        foreach ([$partA, $partB] as $part) {
            InventoryMovement::create([
                'part_id'        => $part->id,
                'type'           => 'stock_in',
                'quantity'       => 10,
                'stock_before'   => 0,
                'stock_after'    => 10,
                'reference_type' => 'PurchaseOrder',
                'reference_id'   => 1,
                'notes'          => 'Initial stock',
                'user_id'        => $user->id,
            ]);
        }

        $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Carlos Tan',
            'service_type' => 'Freon Refilling / Refrigerant Charging',
            'amount' => 1800,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
            'parts' => [
                ['part_id' => $partA->id, 'quantity' => 3],
            ],
            'parts_included_in_price' => 0,
        ])->assertRedirect(route('daily-customers.index'));

        $this->assertSame(7, $partA->fresh()->stock_quantity);

        $entry = DailyCustomer::where('customer_name', 'Carlos Tan')->first();

        $this->actingAs($user)->put(route('daily-customers.update', $entry), [
            'customer_name' => 'Carlos Tan',
            'service_type' => 'Freon Refilling / Refrigerant Charging',
            'amount' => 1800,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
            'parts' => [
                ['part_id' => $partB->id, 'quantity' => 4],
            ],
            'parts_included_in_price' => 1,
        ])->assertRedirect(route('daily-customers.index'));

        $this->assertSame(10, $partA->fresh()->stock_quantity);
        $this->assertSame(6, $partB->fresh()->stock_quantity);

        $entry->refresh();
        $this->assertTrue($entry->parts_included_in_price);
        $this->assertDatabaseMissing('daily_customer_parts', [
            'daily_customer_id' => $entry->id,
            'part_id'           => $partA->id,
        ]);
        $this->assertDatabaseHas('daily_customer_parts', [
            'daily_customer_id' => $entry->id,
            'part_id'           => $partB->id,
            'quantity'          => 4,
        ]);
    }

    public function test_deleting_entry_with_parts_restores_stock(): void
    {
        $user = User::factory()->create();
        $part = Part::create(['name' => 'Fan Motor', 'cost' => 500, 'is_active' => true]);

        InventoryMovement::create([
            'part_id'        => $part->id,
            'type'           => 'stock_in',
            'quantity'       => 5,
            'stock_before'   => 0,
            'stock_after'    => 5,
            'reference_type' => 'PurchaseOrder',
            'reference_id'   => 1,
            'notes'          => 'Initial stock',
            'user_id'        => $user->id,
        ]);

        $this->actingAs($user)->post(route('daily-customers.store'), [
            'customer_name' => 'Maria Santos',
            'service_type' => 'Outdoor Unit Cleaning',
            'amount' => 900,
            'status' => 'unpaid',
            'service_date' => now()->toDateString(),
            'parts' => [
                ['part_id' => $part->id, 'quantity' => 2],
            ],
            'parts_included_in_price' => 1,
        ])->assertRedirect(route('daily-customers.index'));

        $this->assertSame(3, $part->fresh()->stock_quantity);

        $entry = DailyCustomer::where('customer_name', 'Maria Santos')->first();

        $this->actingAs($user)->delete(route('daily-customers.destroy', $entry))
            ->assertRedirect(route('daily-customers.index'));

        $this->assertSame(5, $part->fresh()->stock_quantity);
        $this->assertDatabaseMissing('daily_customer_parts', ['daily_customer_id' => $entry->id]);
    }
}
