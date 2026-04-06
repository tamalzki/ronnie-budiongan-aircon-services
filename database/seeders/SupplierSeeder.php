<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'is_active'        => true,
            'contact_person'   => null,
            'contact_number'   => null,
            'email'            => null,
            'address'          => null,
        ];

        Supplier::firstOrCreate(
            ['name' => 'Daikin Philippines'],
            $defaults
        );

        $extras = [
            ['name' => 'Carrier Philippines'],
            ['name' => 'LG Philippines'],
        ];

        foreach ($extras as $row) {
            Supplier::firstOrCreate(['name' => $row['name']], $defaults);
        }
    }
}
