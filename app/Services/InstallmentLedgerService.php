<?php

namespace App\Services;

use App\Models\InstallmentPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InstallmentLedgerService
{
    /**
     * Build ledger rows, summary, payment history, and aging for a customer's installment account.
     *
     * @param  Collection<int, \App\Models\Sale>  $sales
     * @param  Collection<int, InstallmentPayment>  $installments
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     paymentHistory: list<array<string, mixed>>,
     *     aging: array<string, float>
     * }
     */
    public function build(Collection $sales, Collection $installments): array
    {
        $totalContract = round((float) $sales->sum('total'), 2);
        $totalPaid     = round((float) $sales->sum('paid_amount'), 2);
        $totalBalance  = round((float) $sales->sum('balance'), 2);
        $totalDiscount = round((float) $sales->sum('discount'), 2);

        $downPayment = round(
            (float) $installments
                ->filter(fn (InstallmentPayment $p) => $p->notes === 'Downpayment')
                ->sum('amount_paid'),
            2
        );

        $netFinanced = round(max(0, $totalContract - $downPayment), 2);

        $monthlyAmounts = $sales
            ->pluck('installment_amount')
            ->filter(fn ($v) => $v !== null && (float) $v > 0)
            ->map(fn ($v) => round((float) $v, 2))
            ->unique()
            ->values();

        $installmentsPaid = $installments->filter(
            fn (InstallmentPayment $p) => $p->status === 'paid' && $p->notes !== 'Downpayment'
        )->count();

        $installmentsRemaining = $installments->filter(
            fn (InstallmentPayment $p) => in_array($p->status, ['unpaid', 'partial'], true)
        )->count();

        $nextDue = $installments
            ->filter(fn (InstallmentPayment $p) => in_array($p->status, ['unpaid', 'partial'], true))
            ->sortBy('due_date')
            ->first();

        $runningPaid = 0.0;
        $rows = [];

        foreach ($installments as $installment) {
            $beginningBalance = round($totalContract - $runningPaid, 2);
            $monthlyDue       = round((float) $installment->amount, 2);
            $amountPaid       = round((float) $installment->amount_paid, 2);
            $rebate           = 0.0;
            $totalCredit      = round($runningPaid + $amountPaid + $rebate, 2);
            $runningPaid      = round($runningPaid + $amountPaid, 2);
            $remainingBalance = round($totalContract - $runningPaid, 2);

            $cumulativeDue = round(
                (float) $installments
                    ->filter(fn (InstallmentPayment $p) => $p->due_date <= $installment->due_date)
                    ->sum('amount'),
                2
            );
            $arrearsAdvance = round($cumulativeDue - $runningPaid, 2);

            $isDownpayment = $installment->notes === 'Downpayment';
            $billLabel = $isDownpayment ? 'Full DP' : (string) $installment->installment_number;

            $rows[] = [
                'installment'        => $installment,
                'installment_no'     => $installment->installment_number,
                'bill_no'            => $billLabel,
                'label'              => $isDownpayment ? 'Full DP' : null,
                'invoice_number'     => $installment->sale?->invoice_number,
                'due_date'           => $installment->due_date,
                'beginning_balance'  => $beginningBalance,
                'monthly_due'        => $monthlyDue,
                'total_amount_due'   => $beginningBalance,
                'amount_paid'        => $amountPaid,
                'rebate'             => $rebate,
                'total_credit'       => $totalCredit,
                'penalty'            => 0.0,
                'running_total_paid' => $runningPaid,
                'remaining_balance'  => $remainingBalance,
                'paid_date'          => $installment->paid_date,
                'reference_number'   => $installment->reference_number,
                'payment_method'     => $installment->payment_method,
                'arrears_advance'    => $arrearsAdvance,
                'status'             => $this->resolveStatus($installment),
            ];
        }

        $paymentHistory = $installments
            ->filter(fn (InstallmentPayment $p) => (float) $p->amount_paid > 0)
            ->sortBy(fn (InstallmentPayment $p) => [
                $p->paid_date?->format('Y-m-d') ?? '9999-99-99',
                $p->due_date->format('Y-m-d'),
                $p->installment_number,
            ])
            ->values()
            ->map(function (InstallmentPayment $payment) {
                $label = $payment->notes === 'Downpayment'
                    ? 'Down Payment'
                    : 'Installment #' . $payment->installment_number;

                return [
                    'payment'          => $payment,
                    'paid_date'        => $payment->paid_date,
                    'amount'           => round((float) $payment->amount_paid, 2),
                    'method'           => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'cheque_bank'      => $payment->cheque_bank,
                    'invoice_number'   => $payment->sale?->invoice_number,
                    'label'            => $label,
                    'recorded_by'      => $payment->sale?->user?->name,
                    'notes'            => $payment->notes,
                ];
            })
            ->all();

        $aging = $this->buildAging($installments);
        $advancePayment = round(max(0, -1 * ($aging['advance'] ?? 0)), 2);

        $products = $sales->flatMap(function ($sale) {
            return $sale->items->map(fn ($item) => [
                'name'     => $item->item_name,
                'quantity' => $item->quantity,
                'invoice'  => $sale->invoice_number,
                'model'    => $item->product?->model,
                'serials'  => $item->serials->pluck('serial_number')->all(),
            ]);
        })->values();

        $primaryItem = $sales->flatMap->items->first();
        $otherItems  = $sales->flatMap->items->slice(1);

        $termMonths = $sales->max('installment_months') ?? $installments->count();
        $primarySale = $sales->sortBy('sale_date')->first();
        $earliestSaleDate = $sales->min('sale_date');

        $header = [
            'account_ref'     => strtoupper(str_replace(['INV-', '-'], '', $primarySale?->invoice_number ?? 'ACCT')),
            'unit_acquired'   => $primaryItem?->item_name,
            'model'           => $primaryItem?->product?->model,
            'serial_numbers'  => $primaryItem
                ? $primaryItem->serials->pluck('serial_number')->implode(' / ')
                : '',
            'accessories'     => $otherItems->map(fn ($i) => $i->quantity . ' ' . $i->item_name)->implode(' · '),
            'date_delivered'  => $earliestSaleDate,
            'lcp_srp'         => round((float) $sales->sum('subtotal'), 2) ?: $totalContract,
            'invoice_no'      => $sales->pluck('invoice_number')->map(fn ($n) => preg_replace('/^INV-/', '', $n))->implode(', '),
            'term_months'     => $termMonths,
            'rebate'          => $totalDiscount,
        ];

        $summary = [
            'original_contract_amount' => $totalContract,
            'down_payment'             => $downPayment,
            'net_financed_amount'      => $netFinanced,
            'total_paid'               => $totalPaid,
            'total_penalties'          => 0.0,
            'total_discounts'          => $totalDiscount,
            'current_balance'          => $totalBalance,
            'installments_paid'        => $installmentsPaid,
            'installments_remaining'   => $installmentsRemaining,
            'next_due_date'            => $nextDue?->due_date,
            'monthly_amortization'     => $monthlyAmounts->count() === 1
                ? $monthlyAmounts->first()
                : ($monthlyAmounts->isNotEmpty() ? $monthlyAmounts->min() . ' – ' . $monthlyAmounts->max() : null),
            'total_terms'              => $termMonths,
            'advance_payment'          => $advancePayment,
            'products'                 => $products,
            'invoice_numbers'          => $sales->pluck('invoice_number')->implode(', '),
            'earliest_sale_date'       => $earliestSaleDate,
            'latest_sale_date'         => $sales->max('sale_date'),
        ];

        return [
            'rows'           => $rows,
            'summary'        => $summary,
            'header'         => $header,
            'paymentHistory' => $paymentHistory,
            'aging'          => $aging,
        ];
    }

    /**
     * @param  Collection<int, InstallmentPayment>  $installments
     * @return array<string, float>
     */
    private function buildAging(Collection $installments): array
    {
        $today = Carbon::today();

        $buckets = [
            'current'    => 0.0,
            'days_1_30'  => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_90_up' => 0.0,
        ];

        foreach ($installments as $installment) {
            if ($installment->status === 'paid') {
                continue;
            }

            $remaining = round((float) $installment->amount - (float) $installment->amount_paid, 2);
            if ($remaining <= 0) {
                continue;
            }

            if ($installment->due_date->gte($today)) {
                $buckets['current'] = round($buckets['current'] + $remaining, 2);
                continue;
            }

            $daysOverdue = $installment->due_date->diffInDays($today);

            if ($daysOverdue <= 30) {
                $buckets['days_1_30'] = round($buckets['days_1_30'] + $remaining, 2);
            } elseif ($daysOverdue <= 60) {
                $buckets['days_31_60'] = round($buckets['days_31_60'] + $remaining, 2);
            } elseif ($daysOverdue <= 90) {
                $buckets['days_61_90'] = round($buckets['days_61_90'] + $remaining, 2);
            } else {
                $buckets['days_90_up'] = round($buckets['days_90_up'] + $remaining, 2);
            }
        }

        $totalOverdue = round(
            $buckets['days_1_30'] + $buckets['days_31_60'] + $buckets['days_61_90'] + $buckets['days_90_up'],
            2
        );

        $cumulativeDueToday = round(
            (float) $installments
                ->filter(fn (InstallmentPayment $p) => $p->due_date->lte($today))
                ->sum('amount'),
            2
        );
        $cumulativePaid = round((float) $installments->sum('amount_paid'), 2);
        $advance = round(min(0, $cumulativeDueToday - $cumulativePaid), 2);

        return array_merge($buckets, [
            'total_overdue' => $totalOverdue,
            'advance'       => $advance,
        ]);
    }

    private function resolveStatus(InstallmentPayment $installment): string
    {
        if ($installment->status === 'paid') {
            return 'paid';
        }

        if ($installment->status === 'partial') {
            return $installment->due_date->lt(Carbon::today()) ? 'overdue' : 'partial';
        }

        if ($installment->due_date->lt(Carbon::today())) {
            return 'overdue';
        }

        return 'pending';
    }
}
