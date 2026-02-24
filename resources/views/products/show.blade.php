@extends('layouts.app')
@section('title', ($product->brand->name ?? '') . ' ' . $product->model)

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                    <li class="breadcrumb-item active">{{ ($product->brand->name ?? '') . ' ' . $product->model }}</li>
                </ol>
            </nav>
            <h2 class="mb-0">
                <i class="bi bi-box-seam text-primary"></i>
                {{ $product->brand->name ?? '' }} {{ $product->model }}
                @if($product->unit_type === 'indoor')
                    <span style="font-size:0.75rem;padding:3px 10px;border-radius:20px;background:#e8f0fe;color:#1a56db;border:1px solid #93c5fd;font-weight:600;vertical-align:middle;">❄️ Indoor</span>
                @elseif($product->unit_type === 'outdoor')
                    <span style="font-size:0.75rem;padding:3px 10px;border-radius:20px;background:#dcfce7;color:#166534;border:1px solid #86efac;font-weight:600;vertical-align:middle;">🌀 Outdoor</span>
                @endif
            </h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil"></i> Edit Product
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
        {!! session('success') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Top Row: Product Info + Inventory Summary --}}
    <div class="row g-4 mb-4">

        {{-- Product Info --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white border-0">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Product Details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Brand</small>
                            <strong>{{ $product->brand->name ?? '—' }}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Model</small>
                            <strong>{{ $product->model }}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Unit Type</small>
                            <strong>{{ ucfirst($product->unit_type ?? '—') }}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Supplier</small>
                            <strong>{{ $product->supplier->name ?? '—' }}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Cost (latest PO)</small>
                            @if($product->cost > 0)
                                <strong class="text-danger">₱{{ number_format($product->cost, 2) }}</strong>
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Selling Price</small>
                            @if($product->price > 0)
                                <strong class="text-success">₱{{ number_format($product->price, 2) }}</strong>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-lock-fill"></i> Not set</span>
                            @endif
                        </div>
                        @if($product->price > 0 && $product->cost > 0)
                        <div class="col-6">
                            <small class="text-muted d-block">Profit Margin</small>
                            @php $profit = $product->price - $product->cost; $pct = ($profit / $product->cost) * 100; @endphp
                            <strong class="{{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                ₱{{ number_format($profit, 2) }} ({{ number_format($pct, 1) }}%)
                            </strong>
                        </div>
                        @endif
                        <div class="col-6">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @if($product->price == 0)
                        <div class="col-12">
                            <form action="{{ route('products.set-price', $product) }}" method="POST"
                                  class="d-flex align-items-center gap-2">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="0.01"
                                           class="form-control" name="price"
                                           placeholder="Set selling price" required>
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm fw-semibold px-3">
                                    <i class="bi bi-check"></i> Set Price
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Inventory Summary Cards --}}
        <div class="col-md-7">
            <div class="row g-3 h-100">
                <div class="col-6">
                    <div class="card border-0 shadow-sm h-100"
                         style="border-left:4px solid #10b981 !important;cursor:pointer;"
                         onclick="filterSerials('in_stock')">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:52px;height:52px;background:#d1fae5;flex-shrink:0;">
                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em">In Stock</div>
                                <div class="fw-bold" style="font-size:2rem;line-height:1;color:#10b981;">{{ $counts['in_stock'] }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">units available</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm h-100"
                         style="border-left:4px solid #f59e0b !important;cursor:pointer;"
                         onclick="filterSerials('pending')">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:52px;height:52px;background:#fef3c7;flex-shrink:0;">
                                <i class="bi bi-clock-fill text-warning fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em">Pending</div>
                                <div class="fw-bold" style="font-size:2rem;line-height:1;color:#f59e0b;">{{ $counts['pending'] }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">not yet received</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm h-100"
                         style="border-left:4px solid #3b82f6 !important;cursor:pointer;"
                         onclick="filterSerials('sold')">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:52px;height:52px;background:#dbeafe;flex-shrink:0;">
                                <i class="bi bi-cart-check-fill fs-4" style="color:#3b82f6;"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em">Sold</div>
                                <div class="fw-bold" style="font-size:2rem;line-height:1;color:#3b82f6;">{{ $counts['sold'] }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">units sold</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm h-100"
                         style="border-left:4px solid #ef4444 !important;cursor:pointer;"
                         onclick="filterSerials('defective')">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:52px;height:52px;background:#fee2e2;flex-shrink:0;">
                                <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.05em">Defective / Lost</div>
                                <div class="fw-bold" style="font-size:2rem;line-height:1;color:#ef4444;">{{ $counts['defective'] + $counts['lost'] }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">removed from stock</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Serial Numbers Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 d-flex justify-content-between align-items-center"
             style="background:linear-gradient(135deg,#4F46E5,#4338CA);">
            <h5 class="mb-0 text-white"><i class="bi bi-upc-scan"></i> Serial Numbers — All Units</h5>
            <div class="d-flex gap-2 align-items-center">
                {{-- Filter buttons --}}
                <button type="button" class="btn btn-sm btn-light filter-btn active" data-filter="all"
                        onclick="filterSerials('all')" style="font-size:0.78rem;">
                    All <span class="badge bg-secondary ms-1">{{ array_sum($counts) }}</span>
                </button>
                <button type="button" class="btn btn-sm filter-btn" data-filter="in_stock"
                        onclick="filterSerials('in_stock')"
                        style="font-size:0.78rem;background:#d1fae5;color:#065f46;border:1px solid #86efac;">
                    In Stock <span class="badge ms-1" style="background:#10b981;">{{ $counts['in_stock'] }}</span>
                </button>
                @if($counts['pending'] > 0)
                <button type="button" class="btn btn-sm filter-btn" data-filter="pending"
                        onclick="filterSerials('pending')"
                        style="font-size:0.78rem;background:#fef3c7;color:#92400e;border:1px solid #fcd34d;">
                    Pending <span class="badge bg-warning text-dark ms-1">{{ $counts['pending'] }}</span>
                </button>
                @endif
                @if($counts['sold'] > 0)
                <button type="button" class="btn btn-sm filter-btn" data-filter="sold"
                        onclick="filterSerials('sold')"
                        style="font-size:0.78rem;background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;">
                    Sold <span class="badge ms-1" style="background:#3b82f6;">{{ $counts['sold'] }}</span>
                </button>
                @endif
                @if($counts['defective'] + $counts['lost'] > 0)
                <button type="button" class="btn btn-sm filter-btn" data-filter="defective"
                        onclick="filterSerials('defective')"
                        style="font-size:0.78rem;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;">
                    Defective/Lost <span class="badge bg-danger ms-1">{{ $counts['defective'] + $counts['lost'] }}</span>
                </button>
                @endif
            </div>
        </div>

        <div class="card-body p-0">
            @if($serials->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2"><i class="bi bi-upc-scan me-1"></i>Serial Number</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Purchase Order</th>
                            <th class="px-4 py-2">Received Date</th>
                            <th class="px-4 py-2">Notes</th>
                        </tr>
                    </thead>
                    <tbody id="serialsTableBody">
                        @foreach($serials as $serial)
                        @php
                            $statusConfig = [
                                'in_stock'  => ['bg' => '#f0fdf4', 'border' => '#86efac', 'badge' => 'bg-success',         'icon' => '✅', 'label' => 'In Stock'],
                                'pending'   => ['bg' => '#fffbeb', 'border' => '#fcd34d', 'badge' => 'bg-warning text-dark','icon' => '⏳', 'label' => 'Pending'],
                                'sold'      => ['bg' => '#eff6ff', 'border' => '#93c5fd', 'badge' => 'bg-primary',          'icon' => '🛒', 'label' => 'Sold'],
                                'returned'  => ['bg' => '#fdf4ff', 'border' => '#d8b4fe', 'badge' => 'bg-purple',           'icon' => '↩️', 'label' => 'Returned'],
                                'defective' => ['bg' => '#fff1f2', 'border' => '#fca5a5', 'badge' => 'bg-danger',           'icon' => '⚠️', 'label' => 'Defective'],
                                'lost'      => ['bg' => '#f9fafb', 'border' => '#d1d5db', 'badge' => 'bg-secondary',        'icon' => '❓', 'label' => 'Lost'],
                            ];
                            $cfg = $statusConfig[$serial->status] ?? $statusConfig['lost'];
                            $filterStatus = in_array($serial->status, ['defective','lost']) ? 'defective' : $serial->status;
                        @endphp
                        <tr class="serial-row" data-status="{{ $filterStatus }}"
                            style="background:{{ $cfg['bg'] }};">
                            <td class="px-4 py-2 text-muted">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2">
                                <code class="fw-semibold" style="font-size:0.88rem;color:#1e293b;letter-spacing:0.03em;">
                                    {{ $serial->serial_number }}
                                </code>
                            </td>
                            <td class="px-4 py-2">
                                <span class="badge {{ $cfg['badge'] }}">
                                    {{ $cfg['icon'] }} {{ $cfg['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                @if($serial->purchaseOrder)
                                    <a href="{{ route('purchase-orders.show', $serial->purchaseOrder) }}"
                                       class="text-decoration-none text-primary fw-semibold"
                                       style="font-size:0.82rem;">
                                        <i class="bi bi-cart-plus me-1"></i>{{ $serial->purchaseOrder->po_number }}
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($serial->received_date)
                                    <small>{{ \Carbon\Carbon::parse($serial->received_date)->format('M d, Y') }}</small>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <small class="text-muted">{{ $serial->notes ?? '—' }}</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-upc fs-1 d-block mb-2"></i>
                <p class="mb-1">No serial numbers recorded yet.</p>
                <small>Serial numbers are added when creating a Purchase Order.</small>
            </div>
            @endif
        </div>

        @if($serials->count() > 0)
        <div class="card-footer bg-light border-0 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                <span id="visibleCount">{{ $serials->count() }}</span> of {{ $serials->count() }} serials shown
            </small>
            <small class="text-muted">
                Total units ever: <strong>{{ array_sum($counts) }}</strong>
            </small>
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function filterSerials(status) {
    const rows    = document.querySelectorAll('.serial-row');
    const btns    = document.querySelectorAll('.filter-btn');
    let   visible = 0;

    rows.forEach(row => {
        const show = status === 'all' || row.dataset.status === status;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    // Update active button
    btns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === status);
        if (btn.dataset.filter === status) {
            btn.style.boxShadow = '0 0 0 2px #4F46E5';
        } else {
            btn.style.boxShadow = '';
        }
    });

    const countEl = document.getElementById('visibleCount');
    if (countEl) countEl.textContent = visible;
}
</script>
@endpush

@endsection