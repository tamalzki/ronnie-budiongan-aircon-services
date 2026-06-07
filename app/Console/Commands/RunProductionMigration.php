<?php

namespace App\Console\Commands;

use Database\Seeders\PurchaseOrderJanuaryToMaySeeder;
use Database\Seeders\SalesFromMasterSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-shot production data migration.
 *
 * WHAT IT DOES
 *  1. Clears all transactional data (POs, serials, inventory movements, sales,
 *     installment payments).  Non-transactional tables — users, brands,
 *     suppliers, products, services — are left untouched so the seeders can
 *     reference them immediately.
 *  2. Runs PurchaseOrderJanuaryToMaySeeder   (Jan–May 2026 Daikin DRs)
 *  3. Runs SalesFromMasterSeeder             (historical customer sales)
 *
 * USAGE
 *  php artisan app:run-production-migration
 *  php artisan app:run-production-migration --force   ← skip confirmation (CI / deploy scripts)
 */
class RunProductionMigration extends Command
{
    protected $signature = 'app:run-production-migration
                            {--force : Skip confirmation prompt (use in deploy scripts)}';

    protected $description = 'Clear transactional data and re-seed Purchase Orders + Sales (users/brands/suppliers kept)';

    /** Tables cleared — ORDER MATTERS (children before parents to avoid FK violations). */
    private const CLEAR_TABLES = [
        'installment_payments',   // → sales
        'sale_items',             // → sales, product_serials
        'sales',
        'inventory_movements',    // → products
        'product_serials',        // → purchase_orders, products
        'purchase_order_items',   // → purchase_orders, products
        'purchase_orders',        // → suppliers
        'supplier_payments',      // → purchase_orders (if exists)
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('┌──────────────────────────────────────────────────────────┐');
        $this->line('│        <fg=yellow;options=bold>Production Data Migration — Ronnie Aircon</>        │');
        $this->line('├──────────────────────────────────────────────────────────┤');
        $this->line('│  Will CLEAR:  purchase orders, serials, inventory,        │');
        $this->line('│               sales, installment payments                 │');
        $this->line('│  Will KEEP:   users, brands, suppliers, products,         │');
        $this->line('│               services, expense categories                │');
        $this->line('└──────────────────────────────────────────────────────────┘');
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('<fg=red>⚠  This will permanently delete all transactional data. Continue?</>')) {
                $this->info('Cancelled. No data was changed.');
                return self::FAILURE;
            }
        }

        // ── Step 1: Clear transactional tables ─────────────────────────
        $this->info('Step 1/3 — Clearing transactional tables…');
        Schema::disableForeignKeyConstraints();

        foreach (self::CLEAR_TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  ✓ <fg=yellow>Cleared:</> {$table}");
            } else {
                $this->line("  <fg=gray>- Skipped (not found): {$table}</>");
            }
        }

        Schema::enableForeignKeyConstraints();
        $this->newLine();

        // ── Step 2: Purchase Orders (Jan–May 2026) ──────────────────────
        $this->info('Step 2/3 — Running PurchaseOrderJanuaryToMaySeeder…');
        $this->newLine();
        $this->call('db:seed', ['--class' => PurchaseOrderJanuaryToMaySeeder::class, '--force' => true]);
        $this->newLine();

        // ── Step 3: Sales migration ─────────────────────────────────────
        $this->info('Step 3/3 — Running SalesFromMasterSeeder…');
        $this->newLine();
        $this->call('db:seed', ['--class' => SalesFromMasterSeeder::class, '--force' => true]);
        $this->newLine();

        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║  <fg=green;options=bold>✅  Migration complete. System is ready for production.</>   ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        return self::SUCCESS;
    }
}
