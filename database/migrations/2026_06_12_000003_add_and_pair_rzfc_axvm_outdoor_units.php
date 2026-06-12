<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddAndPairRzfcAxvmOutdoorUnits extends Migration
{
    /**
     * Per the SKY Standard price list, these "Ceiling Suspended" indoor
     * units pair with RZFC*AXVM outdoor units, which don't exist in the
     * catalog yet. Create them and pair so the set shows up correctly;
     * details (cost/price/supplier) can be edited on the Products page.
     */
    private const PAIRS = [
        'FHFC100AVM' => ['outdoor_model' => 'RZFC100AXVM', 'hp' => '4.0'],
        'FHFC140AVM' => ['outdoor_model' => 'RZFC140AXVM', 'hp' => '5.0'],
    ];

    public function up()
    {
        foreach (self::PAIRS as $indoorModel => $info) {
            $indoor = DB::table('products')
                ->where('model', $indoorModel)
                ->where('unit_type', 'indoor')
                ->whereNull('paired_product_id')
                ->first();

            if (!$indoor) {
                continue;
            }

            $outdoorId = DB::table('products')->insertGetId([
                'brand_id'    => $indoor->brand_id,
                'model'       => $info['outdoor_model'],
                'name'        => 'Daikin ' . $info['outdoor_model'] . ' Outdoor Inverter ' . $info['hp'] . 'HP',
                'unit_type'   => 'outdoor',
                'supplier_id' => $indoor->supplier_id,
                'description' => null,
                'cost'        => 0,
                'price'       => 0,
                'is_active'   => $indoor->is_active,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('products')->where('id', $indoor->id)
                ->update(['paired_product_id' => $outdoorId]);
        }
    }

    public function down()
    {
        foreach (self::PAIRS as $indoorModel => $info) {
            $outdoor = DB::table('products')
                ->where('model', $info['outdoor_model'])
                ->where('unit_type', 'outdoor')
                ->first();

            if (!$outdoor) {
                continue;
            }

            DB::table('products')
                ->where('paired_product_id', $outdoor->id)
                ->update(['paired_product_id' => null]);

            DB::table('products')->where('id', $outdoor->id)->delete();
        }
    }
}
