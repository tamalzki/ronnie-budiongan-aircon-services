<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\InstallmentPayment;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use App\Support\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds historical customer sales from the verified po_to_sales_master dataset,
 * connecting them to the Purchase-Order inventory that was already migrated.
 *
 * Source: database/seeders/data/po_to_sales_master.json
 *   The "master" array holds one row per serial (1 serial = 1 unit). Rows with
 *   status "SOLD" become sales; "IN STOCK" rows are left untouched in inventory.
 *
 * Grouping logic (mirrors a real point-of-sale invoice):
 *   One Sale per (customer + sale_date). Every serial that customer bought on
 *   that day is merged into a single invoice. Within a sale, serials are grouped
 *   into one SaleItem per distinct product model (so an IDU + ODU set shows as
 *   two line items, each carrying its own serials).
 *
 * Payment tagging (the key requirement):
 *   terms containing "install"  → payment_type = installment (+ schedule built)
 *   terms "COD" / "transfer" / "" → payment_type = cash (fully paid)
 *   payment_method: "transfer" → bank_transfer, otherwise cash.
 *   amount  = deal total (repeated on each row; taken once, never summed)
 *   balance = outstanding; paid = total - balance. Cash sales are fully paid.
 *
 * Inventory deduction (same as SaleController@store):
 *   ProductSerial → sold (sale_id / sale_item_id / sold_date) and a stock_out
 *   InventoryMovement is logged per serial.
 *
 * Idempotent / no duplicates:
 *   Each invoice is stamped with a deterministic [MIG-SALE:hash] marker in its
 *   notes. A group whose marker already exists is skipped wholesale. Serials
 *   already marked sold are skipped defensively. Missing product models are
 *   created on the fly.
 */
class SalesFromMasterSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('seeders/data/po_to_sales_master.json');
        if (! is_file($file)) {
            $this->command->error("Data file not found: {$file}");
            return;
        }

        $raw = json_decode(file_get_contents($file), true);
        if (! is_array($raw) || empty($raw['master'])) {
            $this->command->error('Could not parse po_to_sales_master.json or "master" key missing.');
            return;
        }

        $brand    = Brand::where('name', 'like', '%Daikin%')->first();
        $supplier = Supplier::where('name', 'like', '%Daikin%')->first();
        $user     = User::orderBy('id')->first();

        if (! $brand || ! $user) {
            $this->command->error('Missing Daikin brand or a user record. Run the PO seeder first.');
            return;
        }

        // ── 1) Keep only sellable SOLD rows ────────────────────────────────
        $soldRows = array_filter(
            $raw['master'],
            fn ($r) => ($r['status'] ?? '') === 'SOLD'
                && ! empty($r['customer'])
                && ! empty($r['sale_date'])
        );

        // ── 2) Deduplicate by serial (a serial must never sell twice) ──────
        $seen    = [];
        $deduped = [];
        foreach ($soldRows as $r) {
            $key = trim($r['serial']);
            if ($key === '' || isset($seen[$key])) {
                if ($key !== '') {
                    $this->command->warn("Duplicate serial in JSON (skipping later occurrence): {$key}");
                }
                continue;
            }
            $seen[$key] = true;

            // Normalise sale dates: some source rows have an unknown day ("2026-05-??").
            // Anchor those to the 1st of the month and flag the estimate for the notes.
            $normalized = $this->normalizeSaleDate($r['sale_date']);
            if ($normalized === null) {
                $this->command->warn("Unparseable sale_date '{$r['sale_date']}' for {$r['customer']} — row skipped");
                continue;
            }
            if ($normalized !== $r['sale_date']) {
                $r['_date_estimated'] = true;
                $r['sale_date']       = $normalized;
            }

            $deduped[]  = $r;
        }

        // ── 3) Ensure every referenced model exists as a product ───────────
        $newProducts = 0;
        foreach (array_unique(array_column($deduped, 'model')) as $model) {
            $model = trim((string) $model);
            if ($model === '') {
                continue;
            }
            $unitType = (strtoupper($model[0]) === 'R') ? 'outdoor' : 'indoor';
            $product  = Product::firstOrCreate(
                ['model' => $model],
                [
                    'name'        => 'Daikin ' . $model,
                    'brand_id'    => $brand->id,
                    'supplier_id' => $supplier?->id,
                    'unit_type'   => $unitType,
                    'price'       => 0,
                    'cost'        => 0,
                    'is_active'   => true,
                ]
            );
            if ($product->wasRecentlyCreated) {
                $newProducts++;
                $this->command->info("Created missing product: {$model} ({$unitType})");
            }
        }

        $productByModel = Product::pluck('id', 'model');

        // ── 4) Group rows into invoices (customer + sale_date) ─────────────
        $groups = [];
        foreach ($deduped as $r) {
            $groups[$r['customer'] . '||' . $r['sale_date']][] = $r;
        }

        $this->command->info('Products created : ' . $newProducts);
        $this->command->info('Sale groups found: ' . count($groups));

        $createdSales    = 0;
        $skippedGroups   = 0;
        $skippedSerials  = 0;
        $attachedSerials = 0;

        foreach ($groups as $groupKey => $rows) {
            [$customer, $saleDate] = explode('||', $groupKey, 2);
            $marker = '[MIG-SALE:' . md5($groupKey) . ']';

            // Idempotency: this invoice was already migrated.
            if (Sale::where('notes', 'like', '%' . $marker . '%')->exists()) {
                $skippedGroups++;
                continue;
            }

            // Resolve serials that are genuinely available in inventory.
            $available = [];
            foreach ($rows as $r) {
                $ps = ProductSerial::where('serial_number', $r['serial'])->first();
                if (! $ps) {
                    $this->command->warn("  Serial not in inventory: {$r['serial']} ({$r['model']}) — skipped");
                    $skippedSerials++;
                    continue;
                }
                if ($ps->status === 'sold') {
                    $this->command->warn("  Serial already sold: {$r['serial']} — skipped");
                    $skippedSerials++;
                    continue;
                }
                $available[] = ['row' => $r, 'serial' => $ps];
            }

            if (empty($available)) {
                $this->command->warn("  No available serials for [{$customer} / {$saleDate}] — sale skipped");
                $skippedGroups++;
                continue;
            }

            // ── Financials for the whole invoice ───────────────────────────
            $fin = $this->resolveFinancials($rows);

            $months      = $fin['months'];
            $paymentType = $fin['payment_type'];
            $paymentMet  = $fin['payment_method'];
            $total       = $fin['total'];

            if ($paymentType === 'cash') {
                $paid    = $total;
                $balance = 0.0;
            } else {
                $balance = $fin['balance'] !== null
                    ? $fin['balance']
                    : max(0.0, $total - ($fin['payment'] ?? 0.0));
                $paid    = max(0.0, $total - $balance);
            }

            $notes = $this->buildNotes($rows, $marker);

            // ── Group available serials by product for line items ──────────
            $byProduct = [];
            foreach ($available as $entry) {
                $pid = $productByModel[$entry['row']['model']] ?? null;
                if (! $pid) {
                    continue;
                }
                $byProduct[$pid][] = $entry;
            }
            if (empty($byProduct)) {
                $skippedGroups++;
                continue;
            }

            try {
                DB::transaction(function () use (
                    $customer, $saleDate, $paymentType, $paymentMet, $months,
                    $total, $paid, $balance, $byProduct, $user, $notes,
                    &$createdSales, &$attachedSerials
                ) {
                $unitCount = array_sum(array_map('count', $byProduct));
                $unitPrice = ($unitCount > 0 && $total > 0) ? round($total / $unitCount, 2) : 0.0;

                $sale = Sale::create([
                    'customer_name'      => $customer,
                    'sale_date'          => $saleDate,
                    'sale_type'          => 'product',
                    'payment_type'       => $paymentType,
                    'payment_method'     => $paymentMet,
                    'subtotal'           => $total,
                    'discount'           => 0,
                    'total'              => $total,
                    'paid_amount'        => $paid,
                    'balance'            => $balance,
                    'installment_months' => $paymentType === 'installment' ? $months : null,
                    'status'             => 'completed',
                    'notes'              => $notes,
                    'user_id'            => $user->id,
                ]);

                // Distribute the deal total across line items; fix rounding on the last.
                $runningAllocated = 0.0;
                $productIds       = array_keys($byProduct);
                $lastPid          = end($productIds);

                foreach ($byProduct as $pid => $entries) {
                    $qty     = count($entries);
                    $product = Product::with('brand')->find($pid);
                    $name    = trim(($product->brand->name ?? 'Daikin') . ' ' . $product->model);

                    if ($pid === $lastPid) {
                        $lineTotal = round($total - $runningAllocated, 2);
                    } else {
                        $lineTotal = round($unitPrice * $qty, 2);
                        $runningAllocated += $lineTotal;
                    }
                    $linePrice = $qty > 0 ? round($lineTotal / $qty, 2) : 0.0;

                    $saleItem = SaleItem::create([
                        'sale_id'     => $sale->id,
                        'item_type'   => 'product',
                        'product_id'  => $pid,
                        'item_name'   => $name,
                        'quantity'    => $qty,
                        'unit_price'  => $linePrice,
                        'total_price' => $lineTotal,
                    ]);

                    foreach ($entries as $entry) {
                        /** @var ProductSerial $ps */
                        $ps = $entry['serial'];

                        $stockBefore = Product::find($pid)->inStockSerials()->count();

                        $ps->status       = 'sold';
                        $ps->sale_id      = $sale->id;
                        $ps->sale_item_id = $saleItem->id;
                        $ps->sold_date    = $saleDate;
                        $ps->save();

                        $stockAfter = Product::find($pid)->inStockSerials()->count();

                        InventoryMovement::create([
                            'product_id'     => $pid,
                            'type'           => 'stock_out',
                            'quantity'       => 1,
                            'stock_before'   => $stockBefore,
                            'stock_after'    => $stockAfter,
                            'reference_type' => 'Sale',
                            'reference_id'   => $sale->id,
                            'notes'          => 'Sold (migrated) — ' . $sale->invoice_number . ' | SN: ' . $ps->serial_number,
                            'user_id'        => $user->id,
                        ]);

                        $attachedSerials++;
                    }
                }

                // ── Installment schedule (mirrors SaleController@store) ────
                if ($paymentType === 'installment') {
                    $this->buildInstallmentSchedule($sale, $paid, $balance, $months, $saleDate, $paymentMet);
                }

                $createdSales++;
                $this->command->info(sprintf(
                    '  ✓ %s | %s | %s | %d unit(s) | %s | total=%s paid=%s bal=%s',
                    $sale->invoice_number,
                    $customer,
                    $saleDate,
                    $unitCount,
                    $paymentType,
                    number_format($total, 2),
                    number_format($paid, 2),
                    number_format($balance, 2)
                ));
                });
            } catch (\Throwable $e) {
                $skippedGroups++;
                $this->command->error("  ✗ Failed [{$customer} / {$saleDate}]: " . $e->getMessage());
            }
        }

        $this->command->info('');
        $this->command->info('═══ Sales migration complete ═══');
        $this->command->info("Sales created    : {$createdSales}");
        $this->command->info("Groups skipped   : {$skippedGroups} (already migrated / no stock)");
        $this->command->info("Serials attached : {$attachedSerials}");
        $this->command->info("Serials skipped  : {$skippedSerials}");
    }

    /**
     * Pull terms / amounts out of a group of rows belonging to one invoice.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{payment_type:string, payment_method:string, total:float, payment:?float, balance:?float, months:int}
     */
    private function resolveFinancials(array $rows): array
    {
        $terms   = '';
        $total   = 0.0;
        $payment = null;
        $balance = null;
        $months  = null;

        foreach ($rows as $r) {
            if (! empty($r['terms'])) {
                $terms = $r['terms'];
            }
            if (! empty($r['amount']) && $total === 0.0) {
                $total = $this->money($r['amount']);
            }
            if (! empty($r['payment']) && $payment === null) {
                $payment = $this->money($r['payment']);
            }
            if (isset($r['balance']) && $r['balance'] !== '' && $balance === null) {
                $balance = $this->money($r['balance']);
            }
            if (! empty($r['months']) && $months === null) {
                $months = (int) $r['months'];
            }
        }

        // Derive month count from free-text notes when not explicit.
        if ($months === null) {
            $months = $this->monthsFromNotes($rows);
        }

        $isInstallment = stripos($terms, 'install') !== false;

        $method = PaymentMethod::CASH;
        if (stripos($terms, 'transfer') !== false) {
            $method = PaymentMethod::BANK_TRANSFER;
        }

        return [
            'payment_type'   => $isInstallment ? 'installment' : 'cash',
            'payment_method' => $method,
            'total'          => $total,
            'payment'        => $payment,
            'balance'        => $balance,
            'months'         => $isInstallment ? ($months ?: 12) : ($months ?: 0),
        ];
    }

    /**
     * Build the installment payment rows. An initial/down payment (if any) is
     * recorded as a paid line; the remaining balance is split evenly across the
     * agreed months, with the final line absorbing any rounding remainder.
     */
    private function buildInstallmentSchedule(
        Sale $sale,
        float $paid,
        float $balance,
        int $months,
        string $saleDate,
        string $method
    ): void {
        $start = Carbon::parse($saleDate);
        $num   = 1;

        if ($paid > 0.0) {
            InstallmentPayment::create([
                'sale_id'            => $sale->id,
                'installment_number' => $num++,
                'amount'             => $paid,
                'amount_paid'        => $paid,
                'due_date'           => $start,
                'paid_date'          => $start,
                'status'             => 'paid',
                'payment_method'     => $method,
                'notes'              => 'Downpayment',
            ]);
        }

        if ($balance > 0.0 && $months > 0) {
            $monthly   = round($balance / $months, 2);
            $allocated = 0.0;
            for ($i = 0; $i < $months; $i++) {
                $amount = $i === $months - 1
                    ? round($balance - $allocated, 2)
                    : $monthly;
                $allocated += $monthly;

                InstallmentPayment::create([
                    'sale_id'            => $sale->id,
                    'installment_number' => $num++,
                    'amount'             => $amount,
                    'amount_paid'        => 0,
                    'due_date'           => $start->copy()->addMonths($i + 1),
                    'status'             => 'unpaid',
                ]);
            }
        }
    }

    /**
     * Compose human-friendly sale notes plus the idempotency marker.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function buildNotes(array $rows, string $marker): string
    {
        $docRefs = [];
        $poNos   = [];
        $soNos   = [];
        $docNos  = [];
        $freeT   = [];
        $hasConf = false;
        $estDate = false;

        foreach ($rows as $r) {
            if (! empty($r['sale_doc_ref']))    $docRefs[] = $r['sale_doc_ref'];
            if (! empty($r['po_no']))           $poNos[]   = $r['po_no'];
            if (! empty($r['so_no']))           $soNos[]   = $r['so_no'];
            if (! empty($r['document_no']))     $docNos[]  = $r['document_no'];
            if (! empty($r['note']))            $freeT[]   = $r['note'];
            if (! empty($r['conflict']))        $hasConf   = true;
            if (! empty($r['_date_estimated'])) $estDate   = true;
        }

        $lines = array_filter([
            $docRefs ? 'Sale Ref: ' . implode(' / ', array_unique($docRefs)) : null,
            $poNos   ? 'PO No: ' . implode(', ', array_unique($poNos)) : null,
            $soNos   ? 'SO No: ' . implode(', ', array_unique($soNos)) : null,
            $docNos  ? 'DR/Doc No: ' . implode(', ', array_unique($docNos)) : null,
            $freeT   ? 'Note: ' . implode('; ', array_unique($freeT)) : null,
            $hasConf ? '⚠ Data conflict flagged in source — verify customer/serial assignment.' : null,
            $estDate ? '⚠ Exact sale day unknown in source — anchored to the 1st of the month.' : null,
            'Migrated from po_to_sales_master. ' . $marker,
        ]);

        return implode("\n", $lines);
    }

    /**
     * Extract a month count from any of the group's free-text notes.
     * Understands "6 months", "24 mos", "1 year", "2 years".
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function monthsFromNotes(array $rows): ?int
    {
        foreach ($rows as $r) {
            $note = $r['note'] ?? '';
            if ($note === '') {
                continue;
            }
            if (preg_match('/(\d+)\s*(?:months?|mos?)\b/i', $note, $m)) {
                return (int) $m[1];
            }
            if (preg_match('/(\d+)\s*years?\b/i', $note, $m)) {
                return ((int) $m[1]) * 12;
            }
        }
        return null;
    }

    /**
     * Parse a money string like "1,162,000" or "53,000 " into a float.
     */
    private function money(mixed $value): float
    {
        return (float) str_replace([',', ' ', '₱'], '', (string) $value);
    }

    /**
     * Normalise a source sale_date to YYYY-MM-DD. Rows with an unknown day
     * ("2026-05-??") are anchored to the 1st of the month. Returns null when
     * the value cannot be salvaged.
     */
    private function normalizeSaleDate(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{4})-(\d{2})-\?\?$/', $value, $m)) {
            return "{$m[1]}-{$m[2]}-01";
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
