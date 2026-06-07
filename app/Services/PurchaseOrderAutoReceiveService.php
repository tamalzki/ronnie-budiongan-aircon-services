<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductSerial;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class PurchaseOrderAutoReceiveService
{
    /**
     * When expected delivery is today or earlier and every line has exactly enough
     * pending serials for its remaining quantity, mark serials in stock, update
     * quantity_received, log inventory movements, and mark the PO received.
     */
    public function receiveIfPastExpectedAndSerialsComplete(PurchaseOrder $purchaseOrder): bool
    {
        $purchaseOrder->refresh();

        if ($purchaseOrder->status !== 'pending') {
            return false;
        }

        if (!$purchaseOrder->expected_delivery_date) {
            return false;
        }

        $expected = Carbon::parse($purchaseOrder->expected_delivery_date)->startOfDay();
        if ($expected->isAfter(Carbon::today())) {
            return false;
        }

        $purchaseOrder->load('items.product');

        if ($purchaseOrder->items->isEmpty()) {
            return false;
        }

        $needsReceipt = false;

        foreach ($purchaseOrder->items as $poItem) {
            $remaining = $poItem->quantity_ordered - $poItem->quantity_received;
            if ($remaining <= 0) {
                continue;
            }

            $needsReceipt = true;

            $pendingCount = ProductSerial::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->where('product_id', $poItem->product_id)
                ->where('status', 'pending')
                ->count();

            if ($pendingCount !== $remaining) {
                return false;
            }
        }

        if (!$needsReceipt) {
            return false;
        }

        $receivedDate = Carbon::today();
        $userId       = Auth::id();

        foreach ($purchaseOrder->items as $poItem) {
            $remaining = $poItem->quantity_ordered - $poItem->quantity_received;
            if ($remaining <= 0) {
                continue;
            }

            $pendingSerials = ProductSerial::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->where('product_id', $poItem->product_id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->limit($remaining)
                ->get();

            if ($pendingSerials->count() !== $remaining) {
                throw new RuntimeException('Pending serials changed during auto-receive.');
            }

            $product = $poItem->product;

            $poItem->update([
                'quantity_received' => $poItem->quantity_received + $remaining,
            ]);

            $product->update([
                'cost' => $poItem->discounted_cost ?? $poItem->unit_cost,
            ]);

            foreach ($pendingSerials as $serial) {
                $serial->update([
                    'status'        => 'in_stock',
                    'received_date' => $receivedDate,
                ]);
            }

            $stockAfter  = $product->fresh()->inStockSerials()->count();
            $stockBefore = $stockAfter - $remaining;

            InventoryMovement::create([
                'product_id'     => $product->id,
                'type'           => 'stock_in',
                'quantity'       => $remaining,
                'stock_before'   => max(0, $stockBefore),
                'stock_after'    => $stockAfter,
                'reference_type' => 'PurchaseOrder',
                'reference_id'   => $purchaseOrder->id,
                'notes'          => 'Auto-received (expected delivery date reached): PO ' . $purchaseOrder->po_number .
                    ' at ₱' . number_format((float) ($poItem->discounted_cost ?? $poItem->unit_cost), 2) . '/unit',
                'user_id'        => $userId,
            ]);
        }

        $purchaseOrder->update([
            'status'        => 'received',
            'received_date' => $receivedDate,
        ]);

        return true;
    }
}
