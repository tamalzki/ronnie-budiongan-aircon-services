@extends('layouts.app')

@section('title', 'Sales Reports')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Sales Reports</h2>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('reports.index') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Sales</h6>
                    <h3>₱{{ number_format($totalSales, 2) }}</h3>
                    <small>{{ $startDate }} to {{ $endDate }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Cash Sales</h6>
                    <h3>₱{{ number_format($totalCashSales, 2) }}</h3>
                    <small>Fully paid transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Installment Sales</h6>
                    <h3>₱{{ number_format($totalInstallmentSales, 2) }}</h3>
                    <small>Payment plans</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales by Date Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Daily Sales Trend</h5>
        </div>
        <div class="card-body">
            <canvas id="salesChart" height="80"></canvas>
        </div>
    </div>

    <!-- Top Products -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Top 10 Products</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Product Name</th>
                            <th>Brand</th>
                            <th>Units Sold</th>
                            <th>Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $index => $product)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->brand->name ?? 'N/A' }}</td>
                            <td>{{ $product->sale_items_count }}</td>
                            <td>
                                <span class="badge bg-{{ $product->stock_quantity > 5 ? 'success' : 'warning' }}">
                                    {{ $product->stock_quantity }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No product sales in this period</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pending Installments -->
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Pending Installments Summary</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Pending Payments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $salesWithInstallments = $pendingInstallments->groupBy('sale_id');
                        @endphp
                        @forelse($salesWithInstallments as $saleId => $payments)
                            @php
                                $sale = $payments->first()->sale;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a>
                                </td>
                                <td>{{ $sale->customer_name }}</td>
                                <td>₱{{ number_format($sale->total, 2) }}</td>
                                <td>₱{{ number_format($sale->paid_amount, 2) }}</td>
                                <td class="text-danger fw-bold">₱{{ number_format($sale->balance, 2) }}</td>
                                <td>{{ $payments->count() }}</td>
                            </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No pending installments</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesData = @json($salesByDate);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: salesData.map(item => item.date),
        datasets: [{
            label: 'Daily Sales (₱)',
            data: salesData.map(item => item.total),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Sales Trend'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>
@endpush
@endsection