<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Product::class);

        return redirect()->route('products.index');
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $product->load(['brand', 'supplier']);

        $pairedProduct = $product->paired_product_id
            ? Product::with('brand')->find($product->paired_product_id)
            : Product::with('brand')->where('paired_product_id', $product->id)->first();

        $movements = InventoryMovement::where('product_id', $product->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalStockIn       = $movements->where('type', 'stock_in')->sum('quantity');
        $totalStockOut      = abs($movements->where('type', 'stock_out')->sum('quantity'));
        $totalReturns       = abs($movements->where('type', 'return')->sum('quantity'));
        $totalAdjustments   = $movements->where('type', 'adjustment')->count();

        $serials = ProductSerial::where('product_id', $product->id)
            ->with(['purchaseOrder', 'sale'])
            ->orderByRaw("FIELD(status,'in_stock','pending','sold','returned','defective','lost')")
            ->orderBy('serial_number')
            ->get();

        $serialCounts = [
            'in_stock'  => $serials->where('status', 'in_stock')->count(),
            'pending'   => $serials->where('status', 'pending')->count(),
            'sold'      => $serials->where('status', 'sold')->count(),
            'returned'  => $serials->where('status', 'returned')->count(),
            'defective' => $serials->where('status', 'defective')->count(),
            'lost'      => $serials->where('status', 'lost')->count(),
        ];

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('inventory.show', compact(
            'product',
            'pairedProduct',
            'movements',
            'totalStockIn',
            'totalStockOut',
            'totalReturns',
            'totalAdjustments',
            'serials',
            'serialCounts',
            'suppliers'
        ));
    }

    public function adjust(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'new_quantity' => ['required', 'integer', 'min:0'],
            'notes'        => ['required', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $stockBefore = $product->stock_count;
            $difference = $validated['new_quantity'] - $stockBefore;

            if ($difference > 0) {
                throw new \Exception('To increase stock, use Stock In and encode serial numbers.');
            }

            if ($difference < 0) {
                $toRemove = abs($difference);

                $serials = $product->inStockSerials()
                    ->orderBy('received_date')
                    ->limit($toRemove)
                    ->get();

                if ($serials->count() < $toRemove) {
                    throw new \Exception('Not enough available serials to adjust.');
                }

                foreach ($serials as $serial) {
                    $serial->update(['status' => 'lost']);
                }
            }

            $stockAfter = $product->fresh()->stock_count;

            InventoryMovement::create([
                'product_id'     => $product->id,
                'type'           => 'adjustment',
                'quantity'       => $difference,
                'stock_before'   => $stockBefore,
                'stock_after'    => $stockAfter,
                'reference_type' => 'Manual Adjustment',
                'notes'          => $validated['notes'],
                'user_id'        => auth()->id(),
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
        $this->authorize('update', $product);

        $pairedProduct = $product->paired_product_id
            ? $product->pairedProduct
            : Product::where('paired_product_id', $product->id)->first();

        $rules = [
            'serial_numbers'   => ['required', 'array', 'min:1'],
            'serial_numbers.*' => ['required', 'string', 'distinct', 'unique:product_serials,serial_number'],
            'supplier_id'      => ['nullable', 'exists:suppliers,id'],
            'notes'            => ['nullable', 'string'],
        ];

        if ($pairedProduct) {
            $rules['paired_serial_numbers']   = ['required', 'array', 'min:1'];
            $rules['paired_serial_numbers.*'] = ['required', 'string', 'distinct', 'unique:product_serials,serial_number'];
        }

        $validated = $request->validate($rules);

        if ($pairedProduct) {
            if (count($validated['paired_serial_numbers']) !== count($validated['serial_numbers'])) {
                return back()->withInput()->with('error', 'Enter the same number of serials for both the indoor and outdoor units.');
            }

            if (array_intersect($validated['serial_numbers'], $validated['paired_serial_numbers'])) {
                return back()->withInput()->with('error', 'Serial numbers must be unique across both units in the set.');
            }
        }

        DB::beginTransaction();

        try {
            $stockBefore = $product->stock_count;

            foreach ($validated['serial_numbers'] as $serial) {
                ProductSerial::create([
                    'product_id'    => $product->id,
                    'serial_number' => trim($serial),
                    'status'        => 'in_stock',
                    'received_date' => now(),
                ]);
            }

            $stockAfter = $product->fresh()->stock_count;

            InventoryMovement::create([
                'product_id'     => $product->id,
                'type'           => 'stock_in',
                'quantity'       => count($validated['serial_numbers']),
                'stock_before'   => $stockBefore,
                'stock_after'    => $stockAfter,
                'reference_type' => 'Manual Stock In',
                'notes'          => $validated['notes'] ?? 'Manual stock in',
                'user_id'        => auth()->id(),
            ]);

            if ($pairedProduct) {
                $pairedStockBefore = $pairedProduct->stock_count;

                foreach ($validated['paired_serial_numbers'] as $serial) {
                    ProductSerial::create([
                        'product_id'    => $pairedProduct->id,
                        'serial_number' => trim($serial),
                        'status'        => 'in_stock',
                        'received_date' => now(),
                    ]);
                }

                $pairedStockAfter = $pairedProduct->fresh()->stock_count;

                InventoryMovement::create([
                    'product_id'     => $pairedProduct->id,
                    'type'           => 'stock_in',
                    'quantity'       => count($validated['paired_serial_numbers']),
                    'stock_before'   => $pairedStockBefore,
                    'stock_after'    => $pairedStockAfter,
                    'reference_type' => 'Manual Stock In',
                    'notes'          => $validated['notes'] ?? 'Manual stock in (set)',
                    'user_id'        => auth()->id(),
                ]);
            }

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
        $this->authorize('update', $product);

        $validated = $request->validate([
            'serial_numbers'   => ['required', 'array', 'min:1'],
            'serial_numbers.*' => ['required', 'string', 'distinct', 'unique:product_serials,serial_number'],
        ]);

        DB::beginTransaction();

        try {
            if ($product->inStockSerials()->count() > 0) {
                throw new \Exception('Serials already exist for this product. Use Stock In to add more.');
            }

            foreach ($validated['serial_numbers'] as $serial) {
                ProductSerial::create([
                    'product_id'    => $product->id,
                    'serial_number' => trim($serial),
                    'status'        => 'in_stock',
                    'received_date' => now(),
                ]);
            }

            DB::commit();

            return back()->with('success', count($validated['serial_numbers']) . ' serial(s) encoded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function returnStock(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'quantity'    => ['required', 'integer', 'min:1', 'max:' . $product->stock_count],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'reason'      => ['required', 'string'],
            'notes'       => ['nullable', 'string'],
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
                $serial->update(['status' => 'returned']);
            }

            $stockAfter = $product->fresh()->stock_count;

            $supplier = isset($validated['supplier_id']) ? Supplier::find($validated['supplier_id']) : null;
            $notes = 'Reason: ' . $validated['reason'];

            if ($supplier) {
                $notes = 'Returned to ' . $supplier->name . '. ' . $notes;
            }

            if (! empty($validated['notes'] ?? null)) {
                $notes .= '. Notes: ' . $validated['notes'];
            }

            InventoryMovement::create([
                'product_id'     => $product->id,
                'type'           => 'return',
                'quantity'       => -$validated['quantity'],
                'stock_before'   => $stockBefore,
                'stock_after'    => $stockAfter,
                'reference_type' => 'Return to Supplier',
                'notes'          => $notes,
                'user_id'        => auth()->id(),
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
