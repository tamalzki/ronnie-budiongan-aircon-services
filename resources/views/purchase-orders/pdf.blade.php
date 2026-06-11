<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Purchase Order {{ $po->display_po_number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: "DejaVu Sans", sans-serif;
        font-size: 11px;
        color: #1f2937;
        padding: 28px 32px;
    }
    .header { width: 100%; border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 14px; }
    .header td { vertical-align: top; }
    h1 { font-size: 20px; color: #1d4ed8; letter-spacing: 1px; }
    .po-meta { text-align: right; font-size: 11px; }
    .po-meta .po-no { font-size: 16px; font-weight: bold; color: #111827; }
    .section-label {
        font-size: 9px; font-weight: bold; letter-spacing: 1px;
        text-transform: uppercase; color: #1d4ed8; margin-bottom: 3px;
    }
    .box {
        border: 1px solid #d1d5db; border-radius: 4px;
        padding: 8px 10px; margin-bottom: 12px;
    }
    table.info { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.info td { vertical-align: top; padding: 0; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.items th {
        background: #eff6ff; color: #1e3a8a; text-transform: uppercase;
        font-size: 9px; letter-spacing: 0.5px; text-align: left;
        padding: 6px 8px; border: 1px solid #bfdbfe;
    }
    table.items td { padding: 6px 8px; border: 1px solid #e5e7eb; }
    .num { text-align: right; white-space: nowrap; }
    .ctr { text-align: center; }
    .totals { width: 45%; margin-left: 55%; border-collapse: collapse; }
    .totals td { padding: 4px 8px; }
    .totals .grand { border-top: 2px solid #1d4ed8; font-size: 13px; font-weight: bold; color: #1d4ed8; }
    .muted { color: #6b7280; }
    .small { font-size: 9.5px; }
    .footer { margin-top: 26px; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 6px; }
</style>
</head>
<body>

{{-- Header --}}
<table class="header">
    <tr>
        <td>
            <h1>PURCHASE ORDER</h1>
            <div class="small muted">{{ $po->status === 'received' ? 'Received' : ucfirst($po->status) }}</div>
        </td>
        <td class="po-meta">
            <div class="po-no">PO No: {{ $po->display_po_number }}</div>
            <div>Order Date: <strong>{{ $po->order_date->format('M d, Y') }}</strong></div>
            @if($po->expected_delivery_date)
                <div>Expected Delivery: {{ $po->expected_delivery_date->format('M d, Y') }}</div>
            @endif
            @if($po->delivery_number)
                <div>Doc No. (DR): {{ $po->delivery_number }}</div>
            @endif
        </td>
    </tr>
</table>

{{-- Sold To / Supplier --}}
<table class="info">
    <tr>
        <td style="width: 49%;">
            <div class="box">
                <div class="section-label">Sold To</div>
                <div style="font-weight: bold;">RONNIE BUDIONGAN AIRCON SUPPLY AND SERVICES, INC</div>
                <div class="small">DOOR 7 SORONGON BUILDING QUEZON AVE. TRES DE MAYO DIGOS DAVAO DEL SUR 8002 PH 11</div>
                <div class="small">TIN: 123-962-440-00000</div>
            </div>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 49%;">
            <div class="box">
                <div class="section-label">Supplier</div>
                <div style="font-weight: bold;">{{ $po->supplier->name }}</div>
                @if($po->supplier->address)<div class="small">{{ $po->supplier->address }}</div>@endif
                @if($po->supplier->contact_number)<div class="small">{{ $po->supplier->contact_number }}</div>@endif
                <div class="small muted" style="margin-top: 3px;">
                    Payment Terms: <strong>{{ $po->payment_type === 'full' ? 'Full Payment' : '45 Days' }}</strong>
                    @if($po->payment_type === '45days' && $po->payment_due_date)
                        — due {{ $po->payment_due_date->format('M d, Y') }}
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- Items --}}
<table class="items">
    <thead>
        <tr>
            <th class="ctr" style="width: 5%;">#</th>
            <th>Item</th>
            <th class="ctr" style="width: 8%;">Qty</th>
            <th class="num" style="width: 13%;">Unit Cost</th>
            <th class="num" style="width: 13%;">Discount</th>
            <th class="num" style="width: 14%;">Net Cost</th>
            <th class="num" style="width: 14%;">Line Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($po->items as $item)
        @php
            $isSet = $item->is_set && $item->product?->pairedProduct;
            $label = $isSet ? $item->product->set_model_label : ($item->product->model ?? '—');
            $discountText = '—';
            if ((float) $item->discount_percent > 0) {
                $discountText = rtrim(rtrim(number_format($item->discount_percent, 2), '0'), '.') . '%';
            } elseif ((float) $item->discount_amount > 0) {
                $discountText = '₱' . number_format($item->discount_amount, 2);
            }
        @endphp
        <tr>
            <td class="ctr">{{ $loop->iteration }}</td>
            <td>
                <strong>{{ trim(($item->product->brand->name ?? '') . ' ' . $label) }}</strong>
                @if($isSet)
                    <div class="small muted">Indoor + Outdoor Set (one price)</div>
                @elseif($item->product?->unit_type)
                    <div class="small muted">{{ ucfirst($item->product->unit_type) }} Unit</div>
                @endif
            </td>
            <td class="ctr">{{ $item->quantity_ordered }}</td>
            <td class="num">₱{{ number_format($item->unit_cost, 2) }}</td>
            <td class="num">{{ $discountText }}</td>
            <td class="num">₱{{ number_format($item->discounted_cost, 2) }}</td>
            <td class="num">₱{{ number_format($item->total_cost, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Totals --}}
<table class="totals">
    <tr>
        <td class="muted">Subtotal</td>
        <td class="num">₱{{ number_format($po->subtotal, 2) }}</td>
    </tr>
    @php $lineDiscounts = $po->items->sum(fn($i) => ($i->unit_cost - $i->discounted_cost) * $i->quantity_ordered); @endphp
    @if($lineDiscounts > 0)
    <tr>
        <td class="muted">Total Discount</td>
        <td class="num">-₱{{ number_format($lineDiscounts, 2) }}</td>
    </tr>
    @endif
    @if((float) $po->tax > 0)
    <tr>
        <td class="muted">Tax</td>
        <td class="num">₱{{ number_format($po->tax, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td class="grand">TOTAL</td>
        <td class="grand num">₱{{ number_format($po->total, 2) }}</td>
    </tr>
</table>

@if($po->notes)
<div class="box" style="margin-top: 8px;">
    <div class="section-label">Notes</div>
    <div class="small">{{ $po->notes }}</div>
</div>
@endif

<div class="footer">
    Generated {{ now()->format('M d, Y h:i A') }}{{ $po->user ? ' · Encoded by ' . $po->user->name : '' }} · {{ $po->po_number }}
</div>

</body>
</html>
