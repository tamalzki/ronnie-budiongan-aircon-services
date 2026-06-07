<div class="report-section">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Total Due (Period)</div>
                <div class="fw-bold fs-5">₱{{ number_format($totalInstallmentAmount, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Collected</div>
                <div class="fw-bold fs-5 text-success">₱{{ number_format($paidInstallments, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Pending</div>
                <div class="fw-bold fs-5 text-warning">{{ $pendingInstallments->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10 text-center py-3">
                <div class="text-muted small mb-1">Overdue</div>
                <div class="fw-bold fs-5 text-danger">{{ $overdueInstallments->count() }}</div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-2">
        <h6 class="fw-semibold mb-0"><i class="bi bi-bell-fill text-warning me-1"></i>Due This Month</h6>
        <span class="badge bg-warning text-dark">{{ $dueThisMonth->count() }} customer(s)</span>
    </div>
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th style="white-space:nowrap">Due Date</th>
                        <th class="text-end">Amount Due</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dueThisMonth as $payment)
                    @php $daysLeft = now()->diffInDays($payment->due_date, false); @endphp
                    <tr>
                        <td class="fw-semibold" style="white-space:nowrap">{{ $payment->sale->customer_name }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('sales.show', $payment->sale) }}" class="text-decoration-none text-primary fw-semibold"
                               style="font-family:monospace;font-size:0.78rem;">
                                {{ $payment->sale->invoice_number }}
                            </a>
                        </td>
                        <td style="white-space:nowrap">
                            {{ $payment->due_date->format('M d, Y') }}
                            <br>
                            @if($daysLeft < 0)
                                <small class="text-danger fw-bold"><i class="bi bi-alarm"></i> Overdue {{ abs((int) $daysLeft) }}d</small>
                            @elseif($daysLeft == 0)
                                <small class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> Due Today</small>
                            @else
                                <small class="text-warning fw-bold"><i class="bi bi-clock"></i> {{ (int) $daysLeft }}d left</small>
                            @endif
                        </td>
                        <td class="text-end fw-semibold" style="white-space:nowrap">
                            ₱{{ number_format($payment->amount - $payment->amount_paid, 2) }}
                        </td>
                        <td style="white-space:nowrap">
                            @if($payment->status == 'partial')
                                <span class="badge bg-info text-dark">Partial</span>
                            @else
                                <span class="badge bg-danger">Unpaid</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('installments.show', $payment->sale_id) }}"
                               class="btn btn-warning btn-sm app-act text-dark">
                                <i class="bi bi-calendar-check"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                            No installments due this month
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($overdueInstallments->count() > 0)
    <div class="d-flex align-items-center gap-2 mb-2">
        <h6 class="fw-semibold mb-0"><i class="bi bi-exclamation-octagon-fill text-danger me-1"></i>Overdue Installments</h6>
        <span class="badge bg-danger">{{ $overdueInstallments->count() }}</span>
    </div>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th style="white-space:nowrap">Was Due</th>
                        <th class="text-end">Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($overdueInstallments as $payment)
                    <tr>
                        <td class="fw-semibold" style="white-space:nowrap">{{ $payment->sale->customer_name }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('sales.show', $payment->sale) }}" class="text-danger fw-semibold text-decoration-none"
                               style="font-family:monospace;font-size:0.78rem;">
                                {{ $payment->sale->invoice_number }}
                            </a>
                        </td>
                        <td style="white-space:nowrap">
                            {{ $payment->due_date->format('M d, Y') }}
                            <br><small class="text-danger fw-bold">{{ abs(now()->diffInDays($payment->due_date)) }}d ago</small>
                        </td>
                        <td class="text-end fw-semibold text-danger" style="white-space:nowrap">
                            ₱{{ number_format($payment->amount - $payment->amount_paid, 2) }}
                        </td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('installments.show', $payment->sale_id) }}"
                               class="btn btn-danger btn-sm app-act">
                                <i class="bi bi-cash"></i> Collect
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
