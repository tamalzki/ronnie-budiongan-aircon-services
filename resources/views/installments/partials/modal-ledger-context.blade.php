{{-- Read-only ledger row context for pay/edit modals --}}
@php
    $fmt = fn ($n) => number_format((float) $n, 2);
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('m/d/Y') : '—';
    $arrAdv = '';
    if (($row['arrears_advance'] ?? 0) > 0) {
        $arrAdv = $fmt($row['arrears_advance']);
    } elseif (($row['arrears_advance'] ?? 0) < 0) {
        $arrAdv = '(' . $fmt(abs($row['arrears_advance'])) . ')';
    } else {
        $arrAdv = '—';
    }
    $st = $row['status'] ?? 'pending';
    $statusMap = [
        'paid'    => ['Paid', 'success'],
        'partial' => ['Partial', 'warning'],
        'overdue' => ['Overdue', 'danger'],
        'pending' => ['Upcoming', 'secondary'],
    ];
    [$statusLabel, $statusColor] = $statusMap[$st] ?? ['Pending', 'secondary'];
    $lineRemaining = round((float) $installment->amount - (float) $installment->amount_paid, 2);
@endphp

<div class="modal-ledger-context mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div>
            <span class="text-muted" style="font-size:0.72rem;">Bill No.</span>
            <strong class="ms-1">{{ $row['bill_no'] ?? $installment->installment_number }}</strong>
            @if($installment->sale?->invoice_number)
            <span class="text-muted ms-2" style="font-size:0.72rem;">· {{ $installment->sale->invoice_number }}</span>
            @endif
        </div>
        <span class="badge bg-{{ $statusColor }}">{{ $statusLabel }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 modal-ledger-table">
            <thead>
                <tr>
                    <th>Ins. Date</th>
                    <th class="text-end">Ins. Mons.</th>
                    <th class="text-end">Arr./Adv.</th>
                    <th class="text-end">Total Amt. Due</th>
                    <th class="text-end">Line Balance</th>
                    <th class="text-end">Acct. Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $fmtDate($row['due_date'] ?? $installment->due_date) }}</td>
                    <td class="text-end fw-semibold">{{ $fmt($row['monthly_due'] ?? $installment->amount) }}</td>
                    <td class="text-end {{ ($row['arrears_advance'] ?? 0) > 0 ? 'text-danger' : (($row['arrears_advance'] ?? 0) < 0 ? 'text-success' : '') }}">{{ $arrAdv }}</td>
                    <td class="text-end">{{ $fmt($row['total_amount_due'] ?? $installment->amount) }}</td>
                    <td class="text-end text-danger fw-semibold">{{ $fmt($lineRemaining) }}</td>
                    <td class="text-end fw-bold {{ ($row['remaining_balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ $fmt($row['remaining_balance'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @if(($row['total_credit'] ?? 0) > 0)
    <div class="d-flex flex-wrap gap-3 mt-2" style="font-size:0.78rem;">
        <span><span class="text-muted">Total Credit:</span> <strong>{{ $fmt($row['total_credit']) }}</strong></span>
        @if($installment->paid_date)
        <span><span class="text-muted">Last Paid:</span> <strong>{{ $fmtDate($installment->paid_date) }}</strong></span>
        @endif
        @if($installment->reference_number)
        <span><span class="text-muted">O.R. No.:</span> <strong class="font-monospace">{{ $installment->reference_number }}</strong></span>
        @endif
    </div>
    @endif
</div>
