<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function index()
    {
        // Get all payments with relationships
        $payments = SupplierPayment::with(['purchaseOrder.supplier', 'user'])
            ->orderBy('payment_date', 'desc')
            ->get();

        // Get unpaid/partial POs (45-day terms only)
        $unpaidPOs = PurchaseOrder::with('supplier')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('payment_type', '45days')
            ->where('balance', '>', 0)
            ->orderBy('payment_due_date')
            ->get();

        // Calculate totals
        $totalPaid    = SupplierPayment::sum('amount');
        $totalPending = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])->sum('balance');

        return view('supplier-payments.index', compact('payments', 'unpaidPOs', 'totalPaid', 'totalPending'));
    }
}