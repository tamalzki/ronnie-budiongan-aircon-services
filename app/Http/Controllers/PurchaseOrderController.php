<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Part;
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
                'items as part_items_count'     => fn($q) => $q->whereNotNull('part_id'),
            ])
            ->orderBy('order_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Orders awaiting receiving (serials not yet encoded / stock not in)
        $toReceive = PurchaseOrder::with(['supplier', 'items.product', 'items.part'])
            ->where('status', 'pending')
            ->orderBy('order_date')
            ->get();

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
            'paymentsDue', 'paymentsDueCount', 'toReceive'
        ));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $productsJson = $this->productCatalog->activeProductsForPurchaseOrderJson();
        $partsJson = $this->activePartsForPurchaseOrderJson();

        return view('purchase-orders.create', compact('suppliers', 'productsJson', 'partsJson'));
    }

    /**
     * Active parts as JSON-friendly rows for the PO parts combobox.
     *
     * @return list<array<string, mixed>>
     */
    private function activePartsForPurchaseOrderJson(): array
    {
        return Part::with('product.pairedProduct')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Part $part) => [
                'id'                 => $part->id,
                'name'               => $part->name,
                'product_id'         => $part->product_id,
                'cost'               => (float) $part->cost,
                'linked_model_label' => $part->linked_model_label,
                'stock_quantity'     => $part->stock_quantity,
            ])
            ->values()
            ->all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_po_number'       => 'required|string|max:255',
            'so_number'                => 'nullable|string|max:255',
            'delivery_number'          => 'nullable|string|max:255',
            'supplier_id'              => 'required|exists:suppliers,id',
            'order_date'               => 'required|date',
            'expected_delivery_date'   => 'nullable|date',
            'payment_type'             => 'required|in:full,45days',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.item_type'        => 'nullable|in:product,part',
            'items.*.product_id'       => 'required_if:items.*.item_type,product|nullable|exists:products,id',
            'items.*.part_id'          => 'nullable|exists:parts,id',
            'items.*.new_part_name'    => 'nullable|string|max:255',
            'items.*.new_part_product_id' => 'nullable|exists:products,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit_cost'        => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount'  => 'nullable|numeric|min:0',
            'items.*.serials'          => 'nullable|array',
            'items.*.serials.*'        => 'nullable|string|max:255',
            'items.*.outdoor_serials'   => 'nullable|array',
            'items.*.outdoor_serials.*' => 'nullable|string|max:255',
            'downpayment_amount'       => 'nullable|numeric|min:0',
            'downpayment_date'         => 'nullable|date',
            'downpayment_method'       => ['nullable', Rule::in(PaymentMethod::values())],
            'downpayment_reference'    => 'nullable|string',
        ], [], ['supplier_po_number' => 'PO No.']);

        // ── Normalize + validate line items (serials optional: empty = receive later) ──
        $errors = [];
        $lines  = $this->normalizeItems($request->items, null, $errors);
        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        DB::beginTransaction();

        try {
            $subtotal = collect($lines)->sum(fn($l) => $l['quantity'] * $l['net_cost']);
            $tax      = 0;
            $total    = $subtotal + $tax;

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

            // ── PO is "received" only when every line came with its serials ──
            $allReceived  = collect($lines)->every(fn($l) => $l['received']);
            $receivedDate = $request->order_date;

            $po = PurchaseOrder::create([
                'supplier_po_number'     => $request->supplier_po_number,
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
                'status'                 => $allReceived ? 'received' : 'pending',
                'received_date'          => $allReceived ? $receivedDate : null,
                'notes'                  => $request->notes,
                'user_id'                => auth()->id(),
            ]);

            // ── Create PO items; stock-in serials for lines that have them ──
            foreach ($lines as $line) {
                if ($line['type'] === 'part') {
                    $this->applyPartLine($po, $line, 'Received on PO creation: ' . $po->display_po_number);
                    continue;
                }

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $line['product']->id,
                    'is_set'            => $line['is_set'],
                    'quantity_ordered'  => $line['quantity'],
                    'quantity_received' => $line['received'] ? $line['quantity'] : 0,
                    'unit_cost'         => $line['unit_cost'],
                    'discount_percent'  => $line['discount_percent'],
                    'discount_amount'   => $line['discount_amount'],
                    'discounted_cost'   => $line['net_cost'],
                    'total_cost'        => $line['quantity'] * $line['net_cost'],
                ]);

                // Latest PO cost becomes the product (set) cost — one price per set
                $line['product']->update(['cost' => $line['net_cost']]);
                if ($line['pair']) {
                    $line['pair']->update(['cost' => $line['net_cost']]);
                }

                if ($line['received']) {
                    $this->stockInSerials($po, $line['product'], $line['serials'], $receivedDate,
                        'Received on PO creation: ' . $po->display_po_number, $line['net_cost']);
                    if ($line['is_set']) {
                        $this->stockInSerials($po, $line['pair'], $line['outdoor_serials'], $receivedDate,
                            'Received on PO creation: ' . $po->display_po_number, $line['net_cost']);
                    }
                }
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

            $success = $allReceived
                ? 'Purchase order created and stock received. Serial numbers are now in inventory.'
                : 'Purchase order created (awaiting receiving). Use Order Receive to enter the document number and serial numbers when stock arrives.';
            $success .= ($amountPaid > 0 && $request->payment_type === '45days'
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

    /**
     * Normalize raw request items into validated line data.
     *
     * Paired indoor products ("sets") expect serials for BOTH units:
     * items[i][serials] = indoor side, items[i][outdoor_serials] = outdoor side.
     * Serials may be omitted entirely → the line is received later via Order Receive.
     *
     * @param  array       $items       raw request items
     * @param  int|null    $ignorePoId  exclude this PO's own serials from clash checks (edit)
     * @param  array       $errors      collected validation errors (by reference)
     * @return list<array<string, mixed>>
     */
    private function normalizeItems(array $items, ?int $ignorePoId, array &$errors): array
    {
        $lines = [];

        foreach ($items as $index => $item) {
            if (($item['item_type'] ?? 'product') === 'part') {
                $line = $this->normalizePartItem($item, $index, $errors);
                if ($line !== null) {
                    $lines[] = $line;
                }
                continue;
            }

            $product = Product::with('pairedProduct')->findOrFail($item['product_id']);
            $isSet   = $product->is_set_primary;
            $pair    = $isSet ? $product->pairedProduct : null;
            $qty     = (int) $item['quantity'];

            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $discountAmount  = (float) ($item['discount_amount'] ?? 0);
            $unitCost        = (float) ($item['unit_cost'] ?? 0);

            $netCost = $unitCost * (1 - ($discountPercent / 100));
            if ($qty > 0 && $discountAmount > 0) {
                $netCost -= ($discountAmount / $qty);
            }
            $netCost = max(0, $netCost);

            $serials        = collect($item['serials'] ?? [])->map(fn($s) => trim((string) $s))->filter()->values();
            $outdoorSerials = collect($item['outdoor_serials'] ?? [])->map(fn($s) => trim((string) $s))->filter()->values();

            $received = false;

            if ($serials->isEmpty() && $outdoorSerials->isEmpty()) {
                // No serials yet — line will be received later
            } elseif ($isSet) {
                if ($serials->count() !== $qty || $outdoorSerials->count() !== $qty) {
                    $errors["items.{$index}.serials"] =
                        "Set of {$qty}: enter {$qty} indoor and {$qty} outdoor serial(s), or leave all empty to receive later. " .
                        "(Got {$serials->count()} indoor / {$outdoorSerials->count()} outdoor.)";
                    continue;
                }
                $received = true;
            } else {
                if ($serials->count() !== $qty) {
                    $errors["items.{$index}.serials"] =
                        "Serial count ({$serials->count()}) must match quantity ({$qty}), or leave all empty to receive later.";
                    continue;
                }
                $received = true;
            }

            if ($received) {
                $err = $this->validateSerialBatch($product, $serials, $ignorePoId)
                    ?? ($isSet ? $this->validateSerialBatch($pair, $outdoorSerials, $ignorePoId) : null);
                if ($err !== null) {
                    $errors["items.{$index}.serials"] = $err;
                    continue;
                }
            }

            $lines[] = [
                'type'             => 'product',
                'product'          => $product,
                'pair'             => $pair,
                'is_set'           => $isSet,
                'quantity'         => $qty,
                'unit_cost'        => $unitCost,
                'discount_percent' => $discountPercent,
                'discount_amount'  => $discountAmount,
                'net_cost'         => $netCost,
                'serials'          => $serials,
                'outdoor_serials'  => $outdoorSerials,
                'received'         => $received,
            ];
        }

        return $lines;
    }

    /** Normalize a "parts only" order line — resolves or creates the Part. */
    private function normalizePartItem(array $item, int $index, array &$errors): ?array
    {
        $qty = (int) ($item['quantity'] ?? 0);

        $discountPercent = (float) ($item['discount_percent'] ?? 0);
        $discountAmount  = (float) ($item['discount_amount'] ?? 0);
        $unitCost        = (float) ($item['unit_cost'] ?? 0);

        $netCost = $unitCost * (1 - ($discountPercent / 100));
        if ($qty > 0 && $discountAmount > 0) {
            $netCost -= ($discountAmount / $qty);
        }
        $netCost = max(0, $netCost);

        $partId      = $item['part_id'] ?? null;
        $newPartName = trim((string) ($item['new_part_name'] ?? ''));

        if ($partId) {
            $part = Part::find($partId);
            if ($part === null) {
                $errors["items.{$index}.part_id"] = 'Selected part could not be found.';
                return null;
            }
        } elseif ($newPartName !== '') {
            $part = Part::create([
                'name'       => $newPartName,
                'product_id' => $item['new_part_product_id'] ?? null,
                'cost'       => $netCost,
                'is_active'  => true,
            ]);
        } else {
            $errors["items.{$index}.part_id"] = 'Select an existing part or enter a name for a new part.';
            return null;
        }

        return [
            'type'             => 'part',
            'part'             => $part,
            'quantity'         => $qty,
            'unit_cost'        => $unitCost,
            'discount_percent' => $discountPercent,
            'discount_amount'  => $discountAmount,
            'net_cost'         => $netCost,
            'received'         => true,
        ];
    }

    /** Create the PO item, update the part's cost, and log a stock-in movement for a part line. */
    private function applyPartLine(PurchaseOrder $po, array $line, string $note): void
    {
        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'part_id'           => $line['part']->id,
            'is_set'            => false,
            'quantity_ordered'  => $line['quantity'],
            'quantity_received' => $line['quantity'],
            'unit_cost'         => $line['unit_cost'],
            'discount_percent'  => $line['discount_percent'],
            'discount_amount'   => $line['discount_amount'],
            'discounted_cost'   => $line['net_cost'],
            'total_cost'        => $line['quantity'] * $line['net_cost'],
        ]);

        $line['part']->update(['cost' => $line['net_cost']]);

        $stockBefore = $line['part']->stock_quantity;

        InventoryMovement::create([
            'part_id'        => $line['part']->id,
            'type'           => 'stock_in',
            'quantity'       => $line['quantity'],
            'stock_before'   => $stockBefore,
            'stock_after'    => $stockBefore + $line['quantity'],
            'reference_type' => 'PurchaseOrder',
            'reference_id'   => $po->id,
            'notes'          => $note . ' at ₱' . number_format($line['net_cost'], 2) . '/unit',
            'user_id'        => auth()->id(),
        ]);
    }

    /** When the edit form omits serials, keep the ones already received on this PO. */
    private function injectExistingSerialsIfMissing(array $items, PurchaseOrder $po): array
    {
        $byProduct = $po->serials()
            ->whereIn('status', ['in_stock', 'pending'])
            ->orderBy('serial_number')
            ->get()
            ->groupBy('product_id');

        if ($byProduct->isEmpty()) {
            return $items;
        }

        foreach ($items as &$item) {
            $serials        = collect($item['serials'] ?? [])->map(fn($s) => trim((string) $s))->filter();
            $outdoorSerials = collect($item['outdoor_serials'] ?? [])->map(fn($s) => trim((string) $s))->filter();

            if ($serials->isNotEmpty() || $outdoorSerials->isNotEmpty()) {
                continue;
            }

            $product = Product::with('pairedProduct')->find($item['product_id'] ?? null);
            if ($product === null) {
                continue;
            }

            $item['serials'] = $byProduct->get($product->id, collect())
                ->pluck('serial_number')
                ->values()
                ->all();

            if ($product->is_set_primary && $product->pairedProduct) {
                $item['outdoor_serials'] = $byProduct->get($product->pairedProduct->id, collect())
                    ->pluck('serial_number')
                    ->values()
                    ->all();
            }
        }
        unset($item);

        return $items;
    }

    /** Duplicate / clash checks for one product's serial batch. Returns an error string or null. */
    private function validateSerialBatch(Product $product, $serials, ?int $ignorePoId): ?string
    {
        if ($serials->isEmpty()) {
            return null;
        }

        $dupes = $serials->duplicates();
        if ($dupes->isNotEmpty()) {
            return 'Duplicate serial numbers found: ' . $dupes->unique()->implode(', ');
        }

        $existing = ProductSerial::where('product_id', $product->id)
            ->whereIn('serial_number', $serials)
            ->when($ignorePoId, fn($q) => $q->where(fn($qq) =>
                $qq->whereNull('purchase_order_id')->orWhere('purchase_order_id', '!=', $ignorePoId)))
            ->pluck('serial_number');
        if ($existing->isNotEmpty()) {
            return "These serials already exist for {$product->model}: " . $existing->implode(', ');
        }

        return null;
    }

    /** Create in-stock serials for a product under a PO and log the movement. */
    private function stockInSerials(PurchaseOrder $po, Product $product, $serials, $receivedDate, string $note, float $netCost): void
    {
        if ($serials->isEmpty()) {
            return;
        }

        foreach ($serials as $serialNumber) {
            ProductSerial::create([
                'product_id'        => $product->id,
                'purchase_order_id' => $po->id,
                'serial_number'     => trim($serialNumber),
                'status'            => 'in_stock',
                'received_date'     => $receivedDate,
            ]);
        }

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
            'notes'          => $note . ' at ₱' . number_format($netCost, 2) . '/unit',
            'user_id'        => auth()->id(),
        ]);
    }

    public function downloadPdf(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'items.product.brand', 'items.product.pairedProduct', 'items.part.product.pairedProduct', 'user']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('purchase-orders.pdf', [
            'po' => $purchaseOrder,
        ])->setPaper('a4');

        $poNo = preg_replace('/[^\w\-. ]+/u', '', $purchaseOrder->display_po_number)
            ?: $purchaseOrder->po_number;
        $poDate = ($purchaseOrder->order_date ?? $purchaseOrder->created_at)->format('Y-m-d');

        return $pdf->download('PO ' . $poNo . ' ' . $poDate . '.pdf');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier',
            'items.product.brand',
            'items.product.pairedProduct',
            'items.part.product.pairedProduct',
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
            'received_date'             => 'required|date',
            'delivery_number'           => 'required|string|max:255',
            'items'                     => 'required|array',
            'items.*.id'                => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'items.*.serials'           => 'nullable|array',
            'items.*.serials.*'         => 'nullable|string|max:255',
            'items.*.outdoor_serials'   => 'nullable|array',
            'items.*.outdoor_serials.*' => 'nullable|string|max:255',
        ]);

        // ── Validate each line: serial counts must match quantity received ──
        $parsed = [];
        foreach ($request->items as $index => $itemData) {
            $poItem = PurchaseOrderItem::with('product.pairedProduct')->findOrFail($itemData['id']);
            if ($poItem->purchase_order_id !== $purchaseOrder->id) {
                return back()->with('error', 'Invalid item for this purchase order.')->withInput();
            }

            $qty = (int) $itemData['quantity_received'];
            if ($qty === 0) {
                continue;
            }

            $remaining = $poItem->quantity_ordered - $poItem->quantity_received;
            if ($qty > $remaining) {
                return back()->withErrors([
                    "items.{$index}.serials" => "Quantity received ({$qty}) exceeds remaining ({$remaining}) for {$poItem->product->display_model}.",
                ])->withInput();
            }

            $isSet          = $poItem->is_set && $poItem->product->pairedProduct;
            $serials        = collect($itemData['serials'] ?? [])->map(fn($s) => trim((string) $s))->filter()->values();
            $outdoorSerials = collect($itemData['outdoor_serials'] ?? [])->map(fn($s) => trim((string) $s))->filter()->values();

            if ($serials->count() !== $qty) {
                return back()->withErrors([
                    "items.{$index}.serials" => "Serial count ({$serials->count()}) must match quantity received ({$qty}) for {$poItem->product->display_model}.",
                ])->withInput();
            }
            if ($isSet && $outdoorSerials->count() !== $qty) {
                return back()->withErrors([
                    "items.{$index}.serials" => "Outdoor serial count ({$outdoorSerials->count()}) must match quantity received ({$qty}) for {$poItem->product->set_model_label}.",
                ])->withInput();
            }

            $err = $this->validateSerialBatch($poItem->product, $serials, null)
                ?? ($isSet ? $this->validateSerialBatch($poItem->product->pairedProduct, $outdoorSerials, null) : null);
            if ($err !== null) {
                return back()->withErrors(["items.{$index}.serials" => $err])->withInput();
            }

            $parsed[] = [
                'po_item'         => $poItem,
                'is_set'          => $isSet,
                'quantity'        => $qty,
                'serials'         => $serials,
                'outdoor_serials' => $outdoorSerials,
            ];
        }

        if ($parsed === []) {
            return back()->with('error', 'Nothing to receive — enter a quantity and serial numbers for at least one item.');
        }

        DB::beginTransaction();

        try {
            if ($request->filled('delivery_number')) {
                $purchaseOrder->update(['delivery_number' => $request->delivery_number]);
            }

            $receivedDate = $request->received_date;

            foreach ($parsed as $line) {
                $poItem  = $line['po_item'];
                $product = $poItem->product;
                $netCost = (float) ($poItem->discounted_cost ?? $poItem->unit_cost);

                $poItem->update([
                    'quantity_received' => $poItem->quantity_received + $line['quantity'],
                ]);

                $product->update(['cost' => $netCost]);
                if ($line['is_set']) {
                    $product->pairedProduct->update(['cost' => $netCost]);
                }

                $note = 'Received from PO: ' . $purchaseOrder->display_po_number .
                    ($request->delivery_number ? ' | DR#: ' . $request->delivery_number : '');

                $this->stockInSerials($purchaseOrder, $product, $line['serials'], $receivedDate, $note, $netCost);
                if ($line['is_set']) {
                    $this->stockInSerials($purchaseOrder, $product->pairedProduct, $line['outdoor_serials'], $receivedDate, $note, $netCost);
                }
            }

            // PO becomes "received" only once every line is fully received
            $fullyReceived = $purchaseOrder->items()
                ->whereColumn('quantity_received', '<', 'quantity_ordered')
                ->doesntExist();

            $purchaseOrder->update([
                'status'        => $fullyReceived ? 'received' : 'pending',
                'received_date' => $fullyReceived ? $receivedDate : null,
            ]);

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', $fullyReceived
                    ? 'Stock received successfully. Serial numbers recorded and inventory updated.'
                    : 'Partial receiving recorded. Remaining units can be received later.');

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

        $purchaseOrder->load(['supplier', 'items.product.brand', 'items.part']);

        // Load this PO's serials (in stock or still pending) grouped by product_id
        $existingSerials = ProductSerial::where('purchase_order_id', $purchaseOrder->id)
            ->whereIn('status', ['in_stock', 'pending'])
            ->get()
            ->groupBy('product_id')
            ->map(fn($serials) => $serials->pluck('serial_number')->toArray());

        $suppliers    = Supplier::where('is_active', true)->orderBy('name')->get();
        $productsJson = $this->productCatalog->activeProductsForPurchaseOrderJson();
        $partsJson    = $this->activePartsForPurchaseOrderJson();

        return view('purchase-orders.edit', compact(
            'purchaseOrder', 'suppliers', 'productsJson', 'partsJson', 'existingSerials'
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
            'supplier_po_number'       => 'required|string|max:255',
            'so_number'                => 'nullable|string|max:255',
            'delivery_number'          => 'nullable|string|max:255',
            'supplier_id'              => 'required|exists:suppliers,id',
            'order_date'               => 'required|date',
            'expected_delivery_date'   => 'nullable|date',
            'payment_type'             => 'required|in:full,45days',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.item_type'        => 'nullable|in:product,part',
            'items.*.product_id'       => 'required_if:items.*.item_type,product|nullable|exists:products,id',
            'items.*.part_id'          => 'nullable|exists:parts,id',
            'items.*.new_part_name'    => 'nullable|string|max:255',
            'items.*.new_part_product_id' => 'nullable|exists:products,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit_cost'        => 'required|numeric|min:0',
            'items.*.discount_amount'  => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.serials'          => 'nullable|array',
            'items.*.serials.*'        => 'nullable|string|max:255',
            'items.*.outdoor_serials'   => 'nullable|array',
            'items.*.outdoor_serials.*' => 'nullable|string|max:255',
        ], [], ['supplier_po_number' => 'PO No.']);

        // ── Normalize + validate (edit form omits serials — keep existing received units) ──
        $errors = [];
        $items  = $this->injectExistingSerialsIfMissing($request->items, $purchaseOrder);
        $lines  = $this->normalizeItems($items, $purchaseOrder->id, $errors);
        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        DB::beginTransaction();
        try {
            $receivedDate = $purchaseOrder->received_date ?? $request->order_date;

            // ── Reverse the previous receive: drop this PO's serials, movements, items ──
            ProductSerial::where('purchase_order_id', $purchaseOrder->id)
                ->whereIn('status', ['in_stock', 'pending'])
                ->delete();
            InventoryMovement::where('reference_type', 'PurchaseOrder')
                ->where('reference_id', $purchaseOrder->id)
                ->delete();
            $purchaseOrder->items()->delete();

            // ── Re-apply: recreate items, put serials back in stock, log movements ──
            $subtotal    = 0;
            $allReceived = collect($lines)->every(fn($l) => $l['received']);

            foreach ($lines as $line) {
                $subtotal += $line['quantity'] * $line['net_cost'];

                if ($line['type'] === 'part') {
                    $this->applyPartLine($purchaseOrder, $line, 'Updated PO ' . $purchaseOrder->display_po_number);
                    continue;
                }

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'        => $line['product']->id,
                    'is_set'            => $line['is_set'],
                    'quantity_ordered'  => $line['quantity'],
                    'quantity_received' => $line['received'] ? $line['quantity'] : 0,
                    'unit_cost'         => $line['unit_cost'],
                    'discount_percent'  => $line['discount_percent'],
                    'discount_amount'   => $line['discount_amount'],
                    'discounted_cost'   => $line['net_cost'],
                    'total_cost'        => $line['quantity'] * $line['net_cost'],
                ]);

                $line['product']->update(['cost' => $line['net_cost']]);
                if ($line['pair']) {
                    $line['pair']->update(['cost' => $line['net_cost']]);
                }

                if ($line['received']) {
                    $this->stockInSerials($purchaseOrder, $line['product'], $line['serials'], $receivedDate,
                        'Updated PO ' . $purchaseOrder->display_po_number, $line['net_cost']);
                    if ($line['is_set']) {
                        $this->stockInSerials($purchaseOrder, $line['pair'], $line['outdoor_serials'], $receivedDate,
                            'Updated PO ' . $purchaseOrder->display_po_number, $line['net_cost']);
                    }
                }
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
                'supplier_po_number'     => $request->supplier_po_number,
                'so_number'              => $request->input('so_number', $purchaseOrder->so_number),
                'delivery_number'        => $request->input('delivery_number', $purchaseOrder->delivery_number),
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
                'status'                 => $allReceived ? 'received' : 'pending',
                'received_date'          => $allReceived ? $receivedDate : null,
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
                ->with('success', 'Purchase order updated.');

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