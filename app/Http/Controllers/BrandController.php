<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount('products')->orderBy('name')->get();
        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        return view('brands.create');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        // ✅ Remove 'is_active' => 'boolean',
    ]);

    $validated['is_active'] = $request->has('is_active'); // ✅ Add this line

    Brand::create($validated);

    return redirect()->route('brands.index')
        ->with('success', 'Brand created successfully.');
}

    public function show(Brand $brand)
    {
        $brand->load('products');
        return view('brands.show', compact('brand'));
    }

    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        // ✅ Remove 'is_active' => 'boolean',
    ]);

    $validated['is_active'] = $request->has('is_active'); // ✅ Add this line

    $brand->update($validated);

    return redirect()->route('brands.index')
        ->with('success', 'Brand updated successfully.');
}


    public function destroy(Brand $brand)
    {
        if ($brand->products()->count() > 0) {
            return redirect()->route('brands.index')
                ->with('error', 'Cannot delete brand with associated products.');
        }

        $brand->delete();

        return redirect()->route('brands.index')
            ->with('success', 'Brand deleted successfully.');
    }
}