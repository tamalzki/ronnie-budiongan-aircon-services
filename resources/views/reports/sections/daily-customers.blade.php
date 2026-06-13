<div class="report-section">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card app-card-panel h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-primary bg-opacity-10 rounded p-2"><i class="bi bi-person-lines-fill fs-4 text-primary"></i></div>
                    <div>
                        <div class="text-muted small">Total Entries</div>
                        <div class="fw-bold">{{ $dailyCustomersCount }}</div>
                        <div class="text-muted" style="font-size:0.72rem;">₱{{ number_format($dailyCustomersAmount, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card-panel h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-success bg-opacity-10 rounded p-2"><i class="bi bi-check-circle fs-4 text-success"></i></div>
                    <div>
                        <div class="text-muted small">Paid</div>
                        <div class="fw-bold text-success">₱{{ number_format($dailyCustomersPaidAmount, 2) }}</div>
                        <div class="text-muted" style="font-size:0.72rem;">{{ $dailyCustomersPaidCount }} entr{{ $dailyCustomersPaidCount == 1 ? 'y' : 'ies' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card-panel h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-danger bg-opacity-10 rounded p-2"><i class="bi bi-exclamation-circle fs-4 text-danger"></i></div>
                    <div>
                        <div class="text-muted small">Unpaid</div>
                        <div class="fw-bold text-danger">₱{{ number_format($dailyCustomersUnpaidAmount, 2) }}</div>
                        <div class="text-muted" style="font-size:0.72rem;">{{ $dailyCustomersUnpaidCount }} entr{{ $dailyCustomersUnpaidCount == 1 ? 'y' : 'ies' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card-panel h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-info bg-opacity-10 rounded p-2"><i class="bi bi-percent fs-4 text-info"></i></div>
                    <div>
                        <div class="text-muted small">Collection Rate</div>
                        <div class="fw-bold">
                            {{ $dailyCustomersAmount > 0 ? number_format(($dailyCustomersPaidAmount / $dailyCustomersAmount) * 100, 1) : 0 }}%
                        </div>
                        <div class="text-muted" style="font-size:0.72rem;">Paid of total</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-pie-chart text-primary me-1"></i>By Service Type</h6>
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th class="text-center">Count</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyCustomersByService as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row->service_type }}</td>
                        <td class="text-center">
                            <span class="badge bg-info text-dark">{{ $row->cnt }}</span>
                        </td>
                        <td class="text-end">₱{{ number_format($row->amt, 2) }}</td>
                        <td class="text-end text-muted">
                            {{ $dailyCustomersAmount > 0 ? number_format(($row->amt / $dailyCustomersAmount) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-4 text-muted">No daily customer entries in this period</td></tr>
                    @endforelse
                </tbody>
                @if($dailyCustomersByService->count() > 0)
                <tfoot>
                    <tr class="fw-semibold">
                        <td>Total</td>
                        <td class="text-center">{{ $dailyCustomersCount }}</td>
                        <td class="text-end">₱{{ number_format($dailyCustomersAmount, 2) }}</td>
                        <td class="text-end text-muted">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-list-ul text-secondary me-1"></i>Entries</h6>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Service Availed</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyCustomersList as $entry)
                    <tr>
                        <td style="white-space:nowrap">{{ $entry->service_date->format('M d, Y') }}</td>
                        <td class="fw-semibold">{{ $entry->customer_name }}</td>
                        <td>{{ $entry->service_label }}</td>
                        <td class="text-end">₱{{ number_format($entry->amount, 2) }}</td>
                        <td>
                            @if($entry->status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @else
                                <span class="badge bg-danger">Unpaid</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No daily customer entries in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
