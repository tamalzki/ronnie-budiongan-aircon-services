<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Supplier;
use App\Models\ProductSerial;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index()
    {
        $all = Product::with(['brand'])
            ->withCount([
                'serials as in_stock_count'  => fn($q) => $q->where('status', 'in_stock'),
                'serials as pending_count'   => fn($q) => $q->where('status', 'pending'),
                'serials as sold_count'      => fn($q) => $q->where('status', 'sold'),
                'serials as linked_serials_count',
                'saleItems',
                'purchaseOrderItems',
                'inventoryMovements',
            ])
            ->orderBy('brand_id')
            ->orderBy('model')
            ->get();

        // Indoor + paired outdoor display as ONE line (one price per set)
        $byId             = $all->keyBy('id');
        $pairedOutdoorIds = $all->pluck('paired_product_id')->filter()->unique();

        $products = $all
            ->reject(fn($p) => $pairedOutdoorIds->contains($p->id))
            ->map(function ($p) use ($byId) {
                $p->setRelation('pairedProduct', $p->paired_product_id ? ($byId[$p->paired_product_id] ?? null) : null);

                return $p;
            })
            ->values();

        $stockOf = function ($p) {
            $own = (int) ($p->in_stock_count ?? 0);
            if ($p->pairedProduct) {
                return min($own, (int) ($p->pairedProduct->in_stock_count ?? 0));
            }

            return $own;
        };

        $noPriceCount = $products->where('price', 0)->count();

        $totalProducts = $products->count();
        $outOfStock = $products->filter(fn($p) => $stockOf($p) === 0)->count();
        $lowStock = $products->filter(function ($p) use ($stockOf) {
            $n = $stockOf($p);

            return $n >= 1 && $n <= 5;
        })->count();
        $mediumStock = $products->filter(function ($p) use ($stockOf) {
            $n = $stockOf($p);

            return $n >= 6 && $n <= 20;
        })->count();
        $highStock = $products->filter(fn($p) => $stockOf($p) > 20)->count();
        $totalValue = $products->sum(fn($p) => $stockOf($p) * (float) $p->price);

        return view('products.index', compact(
            'products',
            'noPriceCount',
            'totalProducts',
            'lowStock',
            'mediumStock',
            'highStock',
            'outOfStock',
            'totalValue'
        ));
    }

    public function show(Product $product)
    {
        $product->load(['brand', 'supplier']);

        $serials = $product->serials()
            ->with(['purchaseOrder'])
            ->orderByRaw("FIELD(status, 'in_stock', 'pending', 'sold', 'returned', 'defective', 'lost')")
            ->orderBy('serial_number')
            ->get();

        $counts = [
            'in_stock'  => $serials->where('status', 'in_stock')->count(),
            'pending'   => $serials->where('status', 'pending')->count(),
            'sold'      => $serials->where('status', 'sold')->count(),
            'returned'  => $serials->where('status', 'returned')->count(),
            'defective' => $serials->where('status', 'defective')->count(),
            'lost'      => $serials->where('status', 'lost')->count(),
        ];

        return view('products.show', compact('product', 'serials', 'counts'));
    }

    public function create()
    {
        $brands    = Brand::orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('products.create', compact('brands', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_id'    => 'required|exists:brands,id',
            'model'       => 'required|string|max:255',
            'unit_type'   => 'required|in:indoor,outdoor',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable|string',
            'cost'        => 'nullable|numeric|min:0',
            'price'       => 'required|numeric|min:0.01',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['cost']      = $validated['cost'] ?? 0;

        $brand = Brand::find($validated['brand_id']);
        $validated['name'] = $brand->name . ' ' . $validated['model'];

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product added successfully.');
    }

    public function edit(Product $product)
    {
        $brands    = Brand::orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        // Outdoor units available to pair with (free, or already paired to this product)
        $outdoorUnits = Product::where('unit_type', 'outdoor')
            ->whereKeyNot($product->id)
            ->orderBy('model')
            ->get()
            ->filter(function ($o) use ($product) {
                $pairedTo = Product::where('paired_product_id', $o->id)->whereKeyNot($product->id)->exists();

                return !$pairedTo;
            })
            ->values();

        return view('products.edit', compact('product', 'brands', 'suppliers', 'outdoorUnits'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'brand_id'          => 'required|exists:brands,id',
            'model'             => 'required|string|max:255',
            'unit_type'         => 'required|in:indoor,outdoor',
            'supplier_id'       => 'nullable|exists:suppliers,id',
            'description'       => 'nullable|string',
            'cost'              => 'nullable|numeric|min:0',
            'price'             => 'required|numeric|min:0.01',
            'paired_product_id' => 'nullable|exists:products,id',
        ]);

        if ($validated['unit_type'] !== 'indoor') {
            $validated['paired_product_id'] = null;
        } elseif (!empty($validated['paired_product_id'])) {
            $outdoor = Product::find($validated['paired_product_id']);
            if (!$outdoor || $outdoor->unit_type !== 'outdoor' || (int) $outdoor->id === (int) $product->id) {
                return back()->withInput()->withErrors(['paired_product_id' => 'The paired unit must be an outdoor unit.']);
            }
            $takenBy = Product::where('paired_product_id', $outdoor->id)->whereKeyNot($product->id)->first();
            if ($takenBy) {
                return back()->withInput()->withErrors(['paired_product_id' => $outdoor->model . ' is already paired with ' . $takenBy->model . '.']);
            }
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['cost']      = $validated['cost'] ?? 0;

        $brand = Brand::find($validated['brand_id']);
        $validated['name'] = $brand->name . ' ' . $validated['model'];

        $product->update($validated);

        // One price per set — keep the paired outdoor unit in sync
        if ($product->paired_product_id) {
            Product::whereKey($product->paired_product_id)->update([
                'price' => $validated['price'],
                'cost'  => $validated['cost'],
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function setPrice(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $request->validate(['price' => 'required|numeric|min:0.01']);

        $product->update(['price' => $request->price]);

        // One price per set — keep the paired outdoor unit in sync
        if ($product->paired_product_id) {
            Product::whereKey($product->paired_product_id)->update(['price' => $request->price]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'price' => $request->price]);
        }

        return back()->with('success',
            ($product->brand->name ?? '') . ' ' . $product->model .
            ' selling price set to ₱' . number_format($request->price, 2) . '.');
    }

    public function destroy(Product $product)
    {
        $label = $product->display_model;
        $blocks = [];

        if ($product->saleItems()->exists()) {
            $blocks[] = 'sales line items';
        }
        if ($product->purchaseOrderItems()->exists()) {
            $blocks[] = 'purchase order lines';
        }
        if ($product->inventoryMovements()->exists()) {
            $blocks[] = 'inventory movements';
        }
        if ($product->serials()->exists()) {
            $blocks[] = 'serial / stock records';
        }

        if ($blocks !== []) {
            return redirect()->route('products.index')
                ->with('error',
                    'Cannot delete "' . $label . '" because it is linked to: ' . implode(', ', $blocks) . '. '
                    . 'Remove or resolve those records first, or deactivate the product instead.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }
}