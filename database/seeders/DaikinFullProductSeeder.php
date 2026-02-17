<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Supplier;

class DaikinFullProductSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::firstOrCreate(['name' => 'Daikin']);
        $supplier = Supplier::firstOrCreate(['name' => 'Daikin Air-conditioning Philippines']);

        $products = [

            // COOLING KING SERIES – Premium Wall Mounted (Non-Inverter)
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'FTNE20AXVL9 (0.8HP)', 'price' => 23800],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'RNE20AGXVL9 (0.8HP)', 'price' => 23800],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'FTN25AXVL9 (1.0HP)', 'price' => 28400],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'RN25AGXVL9 (1.0HP)', 'price' => 28400],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'FTN35AXVL9 (1.5HP)', 'price' => 32100],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'RN35AGXVL9 (1.5HP)', 'price' => 32100],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'FTN50AXVL9 (2.0HP)', 'price' => 45200],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'RN50AGXVL9 (2.0HP)', 'price' => 45200],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'FTN60AXVL9 (2.5HP)', 'price' => 53700],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'RN60AGXVL9 (2.5HP)', 'price' => 53700],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'FTN71AXVL (3.0HP)', 'price' => 78300],
            ['title' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter)', 'model' => 'RN71AGXVL (3.0HP)', 'price' => 78300],

            // NEW D-SMART SERIES – Wall Mounted
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'FTKQ20CVAF (0.8HP)', 'price' => 32500],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'RKQ20CVA (0.8HP)', 'price' => 32500],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'FTKQ25CVAF (1.0HP)', 'price' => 36700],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'RKQ25CVA (1.0HP)', 'price' => 36700],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'FTKQ35CVAF (1.5HP)', 'price' => 41200],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'RKQ35CVA (1.5HP)', 'price' => 41200],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'FTKQ50CVAF (2.0HP)', 'price' => 53800],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'RKQ50CVA (2.0HP)', 'price' => 53800],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'FTKQ60CVAF (2.5HP)', 'price' => 62300],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'RKQ60CVA (2.5HP)', 'price' => 62300],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'FTKQ71CVAF (3.0HP)', 'price' => 86900],
            ['title' => 'New D-Smart Series – Wall Mounted', 'model' => 'RKQ71CVA (3.0HP)', 'price' => 86900],

            // NEW D-SMART QUEEN SERIES – Wall Mounted
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'FTKC25BVAF (1.0HP)', 'price' => 44900],
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'RKC25BVA (1.0HP)', 'price' => 44900],
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'FTKC35BVAF (1.5HP)', 'price' => 50400],
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'RKC35BVA (1.5HP)', 'price' => 50400],
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'FTKC50BVAF (2.0HP)', 'price' => 64600],
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'RKC50BVA (2.0HP)', 'price' => 64600],
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'FTKC60BVAF (2.5HP)', 'price' => 75000],
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'RKC60BVA (2.5HP)', 'price' => 75000],
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'FTKC71BVAF (3.0HP)', 'price' => 104600],
            ['title' => 'New D-Smart Queen Series – Wall Mounted', 'model' => 'RKC71BVA (3.0HP)', 'price' => 104600],

            // D-SMART KING SERIES – Wall Mounted
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'FTKZ25WVM (1.0HP)', 'price' => 56800],
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'RKZ25WVM (1.0HP)', 'price' => 56800],
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'FTKZ35WVM (1.5HP)', 'price' => 62800],
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'RKZ35WVM (1.5HP)', 'price' => 62800],
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'FTKZ50WVM (2.0HP)', 'price' => 80300],
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'RKZ50WVM (2.0HP)', 'price' => 80300],
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'FTKZ60WVM (2.5HP)', 'price' => 93400],
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'RKZ60WVM (2.5HP)', 'price' => 93400],
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'FTKZ71WVM (3.0HP)', 'price' => 125800],
            ['title' => 'D-Smart King Series – Wall Mounted', 'model' => 'RKZ71WVM (3.0HP)', 'price' => 125800],

            // DAIKIN AMIHAN – Wall Mounted
            ['title' => 'Daikin Amihan – Wall Mounted', 'model' => 'FTKE20AVA (0.8HP)', 'price' => 29300],
            ['title' => 'Daikin Amihan – Wall Mounted', 'model' => 'RKE20AVA (0.8HP)', 'price' => 29300],
            ['title' => 'Daikin Amihan – Wall Mounted', 'model' => 'FTKE25AVA (1.0HP)', 'price' => 32000],
            ['title' => 'Daikin Amihan – Wall Mounted', 'model' => 'RKE25AVA (1.0HP)', 'price' => 32000],
            ['title' => 'Daikin Amihan – Wall Mounted', 'model' => 'FTKE35AVA (1.5HP)', 'price' => 34000],
            ['title' => 'Daikin Amihan – Wall Mounted', 'model' => 'RKE35AVA (1.5HP)', 'price' => 34000],
            ['title' => 'Daikin Amihan – Wall Mounted', 'model' => 'FTKE50AVA (2.0HP)', 'price' => 48700],
            ['title' => 'Daikin Amihan – Wall Mounted', 'model' => 'RKE50AVA (2.0HP)', 'price' => 48700],
        ];

        foreach ($products as $item) {
            Product::updateOrCreate(
                ['model' => $item['model']],
                [
                    'name' => '-', // required column placeholder
                    'brand_id' => $brand->id,
                    'supplier_id' => $supplier->id,
                    'description' => $item['title'],
                    'price' => $item['price'],
                    'stock_quantity' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}
