<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearAllData extends Command
{
    protected $signature   = 'app:clear-data';
    protected $description = 'Truncate all application data tables, keeping only users';

    public function handle(): void
    {
        if (! $this->confirm('⚠️  This will DELETE ALL DATA except users. Are you sure?')) {
            $this->info('Cancelled.');
            return;
        }

        $this->warn('Disabling foreign key checks...');
        Schema::disableForeignKeyConstraints();

        $tables = [
            'installment_payments',
            'sale_items',
            'sales',
            'inventory_movements',
            'supplier_payments',
            'purchase_order_items',
            'purchase_orders',
            'products',
            'brands',
            'services',
            'suppliers',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  ✓ Cleared: <fg=yellow>{$table}</>");
            } else {
                $this->line("  - Skipped (not found): <fg=gray>{$table}</>");
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        $this->info('✅  All data cleared. Users retained.');
    }
}