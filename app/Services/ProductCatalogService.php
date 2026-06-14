<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Shared product payloads for PO forms and similar JSON-driven UIs.
 *
 * Paired indoor+outdoor units (split-type sets) are presented as ONE option
 * with ONE price: id = indoor product id, pair_id = outdoor product id.
 */
class ProductCatalogService
{
    /**
     * Active products as JSON-friendly rows for purchase order create/edit screens.
     * Sets come first, then unpaired single units.
     *
     * @return list<array<string, mixed>>
     */
    public function activeProductsForPurchaseOrderJson(): array
    {
        return $this->activeProductsForPurchaseOrder()->values()->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function activeProductsForPurchaseOrder(): Collection
    {
        $products = Product::query()
            ->with(['brand', 'pairedProduct'])
            ->where('is_active', true)
            ->orderBy('brand_id')
            ->orderBy('model')
            ->get();

        $pairedOutdoorIds = $products->pluck('paired_product_id')->filter()->unique();

        $sets = $products
            ->filter(fn (Product $p) => $p->is_set_primary)
            ->map(fn (Product $p) => $this->mapSet($p));

        $singles = $products
            ->filter(fn (Product $p) => !$p->is_set_primary && !$pairedOutdoorIds->contains($p->id))
            ->map(fn (Product $p) => $this->mapProductForPurchaseOrder($p));

        return $sets->concat($singles)->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function mapSet(Product $p): array
    {
        return [
            'id'              => $p->id,
            'is_set'          => true,
            'pair_id'         => $p->paired_product_id,
            'label'           => $p->set_model_label ?: 'Unknown Set',
            'indoor_model'    => $p->model,
            'outdoor_model'   => $p->pairedProduct->model ?? '',
            'unit_type'       => 'set',
            'unit_type_label' => 'Set',
            'cost'            => (float) ($p->cost ?? 0),
            'price'           => (float) ($p->price ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapProductForPurchaseOrder(Product $p): array
    {
        return [
            'id'              => $p->id,
            'is_set'          => false,
            'pair_id'         => null,
            'label'           => $p->model ?: 'Unknown Product',
            'indoor_model'    => $p->unit_type === 'indoor' ? $p->model : '',
            'outdoor_model'   => $p->unit_type === 'outdoor' ? $p->model : '',
            'unit_type'       => $p->unit_type,
            'unit_type_label' => $p->unit_type ? ucfirst($p->unit_type) : null,
            'cost'            => (float) ($p->cost ?? 0),
            'price'           => (float) ($p->price ?? 0),
        ];
    }
}
