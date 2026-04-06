<div class="report-section app-tab-panel">
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 bg-light h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="bi bi-receipt-cutoff"></i> Period total</h6>
                    <div class="fs-3 fw-bold text-danger">₱{{ number_format($totalOperatingExpenses, 2) }}</div>
                    <p class="text-muted small mb-0 mt-2">Operational expenses recorded in the selected date range.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 bg-light h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="bi bi-pencil-square"></i> Manage</h6>
                    <p class="small text-muted">Add or edit entries from the operational expense list.</p>
                    <a href="{{ route('operation-expenses.index') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-receipt"></i> Operational expenses
                    </a>
                    <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary btn-sm ms-1">
                        <i class="bi bi-folder2"></i> Categories
                    </a>
                </div>
            </div>
        </div>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-pie-chart text-primary"></i> By category</h6>
    <div class="table-responsive mb-4">
        <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 px-3 py-2">Category</th>
                    <th class="border-0 px-3 py-2 text-end">Amount</th>
                    <th class="border-0 px-3 py-2 text-end">Share</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expensesByCategory as $row)
                <tr>
                    <td class="px-3 py-2 fw-semibold">{{ $row->category_name }}</td>
                    <td class="px-3 py-2 text-end">₱{{ number_format($row->total, 2) }}</td>
                    <td class="px-3 py-2 text-end text-muted">
                        {{ $totalOperatingExpenses > 0 ? number_format(($row->total / $totalOperatingExpenses) * 100, 1) : 0 }}%
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-4 text-muted">No expenses in this period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-list-ul text-secondary"></i> Line items</h6>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 px-3 py-2">Date</th>
                    <th class="border-0 px-3 py-2">Category</th>
                    <th class="border-0 px-3 py-2">Description</th>
                    <th class="border-0 px-3 py-2 text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($operationExpensesList as $ex)
                <tr>
                    <td class="px-3 py-2 text-nowrap">{{ $ex->expense_date->format('M d, Y') }}</td>
                    <td class="px-3 py-2"><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $ex->category->name }}</span></td>
                    <td class="px-3 py-2">{{ $ex->description }}</td>
                    <td class="px-3 py-2 text-end fw-semibold">₱{{ number_format($ex->amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">No line items</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
