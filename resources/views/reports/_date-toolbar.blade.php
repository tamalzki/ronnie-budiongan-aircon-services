{{-- Date range for reports; pass $preserveReport (slug) when viewing a specific report --}}
@php
    $qp = ['start_date' => $startDate, 'end_date' => $endDate];
    if (! empty($preserveReport)) {
        $qp['report'] = $preserveReport;
    }
    $monthStart = now()->startOfMonth()->format('Y-m-d');
    $today = now()->format('Y-m-d');
    $lastMonthStart = now()->subMonth()->startOfMonth()->format('Y-m-d');
    $lastMonthEnd = now()->subMonth()->endOfMonth()->format('Y-m-d');
    $yearStart = now()->startOfYear()->format('Y-m-d');
    $mergeReport = fn(array $extra) => ! empty($preserveReport) ? array_merge($extra, ['report' => $preserveReport]) : $extra;
@endphp
<div class="card app-card-panel mb-3 app-filter-toolbar">
    <div class="card-body py-3">
        <form action="{{ route('reports.index') }}" method="GET" class="row g-2 align-items-end">
            @if(! empty($preserveReport))
                <input type="hidden" name="report" value="{{ $preserveReport }}">
            @endif
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">From</label>
                <input type="date" class="form-control form-control-sm" name="start_date" value="{{ $startDate }}">
            </div>
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">To</label>
                <input type="date" class="form-control form-control-sm" name="end_date" value="{{ $endDate }}">
            </div>
            <div class="col-auto d-flex gap-1 align-items-end flex-wrap">
                <a href="{{ route('reports.index', $mergeReport(['start_date' => $monthStart, 'end_date' => $today])) }}"
                   class="btn btn-outline-secondary btn-sm">This Month</a>
                <a href="{{ route('reports.index', $mergeReport(['start_date' => $lastMonthStart, 'end_date' => $lastMonthEnd])) }}"
                   class="btn btn-outline-secondary btn-sm">Last Month</a>
                <a href="{{ route('reports.index', $mergeReport(['start_date' => $yearStart, 'end_date' => $today])) }}"
                   class="btn btn-outline-secondary btn-sm">This Year</a>
            </div>
            <div class="col-auto d-flex gap-1 align-items-end">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Apply</button>
                <a href="{{ route('reports.index', $preserveReport ? ['report' => $preserveReport] : []) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x"></i> Reset</a>
            </div>
            <div class="col-auto ms-auto align-items-end d-flex">
                <span class="badge bg-light text-muted border" style="font-size:0.8rem;padding:6px 10px;">
                    <i class="bi bi-calendar3"></i>
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                </span>
            </div>
        </form>
    </div>
</div>
