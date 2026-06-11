<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconcile legacy data where indoor + outdoor units were stored as separate
 * line items. Merges them into one set line (is_set = true) with one unit price.
 *
 * Safe for production: defaults to dry-run. Serial numbers are kept; only line
 * items and totals are restructured.
 */
class ReconcileProductSets extends Command
{
    protected $signature = 'app:reconcile-product-sets
                            {--apply : Write changes to the database (default is dry-run)}
                            {--force : Skip confirmation when using --apply}';

    protected $description = 'Merge split indoor/outdoor PO & sale lines into one set price; sync product pair prices';

    private int $productPricesSynced = 0;

    private int $poPairsMerged = 0;

    private int $salePairsMerged = 0;

    private array $warnings = [];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->newLine();
        $this->line('┌────────────────────────────────────────────────────────────┐');
        $this->line('│  <fg=cyan;options=bold>Product Set Reconciliation — Ronnie Aircon</>              │');
        $this->line('├────────────────────────────────────────────────────────────┤');
        $this->line($apply
            ? '│  Mode: <fg=red;options=bold>APPLY</> — database will be updated                  │'
            : '│  Mode: <fg=yellow>DRY RUN</> — no changes (add --apply to write)              │');
        $this->line('└────────────────────────────────────────────────────────────┘');
        $this->newLine();

        if ($apply && ! $this->option('force')) {
            if (! $this->confirm('<fg=red>Apply set reconciliation to production data?</>')) {
                $this->info('Cancelled.');
                return self::FAILURE;
            }
        }

        $runner = function () use ($apply) {
            $this->syncProductPrices($apply);
            $this->reconcilePurchaseOrders($apply);
            $this->reconcileSales($apply);
        };

        if ($apply) {
            DB::transaction($runner);
        } else {
            $runner();
        }

        $this->newLine();
        $this->info('Summary');
        $this->line("  Product prices synced (indoor → outdoor): {$this->productPricesSynced}");
        $this->line("  PO line pairs merged: {$this->poPairsMerged}");
        $this->line("  Sale line pairs merged: {$this->salePairsMerged}");

        if ($this->warnings !== []) {
            $this->newLine();
            $this->warn('Warnings (' . count($this->warnings) . '):');
            foreach (array_slice($this->warnings, 0, 30) as $w) {
                $this->line("  • {$w}");
            }
            if (count($this->warnings) > 30) {
                $this->line('  … and ' . (count($this->warnings) - 30) . ' more');
            }
        }

        $this->newLine();
        $this->line($apply
            ? '<fg=green>✅ Reconciliation applied.</>'
            : '<fg=yellow>ℹ Dry run complete. Re-run with --apply --force to write changes.</>');

        return self::SUCCESS;
    }

    private function syncProductPrices(bool $apply): void
    {
        $this->info('Step 1/3 — Sync indoor/outdoor catalog prices…');

        $indoorSets = Product::with('pairedProduct')
            ->where('unit_type', 'indoor')
            ->whereNotNull('paired_product_id')
            ->get();

        foreach ($indoorSets as $indoor) {
            $outdoor = $indoor->pairedProduct;
            if ($outdoor === null) {
                continue;
            }

            $indoorPrice = (float) $indoor->price;
            $outdoorPrice = (float) $outdoor->price;

            if ($indoorPrice <= 0 && $outdoorPrice > 0) {
                $this->line("  ↺ {$indoor->model}: copy outdoor price ₱" . number_format($outdoorPrice, 2) . ' → indoor');
                if ($apply) {
                    $indoor->update(['price' => $outdoorPrice]);
                }
                $this->productPricesSynced++;
            } elseif ($indoorPrice > 0 && abs($indoorPrice - $outdoorPrice) > 0.009) {
                $this->line("  ↺ {$indoor->model}: sync outdoor price ₱" . number_format($outdoorPrice, 2) . ' → ₱' . number_format($indoorPrice, 2));
                if ($apply) {
                    $outdoor->update(['price' => $indoorPrice]);
                }
                $this->productPricesSynced++;
            }

            $indoorCost = (float) $indoor->cost;
            $outdoorCost = (float) $outdoor->cost;
            if ($indoorCost > 0 && abs($indoorCost - $outdoorCost) > 0.009 && $apply) {
                $outdoor->update(['cost' => $indoorCost]);
            }
        }
    }

    private function reconcilePurchaseOrders(bool $apply): void
    {
        $this->info('Step 2/3 — Merge split PO lines (indoor + outdoor → one set)…');

        PurchaseOrder::with(['items.product'])->orderBy('id')->chunkById(50, function ($orders) use ($apply) {
            foreach ($orders as $po) {
                $byProduct = $po->items->keyBy('product_id');
                $mergedAny = false;

                foreach ($po->items as $indoorItem) {
                    if ($indoorItem->is_set) {
                        continue;
                    }

                    $indoor = $indoorItem->product;
                    if ($indoor === null || $indoor->unit_type !== 'indoor' || ! $indoor->paired_product_id) {
                        continue;
                    }

                    $outdoorItem = $byProduct->get($indoor->paired_product_id);
                    if ($outdoorItem === null || $outdoorItem->is_set) {
                        continue;
                    }

                    $iq = (int) $indoorItem->quantity_ordered;
                    $oq = (int) $outdoorItem->quantity_ordered;
                    if ($iq !== $oq) {
                        $this->warnings[] = "PO {$po->display_po_number}: qty mismatch {$indoor->model} ({$iq}) vs outdoor ({$oq}) — merging using min({$iq}, {$oq})";
                    }

                    $setQty = min($iq, $oq);
                    if ($setQty <= 0) {
                        continue;
                    }

                    $indoorUnit = (float) ($indoorItem->discounted_cost ?? $indoorItem->unit_cost);
                    $outdoorUnit = (float) ($outdoorItem->discounted_cost ?? $outdoorItem->unit_cost);
                    $setUnit = round($indoorUnit + $outdoorUnit, 2);
                    $setTotal = round($setUnit * $setQty, 2);

                    $oldSubtotal = (float) $indoorItem->total_cost + (float) $outdoorItem->total_cost;

                    $this->line(sprintf(
                        '  PO %s: %s + %s → 1 set line · qty %d · unit ₱%s (was ₱%s indoor + ₱%s outdoor)',
                        $po->display_po_number,
                        $indoor->model,
                        $outdoorItem->product->model ?? '?',
                        $setQty,
                        number_format($setUnit, 2),
                        number_format($indoorUnit, 2),
                        number_format($outdoorUnit, 2),
                    ));

                    if ($apply) {
                        $indoorItem->update([
                            'is_set'            => true,
                            'quantity_ordered'  => $setQty,
                            'quantity_received' => min((int) $indoorItem->quantity_received, (int) $outdoorItem->quantity_received, $setQty),
                            'unit_cost'         => $setUnit,
                            'discounted_cost'   => $setUnit,
                            'total_cost'        => $setTotal,
                        ]);

                        $outdoorItem->delete();
                        $mergedAny = true;
                    }

                    $this->poPairsMerged++;

                    if (abs($oldSubtotal - $setTotal) > 0.02) {
                        $this->warnings[] = "PO {$po->display_po_number} set {$indoor->model}: line total changed ₱" . number_format($oldSubtotal, 2) . ' → ₱' . number_format($setTotal, 2);
                    }
                }

                if ($apply && $mergedAny) {
                    $this->refreshPurchaseOrderTotals($po->fresh(['items']));
                }
            }
        });
    }

    private function refreshPurchaseOrderTotals(PurchaseOrder $po): void
    {
        $subtotal = $po->items->sum(fn(PurchaseOrderItem $i) => (float) $i->total_cost);
        $total    = $subtotal + (float) $po->tax;

        $amountPaid = (float) $po->amount_paid;
        $balance    = max(0, $total - $amountPaid);

        $updates = [
            'subtotal' => $subtotal,
            'total'    => $total,
            'balance'  => $balance,
        ];

        if ($po->payment_type === 'full') {
            $updates['amount_paid']    = $total;
            $updates['balance']        = 0;
            $updates['payment_status'] = 'paid';
        } elseif ($balance <= 0) {
            $updates['payment_status'] = 'paid';
        } elseif ($amountPaid > 0) {
            $updates['payment_status'] = 'partial';
        }

        $po->update($updates);
    }

    private function reconcileSales(bool $apply): void
    {
        $this->info('Step 3/3 — Merge split sale lines (indoor + outdoor → one set)…');

        Sale::with(['items.product.brand'])->orderBy('id')->chunkById(50, function ($sales) use ($apply) {
            foreach ($sales as $sale) {
                $byProduct = $sale->items->where('item_type', 'product')->keyBy('product_id');
                $mergedAny = false;

                foreach ($sale->items->where('item_type', 'product') as $indoorItem) {
                    if ($indoorItem->is_set) {
                        continue;
                    }

                    $indoor = $indoorItem->product;
                    if ($indoor === null || $indoor->unit_type !== 'indoor' || ! $indoor->paired_product_id) {
                        continue;
                    }

                    $outdoorItem = $byProduct->get($indoor->paired_product_id);
                    if ($outdoorItem === null || $outdoorItem->is_set) {
                        continue;
                    }

                    $iq = (int) $indoorItem->quantity;
                    $oq = (int) $outdoorItem->quantity;
                    if ($iq !== $oq) {
                        $this->warnings[] = "Sale {$sale->invoice_number} ({$sale->customer_name}): qty mismatch {$indoor->model} ({$iq}) vs outdoor ({$oq})";
                    }

                    $setQty = min($iq, $oq);
                    if ($setQty <= 0) {
                        continue;
                    }

                    $setUnit = round((float) $indoorItem->unit_price + (float) $outdoorItem->unit_price, 2);
                    $setTotal = round($setUnit * $setQty, 2);
                    $setName = trim(($indoor->brand->name ?? 'Daikin') . ' ' . $indoor->set_model_label);

                    $this->line(sprintf(
                        '  Sale %s / %s: %s → 1 set · qty %d · unit ₱%s',
                        $sale->invoice_number,
                        $sale->customer_name,
                        $indoor->set_model_label,
                        $setQty,
                        number_format($setUnit, 2),
                    ));

                    if ($apply) {
                        $indoorItem->update([
                            'is_set'      => true,
                            'quantity'    => $setQty,
                            'unit_price'  => $setUnit,
                            'total_price' => $setTotal,
                            'item_name'   => $setName,
                        ]);

                        DB::table('product_serials')
                            ->where('sale_item_id', $outdoorItem->id)
                            ->update(['sale_item_id' => $indoorItem->id]);

                        $outdoorItem->delete();
                        $mergedAny = true;
                    }

                    $this->salePairsMerged++;
                }

                if ($apply && $mergedAny) {
                    $this->refreshSaleTotals($sale->fresh(['items']));
                }
            }
        });
    }

    private function refreshSaleTotals(Sale $sale): void
    {
        $subtotal = $sale->items->sum(fn(SaleItem $i) => (float) $i->total_price);
        $total    = max(0, $subtotal - (float) $sale->discount);
        $paid     = (float) $sale->paid_amount;
        $balance  = max(0, $total - $paid);

        $sale->update([
            'subtotal' => $subtotal,
            'total'    => $total,
            'balance'  => $balance,
        ]);
    }
}
