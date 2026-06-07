{{-- Sales Report Section --}}
<div class="report-section">

    {{-- Summary strip --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Total Sales</div>
                <div class="fw-bold fs-5 text-primary">₱{{ number_format($totalSales, 2) }}</div>
                <div class="text-muted" style="font-size:0.72rem;">{{ $salesCount }} transactions</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Cash Sales</div>
                <div class="fw-bold fs-5 text-success">₱{{ number_format($totalCashSales, 2) }}</div>
                <div class="text-muted" style="font-size:0.72rem;">
                    {{ $totalSales > 0 ? number_format(($totalCashSales/$totalSales)*100, 1) : 0 }}% of total
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Installment Sales</div>
                <div class="fw-bold fs-5 text-warning">₱{{ number_format($totalInstallmentSales, 2) }}</div>
                <div class="text-muted" style="font-size:0.72rem;">
                    {{ $totalSales > 0 ? number_format(($totalInstallmentSales/$totalSales)*100, 1) : 0 }}% of total
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Receivables</div>
                <div class="fw-bold fs-5 text-danger">₱{{ number_format($totalPending, 2) }}</div>
                <div class="text-muted" style="font-size:0.72rem;">Still to collect</div>
            </div>
        </div>
    </div>

    {{-- Payment method breakdown --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-0 py-2 px-3">
            <span class="fw-semibold small"><i class="bi bi-pie-chart text-primary me-1"></i>Payment Breakdown</span>
        </div>
        <div class="card-body py-2 px-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Cash</small>
                        <span class="fw-semibold text-success small">₱{{ number_format($totalCashSales, 2) }}</span>
                    </div>
                    <div class="progress mb-2" style="height:5px;">
                        <div class="progress-bar bg-success" style="width:{{ $totalSales > 0 ? ($totalCashSales/$totalSales)*100 : 0 }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Installment</small>
                        <span class="fw-semibold text-warning small">₱{{ number_format($totalInstallmentSales, 2) }}</span>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-warning" style="width:{{ $totalSales > 0 ? ($totalInstallmentSales/$totalSales)*100 : 0 }}%"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Collected</small>
                        <span class="fw-semibold text-success small">₱{{ number_format($totalCollected, 2) }}</span>
                    </div>
                    <div class="progress mb-2" style="height:5px;">
                        <div class="progress-bar bg-success" style="width:{{ $totalSales > 0 ? ($totalCollected/$totalSales)*100 : 0 }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Receivables</small>
                        <span class="fw-semibold text-danger small">₱{{ number_format($totalPending, 2) }}</span>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-danger" style="width:{{ $totalSales > 0 ? ($totalPending/$totalSales)*100 : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales transaction list --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-semibold mb-0"><i class="bi bi-receipt text-primary me-1"></i>Sales Transactions</h6>
        <span class="badge bg-secondary" style="font-size:0.7rem;">{{ $salesDetailList->count() }} records</span>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total</th>
                        <th>Payment</th>
                        <th class="text-end">Collected</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesDetailList as $sale)
                    @php
                        $isCash   = $sale->payment_type === 'cash';
                        $isPaid   = $sale->balance <= 0;
                    @endphp
                    <tr>
                        <td style="white-space:nowrap">
                            <a href="{{ route('sales.show', $sale) }}"
                               class="fw-semibold text-primary text-decoration-none"
                               style="font-family:monospace;font-size:0.78rem;">
                                {{ $sale->invoice_number }}
                            </a>
                        </td>
                        <td style="white-space:nowrap">
                            <div class="fw-semibold">{{ $sale->customer_name }}</div>
                            @if($sale->customer_contact)
                                <div class="text-muted" style="font-size:0.7rem;">{{ $sale->customer_contact }}</div>
                            @endif
                        </td>
                        <td style="white-space:nowrap">
                            {{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary" style="font-size:0.7rem;">
                                {{ $sale->items_count }}
                            </span>
                        </td>
                        <td class="text-end fw-semibold" style="white-space:nowrap">
                            ₱{{ number_format($sale->total, 2) }}
                        </td>
                        <td style="white-space:nowrap">
                            <span class="badge {{ $isCash ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size:0.65rem;">
                                {{ ucfirst($sale->payment_type) }}
                            </span>
                            <div class="text-muted" style="font-size:0.68rem;">{{ ucfirst(str_replace('_',' ',$sale->payment_method)) }}</div>
                        </td>
                        <td class="text-end text-success fw-semibold" style="white-space:nowrap">
                            ₱{{ number_format($sale->paid_amount, 2) }}
                        </td>
                        <td class="text-end" style="white-space:nowrap">
                            @if($sale->balance > 0)
                                <span class="text-danger fw-semibold">₱{{ number_format($sale->balance, 2) }}</span>
                            @else
                                <span class="text-success"><i class="bi bi-check-circle-fill"></i> Paid</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap">
                            <span class="badge bg-{{ $sale->status === 'completed' ? 'success' : ($sale->status === 'pending' ? 'warning text-dark' : 'danger') }}" style="font-size:0.65rem;">
                                {{ ucfirst($sale->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-2 d-block mb-2 opacity-40"></i>
                            No sales recorded in this period
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($salesDetailList->count() > 0)
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end text-muted" style="font-size:0.78rem;">Totals</td>
                        <td class="text-end text-primary">₱{{ number_format($totalSales, 2) }}</td>
                        <td></td>
                        <td class="text-end text-success">₱{{ number_format($totalCollected, 2) }}</td>
                        <td class="text-end text-danger">₱{{ number_format($totalPending, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
