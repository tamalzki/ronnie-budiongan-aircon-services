<div class="report-section">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="bi bi-pie-chart"></i> Sales Breakdown</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Cash Sales</span>
                        <span class="fw-semibold text-success">₱{{ number_format($totalCashSales, 2) }}</span>
                    </div>
                    <div class="progress mb-3" style="height:6px;">
                        <div class="progress-bar bg-success" style="width:{{ $totalSales > 0 ? ($totalCashSales/$totalSales)*100 : 0 }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Installment Sales</span>
                        <span class="fw-semibold text-warning">₱{{ number_format($totalInstallmentSales, 2) }}</span>
                    </div>
                    <div class="progress mb-3" style="height:6px;">
                        <div class="progress-bar bg-warning" style="width:{{ $totalSales > 0 ? ($totalInstallmentSales/$totalSales)*100 : 0 }}%"></div>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Avg. Sale Value</span>
                        <span class="fw-semibold">₱{{ number_format($averageSaleAmount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="bi bi-graph-up"></i> Daily Sales Trend</h6>
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-trophy text-warning"></i> Top 10 Products</h6>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 app-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Brand</th>
                        <th class="text-center">Units Sold</th>
                        <th class="text-center">Stock Left</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $i => $product)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold" style="white-space:nowrap">{{ $product->display_model }}</td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                {{ $product->brand->name ?? '—' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ $product->sale_items_count }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $product->in_stock_count == 0 ? 'bg-danger' : ($product->in_stock_count <= 5 ? 'bg-warning text-dark' : 'bg-success') }}">
                                {{ $product->in_stock_count }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No sales in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
