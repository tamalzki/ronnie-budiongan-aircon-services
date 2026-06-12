<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PairFaa100BvmaWithRzf100Cvm extends Migration
{
    private const PAIRS = [
        'FAA100BVMA' => 'RZF100CVM',
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
        // No-op: reversing would unset a pairing that may have existed before
        // this migration ran for unrelated reasons.
    }
}
