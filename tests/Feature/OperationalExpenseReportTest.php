<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\OperationExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OperationalExpenseReportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_view_operational_expense_report(): void
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::query()->orderBy('id')->first();
        $this->assertNotNull($category, 'Seeded or migrated expense categories are required for this test database.');

        OperationExpense::create([
            'expense_category_id' => $category->id,
            'description' => 'Audit smoke test',
            'amount' => 100.5,
            'expense_date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index', [
            'report' => 'expenses',
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('Period total', false);
        $response->assertSee('By category', false);
        $response->assertSee('Line items', false);
    }
}
