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
        $sales = Sale::with('user')->orderBy('sale_date', 'desc')->get();
        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        // Only products WITH a selling price — locked products are hidden
        $products = Product::with('brand')
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->orderBy('brand_id')
            ->orderBy('model')
            ->get();

        // Count locked products to show warning tip in the view
        $lockedCount = Product::where('is_active', true)->where('price', 0)->count();

        $services = Service::where('is_active', true)->get();

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
            'notes'              => 'nullable|string',
            'down_payment'       => 'nullable|numeric|min:0',
            'installment_months' => 'nullable|integer|min:1|max:24',
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

            $tax   = 0;
            $total = $subtotal + $tax;

            $paidAmount = 0;
            $balance    = $total;

            if ($request->payment_type === 'installment' && $request->filled('down_payment')) {
                $paidAmount = $request->down_payment;
                $balance    = $total - $paidAmount;
            }

            $sale = Sale::create([
                'customer_name'    => $request->customer_name,
                'customer_contact' => $request->customer_contact,
                'customer_address' => $request->customer_address,
                'sale_date'        => $request->sale_date,
                'subtotal'         => $subtotal,
                'tax'              => $tax,
                'total'            => $total,
                'payment_type'     => $request->payment_type,
                'paid_amount'      => $paidAmount,
                'balance'          => $balance,
                'status'           => 'completed',
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
                $installmentMonths = $request->installment_months ?? 12;
                $installmentAmount = $balance / $installmentMonths;

                for ($i = 1; $i <= $installmentMonths; $i++) {
                    $dueDate = Carbon::parse($request->sale_date)->addMonths($i);

                    InstallmentPayment::create([
                        'sale_id'            => $sale->id,
                        'installment_number' => $i,
                        'amount'             => round($installmentAmount, 2),
                        'amount_paid'        => 0,
                        'due_date'           => $dueDate,
                        'status'             => 'unpaid',
                    ]);
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
                        $product->increment('stock_quantity', $item->quantity);
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