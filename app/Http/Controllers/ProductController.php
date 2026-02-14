<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products     = Product::with(['brand', 'supplier'])->orderBy('brand_id')->orderBy('model')->get();
        $noPriceCount = $products->where('price', 0)->count();

        return view('products.index', compact('products', 'noPriceCount'));
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
            'brand_id'       => 'required|exists:brands,id',
            'model'          => 'required|string|max:255',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'description'    => 'nullable|string',
            'price'          => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['cost']      = 0;
        $validated['price']     = $validated['price'] ?? 0;

        $brand = Brand::find($validated['brand_id']);
        $validated['name'] = $brand->name . ' ' . $validated['model'];

        Product::create($validated);

        $msg = $validated['price'] == 0
            ? 'Product added. ⚠️ No selling price set — set it after receiving from Purchase Order.'
            : 'Product added successfully.';

        return redirect()->route('products.index')->with('success', $msg);
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
            'brand_id'       => 'required|exists:brands,id',
            'model'          => 'required|string|max:255',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'description'    => 'nullable|string',
            'price'          => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['price']     = $validated['price'] ?? 0;

        $brand = Brand::find($validated['brand_id']);
        $validated['name'] = $brand->name . ' ' . $validated['model'];

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Quick price setter — called from PO show page after receiving stock
     */
    public function setPrice(Request $request, Product $product)
    {
        $request->validate([
            'price' => 'required|numeric|min:0.01',
        ]);

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