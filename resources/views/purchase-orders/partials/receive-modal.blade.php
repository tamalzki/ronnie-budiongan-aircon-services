@php
    $modalId = $modalId ?? 'receiveModal';
    $itemsToReceive = app(\App\Services\PurchaseOrderDueReceivingService::class)
        ->productItemsToReceive($purchaseOrder);
    $autoOpen = $autoOpen ?? false;
@endphp

@if($purchaseOrder->status !== 'cancelled' && $itemsToReceive->count() > 0)
<div class="modal fade po-due-receive-modal {{ $autoOpen ? 'auto-open-due-receive' : '' }}"
     id="{{ $modalId }}"
     tabindex="-1"
     aria-labelledby="{{ $modalId }}Label"
     aria-hidden="true"
     data-po-due-receive="1"
     data-po-id="{{ $purchaseOrder->id }}">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST" class="po-receive-form" data-modal-id="{{ $modalId }}">
                @csrf
                <div class="modal-header border-0 text-dark" style="background:#fffbeb;">
                    <h5 class="modal-title" id="{{ $modalId }}Label" style="font-size:1rem;">
                        <i class="bi bi-box-arrow-in-down text-warning"></i>
                        Order Receive — PO No: {{ $purchaseOrder->display_po_number }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="font-size:0.875rem;">
                    @if($purchaseOrder->expected_delivery_date)
                    <div class="alert alert-warning py-2 px-3 mb-3 border-0" style="font-size:0.82rem;">
                        <i class="bi bi-calendar-check"></i>
                        Expected delivery was <strong>{{ $purchaseOrder->expected_delivery_date->format('M d, Y') }}</strong>.
                        Please confirm if stock has arrived and encode serial numbers for inventory.
                    </div>
                    @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Received Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="received_date"
                                   value="{{ old('received_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Document No. (DR) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="delivery_number"
                                   value="{{ old('delivery_number', $purchaseOrder->delivery_number) }}"
                                   placeholder="e.g. 8010361871" style="font-family:monospace;" required>
                            <small class="text-muted">Delivery receipt number from the supplier</small>
                        </div>
                    </div>

                    @foreach($itemsToReceive as $item)
                    @php
                        $isSetItem = $item->is_set && $item->product?->pairedProduct;
                        $remaining = $item->quantity_ordered - $item->quantity_received;
                    @endphp
                    <div class="border rounded p-3 mb-2 receive-item"
                         data-item-id="{{ $item->id }}"
                         data-is-set="{{ $isSetItem ? 1 : 0 }}"
                         data-indoor-model="{{ $item->product->model }}"
                         data-outdoor-model="{{ $isSetItem ? $item->product->pairedProduct->model : '' }}">
                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <div>
                                <span class="fw-bold" style="font-family:monospace;">{{ $isSetItem ? $item->product->set_model_label : $item->product->model }}</span>
                                @if($isSetItem)
                                    <span class="badge ms-1" style="background:#f3e8ff;color:#7c3aed;border:1px solid #c4b5fd;font-size:0.63rem;">❄️🌀 Set</span>
                                @endif
                                <span class="text-muted small ms-2">{{ $remaining }} {{ $isSetItem ? 'set(s)' : 'unit(s)' }} remaining</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="small fw-semibold mb-0">Receive now:</label>
                                <input type="number" class="form-control form-control-sm text-center receive-qty"
                                       name="items[{{ $loop->index }}][quantity_received]"
                                       value="{{ $remaining }}" min="0" max="{{ $remaining }}"
                                       style="width:80px;"
                                       data-loop-index="{{ $loop->index }}">
                            </div>
                        </div>
                        <div class="receive-serials" id="receive-serials-{{ $modalId }}-{{ $loop->index }}"></div>
                    </div>
                    @endforeach
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-po-due-receive-dismiss>
                        Not yet received
                    </button>
                    <button type="submit" class="btn btn-warning fw-semibold">
                        <i class="bi bi-check-circle"></i> Receive Stock &amp; Save Serials
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
