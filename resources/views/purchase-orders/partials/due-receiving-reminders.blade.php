@if(!empty($dueReceivingOrders) && $dueReceivingOrders->isNotEmpty())
    @php
        $currentPoId = request()->route('purchase_order')?->id;
        $openedAuto = false;
    @endphp

    @foreach($dueReceivingOrders as $po)
        @if(request()->routeIs('purchase-orders.show') && $currentPoId === $po->id)
            @continue
        @endif

        @include('purchase-orders.partials.receive-modal', [
            'purchaseOrder' => $po,
            'modalId'       => 'dueReceiveModal' . $po->id,
            'autoOpen'      => !$openedAuto,
        ])

        @php if (!$openedAuto) { $openedAuto = true; } @endphp
    @endforeach

    @include('purchase-orders.partials.receive-modal-scripts')
@endif
