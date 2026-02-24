<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Service;
use App\Models\InstallmentPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaleController extends Controller
{
    public function index(Request $request)
{
    $search = $request->search;

    $sales = Sale::with(['user', 'items.serials'])
        ->withCount('items')
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {

                // Invoice / Customer / Contact
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_contact', 'like', "%{$search}%")

                  // 🔥 Serial number search
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
            ->whereHas('serials', fn($q) => $q->where('status', 'in_stock'))
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
            'payment_method'       => 'required|in:cash,gcash,bank_transfer,cheque',
            'items'                => 'required|array|min:1',
            'items.*.type'         => 'required|in:product,service',
            'items.*.id'           => 'required|integer',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.serial_ids'   => 'nullable|array',
            'items.*.serial_ids.*' => 'nullable|integer|exists:product_serials,id',
            'notes'                => 'nullable|string',
            'discount'             => 'nullable|numeric|min:0',
            'down_payment'         => 'nullable|numeric|min:0',
            'down_payment_method'  => 'nullable|in:cash,gcash,bank_transfer,cheque',
            'installment_months'   => 'nullable|integer|min:1|max:24',
        ]);

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

        // Guard: serial count must match quantity
        foreach ($request->items as $idx => $item) {
            if ($item['type'] === 'product') {
                $serialIds = array_filter($item['serial_ids'] ?? []);
                $qty       = (int) $item['quantity'];
                if (count($serialIds) !== $qty) {
                    return back()->withInput()->withErrors([
                        'items' => 'Item #' . ($idx + 1) . ': select exactly ' . $qty . ' serial(s). Got ' . count($serialIds) . '.',
                    ]);
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
                        'product_id'  => $product->id,
                        'item_name'   => trim(($product->brand->name ?? '') . ' ' . $product->model),
                        'quantity'    => $item['quantity'],
                        'unit_price'  => $item['price'],
                        'total_price' => $item['quantity'] * $item['price'],
                    ]);

                    // Flip serials: in_stock → sold
                    $serialIds = array_filter($item['serial_ids'] ?? []);

                    // Update and check how many rows were actually updated
                    $updated = ProductSerial::whereIn('id', $serialIds)
                        ->where('product_id', $product->id)
                        ->where('status', 'in_stock')
                        ->update([
                            'status'       => 'sold',
                            'sale_id'      => $sale->id,
                            'sale_item_id' => $saleItem->id,
                            'sold_date'    => $request->sale_date,
                        ]);

                    // Prevent double-selling or race condition issues
                    if ($updated !== count($serialIds)) {
                        throw new \Exception('One or more selected serial numbers are no longer available.');
                    }

                } else {
                    $service = Service::findOrFail($item['id']);
                    SaleItem::create([
                        'sale_id'     => $sale->id,
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
}