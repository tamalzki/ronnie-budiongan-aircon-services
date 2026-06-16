@extends('layouts.app')

@section('title', 'Installment Ledger - ' . $customer['name'])

@push('styles')
<style>
    .ledger-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    /* ── Digital enhancements (screen only) ── */
    .ledger-enhancements {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .ledger-enhancements-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .ledger-health { display: flex; align-items: center; gap: 0.5rem; }
    .ledger-health-badge {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        color: #fff;
    }
    .ledger-health-name { font-weight: 600; font-size: 0.95rem; color: #1f2937; }
    .ledger-kpis { display: flex; flex-wrap: wrap; gap: 1rem 1.5rem; }
    .ledger-kpi { display: flex; flex-direction: column; line-height: 1.2; }
    .ledger-kpi-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #9ca3af;
    }
    .ledger-kpi-value { font-size: 0.95rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .ledger-progress-wrap { margin-bottom: 0.65rem; }
    .ledger-progress-label { font-size: 0.72rem; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.03em; }
    .ledger-progress-pct { font-size: 0.78rem; color: #6b7280; font-variant-numeric: tabular-nums; }
    .ledger-progress {
        height: 8px;
        background: #e5e7eb;
        border-radius: 999px;
        overflow: hidden;
    }
    .ledger-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #059669);
        border-radius: 999px;
        transition: width 0.4s ease;
    }
    .ledger-progress-meta { font-size: 0.72rem; color: #6b7280; }
    .ledger-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; }
    .ledger-filters-label { font-size: 0.72rem; color: #6b7280; margin-right: 0.25rem; }
    .ledger-filter-btn {
        border: 1px solid #d1d5db;
        background: #fff;
        border-radius: 999px;
        padding: 0.15rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 500;
        color: #374151;
        cursor: pointer;
        transition: all 0.15s;
    }
    .ledger-filter-btn:hover { border-color: #6366f1; color: #4338ca; }
    .ledger-filter-btn.active {
        background: #4f46e5;
        border-color: #4f46e5;
        color: #fff;
    }

    /* ── Paper ledger (print layout preserved) ── */
    .paper-ledger {
        background: #fff;
        border: 2px solid #111;
        padding: 14px 16px 10px;
        font-family: "Courier New", Courier, monospace;
        font-size: 11px;
        color: #111;
        line-height: 1.35;
    }
    .paper-ledger-enhanced {
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        border-color: #374151;
    }
    .pl-company-header { text-align: center; margin-bottom: 8px; }
    .pl-company-name { font-weight: bold; font-size: 12px; letter-spacing: 0.02em; }
    .pl-company-address { font-size: 10px; margin-top: 1px; }
    .pl-title-row {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-top: 6px;
    }
    .pl-title {
        font-weight: bold;
        font-size: 13px;
        text-decoration: underline;
        letter-spacing: 0.05em;
    }
    .pl-account-ref {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        border: 1.5px solid #111;
        padding: 2px 8px;
        font-weight: bold;
        font-size: 11px;
        font-family: "Courier New", Courier, monospace;
    }
    .pl-info-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .pl-info-left, .pl-info-right { width: 50%; vertical-align: top; padding: 0; }
    .pl-kv { width: 100%; border-collapse: collapse; }
    .pl-kv td { padding: 1px 4px 1px 0; vertical-align: top; font-size: 10.5px; }
    .pl-k {
        white-space: nowrap;
        width: 1%;
        padding-right: 6px !important;
    }
    .pl-k::after { content: ':'; }
    .pl-v { font-weight: bold; }
    .pl-num { text-align: right; font-variant-numeric: tabular-nums; }
    .pl-table-wrap { overflow-x: auto; margin-bottom: 0; }
    .pl-ledger-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
    }
    .pl-ledger-table th,
    .pl-ledger-table td {
        border: 1px solid #111;
        padding: 3px 4px;
        vertical-align: middle;
    }
    .pl-ledger-table .pl-h1 th,
    .pl-ledger-table .pl-h2 th {
        background: #fff;
        font-weight: bold;
        text-align: center;
        font-size: 9px;
        line-height: 1.2;
    }
    .paper-ledger-enhanced .pl-payments-group { background: #f0fdf4 !important; }
    .pl-ctr { text-align: center; }
    .pl-mono { font-family: "Courier New", Courier, monospace; font-size: 9.5px; }
    .pl-bold { font-weight: bold; }
    .pl-actions { white-space: nowrap; }

    /* Row status (enhanced screen) */
    .pl-row-paid    { background: #f0fdf4 !important; }
    .pl-row-partial { background: #fffbeb !important; }
    .pl-row-overdue { background: #fef2f2 !important; }
    .pl-row-pending { background: #fff !important; }
    .pl-status-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 3px;
        vertical-align: middle;
    }
    .pl-status-paid    { background: #10b981; }
    .pl-status-partial { background: #f59e0b; }
    .pl-status-overdue { background: #ef4444; }
    .pl-status-pending { background: #9ca3af; }
    .pl-arrears    { color: #dc2626; font-weight: bold; }
    .pl-advance    { color: #059669; font-weight: bold; }
    .pl-advance-val { color: #059669; font-weight: bold; }
    .pl-overdue-val { color: #dc2626; font-weight: bold; }
    .pl-balance-due   { color: #dc2626; }
    .pl-balance-clear { color: #059669; }
    .pl-paid-cell  { color: #047857; }
    .pl-credit-cell { color: #1d4ed8; }

    .pl-aging-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: -1px;
        font-size: 9.5px;
    }
    .pl-aging-table th,
    .pl-aging-table td {
        border: 1px solid #111;
        padding: 4px 3px;
        text-align: center;
        vertical-align: middle;
    }
    .pl-aging-table th {
        font-weight: bold;
        font-size: 8.5px;
        line-height: 1.15;
    }
    .paper-ledger-enhanced .pl-aging-table thead tr:first-child th:nth-child(n+2):nth-child(-n+5) {
        background: #fef2f2;
    }
    .pl-end-record {
        text-align: center;
        font-weight: bold;
        font-size: 10px;
        margin-top: 8px;
        letter-spacing: 0.05em;
    }
    .pl-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
        justify-content: center;
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px dashed #d1d5db;
        font-family: 'Inter', sans-serif;
        font-size: 0.72rem;
        color: #6b7280;
    }
    .pl-legend-sep { color: #d1d5db; }

    .pl-ledger-table tr[data-hidden="1"] { display: none; }

    .modal-ledger-context { font-size: 0.82rem; }
    .modal-ledger-table { font-size: 0.75rem; }
    .modal-ledger-table th {
        background: #f8fafc;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.68rem;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }

    @media print {
        .no-print { display: none !important; }
        .paper-ledger { border: none; padding: 0; box-shadow: none; }
        .pl-row-paid, .pl-row-partial, .pl-row-overdue, .pl-row-pending { background: #fff !important; }
        .pl-arrears, .pl-advance, .pl-advance-val, .pl-overdue-val,
        .pl-balance-due, .pl-balance-clear, .pl-paid-cell, .pl-credit-cell { color: #111 !important; }
        body { background: #fff !important; }
        .sidebar, .top-navbar { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <div class="ledger-toolbar no-print">
        <div>
            <a href="{{ route('installments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Installments
            </a>
        </div>
        <div class="d-flex flex-wrap gap-1 align-items-center">
            @foreach($sales as $s)
            <button class="btn btn-outline-primary btn-sm"
                    data-bs-toggle="modal" data-bs-target="#editPlanModal{{ $s->id }}">
                <i class="bi bi-sliders"></i> Edit Plan{{ $sales->count() > 1 ? ' — ' . $s->invoice_number : '' }}
            </button>
            @endforeach
            <a href="{{ route('installments.pdf', $sales->first()) }}" class="btn btn-dark btn-sm">
                <i class="bi bi-printer"></i> Print Ledger
            </a>
            <button type="button" class="btn btn-outline-dark btn-sm" onclick="window.print()">
                <i class="bi bi-display"></i> Print Page
            </button>
        </div>
    </div>

    <x-flash />

    @include('installments.partials.ledger-document', [
        'mode' => 'screen',
        'showActions' => true,
        'enhanced' => true,
    ])

</div>

@include('installments.partials.modals', [
    'sales' => $sales,
    'ledgerRows' => $ledgerRows,
    'summary' => $summary,
])

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pay-method-select').forEach(function (select) {
        const chequeFields = select.closest('.modal-body').querySelector('.pay-cheque-fields');
        const toggle = () => {
            if (chequeFields) chequeFields.style.display = select.value === 'cheque' ? '' : 'none';
        };
        select.addEventListener('change', toggle);
        toggle();
    });

    document.querySelectorAll('.ledger-filter-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.ledger-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filter = btn.dataset.filter;
            document.querySelectorAll('.pl-ledger-table tbody tr[data-status]').forEach(function (row) {
                row.dataset.hidden = (filter === 'all' || row.dataset.status === filter) ? '0' : '1';
            });
        });
    });

    function formatMoney(n) {
        return Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updatePayPreview(input) {
        const previewId = input.dataset.previewId;
        if (!previewId) return;
        const preview = document.getElementById(previewId);
        if (!preview) return;
        const contract = parseFloat(preview.dataset.contract) || 0;
        const creditBefore = parseFloat(preview.dataset.creditBefore) || 0;
        const amount = parseFloat(input.value) || 0;
        const creditAfter = creditBefore + amount;
        const balanceAfter = Math.max(0, contract - creditAfter);
        const creditEl = preview.querySelector('.pay-preview-credit');
        const balanceEl = preview.querySelector('.pay-preview-balance');
        if (creditEl) creditEl.textContent = formatMoney(creditAfter);
        if (balanceEl) balanceEl.textContent = formatMoney(balanceAfter);
    }

    document.querySelectorAll('.pay-amount-input').forEach(function (input) {
        input.addEventListener('input', () => updatePayPreview(input));
        updatePayPreview(input);
    });

    document.querySelectorAll('[id^="payModal"]').forEach(function (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            const input = modalEl.querySelector('.pay-amount-input');
            if (input) updatePayPreview(input);
        });
    });
});
</script>
@endpush

@endsection
