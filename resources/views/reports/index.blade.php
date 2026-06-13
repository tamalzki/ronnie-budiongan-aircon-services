@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="container-fluid">

    @php
        $hubTitles = [
            'overview'     => 'Overview',
            'sales'        => 'Sales Report',
            'installments' => 'Installments',
            'purchases'    => 'Purchases',
            'customers'    => 'Customers',
            'daily_customers' => 'Daily Customers',
            'inventory'    => 'Inventory',
            'expenses'     => 'Operational expense report',
        ];
        $reportLink = fn (string $key) => route('reports.index', ['report' => $key]);
    @endphp

    <x-page-header
        title="Reports"
        :subtitle="$currentReport ? ($hubTitles[$currentReport] ?? 'Report') : 'Choose a report'"
        icon="bi-bar-chart-line"
        marginClass="mb-3"
    />

    <x-flash />

    @if($currentReport === null)
        @php
            $reportCards = [
                ['key' => 'overview',     'title' => 'Overview',                   'desc' => 'Daily trend, cash vs installment, top products',     'icon' => 'bi-speedometer2',   'circle' => 'success'],
                ['key' => 'sales',        'title' => 'Sales Report',               'desc' => 'Full transaction list, payment breakdown, receivables', 'icon' => 'bi-receipt',        'circle' => 'primary'],
                ['key' => 'installments', 'title' => 'Installments',               'desc' => 'Due this month, overdue, collections',                 'icon' => 'bi-calendar-check', 'circle' => 'warning'],
                ['key' => 'purchases',    'title' => 'Purchases',                  'desc' => 'Purchase orders, paid vs outstanding',                 'icon' => 'bi-cart-plus',      'circle' => 'primary'],
                ['key' => 'customers',    'title' => 'Customers',                  'desc' => 'Top spenders, pending installment balances',           'icon' => 'bi-people',         'circle' => 'info'],
                ['key' => 'daily_customers', 'title' => 'Daily Customers',         'desc' => 'Walk-in services, paid vs unpaid, by service type',    'icon' => 'bi-person-lines-fill', 'circle' => 'warning'],
                ['key' => 'inventory',    'title' => 'Inventory',                  'desc' => 'Stock levels, value, low-stock alerts',                'icon' => 'bi-boxes',          'circle' => 'secondary'],
                ['key' => 'expenses',     'title' => 'Operational Expenses',       'desc' => 'Totals by category and line items for the period',     'icon' => 'bi-receipt-cutoff', 'circle' => 'danger'],
            ];
        @endphp
        <div class="row g-3 g-md-4 report-hub-grid">
            @foreach($reportCards as $card)
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="{{ $reportLink($card['key']) }}" class="text-decoration-none report-hub-card d-block h-100">
                        <div class="card app-card-panel h-100 border shadow-sm">
                            <div class="card-body d-flex align-items-start gap-3 py-3 py-md-4">
                                <div class="report-hub-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-{{ $card['circle'] }} bg-opacity-10 text-{{ $card['circle'] }}">
                                    <i class="bi {{ $card['icon'] }} fs-4"></i>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-semibold text-dark">{{ $card['title'] }}</span>
                                        @if($card['key'] === 'installments' && $overdueInstallments->count() > 0)
                                            <span class="badge bg-danger">{{ $overdueInstallments->count() }} overdue</span>
                                        @endif
                                    </div>
                                    <p class="text-muted small mb-0 mt-1">{{ $card['desc'] }}</p>
                                </div>
                                <i class="bi bi-chevron-right text-muted flex-shrink-0 mt-1 d-none d-sm-block"></i>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <div class="mb-3">
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Reports
            </a>
        </div>

        @include('reports._date-toolbar', ['preserveReport' => $currentReport])

        {{-- KPI Summary Strip — hidden on Sales / Daily Customers reports (they have their own strip) --}}
        @if($currentReport !== 'sales' && $currentReport !== 'daily_customers')
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card app-card-panel">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="bg-primary bg-opacity-10 rounded p-2"><i class="bi bi-bag-check fs-4 text-primary"></i></div>
                        <div>
                            <div class="text-muted small">Total Sales</div>
                            <div class="fw-bold">₱{{ number_format($totalSales, 2) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">{{ $salesCount }} orders</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card app-card-panel">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="bg-success bg-opacity-10 rounded p-2"><i class="bi bi-cash-stack fs-4 text-success"></i></div>
                        <div>
                            <div class="text-muted small">Collected</div>
                            <div class="fw-bold text-success">₱{{ number_format($totalCollected, 2) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">Actual cash in</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card app-card-panel">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="bg-danger bg-opacity-10 rounded p-2"><i class="bi bi-hourglass-split fs-4 text-danger"></i></div>
                        <div>
                            <div class="text-muted small">Receivables</div>
                            <div class="fw-bold text-danger">₱{{ number_format($totalPending, 2) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">Still to collect</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card app-card-panel">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="{{ $profitMargin >= 0 ? 'bg-success' : 'bg-danger' }} bg-opacity-10 rounded p-2">
                            <i class="bi bi-graph-up-arrow fs-4 {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Gross Profit</div>
                            <div class="fw-bold {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}">₱{{ number_format($profitMargin, 2) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">{{ number_format($profitPercentage, 1) }}% margin</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="app-tab-panel">
            @if($currentReport === 'overview')
                @include('reports.sections.overview')
            @elseif($currentReport === 'sales')
                @include('reports.sections.sales')
            @elseif($currentReport === 'installments')
                @include('reports.sections.installments')
            @elseif($currentReport === 'purchases')
                @include('reports.sections.purchases')
            @elseif($currentReport === 'customers')
                @include('reports.sections.customers')
            @elseif($currentReport === 'daily_customers')
                @include('reports.sections.daily-customers')
            @elseif($currentReport === 'inventory')
                @include('reports.sections.inventory')
            @elseif($currentReport === 'expenses')
                @include('reports.sections.expenses')
            @endif
        </div>
    @endif

</div>

@if($currentReport === 'overview')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const salesData = @json($salesByDate);
    const canvas = document.getElementById('salesChart');
    if (!canvas || salesData.length === 0) return;
    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.map(d => d.date),
            datasets: [{
                label: 'Daily Sales (₱)',
                data: salesData.map(d => d.total),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.08)',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => '₱' + v.toLocaleString() },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
@endpush
@endif

<style>
.report-hub-card .card {
    transition: box-shadow 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}
.report-hub-card:hover .card {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    border-color: rgba(13, 110, 253, 0.35) !important;
    transform: translateY(-1px);
}
.report-hub-icon {
    width: 3rem;
    height: 3rem;
}
@media (min-width: 768px) {
    .report-hub-icon {
        width: 3.25rem;
        height: 3.25rem;
    }
}
</style>

@endsection
