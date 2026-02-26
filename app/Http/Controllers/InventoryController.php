<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProductSerial;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::with(['brand', 'supplier'])
            ->withCount('inventoryMovements')
            ->orderBy('name')
            ->get();

        // Calculate stats
        $totalProducts = $products->count();
            $lowStock = $products->filter(fn($p) => $p->stock_count <= 5)->count();
            $outOfStock = $products->filter(fn($p) => $p->stock_count === 0)->count();

            $totalValue = $products->sum(function($p) {
                return $p->stock_count * $p->price;
            });

        return view('inventory.index', compact('products', 'totalProducts', 'lowStock', 'outOfStock', 'totalValue'));
    }

    public function show(Product $product)
    {
        $product->load(['brand', 'supplier']);
        
        $movements = InventoryMovement::where('product_id', $product->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate movement stats
        $totalStockIn = $movements->where('type', 'stock_in')->sum('quantity');
        $totalStockOut = abs($movements->where('type', 'stock_out')->sum('quantity'));
        $totalReturns = abs($movements->where('type', 'return')->sum('quantity'));
        $totalAdjustments = $movements->where('type', 'adjustment')->count();

        // Get suppliers for stock in form
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('inventory.show', compact('product', 'movements', 'totalStockIn', 'totalStockOut', 'totalReturns', 'totalAdjustments', 'suppliers'));
    }

    public function adjust(Request $request, Product $product)
{
    $validated = $request->validate([
        'new_quantity' => 'required|integer|min:0',
        'notes' => 'required|string',
    ]);

    DB::beginTransaction();

    try {

        $stockBefore = $product->stock_count;
        $difference = $validated['new_quantity'] - $stockBefore;

        if ($difference > 0) {
            throw new \Exception("To increase stock, use Stock In and encode serial numbers.");
        }

        if ($difference < 0) {
            $toRemove = abs($difference);

            $serials = $product->inStockSerials()
                ->orderBy('received_date')
                ->limit($toRemove)
                ->get();

            // ✅ Correct safety check
            if ($serials->count() < $toRemove) {
                throw new \Exception('Not enough available serials to adjust.');
            }

            foreach ($serials as $serial) {
                $serial->update([
                    'status' => 'lost',
                ]);
            }
        }

        $stockAfter = $product->fresh()->stock_count;

        InventoryMovement::create([
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => $difference,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => 'Manual Adjustment',
            'notes' => $validated['notes'],
            'user_id' => auth()->id(),
        ]);

        DB::commit();

        return redirect()->route('inventory.show', $product)
            ->with('success', 'Inventory adjusted successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage());
    }
}

        public function stockIn(Request $request, Product $product)
    {
        $validated = $request->validate([
            'serial_numbers' => 'required|array|min:1',
            'serial_numbers.*' => 'required|string|distinct|unique:product_serials,serial_number',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $stockBefore = $product->stock_count;

            foreach ($validated['serial_numbers'] as $serial) {
                ProductSerial::create([
                    'product_id' => $product->id,
                    'serial_number' => $serial,
                    'status' => 'in_stock',
                    'received_date' => now(),
                ]);
            }

            $stockAfter = $product->fresh()->stock_count;

            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'stock_in',
                'quantity' => count($validated['serial_numbers']),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'Manual Stock In',
                'notes' => $validated['notes'] ?? 'Manual stock in',
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('inventory.show', $product)
                ->with('success', 'Stock added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function encodeSerials(Request $request, Product $product)
{
    $expectedCount = $product->stock_count;

    $validated = $request->validate([
        'serial_numbers' => 'required|array|size:' . $expectedCount,
        'serial_numbers.*' => 'required|string|distinct|unique:product_serials,serial_number',
    ]);

    DB::beginTransaction();

    try {

        // Prevent duplicate encoding
        if ($product->inStockSerials()->count() > 0) {
            throw new \Exception('Serials already exist for this product.');
        }

        foreach ($validated['serial_numbers'] as $serial) {
            ProductSerial::create([
                'product_id'   => $product->id,
                'serial_number'=> $serial,
                'status'       => 'in_stock',
                'received_date'=> now(),
            ]);
        }

        DB::commit();

        return back()->with('success', 'Existing serials encoded successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage());
    }
}

    public function returnStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $product->stock_count,
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $stockBefore = $product->stock_count;

            $serials = $product->inStockSerials()
            ->orderBy('received_date')
            ->limit($validated['quantity'])
            ->get();

            if ($serials->count() < $validated['quantity']) {
                throw new \Exception('Not enough available serials to return.');
            }

            foreach ($serials as $serial) {
                $serial->update([
                    'status' => 'returned',
                ]);
            }

            $stockAfter = $product->fresh()->stock_count;

            $supplier = isset($validated['supplier_id']) ? Supplier::find($validated['supplier_id']) : null;
            $notes = "Reason: " . $validated['reason'];
            
            if ($supplier) {
                $notes = "Returned to " . $supplier->name . ". " . $notes;
            }
            
            if (isset($validated['notes']) && !empty($validated['notes'])) {
                $notes .= ". Notes: " . $validated['notes'];
            }

            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'return',
                'quantity' => -$validated['quantity'],
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'Return to Supplier',
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('inventory.show', $product)
                ->with('success', 'Return processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing return: ' . $e->getMessage());
        }
    }
}