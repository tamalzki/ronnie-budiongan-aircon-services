<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indoor + outdoor units of a split-type aircon are sold/purchased as ONE set
 * with ONE unit price. The pairing lives on the indoor product row:
 * products.paired_product_id → its outdoor counterpart.
 *
 * Line items encoded as a set (one price covering both units) are flagged
 * with is_set on purchase_order_items / sale_items.
 */
class AddProductPairingAndSetFlags extends Migration
{
    /**
     * Known indoor model → outdoor model pairs.
     * Multiple indoor models may share the same outdoor model
     * (e.g. ceiling and floor-standing units using the same condenser).
     */
    private const PAIRS = [
        // Cooling King Series
        'FTNE20AXVL9' => 'RNE20AGXVL9',
        'FTN25AXVL9'  => 'RN25AGXVL9',
        'FTN35AXVL9'  => 'RN35AGXVL9',
        'FTN50AXVL9'  => 'RN50AGXVL9',
        'FTN60AXVL9'  => 'RN60AGXVL9',
        'FTN71AXVL'   => 'RN71AGXVL',
        // D-Smart Series
        'FTKQ20CVAF'  => 'RKQ20CVA',
        'FTKQ25CVAF'  => 'RKQ25CVA',
        'FTKQ35CVAF'  => 'RKQ35CVA',
        'FTKQ50CVAF'  => 'RKQ50CVA',
        'FTKQ60CVAF'  => 'RKQ60CVA',
        'FTKQ71CVAF'  => 'RKQ71CVA',
        'FTKQ20BVA'   => 'RKQ20BVA',
        // D-Smart Queen Series
        'FTKC25BVAF'  => 'RKC25BVA',
        'FTKC35BVAF'  => 'RKC35BVA',
        'FTKC50BVAF'  => 'RKC50BVA',
        'FTKC60BVAF'  => 'RKC60BVA',
        'FTKC71BVAF'  => 'RKC71BVA',
        // D-Smart King Series
        'FTKZ25WVM'   => 'RKZ25WVM',
        'FTKZ35WVM'   => 'RKZ35WVM',
        'FTKZ50WVM'   => 'RKZ50WVM',
        'FTKZ60WVM'   => 'RKZ60WVM',
        'FTKZ71WVM'   => 'RKZ71WVM',
        // D-Smart Lite Series
        'FTKF20GVAF'  => 'RKF20GVA',
        'FTKF25GVAF'  => 'RKF25GVA',
        'FTKF35GVAF'  => 'RKF35GVA',
        'FTKF50GVAF'  => 'RKF50GVA',
        'FTKF60GVAF'  => 'RKF60GVA',
        'FTKF71GVAF'  => 'RKF71GVA',
        'FTKF50CVA'   => 'RKF50CVA',
        // Daikin Amihan
        'FTKE20AVA'   => 'RKE20AVA',
        'FTKE25AVA'   => 'RKE25AVA',
        'FTKE35AVA'   => 'RKE35AVA',
        'FTKE50AVA'   => 'RKE50AVA',
        // Floor standing / ceiling inverters
        'FVFC71BXVA'  => 'RZFC71BXVM',
        'FVFC100BXVA' => 'RZFC100BXVM',
        'FVFC140BXVA' => 'RZFC140BXVM',
        'FHFC100AVM'  => 'RZFC100BXVM',
        'FHFC140AVM'  => 'RZFC140BXVM',
    ];

    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('paired_product_id')->nullable()->after('unit_type');
            $table->foreign('paired_product_id')->references('id')->on('products')->nullOnDelete();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->boolean('is_set')->default(false)->after('product_id');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('is_set')->default(false)->after('product_id');
        });

        $this->backfillPairs();
    }

    private function backfillPairs(): void
    {
        $products = DB::table('products')->get(['id', 'model', 'unit_type', 'price']);

        $indoorByModel  = $products->where('unit_type', 'indoor')->keyBy(fn ($p) => strtoupper(trim($p->model)));
        $outdoorByModel = $products->where('unit_type', 'outdoor')->keyBy(fn ($p) => strtoupper(trim($p->model)));

        foreach (self::PAIRS as $indoorModel => $outdoorModel) {
            $indoor  = $indoorByModel->get(strtoupper($indoorModel));
            $outdoor = $outdoorByModel->get(strtoupper($outdoorModel));

            if (!$indoor || !$outdoor) {
                continue;
            }

            DB::table('products')->where('id', $indoor->id)
                ->update(['paired_product_id' => $outdoor->id]);

            // One set = one price. If one side is missing a price, copy from the other.
            if ((float) $indoor->price <= 0 && (float) $outdoor->price > 0) {
                DB::table('products')->where('id', $indoor->id)->update(['price' => $outdoor->price]);
            } elseif ((float) $outdoor->price <= 0 && (float) $indoor->price > 0) {
                DB::table('products')->where('id', $outdoor->id)->update(['price' => $indoor->price]);
            }
        }
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['paired_product_id']);
            $table->dropColumn('paired_product_id');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('is_set');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('is_set');
        });
    }
}
