<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixSkyCeilingSuspendedPairings extends Migration
{
    /**
     * Per the SKY Standard price list, these indoor models belong to the
     * "Ceiling Suspended" line and pair with RZFC*AXVM outdoor units (not
     * yet in the catalog). In production they were incorrectly paired with
     * the RZFC*BXVM outdoor units, which the price list assigns to the
     * "Floor Mounted" (FVFC*) indoor units instead.
     */
    private const INCORRECT_PAIRS = [
        'FHFC100AVM' => 'RZFC100BXVM',
        'FHFC140AVM' => 'RZFC140BXVM',
    ];

    public function up()
    {
        $products = DB::table('products')->get(['id', 'model', 'unit_type', 'paired_product_id']);
        $indoors  = $products->where('unit_type', 'indoor');

        foreach (self::INCORRECT_PAIRS as $indoorModel => $outdoorModel) {
            $indoor = $indoors->first(function ($p) use ($indoorModel, $outdoorModel, $products) {
                if (!$this->modelMatches($p->model, $indoorModel) || !$p->paired_product_id) {
                    return false;
                }
                $outdoor = $products->firstWhere('id', $p->paired_product_id);
                return $outdoor && $this->modelMatches($outdoor->model, $outdoorModel);
            });

            if (!$indoor) {
                continue;
            }

            DB::table('products')->where('id', $indoor->id)
                ->update(['paired_product_id' => null]);
        }
    }

    /**
     * True if $model is exactly $code, or starts with $code followed by a
     * non-alphanumeric character (e.g. "FAA100BVMA — 3.0 HP" matches "FAA100BVMA").
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
        $products = DB::table('products')->get(['id', 'model', 'unit_type', 'paired_product_id']);
        $indoors  = $products->where('unit_type', 'indoor');
        $outdoors = $products->where('unit_type', 'outdoor');

        foreach (self::INCORRECT_PAIRS as $indoorModel => $outdoorModel) {
            $indoor  = $indoors->first(fn ($p) => $this->modelMatches($p->model, $indoorModel) && !$p->paired_product_id);
            $outdoor = $outdoors->first(fn ($p) => $this->modelMatches($p->model, $outdoorModel));

            if (!$indoor || !$outdoor) {
                continue;
            }

            DB::table('products')->where('id', $indoor->id)
                ->update(['paired_product_id' => $outdoor->id]);
        }
    }
}
