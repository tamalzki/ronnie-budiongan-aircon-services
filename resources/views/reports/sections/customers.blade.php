<div class="report-section app-tab-panel">
    <h6 class="fw-semibold mb-2"><i class="bi bi-trophy text-warning"></i> Top 10 Customers by Spending</h6>
    <div class="table-responsive mb-4">
        <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 px-3 py-2">#</th>
                    <th class="border-0 px-3 py-2">Customer</th>
                    <th class="border-0 px-3 py-2 text-center">Orders</th>
                    <th class="border-0 px-3 py-2">Total Spent</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topCustomers as $i => $customer)
                <tr>
                    <td class="px-3 py-2 text-muted">
                        @if($i == 0) 🥇 @elseif($i == 1) 🥈 @elseif($i == 2) 🥉 @else {{ $i + 1 }} @endif
                    </td>
                    <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $customer->customer_name }}</td>
                    <td class="px-3 py-2 text-center">
                        <span class="badge bg-info text-dark">{{ $customer->purchase_count }}</span>
                    </td>
                    <td class="px-3 py-2 fw-semibold text-success" style="white-space:nowrap">
                        ₱{{ number_format($customer->total_spent, 2) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">No customer data in this period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-person-exclamation text-danger"></i> Customers with Pending Installments</h6>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 px-3 py-2">Customer</th>
                    <th class="border-0 px-3 py-2">Invoice</th>
                    <th class="border-0 px-3 py-2">Total</th>
                    <th class="border-0 px-3 py-2">Paid</th>
                    <th class="border-0 px-3 py-2">Balance</th>
                    <th class="border-0 px-3 py-2">Pending</th>
                </tr>
            </thead>
            <tbody>
                @php $salesWithInstallments = $pendingInstallments->groupBy('sale_id'); @endphp
                @forelse($salesWithInstallments as $saleId => $payments)
                @php $sale = $payments->first()->sale; @endphp
                <tr>
                    <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $sale->customer_name }}</td>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        <a href="{{ route('sales.show', $sale) }}" class="text-primary fw-semibold text-decoration-none">
                            {{ $sale->invoice_number }}
                        </a>
                    </td>
                    <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">₱{{ number_format($sale->total, 2) }}</td>
                    <td class="px-3 py-2 text-success" style="white-space:nowrap">₱{{ number_format($sale->paid_amount, 2) }}</td>
                    <td class="px-3 py-2 text-danger fw-semibold" style="white-space:nowrap">₱{{ number_format($sale->balance, 2) }}</td>
                    <td class="px-3 py-2">
                        <span class="badge bg-warning text-dark">{{ $payments->count() }} payment(s)</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                        All installments are up to date
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
