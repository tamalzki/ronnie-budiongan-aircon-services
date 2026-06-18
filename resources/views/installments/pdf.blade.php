<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Installment Ledger - {{ $customer['name'] }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: "DejaVu Sans Mono", "DejaVu Sans", monospace;
        font-size: 8.5px;
        color: #111;
        padding: 12px 14px;
    }
    .paper-ledger { width: 100%; }
    .pl-company-header { text-align: center; margin-bottom: 6px; }
    .pl-company-name { font-weight: bold; font-size: 11px; }
    .pl-company-address { font-size: 8px; margin-top: 1px; }
    .pl-title-row { position: relative; text-align: center; margin-top: 5px; height: 18px; }
    .pl-title { font-weight: bold; font-size: 12px; text-decoration: underline; }
    .pl-account-ref {
        position: absolute; right: 0; top: 0;
        border: 1px solid #111; padding: 2px 6px;
        font-weight: bold; font-size: 9px;
    }
    .pl-info-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    .pl-info-left, .pl-info-right { width: 50%; vertical-align: top; }
    .pl-kv { width: 100%; border-collapse: collapse; }
    .pl-kv td { padding: 1px 3px 1px 0; font-size: 8.5px; vertical-align: top; }
    .pl-k { white-space: nowrap; width: 1%; }
    .pl-v { font-weight: bold; }
    .pl-num { text-align: right; }
    .pl-ledger-table { width: 100%; border-collapse: collapse; font-size: 7.5px; }
    .pl-ledger-table th, .pl-ledger-table td {
        border: 1px solid #111; padding: 2px 3px; vertical-align: middle;
    }
    .pl-ledger-table .pl-h1 th, .pl-ledger-table .pl-h2 th {
        font-weight: bold; text-align: center; font-size: 7px; line-height: 1.15;
    }
    .pl-ctr { text-align: center; }
    .pl-mono { font-size: 7px; }
    .pl-bold { font-weight: bold; }
    .pl-aging-table { width: 100%; border-collapse: collapse; margin-top: -1px; font-size: 7.5px; }
    .pl-aging-table th, .pl-aging-table td {
        border: 1px solid #111; padding: 3px 2px; text-align: center;
    }
    .pl-aging-table th { font-weight: bold; font-size: 7px; line-height: 1.1; }
    .pl-end-record { text-align: center; font-weight: bold; font-size: 9px; margin-top: 6px; }
</style>
</head>
<body>

@php
    $mode = 'print';
    $showActions = false;
    $fmt = fn ($n) => number_format((float) $n, 2);
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('m/d/Y') : '';
    $monthly = $summary['monthly_amortization'];
    $monthlyDisplay = is_numeric($monthly) ? $fmt($monthly) : ($monthly ?? '');
    $header = $header ?? [];
@endphp

<div class="paper-ledger">

    <div class="pl-company-header">
        <div class="pl-company-name">RONNIE BUDIONGAN AIRCON SUPPLY AND SERVICES, INC</div>
        <div class="pl-company-address">DOOR 7 SORONGON BUILDING QUEZON AVE. TRES DE MAYO DIGOS DAVAO DEL SUR 8002</div>
        <div class="pl-title-row">
            <div class="pl-title">Installment Ledger</div>
            <div class="pl-account-ref">{{ $header['account_ref'] ?? '' }}</div>
        </div>
    </div>

    <table class="pl-info-table">
        <tr>
            <td class="pl-info-left">
                <table class="pl-kv">
                    <tr><td class="pl-k">Name of Customer</td><td class="pl-v">{{ $customer['name'] }}</td></tr>
                    <tr><td class="pl-k">Name of Comaker</td><td class="pl-v">—</td></tr>
                    <tr><td class="pl-k">Address</td><td class="pl-v">{{ $customer['address'] ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Unit Acquired</td><td class="pl-v">{{ $header['unit_acquired'] ?? '—' }}</td></tr>
                    <tr><td class="pl-k">Model</td><td class="pl-v">{{ $header['model'] ?? '—' }}</td></tr>
                    <tr><td class="pl-k">Serial No.</td><td class="pl-v">{{ $header['serial_numbers'] ?? '—' }}</td></tr>
                    <tr><td class="pl-k">Date Delivered</td><td class="pl-v">{{ $fmtDate($header['date_delivered'] ?? null) ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Monthly/Daily</td><td class="pl-v">{{ $monthlyDisplay ?: '—' }}</td></tr>
                    <tr><td class="pl-k">Accessories</td><td class="pl-v">{{ $header['accessories'] ?? '—' }}</td></tr>
                </table>
            </td>
            <td class="pl-info-right">
                <table class="pl-kv">
                    <tr><td class="pl-k">Tel. No.</td><td class="pl-v">{{ $customer['contact'] ?: '—' }}</td></tr>
                    <tr><td class="pl-k">LCP/SRP</td><td class="pl-v pl-num">{{ $fmt($header['lcp_srp'] ?? 0) }}</td></tr>
                    <tr><td class="pl-k">D/P</td><td class="pl-v pl-num">{{ $fmt($summary['down_payment']) }}</td></tr>
                    <tr><td class="pl-k">Inv. No.</td><td class="pl-v">{{ $header['invoice_no'] ?? '—' }}</td></tr>
                    <tr><td class="pl-k">Inv. Amt.</td><td class="pl-v pl-num">{{ $fmt($summary['original_contract_amount']) }}</td></tr>
                    <tr><td class="pl-k">Term</td><td class="pl-v">{{ $header['term_months'] ?? $summary['total_terms'] }} Months</td></tr>
                    <tr><td class="pl-k">Rebate</td><td class="pl-v pl-num">{{ ($header['rebate'] ?? 0) > 0 ? $fmt($header['rebate']) : '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="pl-ledger-table">
        <thead>
            <tr class="pl-h1">
                <th rowspan="2">Bill<br>No.</th>
                <th rowspan="2">Ins.<br>Date</th>
                <th rowspan="2">Date<br>Paid</th>
                <th rowspan="2">O.R.<br>No.</th>
                <th colspan="3">PAYMENTS</th>
                <th rowspan="2">Outstanding<br>Balance</th>
            </tr>
            <tr class="pl-h2">
                <th>Amount Paid</th>
                <th>Rebate</th>
                <th>Total Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledgerRows as $row)
            <tr>
                <td class="pl-ctr">{{ $row['bill_no'] }}</td>
                <td class="pl-ctr">{{ $fmtDate($row['due_date']) }}</td>
                <td class="pl-ctr">{{ $row['paid_date'] ? $fmtDate($row['paid_date']) : '' }}</td>
                <td class="pl-ctr pl-mono">{{ $row['reference_number'] ?? '' }}</td>
                <td class="pl-num">{{ $row['amount_paid'] > 0 ? $fmt($row['amount_paid']) : '' }}</td>
                <td class="pl-num">{{ $row['rebate'] > 0 ? $fmt($row['rebate']) : '' }}</td>
                <td class="pl-num">{{ $row['total_credit'] > 0 ? $fmt($row['total_credit']) : '' }}</td>
                <td class="pl-num pl-bold">{{ $fmt($row['remaining_balance']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

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
            <tr>
                <td class="pl-num">{{ $fmt($aging['current']) }}</td>
                <td class="pl-num">{{ $fmt($aging['days_1_30']) }}</td>
                <td class="pl-num">{{ $fmt($aging['days_31_60']) }}</td>
                <td class="pl-num">{{ $fmt($aging['days_61_90']) }}</td>
                <td class="pl-num">{{ $fmt($aging['days_90_up']) }}</td>
                <td class="pl-num pl-bold">{{ $fmt($aging['total_overdue']) }}</td>
                <td class="pl-num">{{ $fmt($summary['advance_payment']) }}</td>
                <td class="pl-num">{{ $monthlyDisplay }}</td>
                <td class="pl-num pl-bold">{{ $fmt($summary['current_balance']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="pl-end-record">*** End of Record ***</div>

</div>

</body>
</html>
