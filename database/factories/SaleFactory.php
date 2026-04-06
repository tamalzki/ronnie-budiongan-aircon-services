<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition()
    {
        return [
            'invoice_number' => 'TINV-' . $this->faker->unique()->numerify('########'),
            'customer_name' => $this->faker->name(),
            'customer_contact' => null,
            'customer_address' => null,
            'sale_type' => 'both',
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'payment_type' => 'installment',
            'payment_method' => 'cash',
            'paid_amount' => 0,
            'balance' => 1000,
            'status' => 'completed',
            'sale_date' => now()->toDateString(),
            'user_id' => User::factory(),
        ];
    }
}
