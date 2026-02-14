@extends('layouts.app')
@section('title', 'Products')
@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-box-seam text-primary"></i> Products</h2>
            <p class="text-muted mb-0">Manage your product catalog</p>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-lg shadow-sm">
            <i class="bi bi-plus-circle"></i> Add Product
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($noPriceCount > 0)
    <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-center gap-3">
        <i class="bi bi-lock-fill fs-2 text-warning flex-shrink-0"></i>
        <div>
            <strong>{{ $noPriceCount }} product(s) cannot be sold — no selling price set.</strong><br>
            <small>Use the inline <strong>Set Price</strong> field below, or receive a Purchase Order first to auto-set the cost, then set your selling price.</small>
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-4 py-3">Brand</th>
                            <th class="border-0 px-4 py-3">Model</th>
                            <th class="border-0 px-4 py-3">Supplier</th>
                            <th class="border-0 px-4 py-3">Cost (PO)</th>
                            <th class="border-0 px-4 py-3">Selling Price</th>
                            <th class="border-0 px-4 py-3">Profit</th>
                            <th class="border-0 px-4 py-3">Stock</th>
                            <th class="border-0 px-4 py-3">Sale Status</th>
                            <th class="border-0 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        @php
                            $canSell   = $product->price > 0;
                            $profit    = $product->price - $product->cost;
                            $profitPct = $product->cost > 0 ? (($profit / $product->cost) * 100) : 0;
                        @endphp
                        <tr class="{{ !$canSell ? 'table-warning bg-warning bg-opacity-10' : '' }}">
                            <td class="px-4 py-3">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                    {{ $product->brand->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 fw-semibold">{{ $product->model ?? '—' }}</td>
                            <td class="px-4 py-3"><small class="text-muted">{{ $product->supplier->name ?? '—' }}</small></td>
                            <td class="px-4 py-3">
                                @if($product->cost > 0)
                                    <strong class="text-danger">₱{{ number_format($product->cost, 2) }}</strong>
                                    <br><small class="text-muted">Auto from PO</small>
                                @else
                                    <small class="text-muted">Not set yet</small>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($canSell)
                                    <strong class="text-success">₱{{ number_format($product->price, 2) }}</strong>
                                @else
                                    <form action="{{ route('products.set-price', $product) }}" method="POST"
                                          class="d-flex align-items-center gap-1">
                                        @csrf
                                        <div class="input-group input-group-sm" style="width:130px;">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" step="0.01" min="0.01"
                                                   class="form-control" name="price"
                                                   placeholder="0.00" required>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-warning fw-semibold">Set</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($canSell && $product->cost > 0)
                                    <span class="badge bg-{{ $profit >= 0 ? 'success' : 'danger' }}">
                                        ₱{{ number_format($profit, 2) }} ({{ number_format($profitPct, 1) }}%)
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($product->stock_quantity == 0)
                                    <span class="badge bg-danger">Out of Stock</span>
                                @elseif($product->stock_quantity <= 5)
                                    <span class="badge bg-warning text-dark">{{ $product->stock_quantity }} units</span>
                                @else
                                    <span class="badge bg-success">{{ $product->stock_quantity }} units</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($canSell)
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="bi bi-check-circle"></i> Can Sell
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark px-3 py-2">
                                        <i class="bi bi-lock-fill"></i> No Price
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('products.edit', $product) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('Delete this product?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i> No products yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection