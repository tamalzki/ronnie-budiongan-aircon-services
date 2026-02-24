<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Supplier;
use App\Models\ProductSerial;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['brand', 'supplier'])
            ->withCount([
                'serials as in_stock_count'  => fn($q) => $q->where('status', 'in_stock'),
                'serials as pending_count'   => fn($q) => $q->where('status', 'pending'),
                'serials as sold_count'      => fn($q) => $q->where('status', 'sold'),
            ])
            ->orderBy('brand_id')
            ->orderBy('model')
            ->get();

        $noPriceCount = $products->where('price', 0)->count();

        return view('products.index', compact('products', 'noPriceCount'));
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

        return view('products.edit', compact('product', 'brands', 'suppliers'));
    }

    public function update(Request $request, Product $product)
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

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function setPrice(Request $request, Product $product)
    {
        $request->validate(['price' => 'required|numeric|min:0.01']);

        $product->update(['price' => $request->price]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'price' => $request->price]);
        }

        return back()->with('success',
            ($product->brand->name ?? '') . ' ' . $product->model .
            ' selling price set to ₱' . number_format($request->price, 2) . '.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }
}