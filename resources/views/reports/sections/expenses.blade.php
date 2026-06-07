@php
    $operationalExpenseListRouteReady = Route::has('operation-expenses.index');
@endphp
<div class="report-section">
    <div class="row g-3 mb-4">
        <div class="col-md-{{ $operationalExpenseListRouteReady ? 6 : 12 }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="bi bi-receipt-cutoff"></i> Period total</h6>
                    <div class="fs-3 fw-bold text-danger">₱{{ number_format($totalOperatingExpenses, 2) }}</div>
                    <p class="text-muted small mb-0 mt-2">Operational expenses recorded in the selected date range.</p>
                </div>
            </div>
        </div>
        @if($operationalExpenseListRouteReady)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="bi bi-pencil-square"></i> Manage</h6>
                    <p class="small text-muted">Add or edit entries from the operational expense list.</p>
                    <a href="{{ route('operation-expenses.index') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-receipt"></i> Operational expenses
                    </a>
                    @if(Route::has('expense-categories.index'))
                    <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary btn-sm ms-1">
                        <i class="bi bi-folder2"></i> Categories
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-pie-chart text-primary me-1"></i>By Category</h6>
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expensesByCategory as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row->category_name }}</td>
                        <td class="text-end">₱{{ number_format($row->total, 2) }}</td>
                        <td class="text-end text-muted">
                            {{ $totalOperatingExpenses > 0 ? number_format(($row->total / $totalOperatingExpenses) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center py-4 text-muted">No expenses in this period</td></tr>
                    @endforelse
                </tbody>
                @if($expensesByCategory->count() > 0)
                <tfoot>
                    <tr class="fw-semibold">
                        <td>Total</td>
                        <td class="text-end text-danger">₱{{ number_format($totalOperatingExpenses, 2) }}</td>
                        <td class="text-end text-muted">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-list-ul text-secondary me-1"></i>Line Items</h6>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operationExpensesList as $ex)
                    <tr>
                        <td style="white-space:nowrap">{{ $ex->expense_date->format('M d, Y') }}</td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">{{ $ex->category->name }}</span></td>
                        <td>{{ $ex->description }}</td>
                        <td class="text-end fw-semibold">₱{{ number_format($ex->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-4 text-muted">No line items</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
