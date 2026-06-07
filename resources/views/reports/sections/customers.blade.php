<div class="report-section">
    <h6 class="fw-semibold mb-2"><i class="bi bi-trophy text-warning me-1"></i>Top 10 Customers by Spending</h6>
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th class="text-center">Orders</th>
                        <th class="text-end">Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topCustomers as $i => $customer)
                    <tr>
                        <td class="text-muted">
                            @if($i == 0) 🥇 @elseif($i == 1) 🥈 @elseif($i == 2) 🥉 @else {{ $i + 1 }} @endif
                        </td>
                        <td class="fw-semibold" style="white-space:nowrap">{{ $customer->customer_name }}</td>
                        <td class="text-center">
                            <span class="badge bg-info text-dark">{{ $customer->purchase_count }}</span>
                        </td>
                        <td class="text-end fw-semibold text-success" style="white-space:nowrap">
                            ₱{{ number_format($customer->total_spent, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-4 text-muted">No customer data in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-person-exclamation text-danger me-1"></i>Customers with Pending Installments</h6>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Pending</th>
                    </tr>
                </thead>
                <tbody>
                    @php $salesWithInstallments = $pendingInstallments->groupBy('sale_id'); @endphp
                    @forelse($salesWithInstallments as $saleId => $payments)
                    @php $sale = $payments->first()->sale; @endphp
                    <tr>
                        <td class="fw-semibold" style="white-space:nowrap">{{ $sale->customer_name }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('sales.show', $sale) }}" class="text-primary fw-semibold text-decoration-none"
                               style="font-family:monospace;font-size:0.78rem;">
                                {{ $sale->invoice_number }}
                            </a>
                        </td>
                        <td class="text-end fw-semibold" style="white-space:nowrap">₱{{ number_format($sale->total, 2) }}</td>
                        <td class="text-end text-success" style="white-space:nowrap">₱{{ number_format($sale->paid_amount, 2) }}</td>
                        <td class="text-end text-danger fw-semibold" style="white-space:nowrap">₱{{ number_format($sale->balance, 2) }}</td>
                        <td>
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
</div>
