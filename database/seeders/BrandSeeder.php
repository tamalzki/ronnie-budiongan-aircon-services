<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Daikin', 'description' => 'Japanese premium air conditioning brand', 'is_active' => true],
            ['name' => 'Carrier', 'description' => 'American air conditioning manufacturer', 'is_active' => true],
            ['name' => 'LG', 'description' => 'Korean electronics and air conditioning', 'is_active' => true],
            ['name' => 'Samsung', 'description' => 'Korean electronics and air conditioning', 'is_active' => true],
            ['name' => 'Panasonic', 'description' => 'Japanese electronics brand', 'is_active' => true],
            ['name' => 'Mitsubishi', 'description' => 'Japanese air conditioning brand', 'is_active' => true],
            ['name' => 'Kolin', 'description' => 'Affordable air conditioning brand', 'is_active' => true],
            ['name' => 'Gree', 'description' => 'Chinese air conditioning manufacturer', 'is_active' => true],
            ['name' => 'Midea', 'description' => 'Chinese appliance manufacturer', 'is_active' => true],
            ['name' => 'Fujitsu', 'description' => 'Japanese air conditioning brand', 'is_active' => true],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}