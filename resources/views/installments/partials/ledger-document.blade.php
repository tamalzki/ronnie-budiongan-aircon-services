{{--
    Traditional installment ledger document — matches paper financing ledger layout.
    Expects: $customer, $header, $summary, $ledgerRows, $aging, $sales, $installments
    Optional: $mode ('screen'|'print'), $showActions (bool), $enhanced (bool)
--}}
@php
    $mode = $mode ?? 'screen';
    $showActions = $showActions ?? ($mode === 'screen');
    $enhanced = $enhanced ?? ($mode === 'screen');
    $fmt = fn ($n) => number_format((float) $n, 2);
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('m/d/Y') : '';
    $monthly = $summary['monthly_amortization'];
    $monthlyDisplay = is_numeric($monthly) ? $fmt($monthly) : ($monthly ?? '');
    $progressPct = $summary['original_contract_amount'] > 0
        ? min(100, round(($summary['total_paid'] / $summary['original_contract_amount']) * 100, 1))
        : 0;
    $accountHealth = $aging['total_overdue'] > 0
        ? ['label' => 'Overdue', 'class' => 'danger']
        : ($summary['advance_payment'] > 0
            ? ['label' => 'In Advance', 'class' => 'success']
            : ($summary['current_balance'] <= 0
                ? ['label' => 'Fully Paid', 'class' => 'success']
                : ['label' => 'Current', 'class' => 'primary']));
@endphp

@if($enhanced)
<div class="ledger-enhancements no-print">
    <div class="ledger-enhancements-top">
        <div class="ledger-health">
            <span class="ledger-health-badge bg-{{ $accountHealth['class'] }}">{{ $accountHealth['label'] }}</span>
            <span class="ledger-health-name">{{ $customer['name'] }}</span>
            @if($showActions && isset($anchorSale))
            <button type="button" class="btn btn-outline-secondary btn-sm no-print"
                    data-bs-toggle="modal" data-bs-target="#editCustomerModal" title="Edit customer">
                <i class="bi bi-pencil"></i> Edit
            </button>
            @endif
        </div>
        <div class="ledger-kpis">
            <div class="ledger-kpi">
                <span class="ledger-kpi-label">Paid</span>
                <span class="ledger-kpi-value text-success">{{ $fmt($summary['total_paid']) }}</span>
            </div>
            <div class="ledger-kpi">
                <span class="ledger-kpi-label">Balance</span>
                <span class="ledger-kpi-value {{ $summary['current_balance'] > 0 ? 'text-danger' : 'text-success' }}">{{ $fmt($summary['current_balance']) }}</span>
            </div>
            <div class="ledger-kpi">
                <span class="ledger-kpi-label">Advance</span>
                <span class="ledger-kpi-value text-success">{{ $fmt($summary['advance_payment']) }}</span>
            </div>
            <div class="ledger-kpi">
                <span class="ledger-kpi-label">Overdue</span>
                <span class="ledger-kpi-value {{ $aging['total_overdue'] > 0 ? 'text-danger' : '' }}">{{ $fmt($aging['total_overdue']) }}</span>
            </div>
        </div>
    </div>
    <div class="ledger-progress-wrap">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="ledger-progress-label">Payment Progress</span>
            <span class="ledger-progress-pct">{{ $progressPct }}% of {{ $fmt($summary['original_contract_amount']) }}</span>
        </div>
        <div class="ledger-progress">
            <div class="ledger-progress-bar" style="width: {{ $progressPct }}%"></div>
        </div>
        <div class="d-flex justify-content-between mt-1 ledger-progress-meta">
            <span>{{ $summary['installments_paid'] }} paid</span>
            <span>{{ $summary['installments_remaining'] }} remaining</span>
            @if($summary['next_due_date'])
            <span>Next due {{ $summary['next_due_date']->format('M d, Y') }}</span>
            @endif
        </div>
    </div>
    <div class="ledger-filters">
        <span class="ledger-filters-label">Show:</span>
        <button type="button" class="ledger-filter-btn active" data-filter="all">All</button>
        <button type="button" class="ledger-filter-btn" data-filter="paid">Paid</button>
        <button type="button" class="ledger-filter-btn" data-filter="partial">Partial</button>
        <button type="button" class="ledger-filter-btn" data-filter="overdue">Overdue</button>
        <button type="button" class="ledger-filter-btn" data-filter="pending">Upcoming</button>
    </div>
</div>
@endif

<div class="paper-ledger {{ $mode === 'print' ? 'paper-ledger-print' : '' }} {{ $enhanced ? 'paper-ledger-enhanced' : '' }}">

    {{-- Company header --}}
    <div class="pl-company-header">
        <div class="pl-company-name">RONNIE BUDIONGAN AIRCON SUPPLY AND SERVICES, INC</div>
        <div class="pl-company-address">DOOR 7 SORONGON BUILDING QUEZON AVE. TRES DE MAYO DIGOS DAVAO DEL SUR 8002</div>
        <div class="pl-title-row">
            <div class="pl-title">Installment Ledger</div>
            <div class="pl-account-ref">{{ $header['account_ref'] }}</div>
        </div>
    </div>

    {{-- Customer & contract info (two columns like paper form) --}}
    <table class="pl-info-table">
        <tr>
            <td class="pl-info-left">
                <table class="pl-kv">
                    <tr>
                        <td class="pl-k">Name of Customer</td>
                        <td class="pl-v">
                            {{ $customer['name'] }}
                            @if($showActions && isset($anchorSale))
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-1 align-baseline no-print"
                                    data-bs-toggle="modal" data-bs-target="#editCustomerModal" title="Edit customer">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            @endif
                        </td>
                    </tr>
                    <tr><td class="pl-k">Name of Comaker</td><td class="pl-v">—</td></tr>
                    <tr><td class="pl-k">Address</td><td class="pl-v">{{ $customer['address'] ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Unit Acquired</td><td class="pl-v">{{ $header['unit_acquired'] ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Model</td><td class="pl-v">{{ $header['model'] ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Serial No.</td><td class="pl-v">{{ $header['serial_numbers'] ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Date Delivered</td><td class="pl-v">{{ $fmtDate($header['date_delivered']) ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Monthly/Daily</td><td class="pl-v">{{ $monthlyDisplay ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Accessories</td><td class="pl-v">{{ $header['accessories'] ?: '—' }}</td></tr>
                </table>
            </td>
            <td class="pl-info-right">
                <table class="pl-kv">
                    <tr><td class="pl-k">Tel. No.</td><td class="pl-v">{{ $customer['contact'] ?: '—' }}</td></tr>
                    <tr><td class="pl-k">LCP/SRP</td><td class="pl-v pl-num">{{ $fmt($header['lcp_srp']) }}</td></tr>
                    <tr><td class="pl-k">D/P</td><td class="pl-v pl-num">{{ $fmt($summary['down_payment']) }}</td></tr>
                    <tr><td class="pl-k">Inv. No.</td><td class="pl-v">{{ $header['invoice_no'] ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Inv. Amt.</td><td class="pl-v pl-num">{{ $fmt($summary['original_contract_amount']) }}</td></tr>
                    <tr><td class="pl-k">Term</td><td class="pl-v">{{ $header['term_months'] }} Months</td></tr>
                    <tr><td class="pl-k">Rebate</td><td class="pl-v pl-num">{{ $header['rebate'] > 0 ? $fmt($header['rebate']) : '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Main ledger table --}}
    <div class="pl-table-wrap">
        <table class="pl-ledger-table">
            <thead>
                <tr class="pl-h1">
                    <th rowspan="2">Bill<br>No.</th>
                    <th rowspan="2">Ins.<br>Date</th>
                    <th rowspan="2">Date<br>Paid</th>
                    <th rowspan="2">O.R.<br>No.</th>
                    <th colspan="3" class="pl-payments-group">PAYMENTS</th>
                    <th rowspan="2">Outstanding<br>Balance</th>
                    @if($showActions)
                    <th rowspan="2" class="no-print">Actions</th>
                    @endif
                </tr>
                <tr class="pl-h2">
                    <th>Amount Paid</th>
                    <th>Rebate</th>
                    <th>Total Credit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledgerRows as $row)
                @php
                    $installment = $row['installment'];
                    $rowStatus = $row['status'];
                    $rowClass = $enhanced ? 'pl-row-' . $rowStatus : '';
                @endphp
                <tr class="{{ $rowClass }}" data-status="{{ $rowStatus }}">
                    <td class="pl-ctr">
                        @if($enhanced && $rowStatus !== 'pending')
                        <span class="pl-status-dot pl-status-{{ $rowStatus }}" title="{{ ucfirst($rowStatus) }}"></span>
                        @endif
                        {{ $row['bill_no'] }}
                    </td>
                    <td class="pl-ctr">{{ $fmtDate($row['due_date']) }}</td>
                    <td class="pl-ctr">{{ $row['paid_date'] ? $fmtDate($row['paid_date']) : '' }}</td>
                    <td class="pl-ctr pl-mono">{{ $row['reference_number'] ?? '' }}</td>
                    <td class="pl-num pl-paid-cell">{{ $row['amount_paid'] > 0 ? $fmt($row['amount_paid']) : '' }}</td>
                    <td class="pl-num">{{ $row['rebate'] > 0 ? $fmt($row['rebate']) : '' }}</td>
                    <td class="pl-num pl-credit-cell" @if($enhanced) title="Running total paid including rebates" @endif>{{ $row['total_credit'] > 0 ? $fmt($row['total_credit']) : '' }}</td>
                    <td class="pl-num pl-bold pl-balance-cell {{ $row['remaining_balance'] > 0 ? 'pl-balance-due' : 'pl-balance-clear' }}">{{ $fmt($row['remaining_balance']) }}</td>
                    @if($showActions)
                    <td class="pl-ctr no-print pl-actions">
                        @if($row['status'] !== 'paid')
                        <button type="button" class="btn btn-success btn-sm py-0 px-1"
                                style="font-size:0.68rem" data-bs-toggle="modal"
                                data-bs-target="#payModal{{ $installment->id }}">Pay</button>
                        @else
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1"
                                style="font-size:0.68rem" data-bs-toggle="modal"
                                data-bs-target="#editModal{{ $installment->id }}">Edit</button>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $showActions ? 9 : 8 }}" class="pl-ctr" style="padding:20px;">No records</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Aging footer (exact paper layout) --}}
    <table class="pl-aging-table">
        <thead>
            <tr>
                <th rowspan="2">CURRENT</th>
                <th colspan="4">OVER DUE</th>
                <th rowspan="2">TOTAL<br>OVERDUE</th>
                <th rowspan="2">ADVANCE<br>PAYMENT</th>
                <th rowspan="2">MONTHLY<br>INSTALLMENT</th>
                <th rowspan="2">OUTSTANDING<br>BALANCE</th>
            </tr>
            <tr>
                <th>1–30 DAYS</th>
                <th>31–60 DAYS</th>
                <th>61–90 DAYS</th>
                <th>90 DAYS &amp; Up</th>
            </tr>
        </thead>
        <tbody>
            <tr class="pl-aging-values">
                <td class="pl-num">{{ $fmt($aging['current']) }}</td>
                <td class="pl-num {{ $aging['days_1_30'] > 0 ? 'pl-overdue-val' : '' }}">{{ $fmt($aging['days_1_30']) }}</td>
                <td class="pl-num {{ $aging['days_31_60'] > 0 ? 'pl-overdue-val' : '' }}">{{ $fmt($aging['days_31_60']) }}</td>
                <td class="pl-num {{ $aging['days_61_90'] > 0 ? 'pl-overdue-val' : '' }}">{{ $fmt($aging['days_61_90']) }}</td>
                <td class="pl-num {{ $aging['days_90_up'] > 0 ? 'pl-overdue-val' : '' }}">{{ $fmt($aging['days_90_up']) }}</td>
                <td class="pl-num pl-bold {{ $aging['total_overdue'] > 0 ? 'pl-overdue-val' : '' }}">{{ $fmt($aging['total_overdue']) }}</td>
                <td class="pl-num {{ $summary['advance_payment'] > 0 ? 'pl-advance-val' : '' }}">{{ $fmt($summary['advance_payment']) }}</td>
                <td class="pl-num">{{ $monthlyDisplay }}</td>
                <td class="pl-num pl-bold {{ $summary['current_balance'] > 0 ? 'pl-balance-due' : 'pl-balance-clear' }}">{{ $fmt($summary['current_balance']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="pl-end-record">*** End of Record ***</div>

    @if($enhanced)
    <div class="pl-legend no-print">
        <span><i class="pl-status-dot pl-status-paid"></i> Paid</span>
        <span><i class="pl-status-dot pl-status-partial"></i> Partial</span>
        <span><i class="pl-status-dot pl-status-overdue"></i> Overdue</span>
        <span><i class="pl-status-dot pl-status-pending"></i> Upcoming</span>
    </div>
    @endif
</div>
