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
            'brand_id'      => 'required|exists:brands,id',
            'indoor_model'  => 'required|string|max:255',
            'outdoor_model' => 'required|string|max:255',
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'description'   => 'nullable|string',
        ]);

        $isActive = $request->has('is_active');
        $brand    = Brand::find($validated['brand_id']);

        $shared = [
            'brand_id'    => $validated['brand_id'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'cost'        => 0,
            'price'       => 0,
            'is_active'   => $isActive,
        ];

        $indoor = Product::create($shared + [
            'model'     => $validated['indoor_model'],
            'name'      => $brand->name . ' ' . $validated['indoor_model'],
            'unit_type' => 'indoor',
        ]);

        $outdoor = Product::create($shared + [
            'model'     => $validated['outdoor_model'],
            'name'      => $brand->name . ' ' . $validated['outdoor_model'],
            'unit_type' => 'outdoor',
        ]);

        $indoor->update(['paired_product_id' => $outdoor->id]);

        return redirect()->route('products.index')->with('success', 'Indoor and outdoor units added as a set.');
    }

    public function edit(Product $product)
    {
        $brands    = Brand::orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('products.edit', compact('product', 'brands', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'brand_id'     => 'required|exists:brands,id',
            'model'        => 'required|string|max:255',
            'paired_model' => 'nullable|string|max:255',
            'supplier_id'  => 'nullable|exists:suppliers,id',
            'description'  => 'nullable|string',
            'cost'         => 'nullable|numeric|min:0',
            'price'        => 'required|numeric|min:0.01',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['cost']      = $validated['cost'] ?? 0;

        $brand = Brand::find($validated['brand_id']);
        $validated['name'] = $brand->name . ' ' . $validated['model'];

        $pairedModel = $validated['paired_model'] ?? null;
        unset($validated['paired_model']);

        $product->update($validated);

        // One price per set — keep the paired outdoor unit in sync
        if ($product->paired_product_id) {
            $pairedUpdate = [
                'price' => $validated['price'],
                'cost'  => $validated['cost'],
            ];

            if ($pairedModel) {
                $pairedBrand = $product->pairedProduct->brand ?? $brand;
                $pairedUpdate['model'] = $pairedModel;
                $pairedUpdate['name']  = $pairedBrand->name . ' ' . $pairedModel;
            }

            Product::whereKey($product->paired_product_id)->update($pairedUpdate);
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