<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Part;
use App\Services\ProductCatalogService;
use Illuminate\Http\Request;

class PartController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Part::class, 'part');
    }

    public function index(Request $request)
    {
        $parts = Part::with('product.pairedProduct')->orderBy('name')->get();

        return view('parts.index', compact('parts'));
    }

    public function create(ProductCatalogService $catalog)
    {
        $productOptions = $catalog->activeProductsForPurchaseOrder();

        return view('parts.create', compact('productOptions'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        Part::create($validated);

        return redirect()->route('parts.index')
            ->with('success', 'Aircon part created successfully.');
    }

    public function show(Part $part)
    {
        $part->load('product.pairedProduct');

        $movements = $part->movements()->with('user')->latest()->take(20)->get();

        return view('parts.show', compact('part', 'movements'));
    }

    public function edit(Part $part, ProductCatalogService $catalog)
    {
        $productOptions = $catalog->activeProductsForPurchaseOrder();

        return view('parts.edit', compact('part', 'productOptions'));
    }

    public function update(Request $request, Part $part)
    {
        $validated = $this->validateData($request);

        $part->update($validated);

        return redirect()->route('parts.index')
            ->with('success', 'Aircon part updated successfully.');
    }

    public function destroy(Part $part)
    {
        if ($part->purchaseOrderItems()->exists()) {
            return redirect()->route('parts.index')
                ->with('error', 'Cannot delete this aircon part — it is referenced on one or more purchase orders.');
        }

        $part->delete();

        return redirect()->route('parts.index')
            ->with('success', 'Aircon part deleted successfully.');
    }

    public function stockIn(Request $request, Part $part)
    {
        $this->authorize('update', $part);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'notes'    => ['nullable', 'string'],
        ]);

        $stockBefore = $part->stock_quantity;

        InventoryMovement::create([
            'part_id'        => $part->id,
            'type'           => 'stock_in',
            'quantity'       => $validated['quantity'],
            'stock_before'   => $stockBefore,
            'stock_after'    => $stockBefore + $validated['quantity'],
            'reference_type' => 'Manual Stock In',
            'notes'          => $validated['notes'] ?? 'Manual stock in',
            'user_id'        => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Stock added: +' . $validated['quantity'] . ' ' . $part->name . '.');
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'product_id'  => ['nullable', 'exists:products,id'],
            'description' => ['nullable', 'string'],
            'cost'        => ['required', 'numeric', 'min:0'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        return $validated;
    }
}
