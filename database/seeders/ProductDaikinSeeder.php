<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class ProductDaikinSeeder extends Seeder
{
    public function run(): void
    {
        // ----------------------------------------------------------------
        // Ensure Brand & Supplier exist before inserting products
        // ----------------------------------------------------------------
        $brandId = DB::table('brands')->insertGetId([
            'name'       => 'Daikin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // If brand already exists, grab its ID instead
        // Comment out the block above and use this if re-running:
        // $brandId = DB::table('brands')->where('name', 'Daikin')->value('id');

        $supplierId = DB::table('suppliers')->insertGetId([
            'name'       => 'Daikin Philippines',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // $supplierId = DB::table('suppliers')->where('name', 'Daikin Philippines')->value('id');

        // ----------------------------------------------------------------
        // Product data: [indoor model, outdoor model, description, price]
        // ----------------------------------------------------------------
        $products = [

            // COOLING KING SERIES (Non-Inverter)
            ['indoor' => 'FTNE20AXVL9', 'outdoor' => 'RNE20AGXVL9', 'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 0.8HP', 'price' => 23800],
            ['indoor' => 'FTN25AXVL9',  'outdoor' => 'RN25AGXVL9',  'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 1.0HP', 'price' => 28400],
            ['indoor' => 'FTN35AXVL9',  'outdoor' => 'RN35AGXVL9',  'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 1.5HP', 'price' => 32100],
            ['indoor' => 'FTN50AXVL9',  'outdoor' => 'RN50AGXVL9',  'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 2.0HP', 'price' => 45200],
            ['indoor' => 'FTN60AXVL9',  'outdoor' => 'RN60AGXVL9',  'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 2.5HP', 'price' => 53700],
            ['indoor' => 'FTN71AXVL',   'outdoor' => 'RN71AGXVL',   'description' => 'Cooling King Series – Premium Wall Mounted (Non-Inverter) – 3.0HP', 'price' => 78300],

            // D-SMART SERIES (Inverter)
            ['indoor' => 'FTKQ20CVAF', 'outdoor' => 'RKQ20CVA', 'description' => 'D-Smart Series – Wall Mounted – 0.8HP', 'price' => 32500],
            ['indoor' => 'FTKQ25CVAF', 'outdoor' => 'RKQ25CVA', 'description' => 'D-Smart Series – Wall Mounted – 1.0HP', 'price' => 36700],
            ['indoor' => 'FTKQ35CVAF', 'outdoor' => 'RKQ35CVA', 'description' => 'D-Smart Series – Wall Mounted – 1.5HP', 'price' => 41200],
            ['indoor' => 'FTKQ50CVAF', 'outdoor' => 'RKQ50CVA', 'description' => 'D-Smart Series – Wall Mounted – 2.0HP', 'price' => 53800],
            ['indoor' => 'FTKQ60CVAF', 'outdoor' => 'RKQ60CVA', 'description' => 'D-Smart Series – Wall Mounted – 2.5HP', 'price' => 62300],
            ['indoor' => 'FTKQ71CVAF', 'outdoor' => 'RKQ71CVA', 'description' => 'D-Smart Series – Wall Mounted – 3.0HP', 'price' => 86900],

            // D-SMART QUEEN SERIES
            ['indoor' => 'FTKC25BVAF', 'outdoor' => 'RKC25BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 1.0HP', 'price' => 44900],
            ['indoor' => 'FTKC35BVAF', 'outdoor' => 'RKC35BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 1.5HP', 'price' => 50400],
            ['indoor' => 'FTKC50BVAF', 'outdoor' => 'RKC50BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 2.0HP', 'price' => 64600],
            ['indoor' => 'FTKC60BVAF', 'outdoor' => 'RKC60BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 2.5HP', 'price' => 75000],
            ['indoor' => 'FTKC71BVAF', 'outdoor' => 'RKC71BVA', 'description' => 'D-Smart Queen Series – Wall Mounted – 3.0HP', 'price' => 104600],

            // D-SMART KING SERIES
            ['indoor' => 'FTKZ25WVM', 'outdoor' => 'RKZ25WVM', 'description' => 'D-Smart King Series – Wall Mounted – 1.0HP', 'price' => 56800],
            ['indoor' => 'FTKZ35WVM', 'outdoor' => 'RKZ35WVM', 'description' => 'D-Smart King Series – Wall Mounted – 1.5HP', 'price' => 62800],
            ['indoor' => 'FTKZ50WVM', 'outdoor' => 'RKZ50WVM', 'description' => 'D-Smart King Series – Wall Mounted – 2.0HP', 'price' => 80300],
            ['indoor' => 'FTKZ60WVM', 'outdoor' => 'RKZ60WVM', 'description' => 'D-Smart King Series – Wall Mounted – 2.5HP', 'price' => 93400],
            ['indoor' => 'FTKZ71WVM', 'outdoor' => 'RKZ71WVM', 'description' => 'D-Smart King Series – Wall Mounted – 3.0HP', 'price' => 125800],

            // D-SMART LITE SERIES
            ['indoor' => 'FTKF20GVAF', 'outdoor' => 'RKF20GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 0.8HP', 'price' => 29900],
            ['indoor' => 'FTKF25GVAF', 'outdoor' => 'RKF25GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 1.0HP', 'price' => 34400],
            ['indoor' => 'FTKF35GVAF', 'outdoor' => 'RKF35GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 1.5HP', 'price' => 39400],
            ['indoor' => 'FTKF50GVAF', 'outdoor' => 'RKF50GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 2.0HP', 'price' => 50500],
            ['indoor' => 'FTKF60GVAF', 'outdoor' => 'RKF60GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 2.5HP', 'price' => 59600],
            ['indoor' => 'FTKF71GVAF', 'outdoor' => 'RKF71GVA', 'description' => 'D-Smart Lite Series – Wall Mounted – 3.0HP', 'price' => 83200],

            // DAIKIN AMIHAN
            ['indoor' => 'FTKE20AVA', 'outdoor' => 'RKE20AVA', 'description' => 'Daikin Amihan – Wall Mounted – 0.8HP', 'price' => 29300],
            ['indoor' => 'FTKE25AVA', 'outdoor' => 'RKE25AVA', 'description' => 'Daikin Amihan – Wall Mounted – 1.0HP', 'price' => 32000],
            ['indoor' => 'FTKE35AVA', 'outdoor' => 'RKE35AVA', 'description' => 'Daikin Amihan – Wall Mounted – 1.5HP', 'price' => 34000],
            ['indoor' => 'FTKE50AVA', 'outdoor' => 'RKE50AVA', 'description' => 'Daikin Amihan – Wall Mounted – 2.0HP', 'price' => 48700],
        ];

        // ----------------------------------------------------------------
        // Build insert array — columns match DB schema exactly
        // ----------------------------------------------------------------
        $insertData = [];

        foreach ($products as $product) {

            // Indoor Unit
            $insertData[] = [
                'name'          => 'Daikin ' . $product['indoor'],
                'brand_id'      => $brandId,
                'supplier_id'   => $supplierId,
                'model'         => $product['indoor'],
                'unit_type'     => 'indoor',
                'serial_number' => null,
                'description'   => $product['description'],
                'price'         => $product['price'],
                'cost'          => 0.00,
                'stock_quantity'=> 0,
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            // Outdoor Unit
            $insertData[] = [
                'name'          => 'Daikin ' . $product['outdoor'],
                'brand_id'      => $brandId,
                'supplier_id'   => $supplierId,
                'model'         => $product['outdoor'],
                'unit_type'     => 'outdoor',
                'serial_number' => null,
                'description'   => $product['description'],
                'price'         => $product['price'],
                'cost'          => 0.00,
                'stock_quantity'=> 0,
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        Product::insert($insertData);

        $total   = count($insertData);
        $perType = $total / 2;

        $this->command->info("✅ Seeded {$total} Daikin products ({$perType} indoor + {$perType} outdoor)");
    }
}