<div class="report-section app-tab-panel">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Total Purchased</div>
                <div class="fw-bold fs-5">₱{{ number_format($totalPurchases, 2) }}</div>
                <div class="text-muted" style="font-size:0.72rem;">{{ $purchaseOrdersCount }} orders</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Paid to Suppliers</div>
                <div class="fw-bold fs-5 text-success">₱{{ number_format($totalPurchasesPaid, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Outstanding</div>
                <div class="fw-bold fs-5 text-danger">₱{{ number_format($totalPurchasesPending, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Sales vs Purchases</div>
                <div class="fw-bold fs-5 {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $profitMargin >= 0 ? '+' : '' }}₱{{ number_format($profitMargin, 2) }}
                </div>
            </div>
        </div>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-cart-plus text-primary"></i> Purchase Orders (Period)</h6>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 px-3 py-2">Supplier</th>
                    <th class="border-0 px-3 py-2" style="white-space:nowrap">Order Date</th>
                    <th class="border-0 px-3 py-2">Total</th>
                    <th class="border-0 px-3 py-2">Paid</th>
                    <th class="border-0 px-3 py-2">Balance</th>
                    <th class="border-0 px-3 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrdersSummary as $po)
                <tr>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        <div class="fw-semibold">{{ $po->supplier->name }}</div>
                        <small class="text-muted">
                            <a href="{{ route('purchase-orders.show', $po) }}" class="text-muted text-decoration-none">
                                {{ $po->po_number }}
                            </a>
                        </small>
                    </td>
                    <td class="px-3 py-2" style="white-space:nowrap">{{ $po->order_date->format('M d, Y') }}</td>
                    <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">₱{{ number_format($po->total, 2) }}</td>
                    <td class="px-3 py-2 text-success" style="white-space:nowrap">₱{{ number_format($po->amount_paid, 2) }}</td>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        @if($po->balance > 0)
                            <span class="text-danger fw-semibold">₱{{ number_format($po->balance, 2) }}</span>
                        @else
                            <span class="text-success">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        @if($po->payment_status == 'paid')
                            <span class="badge bg-success">Paid</span>
                        @elseif($po->payment_status == 'partial')
                            <span class="badge bg-warning text-dark">Partial</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No purchase orders in this period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
