<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The previous pairing backfill matched products.model with an EXACT
 * uppercase comparison. Some catalog rows store extra text after the
 * model code (e.g. "FTKQ35CVAF — 1.5 HP"), so those rows were skipped
 * and are still missing paired_product_id.
 *
 * This migration re-applies the same known indoor/outdoor pairs, but
 * matches by prefix (the model code followed by end-of-string or a
 * non-alphanumeric character), and only touches indoor rows that still
 * have paired_product_id NULL.
 */
class FixUnpairedProductsWithModelSuffixes extends Migration
{
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
        $products = DB::table('products')->get(['id', 'model', 'unit_type', 'paired_product_id', 'price']);

        $indoors  = $products->where('unit_type', 'indoor')->whereNull('paired_product_id');
        $outdoors = $products->where('unit_type', 'outdoor');

        foreach (self::PAIRS as $indoorModel => $outdoorModel) {
            $indoor  = $indoors->first(fn ($p) => $this->modelMatches($p->model, $indoorModel));
            $outdoor = $outdoors->first(fn ($p) => $this->modelMatches($p->model, $outdoorModel));

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

    /**
     * True if $model is exactly $code, or starts with $code followed by a
     * non-alphanumeric character (e.g. "FTKQ35CVAF — 1.5 HP" matches "FTKQ35CVAF").
     */
    private function modelMatches(?string $model, string $code): bool
    {
        $model = strtoupper(trim((string) $model));
        $code  = strtoupper($code);

        if ($model === $code) {
            return true;
        }

        if (!str_starts_with($model, $code)) {
            return false;
        }

        $next = substr($model, strlen($code), 1);
        return $next !== '' && !ctype_alnum($next);
    }

    public function down()
    {
        // No-op: reversing would require knowing which pairs this migration
        // created vs. ones that already existed. The original migration's
        // down() already drops the paired_product_id column entirely.
    }
}
