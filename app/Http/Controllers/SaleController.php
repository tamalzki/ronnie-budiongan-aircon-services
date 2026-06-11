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
        $this->authorizeResource(Sale::class, 'sale');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->search);

        $sales = Sale::with([
                'user',
                'items.serials' => fn ($sq) => $sq->select('id', 'sale_item_id', 'serial_number'),
            ])
            ->withCount('items')
            ->when($search !== '', function ($query) use ($search) {
                $serialSaleIds = ProductSerial::query()
                    ->where('serial_number', 'like', "%{$search}%")
                    ->whereNotNull('sale_id')
                    ->pluck('sale_id');

                $query->where(function ($q) use ($search, $serialSaleIds) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_contact', 'like', "%{$search}%")
                        ->orWhereHas('items.serials', function ($sq) use ($search) {
                            $sq->where('serial_number', 'like', "%{$search}%");
                        })
                        ->when($serialSaleIds->isNotEmpty(), fn($qq) => $qq->orWhereIn('id', $serialSaleIds));
                });
            })
            ->orderBy('sale_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', compact('sales', 'search'));
    }

    public function lookupSerial(Request $request)
    {
        $this->authorize('viewAny', Sale::class);

        $request->validate([
            'q'          => 'required|string|min:2|max:100',
            'product_id' => 'nullable|exists:products,id',
        ]);

        $term = trim($request->q);

        $query = ProductSerial::query()
            ->with([
                'sale:id,customer_name,invoice_number,sale_date',
                'product:id,model',
            ])
            ->where('status', 'sold')
            ->where('serial_number', 'like', "%{$term}%");

        if ($request->filled('product_id')) {
            $product = Product::find($request->product_id);
            $productIds = Product::query()
                ->where('id', $product->id)
                ->orWhere('paired_product_id', $product->id)
                ->when($product->paired_product_id, fn($q) => $q->orWhere('id', $product->paired_product_id))
                ->pluck('id');

            $query->whereIn('product_id', $productIds);
        }

        $results = $query
            ->orderBy('serial_number')
            ->limit(20)
            ->get()
            ->map(fn(ProductSerial $serial) => [
                'serial_number'  => $serial->serial_number,
                'customer_name'  => $serial->sale?->customer_name,
                'invoice_number' => $serial->sale?->invoice_number,
                'sale_date'      => $serial->sale?->sale_date
                    ? Carbon::parse($serial->sale->sale_date)->format('M d, Y')
                    : null,
                'sale_url'       => $serial->sale ? route('sales.show', $serial->sale) : null,
                'model'          => $serial->product?->model,
            ])
            ->values();

        return response()->json($results);
    }

    public function create()
    {
        return view('sales.create', $this->saleFormViewData());
    }

    public function store(Request $request)
    {
        $itemErrors = $this->validateSaleItemsRequest($request, null);
        if ($itemErrors !== []) {
            return back()->withInput()->withErrors($itemErrors);
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

            $this->persistSaleItems($sale, $request->items, $request->sale_date);
            $this->persistInstallmentSchedule($sale, $request);

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
        $sale->load([
            'items.product.brand',
            'items.product.pairedProduct',
            'items.serials',
            'user',
            'installmentPayments',
        ]);

        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        return view('sales.create', $this->saleFormViewData($sale));
    }

    public function update(Request $request, Sale $sale)
    {
        $itemErrors = $this->validateSaleItemsRequest($request, $sale->id);
        if ($itemErrors !== []) {
            return back()->withInput()->withErrors($itemErrors);
        }

        DB::beginTransaction();
        try {
            $reassignSerialIds = ProductSerial::where('sale_id', $sale->id)->pluck('id')->all();
            $this->restoreSaleSerials($sale);

            $subtotal   = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['price']);
            $discount   = (float) ($request->discount ?? 0);
            $total      = max(0, $subtotal - $discount);
            $paidAmount = $request->payment_type === 'cash' ? $total : (float) ($request->down_payment ?? 0);
            $balance    = max(0, $total - $paidAmount);

            $sale->update([
                'customer_name'    => $request->customer_name,
                'customer_contact' => $request->customer_contact,
                'customer_address' => $request->customer_address,
                'sale_date'        => $request->sale_date,
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'total'            => $total,
                'payment_type'     => $request->payment_type,
                'payment_method'   => $request->payment_method,
                'paid_amount'      => $paidAmount,
                'balance'          => $balance,
                'status'           => 'completed',
                'notes'            => $request->notes,
            ]);

            $sale->items()->delete();
            $sale->installmentPayments()->delete();

            $this->persistSaleItems($sale, $request->items, $request->sale_date, $reassignSerialIds);
            $this->persistInstallmentSchedule($sale, $request);

            DB::commit();

            return redirect()->route('sales.show', $sale)
                ->with('success', 'Sale updated. Invoice: ' . $sale->invoice_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(Sale $sale)
    {
        DB::beginTransaction();
        try {
            $this->restoreSaleSerials($sale);

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

    /**
     * Validate one product side (indoor / outdoor / single unit) of a sale line.
     * Returns an error message, or null when valid. Appends the attached count
     * to $attachedCounts so set sides can be cross-checked.
     */
    private function saleFormViewData(?Sale $sale = null): array
    {
        $lockedCount = Product::where('is_active', true)->where('price', 0)
            ->where(function ($q) {
                $q->whereNotIn('id', Product::whereNotNull('paired_product_id')->pluck('paired_product_id'));
            })
            ->count();

        $saleId      = $sale?->id;
        $serialScope = function ($q) use ($saleId) {
            $q->where('status', 'in_stock');
            if ($saleId) {
                $q->orWhere(fn($qq) => $qq->where('sale_id', $saleId)->where('status', 'sold'));
            }
            $q->orderBy('serial_number');
        };

        $all = Product::with([
                'brand',
                'serials'               => $serialScope,
                'pairedProduct.serials' => $serialScope,
            ])
            ->where('is_active', true)
            ->orderBy('brand_id')
            ->orderBy('model')
            ->get();

        $pairedOutdoorIds = $all->pluck('paired_product_id')->filter()->unique();

        $mapSerials = fn($serials) => $serials->map(fn($s) => [
            'id'            => $s->id,
            'serial_number' => $s->serial_number,
        ])->values()->toArray();

        $products = $all
            ->filter(fn($p) => $p->price > 0 && !$pairedOutdoorIds->contains($p->id))
            ->map(function ($p) use ($mapSerials) {
                if ($p->is_set_primary && $p->pairedProduct) {
                    return [
                        'id'              => $p->id,
                        'is_set'          => true,
                        'label'           => trim(($p->brand->name ?? '') . ' · ' . $p->set_model_label),
                        'indoor_model'    => $p->model,
                        'outdoor_model'   => $p->pairedProduct->model,
                        'unit_type'       => 'set',
                        'price'           => (float) $p->price,
                        'stock'           => min($p->serials->count(), $p->pairedProduct->serials->count()),
                        'indoor_stock'    => $p->serials->count(),
                        'outdoor_stock'   => $p->pairedProduct->serials->count(),
                        'serials'         => $mapSerials($p->serials),
                        'outdoor_serials' => $mapSerials($p->pairedProduct->serials),
                    ];
                }

                return [
                    'id'              => $p->id,
                    'is_set'          => false,
                    'label'           => trim(($p->brand->name ?? '') . ' · ' . $p->model) ?: 'Unknown',
                    'indoor_model'    => $p->model,
                    'outdoor_model'   => '',
                    'unit_type'       => $p->unit_type,
                    'price'           => (float) $p->price,
                    'stock'           => $p->serials->count(),
                    'indoor_stock'    => $p->serials->count(),
                    'outdoor_stock'   => 0,
                    'serials'         => $mapSerials($p->serials),
                    'outdoor_serials' => [],
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

        $data = compact('products', 'services', 'lockedCount');
        $data['isEdit']              = $sale !== null;
        $data['sale']                = $sale;
        $data['prefillItems']        = $sale ? $this->buildPrefillItems($sale) : [];
        $data['hasPaidInstallments'] = false;
        $data['prefillDownPayment']  = 0;
        $data['prefillDownMethod']   = '';

        if ($sale) {
            $sale->load('installmentPayments');
            $downRow = $sale->installmentPayments->first(fn($p) => $p->notes === 'Downpayment')
                ?? $sale->installmentPayments->firstWhere('installment_number', 1);
            if ($sale->payment_type === 'installment' && $downRow && $downRow->status === 'paid') {
                $data['prefillDownPayment'] = (float) $downRow->amount_paid;
                $data['prefillDownMethod']  = $downRow->payment_method ?? '';
            }
            $hasDown = $downRow && $downRow->status === 'paid' && (float) $downRow->amount_paid > 0;
            $data['hasPaidInstallments'] = $sale->installmentPayments
                ->where('status', 'paid')
                ->when($hasDown, fn($c) => $c->reject(fn($p) => $p->id === $downRow->id))
                ->isNotEmpty();
        }

        return $data;
    }

    private function buildPrefillItems(Sale $sale): array
    {
        $sale->load(['items.product.pairedProduct']);
        $allSaleSerials = ProductSerial::where('sale_id', $sale->id)->get();

        return $sale->items->map(function (SaleItem $item) use ($allSaleSerials) {
            if ($item->item_type === 'service') {
                return [
                    'type'     => 'service',
                    'id'       => (int) $item->service_id,
                    'quantity' => (int) $item->quantity,
                    'price'    => (float) $item->unit_price,
                ];
            }

            $isSet = $item->is_set && $item->product?->pairedProduct;

            return [
                'type'                    => 'product',
                'id'                      => (int) $item->product_id,
                'quantity'                => (int) $item->quantity,
                'price'                   => (float) $item->unit_price,
                'serial_ids'              => $allSaleSerials->where('product_id', $item->product_id)->pluck('id')->values()->all(),
                'outdoor_serial_ids'      => $isSet
                    ? $allSaleSerials->where('product_id', $item->product->paired_product_id)->pluck('id')->values()->all()
                    : [],
                'new_serials_raw'         => '',
                'outdoor_new_serials_raw' => '',
            ];
        })->values()->all();
    }

    private function validateSaleItemsRequest(Request $request, ?int $ignoreSaleId): array
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
            'items.*.outdoor_serial_ids'      => 'nullable|array',
            'items.*.outdoor_serial_ids.*'    => 'nullable|integer|exists:product_serials,id',
            'items.*.outdoor_new_serials_raw' => 'nullable|string',
            'notes'                => 'nullable|string',
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
            'installment_months'   => 'nullable|integer|min:1|max:60',
        ]);

        $subtotalPreview = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['price']);
        $discountPreview = (float) ($request->discount ?? 0);
        $totalPreview    = max(0, $subtotalPreview - $discountPreview);
        if ($request->payment_type === 'installment' && (float) ($request->down_payment ?? 0) > $totalPreview + 0.001) {
            return ['down_payment' => 'Down payment cannot exceed the sale total.'];
        }

        foreach ($request->items as $item) {
            if ($item['type'] === 'product') {
                $p = Product::with('brand')->find($item['id']);
                if ($p && $p->price == 0) {
                    return ['items' => 'No selling price for: ' . ($p->brand->name ?? '') . ' ' . $p->model];
                }
            }
        }

        foreach ($request->items as $idx => $item) {
            if ($item['type'] !== 'product') {
                continue;
            }

            $product = Product::with('pairedProduct')->find($item['id']);
            if (!$product) {
                return ['items' => 'Item #' . ($idx + 1) . ': product not found.'];
            }

            $qty   = (int) $item['quantity'];
            $isSet = $product->is_set_primary && $product->pairedProduct;

            $sides = [
                ['product' => $product, 'ids' => $item['serial_ids'] ?? [], 'raw' => $item['new_serials_raw'] ?? null, 'label' => $isSet ? 'indoor unit' : 'unit'],
            ];
            if ($isSet) {
                $sides[] = ['product' => $product->pairedProduct, 'ids' => $item['outdoor_serial_ids'] ?? [], 'raw' => $item['outdoor_new_serials_raw'] ?? null, 'label' => 'outdoor unit'];
            }

            $attachedCounts = [];
            foreach ($sides as $side) {
                $error = $this->validateSaleSerialSide($side['product'], $side['ids'], $side['raw'], $qty, $idx, $side['label'], $attachedCounts, $ignoreSaleId);
                if ($error !== null) {
                    return ['items' => $error];
                }
            }

            if ($isSet && array_filter($attachedCounts, fn ($c) => $c !== $qty)) {
                return ['items' => 'Item #' . ($idx + 1) . ': this is an indoor + outdoor set — enter the serials of BOTH units (' . $qty . ' each).'];
            }
        }

        return [];
    }

    private function persistSaleItems(Sale $sale, array $items, $saleDate, array $reassignSerialIds = []): void
    {
        foreach ($items as $item) {
            if ($item['type'] === 'product') {
                $product = Product::with(['brand', 'pairedProduct'])->findOrFail($item['id']);
                $isSet   = $product->is_set_primary && $product->pairedProduct;

                $itemName = $isSet
                    ? trim(($product->brand->name ?? '') . ' ' . $product->set_model_label)
                    : trim(($product->brand->name ?? '') . ' ' . $product->model);

                $saleItem = SaleItem::create([
                    'sale_id'     => $sale->id,
                    'item_type'   => 'product',
                    'product_id'  => $product->id,
                    'is_set'      => $isSet,
                    'item_name'   => $itemName,
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['price'],
                    'total_price' => $item['quantity'] * $item['price'],
                ]);

                $this->sellSerialsForProduct(
                    $sale, $saleItem, $product,
                    $item['serial_ids'] ?? [],
                    $this->parseNewSerialLines($item['new_serials_raw'] ?? null),
                    $saleDate,
                    $reassignSerialIds
                );

                if ($isSet) {
                    $this->sellSerialsForProduct(
                        $sale, $saleItem, $product->pairedProduct,
                        $item['outdoor_serial_ids'] ?? [],
                        $this->parseNewSerialLines($item['outdoor_new_serials_raw'] ?? null),
                        $saleDate,
                        $reassignSerialIds
                    );
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
    }

    private function persistInstallmentSchedule(Sale $sale, Request $request): void
    {
        if ($request->payment_type !== 'installment') {
            $sale->update(['installment_months' => null, 'installment_amount' => null]);
            return;
        }

        $months   = max(1, (int) ($request->installment_months ?? 12));
        $down     = (float) ($request->down_payment ?? 0);
        $balance  = (float) $sale->balance;
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

        $remainingMonths = $down > 0 ? max(1, $months - 1) : $months;
        $monthly         = $balance > 0 ? round($balance / $remainingMonths, 2) : 0;

        if ($balance > 0) {
            for ($i = 0; $i < $remainingMonths; $i++) {
                $amount = $i === $remainingMonths - 1
                    ? round($balance - $monthly * ($remainingMonths - 1), 2)
                    : $monthly;

                InstallmentPayment::create([
                    'sale_id'            => $sale->id,
                    'installment_number' => $num++,
                    'amount'             => $amount,
                    'amount_paid'        => 0,
                    'due_date'           => $saleDate->copy()->addMonths($i + 1),
                    'status'             => 'unpaid',
                ]);
            }
        }

        $sale->update([
            'installment_months' => $months,
            'installment_amount' => $monthly,
        ]);
    }

    private function restoreSaleSerials(Sale $sale): void
    {
        ProductSerial::where('sale_id', $sale->id)->update([
            'status'       => 'in_stock',
            'sale_id'      => null,
            'sale_item_id' => null,
            'sold_date'    => null,
        ]);
    }

    private function validateSaleSerialSide(Product $product, array $serialIdsRaw, ?string $newRaw, int $qty, int $idx, string $sideLabel, array &$attachedCounts, ?int $ignoreSaleId = null): ?string
    {
        $itemNo = 'Item #' . ($idx + 1);

        $serialIdsInt = array_map(fn ($v) => (int) $v, array_values(array_filter($serialIdsRaw, fn($v) => $v !== null && $v !== '')));
        if (count($serialIdsInt) !== count(array_unique($serialIdsInt))) {
            return "{$itemNo}: duplicate serial selection ({$sideLabel}).";
        }
        $serialIds  = array_values(array_unique($serialIdsInt));
        $newSerials = $this->parseNewSerialLines($newRaw);
        $attached   = count($serialIds) + $newSerials->count();

        $attachedCounts[] = $attached;

        $inStockCount = ProductSerial::where('product_id', $product->id)
            ->where(function ($q) use ($ignoreSaleId) {
                $q->where('status', 'in_stock');
                if ($ignoreSaleId) {
                    $q->orWhere(fn($qq) => $qq->where('sale_id', $ignoreSaleId)->where('status', 'sold'));
                }
            })
            ->count();
        if ($inStockCount === 0 && $attached !== $qty) {
            return "{$itemNo}: {$product->model} ({$sideLabel}) has no recorded serial numbers, so a serial is required for each of the {$qty} unit(s).";
        }

        if ($attached !== $qty && $attached !== 0) {
            return "{$itemNo}: quantity is {$qty} but {$sideLabel} has " . count($serialIds)
                . ' in-stock serial(s) and ' . $newSerials->count()
                . ' new serial(s). Either leave serials empty or match the quantity exactly.';
        }

        if ($newSerials->isNotEmpty()) {
            $dupes = $newSerials->duplicates();
            if ($dupes->isNotEmpty()) {
                return "{$itemNo}: duplicate new serial(s) ({$sideLabel}): " . $dupes->unique()->implode(', ');
            }

            $blocked = ProductSerial::query()
                ->where('product_id', $product->id)
                ->whereIn('serial_number', $newSerials)
                ->when($ignoreSaleId, fn($q) => $q->where(fn($qq) =>
                    $qq->whereNull('sale_id')->orWhere('sale_id', '!=', $ignoreSaleId)))
                ->pluck('serial_number');
            if ($blocked->isNotEmpty()) {
                return "{$itemNo}: serial number(s) already exist for {$product->model}: " . $blocked->implode(', ');
            }
        }

        return null;
    }

    /**
     * Mark selected in-stock serials as sold (and register+sell new serials)
     * for one product under a sale line, logging inventory movements.
     */
    private function sellSerialsForProduct(Sale $sale, SaleItem $saleItem, Product $product, array $serialIdsRaw, Collection $newSerials, $saleDate, array $reassignSerialIds = []): void
    {
        $serialIds = array_values(array_unique(array_map(
            fn ($v) => (int) $v,
            array_values(array_filter($serialIdsRaw, fn($v) => $v !== null && $v !== ''))
        )));

        if (count($serialIds) + $newSerials->count() === 0) {
            return;
        }

        $reassignLookup = array_fill_keys($reassignSerialIds, true);

        foreach ($serialIds as $serialId) {
            $ps = ProductSerial::whereKey($serialId)->where('product_id', $product->id)->first();
            if (!$ps) {
                throw new \Exception('Invalid serial selection for ' . $product->model . '.');
            }

            if (isset($reassignLookup[$serialId])) {
                $updated = ProductSerial::whereKey($serialId)
                    ->where('product_id', $product->id)
                    ->where('status', 'in_stock')
                    ->update([
                        'status'       => 'sold',
                        'sale_id'      => $sale->id,
                        'sale_item_id' => $saleItem->id,
                        'sold_date'    => $saleDate,
                    ]);

                if ($updated !== 1) {
                    throw new \Exception('One or more serial numbers from this sale are no longer available.');
                }
                continue;
            }

            $sn = $ps->serial_number;
            $stockBefore = $product->fresh()->inStockSerials()->count();
            $updated     = ProductSerial::whereKey($serialId)
                ->where('product_id', $product->id)
                ->where('status', 'in_stock')
                ->update([
                    'status'       => 'sold',
                    'sale_id'      => $sale->id,
                    'sale_item_id' => $saleItem->id,
                    'sold_date'    => $saleDate,
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
                'received_date'      => $saleDate,
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
                'sold_date'    => $saleDate,
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
    }
}