<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Purchase orders whose expected delivery date has arrived but stock is not yet received.
 */
class PurchaseOrderDueReceivingService
{
    /**
     * Pending POs with expected delivery on or before today that still have product units to receive.
     *
     * @return Collection<int, PurchaseOrder>
     */
    public function ordersDueForReceiving(?User $user = null): Collection
    {
        if ($user === null) {
            return collect();
        }

        if (!$user->can('viewAny', PurchaseOrder::class)) {
            return collect();
        }

        return PurchaseOrder::query()
            ->with(['supplier', 'items.product.pairedProduct', 'items.part'])
            ->where('status', 'pending')
            ->whereNotNull('expected_delivery_date')
            ->whereDate('expected_delivery_date', '<=', now()->toDateString())
            ->whereHas('items', function ($q) {
                $q->whereNull('part_id')
                    ->whereColumn('quantity_received', '<', 'quantity_ordered');
            })
            ->orderBy('expected_delivery_date')
            ->orderBy('order_date')
            ->get()
            ->filter(fn (PurchaseOrder $po) => $this->hasProductUnitsToReceive($po))
            ->values();
    }

    public function hasProductUnitsToReceive(PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->items->contains(
            fn ($item) => $item->part_id === null
                && ($item->quantity_ordered - $item->quantity_received) > 0
        );
    }

    /**
     * @return Collection<int, \App\Models\PurchaseOrderItem>
     */
    public function productItemsToReceive(PurchaseOrder $purchaseOrder): Collection
    {
        return $purchaseOrder->items->filter(
            fn ($item) => $item->part_id === null
                && ($item->quantity_ordered - $item->quantity_received) > 0
        )->values();
    }
}
