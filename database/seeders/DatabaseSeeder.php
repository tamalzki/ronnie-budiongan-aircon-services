<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
             BrandSeeder::class,      // must run before ProductSplitSeeder
               SupplierSeeder::class,   // must run before ProductSplitSeeder
                 ProductSplitSeeder::class,
        ]);
    }
}
