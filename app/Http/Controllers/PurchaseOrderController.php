<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\ProductSerial;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\ProductCatalogService;
use App\Services\PurchaseOrderAutoReceiveService;
use App\Support\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $productCatalog,
        private readonly PurchaseOrderAutoReceiveService $purchaseOrderAutoReceive
    ) {
        $this->authorizeResource(PurchaseOrder::class, 'purchase_order');
    }

    public function index()
    {
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->withCount([
                'serials as serials_count',
                'serials as sold_serials_count' => fn($q) => $q->where('status', 'sold'),
            ])
            ->orderBy('order_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $poCounts = PurchaseOrder::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received")
            ->selectRaw("SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid")
            ->first();

        $totalCount    = (int) ($poCounts->total ?? 0);
        $receivedCount = (int) ($poCounts->received ?? 0);
        $unpaidCount   = (int) ($poCounts->unpaid ?? 0);
        $totalUnits    = \App\Models\ProductSerial::count();

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

        $paymentsDue = PurchaseOrder::with('supplier')
            ->where('payment_type', '45days')
            ->where('balance', '>', 0)
            ->orderBy('payment_due_date')
            ->get();

        $paymentsDueCount = PurchaseOrder::query()
            ->where('payment_type', '45days')
            ->where('balance', '>', 0)
            ->whereNotNull('payment_due_date')
            ->where('payment_due_date', '<=', now()->addDays(30))
            ->count();

        return view('purchase-orders.index', compact(
            'purchaseOrders', 'upcomingDeadlines', 'overdueOrders',
            'totalCount', 'receivedCount', 'unpaidCount', 'totalUnits',
            'paymentsDue', 'paymentsDueCount'
        ));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $productsJson = $this->productCatalog->activeProductsForPurchaseOrderJson();

        return view('purchase-orders.create', compact('suppliers', 'productsJson'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'po_number'                => 'nullable|string|max:255',
            'so_number'                => 'nullable|string|max:255',
            'delivery_number'          => 'nullable|string|max:255',
            'supplier_id'              => 'required|exists:suppliers,id',
            'order_date'               => 'required|date',
            'expected_delivery_date'   => 'nullable|date',
            'payment_type'             => 'required|in:full,45days',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit_cost'        => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount'  => 'nullable|numeric|min:0',
            'items.*.serials'          => 'required|array|min:1',
            'items.*.serials.*'        => 'required|string|max:255',
            'downpayment_amount'       => 'nullable|numeric|min:0',
            'downpayment_date'         => 'nullable|date',
            'downpayment_method'       => ['nullable', Rule::in(PaymentMethod::values())],
            'downpayment_reference'    => 'nullable|string',
        ]);

        // ── Validate serials: if entered, count must match quantity ──
        foreach ($request->items as $index => $item) {
            $serials = collect($item['serials'] ?? [])->filter()->values();

            // Serials are required — one per unit, count must match quantity
            if ($serials->count() !== (int) $item['quantity']) {
                return back()->withInput()->withErrors([
                    "items.{$index}.serials" => "Serial count ({$serials->count()}) must match quantity ({$item['quantity']}) for this item.",
                ]);
            }

            // Check duplicates within submission
            $dupes = $serials->duplicates();
            if ($dupes->isNotEmpty()) {
                return back()->withInput()->withErrors([
                    "items.{$index}.serials" => "Duplicate serial numbers found: " . $dupes->unique()->implode(', '),
                ]);
            }

            // Check duplicates already in DB for this product
            $existing = ProductSerial::where('product_id', $item['product_id'])
                ->whereIn('serial_number', $serials)
                ->pluck('serial_number');
            if ($existing->isNotEmpty()) {
                return back()->withInput()->withErrors([
                    "items.{$index}.serials" => "These serials already exist for this product: " . $existing->implode(', '),
                ]);
            }
        }

        DB::beginTransaction();

        try {
            // ── Calculate totals ──
            $subtotal = 0;

            foreach ($request->items as $item) {

                $discountPercent = $item['discount_percent'] ?? 0;
                $discountAmount  = $item['discount_amount'] ?? 0;
                $unitCost        = $item['unit_cost'] ?? 0;

                // Apply percent first
                $netCost = $unitCost * (1 - ($discountPercent / 100));

                // Apply fixed discount distributed per quantity
                if ($item['quantity'] > 0 && $discountAmount > 0) {
                    $netCost -= ($discountAmount / $item['quantity']);
                }

                // Prevent negative pricing
                $netCost = max(0, $netCost);

                $subtotal += $item['quantity'] * $netCost;

            }
            $total = $subtotal;

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
                $balance       = 0;
                $amountPaid    = $total;
                $paymentStatus = 'paid';
            }

            // ── Create PO (received immediately — PO creation = encoding the delivery receipt) ──
            $receivedDate = $request->order_date;

            $po = PurchaseOrder::create([
                'po_number'              => $request->filled('po_number') ? $request->po_number : null,
                'so_number'              => $request->so_number,
                'delivery_number'        => $request->delivery_number,
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
                'status'                 => 'received',
                'received_date'          => $receivedDate,
                'notes'                  => $request->notes,
                'user_id'                => auth()->id(),
            ]);

            // ── Create PO items, store serials in stock, log inventory movements ──
            foreach ($request->items as $item) {
                $discountPercent = $item['discount_percent'] ?? 0;
                $discountAmount  = $item['discount_amount'] ?? 0;
                $unitCost        = $item['unit_cost'] ?? 0;

                // Apply percent first
                $netCost = $unitCost * (1 - ($discountPercent / 100));

                // Apply fixed discount
                if ($item['quantity'] > 0 && $discountAmount > 0) {
                    $netCost -= ($discountAmount / $item['quantity']);
                }

                // Prevent negative
                $netCost = max(0, $netCost);

                $poItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $item['product_id'],
                    'quantity_ordered'  => $item['quantity'],
                    'quantity_received' => $item['quantity'],
                    'unit_cost'         => $unitCost,
                    'discount_percent'  => $discountPercent,
                    'discount_amount'   => $discountAmount,
                    'discounted_cost'   => $netCost,
                    'total_cost'        => $item['quantity'] * $netCost,
                ]);

                // Store every serial directly in stock
                $serials = collect($item['serials'] ?? [])->filter()->values();
                foreach ($serials as $serialNumber) {
                    ProductSerial::create([
                        'product_id'        => $item['product_id'],
                        'purchase_order_id' => $po->id,
                        'serial_number'     => trim($serialNumber),
                        'status'            => 'in_stock',
                        'received_date'     => $receivedDate,
                    ]);
                }

                // Update product cost from this PO
                $product = $poItem->product;
                $product->update(['cost' => $netCost]);

                // Audit log — stock counts come from serials
                $stockAfter  = $product->inStockSerials()->count();
                $stockBefore = $stockAfter - $serials->count();

                InventoryMovement::create([
                    'product_id'     => $product->id,
                    'type'           => 'stock_in',
                    'quantity'       => $serials->count(),
                    'stock_before'   => max(0, $stockBefore),
                    'stock_after'    => $stockAfter,
                    'reference_type' => 'PurchaseOrder',
                    'reference_id'   => $po->id,
                    'notes'          => 'Received on PO creation: ' . $po->po_number .
                        ' at ₱' . number_format($netCost, 2) . '/unit',
                    'user_id'        => auth()->id(),
                ]);
            }

            // ── Record payment ──
            if ($request->payment_type === 'full') {
                // Full payment — record the entire amount as a single supplier payment
                SupplierPayment::create([
                    'purchase_order_id' => $po->id,
                    'payment_number'    => 'Full Payment',
                    'amount'            => $total,
                    'payment_date'      => $request->order_date,
                    'payment_method'    => 'cash',
                    'notes'             => 'Full payment on purchase order',
                    'user_id'           => auth()->id(),
                ]);
            } elseif ($request->payment_type === '45days' &&
                $request->filled('downpayment_amount') &&
                $request->downpayment_amount > 0) {
                // 45-day terms with optional downpayment
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

            $success = 'Purchase order created and stock received. Serial numbers are now in inventory.' .
                ($amountPaid > 0 && $request->payment_type === '45days'
                    ? ' Downpayment of ₱' . number_format($amountPaid, 2) . ' recorded.'
                    : '');

            return redirect()->route('purchase-orders.show', $po)
                ->with('success', $success);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error creating purchase order: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier',
            'items.product.brand',
            'payments',
            'user',
            'serials',
        ]);

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
        $this->authorize('update', $purchaseOrder);

        $request->validate([
            'received_date'            => 'required|date',
            'delivery_number'          => 'nullable|string|max:255',
            'items'                    => 'required|array',
            'items.*.id'               => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'items.*.serials'          => 'required|array',
            'items.*.serials.*'        => 'required|string|max:255',
        ]);

        // ── Validate: serial count must match quantity_received exactly ──
        foreach ($request->items as $index => $itemData) {
            $poItem  = PurchaseOrderItem::findOrFail($itemData['id']);
            $serials = collect($itemData['serials'] ?? [])->filter()->values();
            $qty     = (int) $itemData['quantity_received'];

            if ($serials->count() !== $qty) {
                return back()->withErrors([
                    "items.{$index}.serials" => "Serial count ({$serials->count()}) must match quantity received ({$qty}) for {$poItem->product->display_model}.",
                ])->withInput();
            }

            // Check duplicates within this submission
            $dupes = $serials->duplicates();
            if ($dupes->isNotEmpty()) {
                return back()->withErrors([
                    "items.{$index}.serials" => "Duplicate serials: " . $dupes->unique()->implode(', '),
                ])->withInput();
            }

            // Check serials not already in_stock from other POs
            $alreadyInStock = ProductSerial::where('product_id', $poItem->product_id)
                ->where('status', 'in_stock')
                ->whereIn('serial_number', $serials)
                ->pluck('serial_number');
            if ($alreadyInStock->isNotEmpty()) {
                return back()->withErrors([
                    "items.{$index}.serials" => "Already in stock: " . $alreadyInStock->implode(', '),
                ])->withInput();
            }
        }

        DB::beginTransaction();

        try {
            $purchaseOrder->update([
                'delivery_number' => $request->delivery_number,
            ]);

            foreach ($request->items as $itemData) {
                $poItem           = PurchaseOrderItem::findOrFail($itemData['id']);
                $product          = $poItem->product;
                $quantityReceived = (int) $itemData['quantity_received'];
                $serials          = collect($itemData['serials'])->filter()->values();
                $receivedDate     = $request->received_date;

                // Update PO item received count
                $poItem->update([
                    'quantity_received' => $poItem->quantity_received + $quantityReceived,
                ]);

                // Update product cost from PO
                $product->update([
                    'cost' => $poItem->discounted_cost ?? $poItem->unit_cost,
                ]);

                // Process each serial
                foreach ($serials as $serialNumber) {
                    $serialNumber = trim($serialNumber);

                    // Was it pre-entered at PO creation? Update it
                    $existing = ProductSerial::where('product_id', $product->id)
                        ->where('purchase_order_id', $purchaseOrder->id)
                        ->where('serial_number', $serialNumber)
                        ->where('status', 'pending')
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'status'        => 'in_stock',
                            'received_date' => $receivedDate,
                        ]);
                    } else {
                        // New serial entered at receiving time
                        ProductSerial::create([
                            'product_id'        => $product->id,
                            'purchase_order_id' => $purchaseOrder->id,
                            'serial_number'     => $serialNumber,
                            'status'            => 'in_stock',
                            'received_date'     => $receivedDate,
                        ]);
                    }
                }

                // Audit log — stock counts now come from serials
                $stockAfter  = $product->inStockSerials()->count();
                $stockBefore = $stockAfter - $quantityReceived;

                InventoryMovement::create([
                    'product_id'     => $product->id,
                    'type'           => 'stock_in',
                    'quantity'       => $quantityReceived,
                    'stock_before'   => max(0, $stockBefore),
                    'stock_after'    => $stockAfter,
                    'reference_type' => 'PurchaseOrder',
                    'reference_id'   => $purchaseOrder->id,
                    'notes'          => 'Received from PO: ' . $purchaseOrder->po_number .
                        ($request->delivery_number ? ' | DR#: ' . $request->delivery_number : '') .
                        ' at ₱' . number_format($poItem->discounted_cost ?? $poItem->unit_cost, 2) . '/unit',
                    'user_id'        => auth()->id(),
                ]);
            }

            $purchaseOrder->update([
                'status'        => 'received',
                'received_date' => $request->received_date,
            ]);

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Stock received successfully. Serial numbers recorded and inventory updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error receiving stock: ' . $e->getMessage());
        }
    }

    public function recordPayment(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $validated = $request->validate([
            'amount'           => 'required|numeric|min:0.01',
            'payment_date'     => 'required|date',
            'payment_method'   => ['required', Rule::in(PaymentMethod::values())],
            'reference_number' => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $referenceNumber = $validated['reference_number'] ?? null;
        $notes = $validated['notes'] ?? null;

        try {
            DB::transaction(function () use ($validated, $purchaseOrder, $referenceNumber, $notes) {
                $po = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();

                $pay = (float) $validated['amount'];
                $remaining = round((float) $po->balance, 2);

                if ($pay > $remaining + 0.009) {
                    throw new \InvalidArgumentException(
                        'Payment amount cannot exceed the remaining balance (₱' . number_format($remaining, 2) . ').'
                    );
                }

                $paymentCount = $po->payments()->count() + 1;

                SupplierPayment::create([
                    'purchase_order_id' => $po->id,
                    'payment_number'    => 'Payment #' . $paymentCount,
                    'amount'            => $pay,
                    'payment_date'      => $validated['payment_date'],
                    'payment_method'    => $validated['payment_method'],
                    'reference_number'  => $referenceNumber,
                    'notes'             => $notes,
                    'user_id'           => auth()->id(),
                ]);

                $newAmountPaid = (float) $po->amount_paid + $pay;
                $newBalance    = (float) $po->total - $newAmountPaid;

                $po->update([
                    'amount_paid'    => $newAmountPaid,
                    'balance'        => max(0, $newBalance),
                    'payment_status' => $newBalance <= 0 ? 'paid' : 'partial',
                ]);
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }

        return back()->with('success', 'Payment of ₱' . number_format((float) $validated['amount'], 2) . ' recorded successfully.');
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        if ($this->hasSoldSerials($purchaseOrder)) {
            return back()->with('error',
                'Cannot edit this purchase order — one or more units from it have already been sold.');
        }

        $purchaseOrder->load(['supplier', 'items.product.brand']);

        // Load this PO's serials (in stock or still pending) grouped by product_id
        $existingSerials = ProductSerial::where('purchase_order_id', $purchaseOrder->id)
            ->whereIn('status', ['in_stock', 'pending'])
            ->get()
            ->groupBy('product_id')
            ->map(fn($serials) => $serials->pluck('serial_number')->toArray());

        $suppliers    = Supplier::where('is_active', true)->orderBy('name')->get();
        $productsJson = $this->productCatalog->activeProductsForPurchaseOrderJson();

        return view('purchase-orders.edit', compact(
            'purchaseOrder', 'suppliers', 'productsJson', 'existingSerials'
        ));
    }

    /** True if any serial received under this PO has already been sold. */
    private function hasSoldSerials(PurchaseOrder $purchaseOrder): bool
    {
        return ProductSerial::where('purchase_order_id', $purchaseOrder->id)
            ->where('status', 'sold')
            ->exists();
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($this->hasSoldSerials($purchaseOrder)) {
            return back()->with('error',
                'Cannot edit this purchase order — one or more units from it have already been sold.');
        }

        $request->validate([
            'po_number'                => 'nullable|string|max:255',
            'so_number'                => 'nullable|string|max:255',
            'delivery_number'          => 'nullable|string|max:255',
            'supplier_id'              => 'required|exists:suppliers,id',
            'order_date'               => 'required|date',
            'expected_delivery_date'   => 'nullable|date',
            'payment_type'             => 'required|in:full,45days',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit_cost'        => 'nullable|numeric|min:0',
            'items.*.discount_amount'  => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.serials'          => 'required|array|min:1',
            'items.*.serials.*'        => 'required|string|max:255',
        ]);

        // ── Validate serials: one per unit, no dupes, no clash with OTHER POs ──
        foreach ($request->items as $index => $item) {
            $serials = collect($item['serials'] ?? [])->filter()->values();

            if ($serials->count() !== (int) $item['quantity']) {
                return back()->withInput()->withErrors([
                    "items.{$index}.serials" => "Serial count ({$serials->count()}) must match quantity ({$item['quantity']}).",
                ]);
            }

            $dupes = $serials->duplicates();
            if ($dupes->isNotEmpty()) {
                return back()->withInput()->withErrors([
                    "items.{$index}.serials" => 'Duplicate serial numbers found: ' . $dupes->unique()->implode(', '),
                ]);
            }

            // This PO's own serials are about to be replaced, so only clash against other POs
            $existing = ProductSerial::where('product_id', $item['product_id'])
                ->whereIn('serial_number', $serials)
                ->where('purchase_order_id', '!=', $purchaseOrder->id)
                ->pluck('serial_number');
            if ($existing->isNotEmpty()) {
                return back()->withInput()->withErrors([
                    "items.{$index}.serials" => 'These serials already exist for this product: ' . $existing->implode(', '),
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $receivedDate = $request->order_date;

            // ── Reverse the previous receive: drop this PO's serials, movements, items ──
            ProductSerial::where('purchase_order_id', $purchaseOrder->id)
                ->whereIn('status', ['in_stock', 'pending'])
                ->delete();
            InventoryMovement::where('reference_type', 'PurchaseOrder')
                ->where('reference_id', $purchaseOrder->id)
                ->delete();
            $purchaseOrder->items()->delete();

            // ── Re-apply: recreate items, put serials back in stock, log movements ──
            $subtotal = 0;
            foreach ($request->items as $item) {
                $discountPercent = $item['discount_percent'] ?? 0;
                $discountAmount  = $item['discount_amount'] ?? 0;
                $unitCost        = $item['unit_cost'] ?? 0;

                $netCost = $unitCost * (1 - ($discountPercent / 100));
                if ($item['quantity'] > 0 && $discountAmount > 0) {
                    $netCost -= ($discountAmount / $item['quantity']);
                }
                $netCost = max(0, $netCost);

                $subtotal += $item['quantity'] * $netCost;

                $poItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'        => $item['product_id'],
                    'quantity_ordered'  => $item['quantity'],
                    'quantity_received' => $item['quantity'],
                    'unit_cost'         => $unitCost,
                    'discount_percent'  => $discountPercent,
                    'discount_amount'   => $discountAmount,
                    'discounted_cost'   => $netCost,
                    'total_cost'        => $item['quantity'] * $netCost,
                ]);

                $serials = collect($item['serials'] ?? [])->filter()->values();
                foreach ($serials as $serialNumber) {
                    ProductSerial::create([
                        'product_id'        => $item['product_id'],
                        'purchase_order_id' => $purchaseOrder->id,
                        'serial_number'     => trim($serialNumber),
                        'status'            => 'in_stock',
                        'received_date'     => $receivedDate,
                    ]);
                }

                $product = $poItem->product;
                $product->update(['cost' => $netCost]);

                $stockAfter  = $product->inStockSerials()->count();
                $stockBefore = $stockAfter - $serials->count();

                InventoryMovement::create([
                    'product_id'     => $product->id,
                    'type'           => 'stock_in',
                    'quantity'       => $serials->count(),
                    'stock_before'   => max(0, $stockBefore),
                    'stock_after'    => $stockAfter,
                    'reference_type' => 'PurchaseOrder',
                    'reference_id'   => $purchaseOrder->id,
                    'notes'          => 'Updated PO ' . $purchaseOrder->po_number .
                        ' at ₱' . number_format($netCost, 2) . '/unit',
                    'user_id'        => auth()->id(),
                ]);
            }

            $total = $subtotal;

            // ── Recompute payment fields ──
            if ($request->payment_type === 'full') {
                $amountPaid     = $total;
                $balance        = 0;
                $paymentStatus  = 'paid';
                $paymentDueDate = null;
                // Re-sync the auto full-payment record to the new total
                $purchaseOrder->payments()->delete();
            } else {
                $amountPaid     = (float) $purchaseOrder->amount_paid; // money already paid stands
                $balance        = max(0, $total - $amountPaid);
                $paymentStatus  = $balance <= 0 ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid');
                $paymentDueDate = $purchaseOrder->payment_due_date
                    ?: Carbon::parse($request->order_date)->addDays(45);
            }

            $purchaseOrder->update([
                'po_number'              => $request->filled('po_number') ? $request->po_number : $purchaseOrder->po_number,
                'so_number'              => $request->so_number,
                'delivery_number'        => $request->delivery_number,
                'supplier_id'            => $request->supplier_id,
                'order_date'             => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'subtotal'               => $subtotal,
                'total'                  => $total,
                'amount_paid'            => $amountPaid,
                'balance'                => $balance,
                'payment_type'           => $request->payment_type,
                'payment_due_date'       => $paymentDueDate,
                'payment_status'         => $paymentStatus,
                'status'                 => 'received',
                'received_date'          => $receivedDate,
                'notes'                  => $request->notes,
            ]);

            if ($request->payment_type === 'full') {
                SupplierPayment::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'payment_number'    => 'Full Payment',
                    'amount'            => $total,
                    'payment_date'      => $request->order_date,
                    'payment_method'    => 'cash',
                    'notes'             => 'Full payment on purchase order (updated)',
                    'user_id'           => auth()->id(),
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase order updated. Inventory and serial numbers were re-synced.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error updating: ' . $e->getMessage());
        }
    }

    public function updateDueDate(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $request->validate([
            'payment_due_date' => 'required|date',
        ]);

        $purchaseOrder->update(['payment_due_date' => $request->payment_due_date]);

        return back()->with('success', 'Payment due date updated to ' .
            Carbon::parse($request->payment_due_date)->format('M d, Y') . '.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($this->hasSoldSerials($purchaseOrder)) {
            return back()->with('error',
                'Cannot delete this purchase order — one or more units from it have already been sold. ' .
                'Process a return/adjustment instead.');
        }

        DB::beginTransaction();

        try {
            // Reverse inventory: remove the serials this PO put in stock and its movements
            ProductSerial::where('purchase_order_id', $purchaseOrder->id)
                ->whereIn('status', ['in_stock', 'pending'])
                ->delete();
            InventoryMovement::where('reference_type', 'PurchaseOrder')
                ->where('reference_id', $purchaseOrder->id)
                ->delete();

            $purchaseOrder->items()->delete();
            $purchaseOrder->payments()->delete();
            $purchaseOrder->delete();

            DB::commit();

            return redirect()->route('purchase-orders.index')
                ->with('success', 'Purchase order deleted. Its stock and serial numbers were removed from inventory.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting purchase order: ' . $e->getMessage());
        }
    }
}