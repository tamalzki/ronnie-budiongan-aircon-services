<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductUnifiedSeeder extends Seeder
{
    public function run(): void
    {
        $products = [

            // COOLING KING SERIES
            ['model' => 'FTNE20AXVL9 / RNE20AGXVL9', 'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 0.8HP', 'price' => 23800],
            ['model' => 'FTN25AXVL9 / RN25AGXVL9', 'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 1.0HP', 'price' => 28400],
            ['model' => 'FTN35AXVL9 / RN35AGXVL9', 'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 1.5HP', 'price' => 32100],
            ['model' => 'FTN50AXVL9 / RN50AGXVL9', 'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 2.0HP', 'price' => 45200],
            ['model' => 'FTN60AXVL9 / RN60AGXVL9', 'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 2.5HP', 'price' => 53700],
            ['model' => 'FTN71AXVL / RN71AGXVL', 'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 3.0HP', 'price' => 78300],

            // D-SMART SERIES
            ['model' => 'FTKQ20CVAF / RKQ20CVA', 'description' => 'D-Smart Series – Wall Mounted – 0.8HP', 'price' => 32500],
            ['model' => 'FTKQ25CVAF / RKQ25CVA', 'description' => 'D-Smart Series – Wall Mounted – 1.0HP', 'price' => 36700],
            ['model' => 'FTKQ35CVAF / RKQ35CVA', 'description' => 'D-Smart Series – Wall Mounted – 1.5HP', 'price' => 41200],
            ['model' => 'FTKQ50CVAF / RKQ50CVA', 'description' => 'D-Smart Series – Wall Mounted – 2.0HP', 'price' => 53800],
            ['model' => 'FTKQ60CVAF / RKQ60CVA', 'description' => 'D-Smart Series – Wall Mounted – 2.5HP', 'price' => 62300],
            ['model' => 'FTKQ71CVAF / RKQ71CVA', 'description' => 'D-Smart Series – Wall Mounted – 3.0HP', 'price' => 86900],

            // D-SMART QUEEN SERIES
            ['model' => 'FTKC25BVAF / RKC25BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 1.0HP', 'price' => 44900],
            ['model' => 'FTKC35BVAF / RKC35BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 1.5HP', 'price' => 50400],
            ['model' => 'FTKC50BVAF / RKC50BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 2.0HP', 'price' => 64600],
            ['model' => 'FTKC60BVAF / RKC60BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 2.5HP', 'price' => 75000],
            ['model' => 'FTKC71BVAF / RKC71BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 3.0HP', 'price' => 104600],

            // D-SMART KING SERIES
            ['model' => 'FTKZ25WVM / RKZ25WVM', 'description' => 'D-Smart King Series – Wall Mounted – 1.0HP', 'price' => 56800],
            ['model' => 'FTKZ35WVM / RKZ35WVM', 'description' => 'D-Smart King Series – Wall Mounted – 1.5HP', 'price' => 62800],
            ['model' => 'FTKZ50WVM / RKZ50WVM', 'description' => 'D-Smart King Series – Wall Mounted – 2.0HP', 'price' => 80300],
            ['model' => 'FTKZ60WVM / RKZ60WVM', 'description' => 'D-Smart King Series – Wall Mounted – 2.5HP', 'price' => 93400],
            ['model' => 'FTKZ71WVM / RKZ71WVM', 'description' => 'D-Smart King Series – Wall Mounted – 3.0HP', 'price' => 125800],

            // D-SMART LITE SERIES
            ['model' => 'FTKF20GVAF / RKF20GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 0.8HP', 'price' => 29900],
            ['model' => 'FTKF25GVAF / RKF25GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 1.0HP', 'price' => 34400],
            ['model' => 'FTKF35GVAF / RKF35GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 1.5HP', 'price' => 39400],
            ['model' => 'FTKF50GVAF / RKF50GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 2.0HP', 'price' => 50500],
            ['model' => 'FTKF60GVAF / RKF60GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 2.5HP', 'price' => 59600],
            ['model' => 'FTKF71GVAF / RKF71GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 3.0HP', 'price' => 83200],

            // DAIKIN AMIHAN
            ['model' => 'FTKE20AVA / RKE20AVA', 'description' => 'Daikin Amihan – Wall Mounted – 0.8HP', 'price' => 29300],
            ['model' => 'FTKE25AVA / RKE25AVA', 'description' => 'Daikin Amihan – Wall Mounted – 1.0HP', 'price' => 32000],
            ['model' => 'FTKE35AVA / RKE35AVA', 'description' => 'Daikin Amihan – Wall Mounted – 1.5HP', 'price' => 34000],
            ['model' => 'FTKE50AVA / RKE50AVA', 'description' => 'Daikin Amihan – Wall Mounted – 2.0HP', 'price' => 48700],
        ];

        $insertData = [];

        foreach ($products as $product) {
            $insertData[] = [
                'name' => 'Daikin',
                'brand_id' => 1,
                'supplier_id' => 1,
                'model' => $product['model'],
                'description' => $product['description'],
                'price' => $product['price'],
                'cost' => 0,
                'stock_quantity' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Product::insert($insertData);
    }
}
