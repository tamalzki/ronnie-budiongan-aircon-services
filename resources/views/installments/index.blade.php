@extends('layouts.app')

@section('title', 'Installment Customers')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="mb-1"><i class="bi bi-people-fill text-primary"></i> Installment Customers</h2>
        <p class="text-muted mb-0">Track customer installment payments</p>
    </div>

    <!-- Customers List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-4 py-3">Customer</th>
                            <th class="border-0 px-4 py-3">Contact</th>
                            <th class="border-0 px-4 py-3">Sales Count</th>
                            <th class="border-0 px-4 py-3">Total Amount</th>
                            <th class="border-0 px-4 py-3">Total Paid</th>
                            <th class="border-0 px-4 py-3">Balance</th>
                            <th class="border-0 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customersData as $customer)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                        <i class="bi bi-person-fill fs-5 text-primary"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $customer->customer_name }}</strong>
                                        @if($customer->customer_address)
                                        <br><small class="text-muted">{{ $customer->customer_address }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $customer->customer_contact ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="badge bg-info">{{ $customer->sales_count }} sale(s)</span>
                            </td>
                            <td class="px-4 py-3">
                                <strong>₱{{ number_format($customer->total_amount, 2) }}</strong>
                            </td>
                            <td class="px-4 py-3 text-success">
                                <strong>₱{{ number_format($customer->total_paid, 2) }}</strong>
                            </td>
                            <td class="px-4 py-3">
                                <strong class="{{ $customer->total_balance > 0 ? 'text-danger' : 'text-success' }}">
                                    ₱{{ number_format($customer->total_balance, 2) }}
                                </strong>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('installments.show', $customer->first_sale_id) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-calendar-check"></i> View Installments
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    <p class="mb-0">No installment customers yet</p>
                                    <small>Customers with installment payments will appear here</small>
                                </div>
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