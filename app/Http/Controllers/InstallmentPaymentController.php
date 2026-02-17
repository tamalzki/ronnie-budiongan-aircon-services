<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPayment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstallmentPaymentController extends Controller
{
    /**
     * Show list of customers with installment sales
     */
    public function index()
    {
        // Get all installment sales grouped by customer
        $customersData = Sale::where('payment_type', 'installment')
            ->with(['installmentPayments'])
            ->select('customer_name', 'customer_contact', 'customer_address')
            ->selectRaw('MIN(id) as first_sale_id')
            ->selectRaw('SUM(total) as total_amount')
            ->selectRaw('SUM(paid_amount) as total_paid')
            ->selectRaw('SUM(balance) as total_balance')
            ->selectRaw('COUNT(*) as sales_count')
            ->groupBy('customer_name', 'customer_contact', 'customer_address')
            ->orderBy('customer_name')
            ->get();

        return view('installments.index', compact('customersData'));
    }

    public function update(Request $request, InstallmentPayment $installment)
{
    $request->validate([
        'amount_paid'      => 'required|numeric|min:0.01|max:' . $installment->amount,
        'paid_date'        => 'required|date',
        'payment_method'   => 'required|in:cash,bank_transfer,check',
        'reference_number' => 'nullable|string',
        'notes'            => 'nullable|string',
    ]);

    $installment->update([
        'amount_paid'      => $request->amount_paid,
        'paid_date'        => $request->paid_date,
        'payment_method'   => $request->payment_method,
        'reference_number' => $request->reference_number,
        'notes'            => $request->notes,
        'status'           => $request->amount_paid >= $installment->amount ? 'paid' : 'partial',
    ]);

    return back()->with('success', 'Payment updated successfully.');
}

    /**
     * Show customer's installment details (using first sale ID)
     */
    public function show(Sale $sale)
    {
        if ($sale->payment_type !== 'installment') {
            return redirect()->route('installments.index')
                ->with('error', 'This sale is not an installment sale.');
        }

        $customerName = $sale->customer_name;

        // Get all sales for this customer
        $sales = Sale::where('customer_name', $customerName)
            ->where('payment_type', 'installment')
            ->with(['installmentPayments'])
            ->orderBy('sale_date', 'desc')
            ->get();

        $customer = [
            'name' => $customerName,
            'contact' => $sale->customer_contact,
            'address' => $sale->customer_address,
        ];

        // Calculate totals
        $totalAmount = $sales->sum('total');
        $totalPaid = $sales->sum('paid_amount');
        $totalBalance = $sales->sum('balance');

        // Get all installment payments for this customer
        $installments = InstallmentPayment::whereIn('sale_id', $sales->pluck('id'))
            ->with('sale')
            ->orderBy('due_date', 'asc')
            ->get();

        return view('installments.show', compact('customer', 'sales', 'installments', 'totalAmount', 'totalPaid', 'totalBalance'));
    }

    /**
     * Record payment for an installment
     */
    public function recordPayment(Request $request, InstallmentPayment $installment)
{
    // Log the request for debugging
    \Log::info('Installment Payment Request:', $request->all());
    
    $validated = $request->validate([
        'amount_paid' => 'required|numeric|min:0.01|max:' . ($installment->amount - $installment->amount_paid),
        'paid_date' => 'required|date',
        'payment_method' => 'required|in:cash,bank_transfer,check',
        'reference_number' => 'nullable|string|max:255',
        'notes' => 'nullable|string',
    ], [
        'amount_paid.max' => 'Payment amount cannot exceed the remaining balance of ₱' . number_format($installment->amount - $installment->amount_paid, 2),
        'amount_paid.min' => 'Payment amount must be at least ₱0.01',
    ]);

    DB::beginTransaction();

    try {
        $sale = $installment->sale;
        $amountToPay = $validated['amount_paid'];

        // Update installment
        $installment->increment('amount_paid', $amountToPay);
        $installment->update([
            'paid_date' => $validated['paid_date'],
            'status' => $installment->amount_paid >= $installment->amount ? 'paid' : 'partial',
        ]);

        // Update sale
        $sale->increment('paid_amount', $amountToPay);
        $sale->decrement('balance', $amountToPay);

        // Update sale status if fully paid
        if ($sale->balance <= 0) {
            $sale->update(['status' => 'completed']);
        }

        DB::commit();

        return back()->with('success', 'Payment of ₱' . number_format($amountToPay, 2) . ' recorded successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Installment Payment Error:', ['error' => $e->getMessage()]);
        return back()->with('error', 'Error recording payment: ' . $e->getMessage());
    }
}
}