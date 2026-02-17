<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\InstallmentPayment;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with('user')->orderBy('sale_date', 'desc')->paginate(15);
        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        // Count locked products to show warning tip in the view
        $lockedCount = Product::where('is_active', true)->where('price', 0)->count();

        // Pre-map with full label: Brand · Model · HP — avoids ambiguity in dropdown
        $products = Product::with('brand')
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->orderBy('brand_id')
            ->orderBy('model')
            ->get()
            ->map(function ($p) {
                $parts = array_filter([
                    $p->brand->name ?? null,
                    $p->model        ?? null,
                    $p->hp           ? $p->hp . ' HP'  : null,
                ]);
                $label = implode(' · ', $parts);
                return [
                    'id'    => $p->id,
                    'label' => $label ?: 'Unknown Product',
                    'price' => (float) $p->price,
                    'stock' => (int)   $p->stock_quantity,
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
        $validated = $request->validate([
            'customer_name'      => 'required|string|max:255',
            'customer_contact'   => 'nullable|string|max:255',
            'customer_address'   => 'nullable|string',
            'sale_date'          => 'required|date',
            'payment_type'       => 'required|in:cash,installment',
            'items'              => 'required|array|min:1',
            'items.*.type'       => 'required|in:product,service',
            'items.*.id'         => 'required|integer',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
            'notes'               => 'nullable|string',
            'discount'            => 'nullable|numeric|min:0',
            'payment_method'      => 'required|in:cash,gcash,bank_transfer,cheque',
            'down_payment'        => 'nullable|numeric|min:0',
            'down_payment_method' => 'nullable|in:cash,gcash,bank_transfer,cheque',
            'installment_months'  => 'nullable|integer|min:1|max:24',
        ]);

        // ── Server-side guard: block products with no selling price ──
        $blockedProducts = [];
        foreach ($request->items as $item) {
            if ($item['type'] === 'product') {
                $product = Product::with('brand')->find($item['id']);
                if ($product && $product->price == 0) {
                    $blockedProducts[] = ($product->brand->name ?? '') . ' ' . $product->model;
                }
            }
        }

        if (!empty($blockedProducts)) {
            return back()->withInput()->withErrors([
                'items' => 'The following product(s) have no selling price set and cannot be sold: ' .
                           implode(', ', $blockedProducts) .
                           '. Go to Products → Set Price first.',
            ]);
        }
        // ────────────────────────────────────────────────────────────

        DB::beginTransaction();

        try {
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }

            $discount = (float) ($request->discount ?? 0);
            $tax      = 0;
            $total    = max(0, $subtotal - $discount + $tax);

            $paidAmount = 0;
            $balance    = $total;

            if ($request->payment_type === 'installment' && $request->filled('down_payment') && $request->down_payment > 0) {
                $paidAmount = (float) $request->down_payment;
                $balance    = max(0, $total - $paidAmount);
            } elseif ($request->payment_type === 'cash') {
                $paidAmount = $total;
                $balance    = 0;
            }

            $sale = Sale::create([
                'customer_name'    => $request->customer_name,
                'customer_contact' => $request->customer_contact,
                'customer_address' => $request->customer_address,
                'sale_date'        => $request->sale_date,
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'tax'              => $tax,
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
                $itemName  = '';
                $productId = null;

                if ($item['type'] === 'product') {
                    $product   = Product::with('brand')->findOrFail($item['id']);
                    // Use Brand + Model as the item name (no separate "name" field)
                    $itemName  = trim(($product->brand->name ?? '') . ' ' . $product->model);
                    $productId = $product->id;

                    $stockBefore = $product->stock_quantity;
                    $product->decrement('stock_quantity', $item['quantity']);
                    $stockAfter = $product->stock_quantity;

                    InventoryMovement::create([
                        'product_id'     => $product->id,
                        'type'           => 'stock_out',
                        'quantity'       => -$item['quantity'],
                        'stock_before'   => $stockBefore,
                        'stock_after'    => $stockAfter,
                        'reference_type' => 'Sale',
                        'reference_id'   => $sale->id,
                        'notes'          => 'Sold via invoice: ' . $sale->invoice_number,
                        'user_id'        => auth()->id(),
                    ]);
                } else {
                    $service  = Service::findOrFail($item['id']);
                    $itemName = $service->name;
                }

                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $productId,
                    'item_name'   => $itemName,
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['price'],
                    'total_price' => $item['quantity'] * $item['price'],
                ]);
            }

            if ($request->payment_type === 'installment') {
                $installmentMonths = (int) ($request->installment_months ?? 12);
                $downPayment       = (float) ($request->down_payment ?? 0);
                $saleDate          = Carbon::parse($request->sale_date);

                $num = 1; // installment counter

                // ── Downpayment = Month #1, recorded as PAID ──
                if ($downPayment > 0) {
                    InstallmentPayment::create([
                        'sale_id'            => $sale->id,
                        'installment_number' => $num,
                        'amount'             => $downPayment,
                        'amount_paid'        => $downPayment,
                        'due_date'           => $saleDate,
                        'paid_date'          => $saleDate,
                        'status'             => 'paid',
                        'payment_method'     => $request->down_payment_method
                                               ?: $request->payment_method,
                        'notes'              => 'Downpayment',
                    ]);
                    $num++;
                }

                // ── Remaining balance ÷ months = each monthly payment ──
                // $balance already = total - downpayment (set above)
                $monthly = $installmentMonths > 0
                    ? round($balance / $installmentMonths, 2)
                    : $balance;

                for ($i = 0; $i < $installmentMonths; $i++) {
                    InstallmentPayment::create([
                        'sale_id'            => $sale->id,
                        'installment_number' => $num,
                        'amount'             => $monthly,
                        'amount_paid'        => 0,
                        'due_date'           => $saleDate->copy()->addMonths($i + 1),
                        'status'             => 'unpaid',
                    ]);
                    $num++;
                }
            }

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Sale created successfully. Invoice: ' . $sale->invoice_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error creating sale: ' . $e->getMessage());
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product.brand', 'user', 'installmentPayments']);
        return view('sales.show', compact('sale'));
    }

    public function destroy(Sale $sale)
    {
        DB::beginTransaction();

        try {
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $stockBefore = $product->stock_quantity;
                        $product->increment('stock_quantity', $item->quantity);
                        $stockAfter = $product->fresh()->stock_quantity;

                        // Log the reversal in inventory movement history
                        InventoryMovement::create([
                            'product_id'     => $product->id,
                            'type'           => 'stock_in',
                            'quantity'       => $item->quantity,
                            'stock_before'   => $stockBefore,
                            'stock_after'    => $stockAfter,
                            'reference_type' => 'Sale',
                            'reference_id'   => $sale->id,
                            'notes'          => 'Stock reversed — Sale deleted: ' . $sale->invoice_number,
                            'user_id'        => auth()->id(),
                        ]);
                    }
                }
            }

            $sale->installmentPayments()->delete();
            $sale->items()->delete();
            $sale->delete();

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Sale deleted successfully and stock restored.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting sale: ' . $e->getMessage());
        }
    }
}