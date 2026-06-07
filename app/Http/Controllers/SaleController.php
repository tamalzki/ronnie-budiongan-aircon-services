<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPayment;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Support\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Sale::class, 'sale', [
            'except' => ['edit', 'update'],
        ]);
    }

    public function index(Request $request)
    {
        $search = $request->search;

        $sales = Sale::with('user')
            ->withCount('items')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_contact', 'like', "%{$search}%")
                        ->orWhereHas('items.serials', function ($sq) use ($search) {
                            $sq->where('serial_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('sale_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $lockedCount = Product::where('is_active', true)->where('price', 0)->count();

        $products = Product::with([
                'brand',
                'serials' => fn($q) => $q->where('status', 'in_stock')->orderBy('serial_number'),
            ])
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->orderBy('brand_id')
            ->orderBy('model')
            ->get()
            ->map(function ($p) {
                return [
                    'id'        => $p->id,
                    'label'     => trim(($p->brand->name ?? '') . ' · ' . $p->model) ?: 'Unknown',
                    'unit_type' => $p->unit_type,
                    'price'     => (float) $p->price,
                    'stock'     => $p->serials->count(),
                    'serials'   => $p->serials->map(fn($s) => [
                        'id'            => $s->id,
                        'serial_number' => $s->serial_number,
                    ])->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $services = Service::where('is_active', true)
            ->get()
            ->map(fn($s) => [
                'id'    => $s->id,
                'label' => $s->name,
                'price' => (float) $s->default_price,
            ])
            ->values()
            ->toArray();

        return view('sales.create', compact('products', 'services', 'lockedCount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name'        => 'required|string|max:255',
            'customer_contact'     => 'nullable|string|max:255',
            'customer_address'     => 'nullable|string',
            'sale_date'            => 'required|date',
            'payment_type'         => 'required|in:cash,installment',
            'payment_method'       => ['required', Rule::in(PaymentMethod::values())],
            'items'                => 'required|array|min:1',
            'items.*.type'         => 'required|in:product,service',
            'items.*.id'           => 'required|integer',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.serial_ids'      => 'nullable|array',
            'items.*.serial_ids.*'    => 'nullable|integer|exists:product_serials,id',
            'items.*.new_serials_raw' => 'nullable|string',
            'notes'                   => 'nullable|string',
            'discount'             => 'nullable|numeric|min:0',
            'down_payment'         => 'nullable|numeric|min:0',
            'down_payment_method'  => [
                'nullable',
                Rule::requiredIf(function () use ($request) {
                    return $request->payment_type === 'installment'
                        && (float) ($request->down_payment ?? 0) > 0;
                }),
                Rule::in(PaymentMethod::values()),
            ],
            'installment_months'   => 'nullable|integer|min:1|max:24',
        ]);

        $subtotalPreview   = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['price']);
        $discountPreview   = (float) ($request->discount ?? 0);
        $totalPreview      = max(0, $subtotalPreview - $discountPreview);
        if ($request->payment_type === 'installment' && (float) ($request->down_payment ?? 0) > $totalPreview + 0.001) {
            return back()->withInput()->withErrors([
                'down_payment' => 'Down payment cannot exceed the sale total.',
            ]);
        }

        // Guard: no price
        foreach ($request->items as $item) {
            if ($item['type'] === 'product') {
                $p = Product::with('brand')->find($item['id']);
                if ($p && $p->price == 0) {
                    return back()->withInput()->withErrors([
                        'items' => 'No selling price for: ' . ($p->brand->name ?? '') . ' ' . $p->model,
                    ]);
                }
            }
        }

        // Guard: product lines — either no serials encoded, or existing + new serials exactly match quantity
        foreach ($request->items as $idx => $item) {
            if ($item['type'] !== 'product') {
                continue;
            }
            $qty        = (int) $item['quantity'];
            $serialIdsRaw = array_values(array_filter($item['serial_ids'] ?? [], fn($v) => $v !== null && $v !== ''));
            $serialIdsInt = array_map(fn ($v) => (int) $v, $serialIdsRaw);
            if (count($serialIdsInt) !== count(array_unique($serialIdsInt))) {
                return back()->withInput()->withErrors([
                    'items' => 'Item #' . ($idx + 1) . ': duplicate serial selection.',
                ]);
            }
            $serialIds  = array_values(array_unique($serialIdsInt));
            $newSerials = $this->parseNewSerialLines($item['new_serials_raw'] ?? null);
            $attached   = count($serialIds) + $newSerials->count();

            // When a product has no in-stock serials on file, encoding a serial per unit is required.
            $inStockCount = ProductSerial::where('product_id', $item['id'])
                ->where('status', 'in_stock')
                ->count();
            if ($inStockCount === 0 && $attached !== $qty) {
                return back()->withInput()->withErrors([
                    'items' => 'Item #' . ($idx + 1) . ': this product has no recorded serial numbers, so a serial number is required for each of the ' . $qty . ' unit(s).',
                ]);
            }

            if ($attached !== $qty && $attached !== 0) {
                return back()->withInput()->withErrors([
                    'items' => 'Item #' . ($idx + 1) . ': quantity is ' . $qty . ' but you selected ' . count($serialIds)
                        . ' in-stock serial(s) and entered ' . $newSerials->count() . ' new serial(s). Either leave serials empty to sell without serials, or match the quantity exactly.',
                ]);
            }

            if ($newSerials->isNotEmpty()) {
                $dupes = $newSerials->duplicates();
                if ($dupes->isNotEmpty()) {
                    return back()->withInput()->withErrors([
                        'items' => 'Item #' . ($idx + 1) . ': duplicate new serial(s): ' . $dupes->unique()->implode(', '),
                    ]);
                }
                $product = Product::find($item['id']);
                if ($product) {
                    $blocked = ProductSerial::query()
                        ->where('product_id', $product->id)
                        ->whereIn('serial_number', $newSerials)
                        ->pluck('serial_number');
                    if ($blocked->isNotEmpty()) {
                        return back()->withInput()->withErrors([
                            'items' => 'Item #' . ($idx + 1) . ': serial number(s) already exist for this product: ' . $blocked->implode(', '),
                        ]);
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            $subtotal   = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['price']);
            $discount   = (float) ($request->discount ?? 0);
            $total      = max(0, $subtotal - $discount);
            $paidAmount = $request->payment_type === 'cash' ? $total : (float) ($request->down_payment ?? 0);
            $balance    = max(0, $total - $paidAmount);

            $sale = Sale::create([
                'customer_name'    => $request->customer_name,
                'customer_contact' => $request->customer_contact,
                'customer_address' => $request->customer_address,
                'sale_date'        => $request->sale_date,
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'tax'              => 0,
                'total'            => $total,
                'payment_type'     => $request->payment_type,
                'paid_amount'      => $paidAmount,
                'balance'          => $balance,
                'status'           => 'completed',
                'payment_method'   => $request->payment_method,
                'notes'            => $request->notes,
                'user_id'          => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                if ($item['type'] === 'product') {
                    $product  = Product::with('brand')->findOrFail($item['id']);
                    $saleItem = SaleItem::create([
                        'sale_id'     => $sale->id,
                        'item_type'   => 'product',
                        'product_id'  => $product->id,
                        'item_name'   => trim(($product->brand->name ?? '') . ' ' . $product->model),
                        'quantity'    => $item['quantity'],
                        'unit_price'  => $item['price'],
                        'total_price' => $item['quantity'] * $item['price'],
                    ]);

                    $serialIdsRaw = array_values(array_filter($item['serial_ids'] ?? [], fn($v) => $v !== null && $v !== ''));
                    $serialIds     = array_values(array_unique(array_map(fn ($v) => (int) $v, $serialIdsRaw)));
                    $newSerials    = $this->parseNewSerialLines($item['new_serials_raw'] ?? null);

                    if (count($serialIds) + $newSerials->count() === 0) {
                        continue;
                    }

                    foreach ($serialIds as $serialId) {
                        $serialId = (int) $serialId;
                        $sn       = ProductSerial::whereKey($serialId)->where('product_id', $product->id)->value('serial_number');
                        if (!$sn) {
                            throw new \Exception('Invalid serial selection for this product.');
                        }

                        $stockBefore = $product->fresh()->inStockSerials()->count();
                        $updated     = ProductSerial::whereKey($serialId)
                            ->where('product_id', $product->id)
                            ->where('status', 'in_stock')
                            ->update([
                                'status'       => 'sold',
                                'sale_id'      => $sale->id,
                                'sale_item_id' => $saleItem->id,
                                'sold_date'    => $request->sale_date,
                            ]);

                        if ($updated !== 1) {
                            throw new \Exception('One or more selected serial numbers are no longer available.');
                        }

                        $stockAfter = $product->fresh()->inStockSerials()->count();

                        InventoryMovement::create([
                            'product_id'     => $product->id,
                            'type'           => 'stock_out',
                            'quantity'       => 1,
                            'stock_before'   => $stockBefore,
                            'stock_after'    => $stockAfter,
                            'reference_type' => 'Sale',
                            'reference_id'   => $sale->id,
                            'notes'          => 'Sold — ' . $sale->invoice_number . ' | SN: ' . $sn,
                            'user_id'        => auth()->id(),
                        ]);
                    }

                    foreach ($newSerials as $serialNumber) {
                        $serialNumber = trim($serialNumber);

                        $stockBeforeIn = $product->fresh()->inStockSerials()->count();

                        $created = ProductSerial::create([
                            'product_id'         => $product->id,
                            'purchase_order_id'  => null,
                            'serial_number'      => $serialNumber,
                            'status'             => 'in_stock',
                            'received_date'      => $request->sale_date,
                            'notes'              => 'Registered at sale',
                        ]);

                        $stockAfterIn = $product->fresh()->inStockSerials()->count();

                        InventoryMovement::create([
                            'product_id'     => $product->id,
                            'type'           => 'stock_in',
                            'quantity'       => 1,
                            'stock_before'   => $stockBeforeIn,
                            'stock_after'    => $stockAfterIn,
                            'reference_type' => 'Sale',
                            'reference_id'   => $sale->id,
                            'notes'          => 'Serial registered at sale — ' . $sale->invoice_number . ' | SN: ' . $serialNumber,
                            'user_id'        => auth()->id(),
                        ]);

                        ProductSerial::whereKey($created->id)->update([
                            'status'       => 'sold',
                            'sale_id'      => $sale->id,
                            'sale_item_id' => $saleItem->id,
                            'sold_date'    => $request->sale_date,
                        ]);

                        $stockAfterOut = $product->fresh()->inStockSerials()->count();

                        InventoryMovement::create([
                            'product_id'     => $product->id,
                            'type'           => 'stock_out',
                            'quantity'       => 1,
                            'stock_before'   => $stockAfterIn,
                            'stock_after'    => $stockAfterOut,
                            'reference_type' => 'Sale',
                            'reference_id'   => $sale->id,
                            'notes'          => 'Sold — ' . $sale->invoice_number . ' | SN: ' . $serialNumber,
                            'user_id'        => auth()->id(),
                        ]);
                    }

                } else {
                    $service = Service::findOrFail($item['id']);
                    SaleItem::create([
                        'sale_id'     => $sale->id,
                        'item_type'   => 'service',
                        'service_id'  => $service->id,
                        'product_id'  => null,
                        'item_name'   => $service->name,
                        'quantity'    => $item['quantity'],
                        'unit_price'  => $item['price'],
                        'total_price' => $item['quantity'] * $item['price'],
                    ]);
                }
            }

            // Installment schedule
            if ($request->payment_type === 'installment') {
                $months   = (int) ($request->installment_months ?? 12);
                $down     = (float) ($request->down_payment ?? 0);
                $saleDate = Carbon::parse($request->sale_date);
                $num      = 1;

                if ($down > 0) {
                    InstallmentPayment::create([
                        'sale_id'            => $sale->id,
                        'installment_number' => $num++,
                        'amount'             => $down,
                        'amount_paid'        => $down,
                        'due_date'           => $saleDate,
                        'paid_date'          => $saleDate,
                        'status'             => 'paid',
                        'payment_method'     => $request->down_payment_method ?: $request->payment_method,
                        'notes'              => 'Downpayment',
                    ]);
                }

                $monthly = $months > 0 ? round($balance / $months, 2) : $balance;
                for ($i = 0; $i < $months; $i++) {
                    InstallmentPayment::create([
                        'sale_id'            => $sale->id,
                        'installment_number' => $num++,
                        'amount'             => $monthly,
                        'amount_paid'        => 0,
                        'due_date'           => $saleDate->copy()->addMonths($i + 1),
                        'status'             => 'unpaid',
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('sales.index')
                ->with('success', 'Sale created. Invoice: ' . $sale->invoice_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product.brand', 'items.serials', 'user', 'installmentPayments']);
        return view('sales.show', compact('sale'));
    }

    public function destroy(Sale $sale)
    {
        DB::beginTransaction();
        try {
            // Restore serials sold → in_stock
            ProductSerial::where('sale_id', $sale->id)->update([
                'status'       => 'in_stock',
                'sale_id'      => null,
                'sale_item_id' => null,
                'sold_date'    => null,
            ]);

            $sale->installmentPayments()->delete();
            $sale->items()->delete();
            $sale->delete();

            DB::commit();
            return redirect()->route('sales.index')
                ->with('success', 'Sale deleted and serials restored to stock.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * @return Collection<int, string>
     */
    private function parseNewSerialLines(?string $raw): Collection
    {
        if ($raw === null || trim($raw) === '') {
            return collect();
        }

        return collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();
    }
}