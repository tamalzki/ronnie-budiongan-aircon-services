<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Shared product payloads for PO forms and similar JSON-driven UIs.
 */
class ProductCatalogService
{
    /**
     * Active products as JSON-friendly rows for purchase order create/edit screens.
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
        return Product::query()
            ->with('brand')
            ->where('is_active', true)
            ->orderBy('brand_id')
            ->orderBy('model')
            ->get()
            ->map(fn(Product $p) => $this->mapProductForPurchaseOrder($p));
    }

    /**
     * @return array<string, mixed>
     */
    public function mapProductForPurchaseOrder(Product $p): array
    {
        $parts = array_filter([
            $p->brand->name ?? null,
            $p->model ?? null,
        ]);

        return [
            'id'              => $p->id,
            'label'           => implode(' · ', $parts) ?: 'Unknown Product',
            'unit_type'       => $p->unit_type,
            'unit_type_label' => $p->unit_type ? ucfirst($p->unit_type) : null,
            'cost'            => (float) ($p->cost ?? 0),
        ];
    }
}
