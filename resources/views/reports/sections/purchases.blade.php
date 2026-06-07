<div class="report-section">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Total Purchased</div>
                <div class="fw-bold fs-5">₱{{ number_format($totalPurchases, 2) }}</div>
                <div class="text-muted" style="font-size:0.72rem;">{{ $purchaseOrdersCount }} orders</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Paid to Suppliers</div>
                <div class="fw-bold fs-5 text-success">₱{{ number_format($totalPurchasesPaid, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Outstanding</div>
                <div class="fw-bold fs-5 text-danger">₱{{ number_format($totalPurchasesPending, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Sales vs Purchases</div>
                <div class="fw-bold fs-5 {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $profitMargin >= 0 ? '+' : '' }}₱{{ number_format($profitMargin, 2) }}
                </div>
            </div>
        </div>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-cart-plus text-primary me-1"></i>Purchase Orders (Period)</h6>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th style="white-space:nowrap">Order Date</th>
                        <th style="white-space:nowrap">Doc No. (DR)</th>
                        <th>Supplier</th>
                        <th style="white-space:nowrap">PO / SO Ref</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrdersSummary as $po)
                    <tr>
                        <td style="white-space:nowrap">{{ $po->order_date->format('M d, Y') }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('purchase-orders.show', $po) }}"
                               class="fw-semibold text-primary text-decoration-none"
                               style="font-size:0.8rem;font-family:monospace;">
                                {{ $po->delivery_number ?? '—' }}
                            </a>
                        </td>
                        <td style="white-space:nowrap">{{ $po->supplier->name ?? '—' }}</td>
                        <td style="white-space:nowrap">
                            @if($po->po_number)
                                <div class="text-muted" style="font-size:0.72rem;">PO: {{ $po->po_number }}</div>
                            @endif
                            @if($po->so_number)
                                <div class="text-muted" style="font-size:0.72rem;">SO: {{ $po->so_number }}</div>
                            @endif
                            @if(!$po->po_number && !$po->so_number)
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold" style="white-space:nowrap">₱{{ number_format($po->total, 2) }}</td>
                        <td class="text-end text-success" style="white-space:nowrap">₱{{ number_format($po->amount_paid, 2) }}</td>
                        <td class="text-end" style="white-space:nowrap">
                            @if($po->balance > 0)
                                <span class="text-danger fw-semibold">₱{{ number_format($po->balance, 2) }}</span>
                            @else
                                <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                            @endif
                        </td>
                        <td style="white-space:nowrap">
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
                    <tr><td colspan="8" class="text-center py-4 text-muted">No purchase orders in this period</td></tr>
                    @endforelse
                </tbody>
                @if($purchaseOrdersSummary->count() > 0)
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end text-muted" style="font-size:0.78rem;">Totals</td>
                        <td class="text-end text-primary">₱{{ number_format($totalPurchases, 2) }}</td>
                        <td class="text-end text-success">₱{{ number_format($totalPurchasesPaid, 2) }}</td>
                        <td class="text-end text-danger">₱{{ number_format($totalPurchasesPending, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
