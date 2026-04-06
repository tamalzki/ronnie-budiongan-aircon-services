<?php

namespace Database\Factories;

use App\Models\InstallmentPayment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstallmentPaymentFactory extends Factory
{
    protected $model = InstallmentPayment::class;

    public function definition()
    {
        return [
            'sale_id' => Sale::factory(),
            'installment_number' => 1,
            'amount' => 500,
            'amount_paid' => 0,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'unpaid',
        ];
    }
}
