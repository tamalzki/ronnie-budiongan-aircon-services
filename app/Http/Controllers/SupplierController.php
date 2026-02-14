<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount('products')->orderBy('name')->get();
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'contact_person' => 'nullable|string|max:255',
        'contact_number' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'address' => 'nullable|string',
        // ✅ Remove 'is_active' => 'boolean',
    ]);

    $validated['is_active'] = $request->has('is_active'); // ✅ Add this line

    Supplier::create($validated);

    return redirect()->route('suppliers.index')
        ->with('success', 'Supplier created successfully.');
}


    public function show(Supplier $supplier)
    {
        $supplier->load('products');
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'contact_person' => 'nullable|string|max:255',
        'contact_number' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'address' => 'nullable|string',
        // ✅ Remove 'is_active' => 'boolean',
    ]);

    $validated['is_active'] = $request->has('is_active'); // ✅ Add this line

    $supplier->update($validated);

    return redirect()->route('suppliers.index')
        ->with('success', 'Supplier updated successfully.');
}


    public function destroy(Supplier $supplier)
    {
        if ($supplier->products()->count() > 0) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Cannot delete supplier with associated products.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}