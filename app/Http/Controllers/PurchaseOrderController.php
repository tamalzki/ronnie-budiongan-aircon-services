<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\InventoryMovement;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Summary card counts (separate queries — not affected by pagination)
        $totalCount    = PurchaseOrder::count();
        $awaitingCount = PurchaseOrder::where('status', 'pending')->count();
        $receivedCount = PurchaseOrder::where('status', 'received')->count();
        $unpaidCount   = PurchaseOrder::where('payment_status', 'unpaid')->count();

        // Alert: POs with deadline within 10 days
        $upcomingDeadlines = PurchaseOrder::with('supplier')
            ->where('payment_type', '45days')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereBetween('payment_due_date', [now(), now()->addDays(10)])
            ->get();

        $overdueOrders = PurchaseOrder::with('supplier')
            ->where('payment_type', '45days')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('payment_due_date', '<', now())
            ->get();

        // Payments Due tab — all 45-day terms with unpaid balance (ordered by due date)
        $paymentsDue = PurchaseOrder::with('supplier')
            ->where('payment_type', '45days')
            ->where('balance', '>', 0)
            ->orderBy('payment_due_date')
            ->get();

        $paymentsDueCount = $paymentsDue->filter(function($po) {
            return $po->payment_due_date && $po->payment_due_date->lte(now()->addDays(30));
        })->count();

        return view('purchase-orders.index', compact(
            'purchaseOrders', 'upcomingDeadlines', 'overdueOrders',
            'totalCount', 'awaitingCount', 'receivedCount', 'unpaidCount',
            'paymentsDue', 'paymentsDueCount'
        ));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        // Pre-map for JavaScript — avoids complex closure in Blade @json()
        $productsJson = Product::with('brand')
            ->where('is_active', true)
            ->orderBy('brand_id')
            ->get()
            ->map(function ($p) {
                $parts = array_filter([
                    $p->brand->name ?? null,
                    $p->model        ?? null,
                    $p->hp           ? $p->hp . ' HP' : null,
                ]);
                return [
                    'id'    => $p->id,
                    'label' => implode(' · ', $parts) ?: 'Unknown Product',
                    'cost'  => (float) ($p->cost ?? 0),
                ];
            })
            ->values()
            ->toArray();

        return view('purchase-orders.create', compact('suppliers', 'productsJson'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'                     => 'required|exists:suppliers,id',
            'order_date'                      => 'required|date',
            'expected_delivery_date'          => 'nullable|date',
            'payment_type'                    => 'required|in:full,45days',
            'notes'                           => 'nullable|string',
            'items'                           => 'required|array|min:1',
            'items.*.product_id'              => 'required|exists:products,id',
            'items.*.quantity'                => 'required|integer|min:1',
            'items.*.unit_cost'               => 'required|numeric|min:0',
            'items.*.discount_percent'        => 'nullable|numeric|min:0|max:100',
            'downpayment_amount'              => 'nullable|numeric|min:0',
            'downpayment_date'                => 'nullable|date',
            'downpayment_method'              => 'nullable|in:cash,gcash,bank_transfer,cheque',
            'downpayment_reference'           => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $subtotal = 0;

            foreach ($request->items as $item) {
                $discountPercent = $item['discount_percent'] ?? 0;
                $discountedCost  = $item['unit_cost'] * (1 - ($discountPercent / 100));
                $subtotal       += $item['quantity'] * $discountedCost;
            }

            $tax   = 0;
            $total = $subtotal + $tax;

            $paymentDueDate = null;
            $balance        = $total;
            $amountPaid     = 0;
            $paymentStatus  = 'unpaid';

            if ($request->payment_type === '45days') {
                $paymentDueDate = Carbon::parse($request->order_date)->addDays(45);

                if ($request->filled('downpayment_amount') && $request->downpayment_amount > 0) {
                    $amountPaid    = $request->downpayment_amount;
                    $balance       = $total - $amountPaid;
                    $paymentStatus = $balance <= 0 ? 'paid' : 'partial';
                }
            } else {
                // Full payment
                $balance       = 0;
                $amountPaid    = $total;
                $paymentStatus = 'paid';
            }

            $po = PurchaseOrder::create([
                'supplier_id'            => $request->supplier_id,
                'order_date'             => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'subtotal'               => $subtotal,
                'tax'                    => $tax,
                'total'                  => $total,
                'payment_type'           => $request->payment_type,
                'payment_due_date'       => $paymentDueDate,
                'amount_paid'            => $amountPaid,
                'balance'                => $balance,
                'payment_status'         => $paymentStatus,
                'status'                 => 'pending',
                'notes'                  => $request->notes,
                'user_id'                => auth()->id(),
            ]);

            // Create PO items with discount
            foreach ($request->items as $item) {
                $discountPercent = $item['discount_percent'] ?? 0;
                $discountedCost  = $item['unit_cost'] * (1 - ($discountPercent / 100));

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $item['product_id'],
                    'quantity_ordered'  => $item['quantity'],
                    'unit_cost'         => $item['unit_cost'],
                    'discount_percent'  => $discountPercent,
                    'discounted_cost'   => $discountedCost,
                    'total_cost'        => $item['quantity'] * $discountedCost,
                ]);
            }

            // Record downpayment as first payment
            if ($request->payment_type === '45days' &&
                $request->filled('downpayment_amount') &&
                $request->downpayment_amount > 0) {

                SupplierPayment::create([
                    'purchase_order_id' => $po->id,
                    'payment_number'    => 'Downpayment / Initial Payment',
                    'amount'            => $request->downpayment_amount,
                    'payment_date'      => $request->downpayment_date ?? $request->order_date,
                    'payment_method'    => $request->downpayment_method ?? 'cash',
                    'reference_number'  => $request->downpayment_reference,
                    'notes'             => 'Initial downpayment upon order creation',
                    'user_id'           => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $po)
                ->with('success', 'Purchase order created successfully.' .
                    ($amountPaid > 0 && $request->payment_type === '45days'
                        ? ' Downpayment of ₱' . number_format($amountPaid, 2) . ' recorded.'
                        : ''));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error creating purchase order: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product.brand', 'payments', 'user']);

        // Days remaining alert
        $daysRemaining = null;
        $deadlineAlert = null;

        if ($purchaseOrder->payment_type === '45days' && $purchaseOrder->payment_due_date) {
            $daysRemaining = now()->diffInDays($purchaseOrder->payment_due_date, false);

            if ($daysRemaining < 0) {
                $deadlineAlert = 'overdue';
            } elseif ($daysRemaining <= 10) {
                $deadlineAlert = 'warning';
            }
        }

        return view('purchase-orders.show', compact('purchaseOrder', 'daysRemaining', 'deadlineAlert'));
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'items'                       => 'required|array',
            'items.*.id'                  => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received'   => 'required|integer|min:0',
            'received_date'               => 'required|date',
            'delivery_number'             => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // Save delivery number on PO
            $purchaseOrder->update([
                'delivery_number' => $request->delivery_number,
            ]);

            foreach ($request->items as $itemData) {
                $item             = PurchaseOrderItem::findOrFail($itemData['id']);
                $product          = $item->product;
                $quantityReceived = $itemData['quantity_received'];

                $item->update([
                    'quantity_received' => $item->quantity_received + $quantityReceived,
                ]);

                $stockBefore = $product->stock_quantity;
                $product->increment('stock_quantity', $quantityReceived);
                $stockAfter = $product->stock_quantity;

                // Update product cost from discounted PO cost
                $product->update(['cost' => $item->discounted_cost ?? $item->unit_cost]);

                InventoryMovement::create([
                    'product_id'     => $product->id,
                    'type'           => 'stock_in',
                    'quantity'       => $quantityReceived,
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stockAfter,
                    'reference_type' => 'PurchaseOrder',
                    'reference_id'   => $purchaseOrder->id,
                    'notes'          => 'Received from PO: ' . $purchaseOrder->po_number .
                        ($request->delivery_number ? ' | DR#: ' . $request->delivery_number : '') .
                        ' at ₱' . number_format($item->discounted_cost ?? $item->unit_cost, 2) . '/unit',
                    'user_id'        => auth()->id(),
                ]);
            }

            $purchaseOrder->update([
                'status'        => 'received',
                'received_date' => $request->received_date,
            ]);

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Stock received successfully. Product costs updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error receiving stock: ' . $e->getMessage());
        }
    }

    public function recordPayment(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'amount'           => 'required|numeric|min:0.01|max:' . $purchaseOrder->balance,
            'payment_date'     => 'required|date',
            'payment_method'   => 'required|in:cash,gcash,bank_transfer,cheque',
            'reference_number' => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $paymentCount = $purchaseOrder->payments()->count() + 1;

            SupplierPayment::create([
                'purchase_order_id' => $purchaseOrder->id,
                'payment_number'    => 'Payment #' . $paymentCount,
                'amount'            => $validated['amount'],
                'payment_date'      => $validated['payment_date'],
                'payment_method'    => $validated['payment_method'],
                'reference_number'  => $validated['reference_number'],
                'notes'             => $validated['notes'],
                'user_id'           => auth()->id(),
            ]);

            $newAmountPaid = $purchaseOrder->amount_paid + $validated['amount'];
            $newBalance    = $purchaseOrder->total - $newAmountPaid;

            $purchaseOrder->update([
                'amount_paid'    => $newAmountPaid,
                'balance'        => max(0, $newBalance),
                'payment_status' => $newBalance <= 0 ? 'paid' : 'partial',
            ]);

            DB::commit();

            return back()->with('success', 'Payment of ₱' . number_format($validated['amount'], 2) . ' recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return back()->with('error', 'Cannot edit a received purchase order.');
        }

        $purchaseOrder->load(['supplier', 'items.product.brand']);
        $suppliers    = Supplier::where('is_active', true)->orderBy('name')->get();
        $productsJson = Product::with('brand')
            ->where('is_active', true)
            ->get()
            ->map(function ($p) {
                $parts = array_filter([
                    $p->brand->name ?? null,
                    $p->model        ?? null,
                    $p->hp           ? $p->hp . ' HP' : null,
                ]);
                return [
                    'id'    => $p->id,
                    'label' => implode(' · ', $parts) ?: 'Unknown Product',
                    'cost'  => (float) ($p->cost ?? 0),
                ];
            })
            ->values()
            ->toArray();

        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers', 'productsJson'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return back()->with('error', 'Cannot edit a received purchase order.');
        }

        $request->validate([
            'supplier_id'            => 'required|exists:suppliers,id',
            'order_date'             => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'payment_type'           => 'required|in:full,45days',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_cost'      => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            foreach ($request->items as $item) {
                $disc      = $item['discount_percent'] ?? 0;
                $netCost   = $item['unit_cost'] * (1 - $disc / 100);
                $subtotal += $item['quantity'] * $netCost;
            }
            $total = $subtotal;

            $paymentDueDate = $purchaseOrder->payment_due_date;
            if ($request->payment_type === '45days') {
                $paymentDueDate = $paymentDueDate ?? Carbon::parse($request->order_date)->addDays(45);
            } else {
                $paymentDueDate = null;
            }

            $purchaseOrder->update([
                'supplier_id'            => $request->supplier_id,
                'order_date'             => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'subtotal'               => $subtotal,
                'total'                  => $total,
                'payment_type'           => $request->payment_type,
                'payment_due_date'       => $paymentDueDate,
                'notes'                  => $request->notes,
            ]);

            // Replace items
            $purchaseOrder->items()->delete();
            foreach ($request->items as $item) {
                $disc    = $item['discount_percent'] ?? 0;
                $netCost = $item['unit_cost'] * (1 - $disc / 100);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'        => $item['product_id'],
                    'quantity_ordered'  => $item['quantity'],
                    'unit_cost'         => $item['unit_cost'],
                    'discount_percent'  => $disc,
                    'discounted_cost'   => $netCost,
                    'total_cost'        => $item['quantity'] * $netCost,
                ]);
            }

            DB::commit();
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error updating: ' . $e->getMessage());
        }
    }

    public function updateDueDate(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'payment_due_date' => 'required|date',
        ]);

        $purchaseOrder->update(['payment_due_date' => $request->payment_due_date]);

        return back()->with('success', 'Payment due date updated to ' .
            Carbon::parse($request->payment_due_date)->format('M d, Y') . '.');
    }

        public function destroy(PurchaseOrder $purchaseOrder)
    {
        // Block deletion of received POs — stock has already been added
        if ($purchaseOrder->status === 'received') {
            return back()->with('error',
                'Cannot delete a received purchase order — stock has already been added to inventory. ' .
                'Create a return/adjustment instead.'
            );
        }

        DB::beginTransaction();

        try {
            $purchaseOrder->items()->delete();
            $purchaseOrder->payments()->delete();
            $purchaseOrder->delete();

            DB::commit();

            return redirect()->route('purchase-orders.index')
                ->with('success', 'Purchase order deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting purchase order: ' . $e->getMessage());
        }
    }
}