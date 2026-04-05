<div class="report-section app-tab-panel">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Total Due (Period)</div>
                <div class="fw-bold fs-5">₱{{ number_format($totalInstallmentAmount, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Collected</div>
                <div class="fw-bold fs-5 text-success">₱{{ number_format($paidInstallments, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Pending</div>
                <div class="fw-bold fs-5 text-warning">{{ $pendingInstallments->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger bg-opacity-10 text-center py-3">
                <div class="text-muted small mb-1">Overdue</div>
                <div class="fw-bold fs-5 text-danger">{{ $overdueInstallments->count() }}</div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-2">
        <h6 class="fw-semibold mb-0"><i class="bi bi-bell-fill text-warning"></i> Due This Month</h6>
        <span class="badge bg-warning text-dark">{{ $dueThisMonth->count() }} customer(s)</span>
    </div>
    <div class="table-responsive mb-4">
        <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 px-3 py-2">Customer</th>
                    <th class="border-0 px-3 py-2">Invoice</th>
                    <th class="border-0 px-3 py-2" style="white-space:nowrap">Due Date</th>
                    <th class="border-0 px-3 py-2">Amount Due</th>
                    <th class="border-0 px-3 py-2">Status</th>
                    <th class="border-0 px-3 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dueThisMonth as $payment)
                @php $daysLeft = now()->diffInDays($payment->due_date, false); @endphp
                <tr class="{{ $daysLeft < 0 ? 'table-danger' : 'table-warning' }}">
                    <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $payment->sale->customer_name }}</td>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        <a href="{{ route('sales.show', $payment->sale) }}" class="text-decoration-none text-primary fw-semibold">
                            {{ $payment->sale->invoice_number }}
                        </a>
                    </td>
                    <td class="px-3 py-2" style="white-space:nowrap">
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
                    <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">
                        ₱{{ number_format($payment->amount - $payment->amount_paid, 2) }}
                    </td>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        @if($payment->status == 'partial')
                            <span class="badge bg-info text-dark">Partial</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        <a href="{{ route('installments.show', $payment->sale_id) }}"
                           class="btn btn-warning"
                           style="padding:2px 8px;font-size:0.78rem">
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

    @if($overdueInstallments->count() > 0)
    <div class="d-flex align-items-center gap-2 mb-2">
        <h6 class="fw-semibold mb-0"><i class="bi bi-exclamation-octagon-fill text-danger"></i> Overdue Installments</h6>
        <span class="badge bg-danger">{{ $overdueInstallments->count() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm table-danger mb-0" style="font-size:0.875rem;">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 px-3 py-2">Customer</th>
                    <th class="border-0 px-3 py-2">Invoice</th>
                    <th class="border-0 px-3 py-2" style="white-space:nowrap">Was Due</th>
                    <th class="border-0 px-3 py-2">Balance</th>
                    <th class="border-0 px-3 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($overdueInstallments as $payment)
                <tr>
                    <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $payment->sale->customer_name }}</td>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        <a href="{{ route('sales.show', $payment->sale) }}" class="text-danger fw-semibold text-decoration-none">
                            {{ $payment->sale->invoice_number }}
                        </a>
                    </td>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        {{ $payment->due_date->format('M d, Y') }}
                        <br><small class="text-danger fw-bold">{{ abs(now()->diffInDays($payment->due_date)) }}d ago</small>
                    </td>
                    <td class="px-3 py-2 fw-semibold text-danger" style="white-space:nowrap">
                        ₱{{ number_format($payment->amount - $payment->amount_paid, 2) }}
                    </td>
                    <td class="px-3 py-2" style="white-space:nowrap">
                        <a href="{{ route('installments.show', $payment->sale_id) }}"
                           class="btn btn-danger"
                           style="padding:2px 8px;font-size:0.78rem">
                            <i class="bi bi-cash"></i> Collect
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
