<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Supplier;

class DaikinProductionSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::where('name', 'Daikin')->firstOrFail();
        $supplier = Supplier::where('name', 'Daikin Air-conditioning Philippines')->firstOrFail();

        $products = [

            /*
            |--------------------------------------------------------------------------
            | DAIKIN AMIHAN WALL MOUNTED
            |--------------------------------------------------------------------------
            */

            [
                'series' => 'Daikin Amihan Wall Mounted',
                'hp' => 0.8,
                'indoor' => 'FTKE20AVA',
                'outdoor' => 'RKE20AVA',
                'price' => 29300,
            ],
            [
                'series' => 'Daikin Amihan Wall Mounted',
                'hp' => 1.0,
                'indoor' => 'FTKE25AVA',
                'outdoor' => 'RKE25AVA',
                'price' => 32000,
            ],
            [
                'series' => 'Daikin Amihan Wall Mounted',
                'hp' => 1.5,
                'indoor' => 'FTKE35AVA',
                'outdoor' => 'RKE35AVA',
                'price' => 34000,
            ],
            [
                'series' => 'Daikin Amihan Wall Mounted',
                'hp' => 2.0,
                'indoor' => 'FTKE50AVA',
                'outdoor' => 'RKE50AVA',
                'price' => 48700,
            ],

            /*
            |--------------------------------------------------------------------------
            | D-SMART LITE SERIES WALL MOUNTED
            |--------------------------------------------------------------------------
            */

            [
                'series' => 'D-Smart Lite Series Wall Mounted',
                'hp' => 0.8,
                'indoor' => 'FTKF20GVAF',
                'outdoor' => 'RKF20GVA',
                'price' => 29900,
            ],
            [
                'series' => 'D-Smart Lite Series Wall Mounted',
                'hp' => 1.0,
                'indoor' => 'FTKF25GVAF',
                'outdoor' => 'RKF25GVA',
                'price' => 34400,
            ],
            [
                'series' => 'D-Smart Lite Series Wall Mounted',
                'hp' => 1.5,
                'indoor' => 'FTKF35GVAF',
                'outdoor' => 'RKF35GVA',
                'price' => 39400,
            ],
            [
                'series' => 'D-Smart Lite Series Wall Mounted',
                'hp' => 2.0,
                'indoor' => 'FTKF50GVAF',
                'outdoor' => 'RKF50GVA',
                'price' => 50500,
            ],
            [
                'series' => 'D-Smart Lite Series Wall Mounted',
                'hp' => 2.5,
                'indoor' => 'FTKF60GVAF',
                'outdoor' => 'RKF60GVA',
                'price' => 59600,
            ],
            [
                'series' => 'D-Smart Lite Series Wall Mounted',
                'hp' => 3.0,
                'indoor' => 'FTKF71GVAF',
                'outdoor' => 'RKF71GVA',
                'price' => 83200,
            ],

        ];

        foreach ($products as $item) {

            $baseDescription = "Series: {$item['series']}
Capacity: {$item['hp']} HP
Power Supply: 220V / 1 Phase / 60 Hz
Refrigerant: R32
Features:
• Daikin All-Voltage Guard
• Gin-Ion Blue Filter
• Quiet Operation";

            /*
            |--------------------------------------------------------------------------
            | Indoor Unit
            |--------------------------------------------------------------------------
            */
            Product::updateOrCreate(
                ['model' => $item['indoor']],
                [
                    'brand_id' => $brand->id,
                    'supplier_id' => $supplier->id,
                    'series' => $item['series'],
                    'hp' => $item['hp'],
                    'unit_type' => 'Indoor',
                    'description' => $baseDescription . "\nUnit Type: Indoor",
                    'price' => $item['price'],
                    'initial_stock' => 0,
                    'is_active' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Outdoor Unit
            |--------------------------------------------------------------------------
            */
            Product::updateOrCreate(
                ['model' => $item['outdoor']],
                [
                    'brand_id' => $brand->id,
                    'supplier_id' => $supplier->id,
                    'series' => $item['series'],
                    'hp' => $item['hp'],
                    'unit_type' => 'Outdoor',
                    'description' => $baseDescription . "\nUnit Type: Outdoor",
                    'price' => $item['price'],
                    'initial_stock' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}
