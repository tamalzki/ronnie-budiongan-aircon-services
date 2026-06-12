<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPayment;
use App\Models\Sale;
use App\Support\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InstallmentPaymentController extends Controller
{
    /**
     * Show list of customers with installment sales
     */
    public function index()
    {
        $this->authorize('viewAny', Sale::class);

        $customersData = Sale::where('payment_type', 'installment')
            ->select('customer_name', 'customer_contact', 'customer_address')
            ->selectRaw('MIN(id) as first_sale_id')
            ->selectRaw('SUM(total) as total_amount')
            ->selectRaw('SUM(paid_amount) as total_paid')
            ->selectRaw('SUM(balance) as total_balance')
            ->selectRaw('COUNT(*) as sales_count')
            ->groupBy('customer_name', 'customer_contact', 'customer_address')
            ->orderBy('customer_name')
            ->get();

        // Payments due this calendar month (unpaid / partial)
        $dueThisMonth = InstallmentPayment::whereYear('due_date', now()->year)
            ->whereMonth('due_date', now()->month)
            ->whereIn('status', ['unpaid', 'partial'])
            ->with('sale')
            ->orderBy('due_date')
            ->get();

        // Overdue (past due date, not yet fully paid)
        $overdueCount = InstallmentPayment::where('due_date', '<', now()->startOfMonth())
            ->whereIn('status', ['unpaid', 'partial'])
            ->count();

        $dueThisMonthTotal = $dueThisMonth->sum(fn ($p) => $p->amount - $p->amount_paid);

        return view('installments.index', compact(
            'customersData',
            'dueThisMonth',
            'overdueCount',
            'dueThisMonthTotal'
        ));
    }

    public function update(Request $request, InstallmentPayment $installment)
    {
        $this->authorize('update', $installment);

        $request->validate([
            'amount_paid'      => ['required', 'numeric', 'min:0.01', 'max:' . $installment->amount],
            'paid_date'        => ['required', 'date'],
            'payment_method'   => ['required', Rule::in(PaymentMethod::values())],
            'cheque_bank'      => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $request->payment_method === PaymentMethod::CHEQUE),
            ],
            'reference_number' => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($request, $installment) {
                $installment->load('sale');
                $sale = Sale::query()->whereKey($installment->sale_id)->lockForUpdate()->firstOrFail();

                $oldPaid = (float) $installment->amount_paid;
                $newPaid = (float) $request->amount_paid;
                $delta = round($newPaid - $oldPaid, 2);

                $installment->update([
                    'amount_paid'      => $newPaid,
                    'paid_date'        => $request->paid_date,
                    'payment_method'   => $request->payment_method,
                    'cheque_bank'      => $request->payment_method === PaymentMethod::CHEQUE ? $request->cheque_bank : null,
                    'reference_number' => $request->reference_number,
                    'notes'            => $request->notes,
                    'status'           => $newPaid >= (float) $installment->amount ? 'paid' : 'partial',
                ]);

                if (abs($delta) >= 0.01) {
                    $sale->increment('paid_amount', $delta);
                    $sale->decrement('balance', $delta);

                    $sale->refresh();

                    if ((float) $sale->balance < 0) {
                        throw new \RuntimeException('This change would make the sale balance negative. Check amounts.');
                    }

                    if ((float) $sale->balance <= 0) {
                        $sale->update(['status' => 'completed']);
                    }
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment updated successfully.');
    }

    /**
     * Edit a sale's installment plan: change total months and/or the monthly amount.
     * Paid / partially-paid installments are kept; the unpaid tail is regenerated.
     */
    public function updateSchedule(Request $request, Sale $sale)
    {
        $this->authorize('update', $sale);

        if ($sale->payment_type !== 'installment') {
            return back()->with('error', 'This sale is not an installment sale.');
        }

        $request->validate([
            'installment_months' => ['required', 'integer', 'min:1', 'max:60'],
            'monthly_amount'     => ['nullable', 'numeric', 'min:0.01'],
        ]);

        try {
            DB::transaction(function () use ($request, $sale) {
                $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

                $months = (int) $request->installment_months;

                // Keep anything with money already applied; regenerate the rest
                $kept = InstallmentPayment::where('sale_id', $sale->id)
                    ->where('amount_paid', '>', 0)
                    ->orderBy('due_date')
                    ->get();

                // Partial lines keep their own unpaid remainder, so schedule only what
                // isn't already covered by the kept installments' amounts.
                $remainingBalance = max(0, round((float) $sale->total - (float) $kept->sum('amount'), 2));

                if ($months < $kept->count()) {
                    throw new \InvalidArgumentException(
                        $kept->count() . ' installment(s) already have payments, so the plan cannot be shorter than ' . $kept->count() . ' month(s).'
                    );
                }

                $slots = $months - $kept->count();
                if ($slots < 1 && $remainingBalance > 0.009) {
                    throw new \InvalidArgumentException(
                        'There is still a balance of ₱' . number_format($remainingBalance, 2) . ' but no months left to schedule it. Increase the number of months.'
                    );
                }

                InstallmentPayment::where('sale_id', $sale->id)
                    ->where('amount_paid', '<=', 0)
                    ->delete();

                // Re-number the kept installments sequentially
                $num = 1;
                foreach ($kept as $ip) {
                    if ((int) $ip->installment_number !== $num) {
                        $ip->update(['installment_number' => $num]);
                    }
                    $num++;
                }

                $monthly = 0;
                if ($slots > 0 && $remainingBalance > 0.009) {
                    $monthly = $request->filled('monthly_amount')
                        ? round((float) $request->monthly_amount, 2)
                        : round($remainingBalance / $slots, 2);

                    if ($monthly * $slots < $remainingBalance - 0.009) {
                        throw new \InvalidArgumentException(
                            '₱' . number_format($monthly, 2) . ' × ' . $slots . ' month(s) = ₱' . number_format($monthly * $slots, 2)
                            . ' which does not cover the remaining balance of ₱' . number_format($remainingBalance, 2) . '.'
                        );
                    }

                    // Due dates continue monthly after the last kept installment (or the sale date)
                    $baseDue = $kept->isNotEmpty()
                        ? \Carbon\Carbon::parse($kept->last()->due_date)
                        : \Carbon\Carbon::parse($sale->sale_date);

                    $scheduled = 0;
                    for ($i = 0; $i < $slots; $i++) {
                        $isLast = $i === $slots - 1;
                        $amount = $isLast ? round($remainingBalance - $scheduled, 2) : min($monthly, round($remainingBalance - $scheduled, 2));
                        if ($amount <= 0) {
                            break;
                        }
                        $scheduled = round($scheduled + $amount, 2);

                        InstallmentPayment::create([
                            'sale_id'            => $sale->id,
                            'installment_number' => $num++,
                            'amount'             => $amount,
                            'amount_paid'        => 0,
                            'due_date'           => $baseDue->copy()->addMonths($i + 1),
                            'status'             => 'unpaid',
                        ]);
                    }
                }

                $sale->update([
                    'installment_months' => $months,
                    'installment_amount' => $monthly,
                ]);
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Installment schedule update failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);

            return back()->with('error', 'Error updating schedule: ' . $e->getMessage());
        }

        return back()->with('success', 'Installment plan updated.');
    }

    /**
     * Show customer's installment details (using first sale ID)
     */
    public function show(Sale $sale)
    {
        $this->authorize('view', $sale);

        if ($sale->payment_type !== 'installment') {
            return redirect()->route('installments.index')
                ->with('error', 'This sale is not an installment sale.');
        }

        // Match installments index grouping (name + contact + address); NULL-safe for address
        $sales = Sale::where('payment_type', 'installment')
            ->where('customer_name', $sale->customer_name)
            ->where('customer_contact', $sale->customer_contact)
            ->when(
                $sale->customer_address === null || $sale->customer_address === '',
                fn($q) => $q->where(function ($q2) {
                    $q2->whereNull('customer_address')->orWhere('customer_address', '');
                }),
                fn($q) => $q->where('customer_address', $sale->customer_address)
            )
            ->with(['installmentPayments', 'items.product', 'items.serials'])
            ->orderBy('sale_date', 'desc')
            ->get();

        $customer = [
            'name'    => $sale->customer_name,
            'contact' => $sale->customer_contact,
            'address' => $sale->customer_address,
        ];

        $totalAmount  = $sales->sum('total');
        $totalPaid    = $sales->sum('paid_amount');
        $totalBalance = $sales->sum('balance');

        $installments = InstallmentPayment::whereIn('sale_id', $sales->pluck('id'))
            ->with('sale')
            ->orderBy('due_date', 'asc')
            ->get();

        return view('installments.show', compact('customer', 'sales', 'installments', 'totalAmount', 'totalPaid', 'totalBalance'));
    }

    /**
     * Record payment for an installment — FLEXIBLE amount
     */
    public function recordPayment(Request $request, InstallmentPayment $installment)
    {
        $this->authorize('update', $installment);

        $validated = $request->validate([
            'amount_paid'      => ['required', 'numeric', 'min:0.01'],
            'paid_date'        => ['required', 'date'],
            'payment_method'   => ['required', Rule::in(PaymentMethod::values())],
            'cheque_bank'      => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $request->payment_method === PaymentMethod::CHEQUE),
            ],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ], [
            'amount_paid.min' => 'Payment amount must be at least ₱0.01',
        ]);

        $chequeBank = $validated['payment_method'] === PaymentMethod::CHEQUE ? ($validated['cheque_bank'] ?? null) : null;
        $referenceNumber = $validated['reference_number'] ?? null;
        $notes = $validated['notes'] ?? null;

        DB::beginTransaction();

        try {
            $sale = $installment->sale()->lockForUpdate()->firstOrFail();
            $amountToPay = (float) $validated['amount_paid'];
            $saleBalance = round((float) $sale->balance, 2);

            if ($amountToPay > $saleBalance + 0.009) {
                throw new \InvalidArgumentException(
                    'Payment amount cannot exceed the sale remaining balance (₱' . number_format($saleBalance, 2) . ').'
                );
            }

            $remainingInstallmentBalance = (float) $installment->amount - (float) $installment->amount_paid;

            if ($amountToPay <= $remainingInstallmentBalance) {
                $installment->increment('amount_paid', $amountToPay);
                $installment->refresh();
                $installment->update([
                    'paid_date'        => $validated['paid_date'],
                    'payment_method'   => $validated['payment_method'],
                    'cheque_bank'      => $chequeBank,
                    'reference_number' => $referenceNumber,
                    'notes'            => $notes,
                    'status'           => (float) $installment->amount_paid >= (float) $installment->amount ? 'paid' : 'partial',
                ]);

                $sale->increment('paid_amount', $amountToPay);
                $sale->decrement('balance', $amountToPay);
            } else {
                $overflow = $amountToPay;

                $installment->update([
                    'amount_paid'      => $installment->amount,
                    'paid_date'        => $validated['paid_date'],
                    'payment_method'   => $validated['payment_method'],
                    'cheque_bank'      => $chequeBank,
                    'reference_number' => $referenceNumber,
                    'notes'            => $notes,
                    'status'           => 'paid',
                ]);
                $overflow -= $remainingInstallmentBalance;

                $nextInstallments = InstallmentPayment::where('sale_id', $sale->id)
                    ->where('id', '!=', $installment->id)
                    ->where('status', '!=', 'paid')
                    ->orderBy('due_date')
                    ->get();

                foreach ($nextInstallments as $next) {
                    if ($overflow <= 0) {
                        break;
                    }

                    $nextRemaining = (float) $next->amount - (float) $next->amount_paid;
                    $applyAmount = min($overflow, $nextRemaining);

                    $next->increment('amount_paid', $applyAmount);
                    $next->refresh();
                    $next->update([
                        'paid_date'      => $validated['paid_date'],
                        'payment_method' => $validated['payment_method'],
                        'notes'          => 'Overflow from payment #' . $installment->installment_number,
                        'status'         => (float) $next->amount_paid >= (float) $next->amount ? 'paid' : 'partial',
                    ]);

                    $overflow -= $applyAmount;
                }

                if ($overflow > 0.009) {
                    throw new \RuntimeException(
                        'This amount cannot be fully applied to the installment schedule. Remaining sale balance is ₱'
                        . number_format($saleBalance, 2) . '; check installment lines or contact support.'
                    );
                }

                $sale->increment('paid_amount', $amountToPay);
                $sale->decrement('balance', $amountToPay);
            }

            $sale->refresh();

            if ((float) $sale->balance < -0.009) {
                throw new \RuntimeException('This payment would make the sale balance invalid. No changes were saved.');
            }

            if ((float) $sale->balance <= 0) {
                $sale->update(['status' => 'completed']);
            }

            DB::commit();

            return back()->with('success', 'Payment of ₱' . number_format($amountToPay, 2) . ' recorded successfully.');
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Installment payment failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);

            return back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }
}
