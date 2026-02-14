<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $lowStock = $products->where('stock_quantity', '<=', 5)->count();
        $outOfStock = $products->where('stock_quantity', 0)->count();
        $totalValue = $products->sum(function($product) {
            return $product->stock_quantity * $product->price;
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
            'quantity' => 'required|integer|min:0',
            'notes' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $stockBefore = $product->stock_quantity;
            $product->stock_quantity = $validated['quantity'];
            $product->save();
            $stockAfter = $product->stock_quantity;

            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $stockAfter - $stockBefore,
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
            return back()->with('error', 'Error adjusting inventory: ' . $e->getMessage());
        }
    }

    public function stockIn(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $stockBefore = $product->stock_quantity;
            $product->increment('stock_quantity', $validated['quantity']);
            $stockAfter = $product->stock_quantity;

            $supplier = isset($validated['supplier_id']) ? Supplier::find($validated['supplier_id']) : null;
            $notes = $validated['notes'] ?? 'Manual stock in';
            
            if ($supplier) {
                $notes = "Stock received from " . $supplier->name . ". " . $notes;
            }

            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'stock_in',
                'quantity' => $validated['quantity'],
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'Manual Stock In',
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('inventory.show', $product)
                ->with('success', 'Stock added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error adding stock: ' . $e->getMessage());
        }
    }

    public function returnStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $product->stock_quantity,
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $stockBefore = $product->stock_quantity;
            $product->decrement('stock_quantity', $validated['quantity']);
            $stockAfter = $product->stock_quantity;

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