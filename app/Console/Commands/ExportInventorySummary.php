<?php

namespace App\Console\Commands;

use App\Models\ProductSerial;
use Illuminate\Console\Command;

class ExportInventorySummary extends Command
{
    protected $signature   = 'app:export-inventory-summary {--out=}';
    protected $description = 'Export model / serial / PO date / sold status / customer to CSV';

    public function handle(): int
    {
        $out = $this->option('out') ?: storage_path('app/inventory_summary_' . date('Ymd_His') . '.csv');

        $this->info('Querying data…');

        $serials = ProductSerial::with([
            'product.brand',
            'purchaseOrder',
            'sale',
        ])
        ->orderBy('product_id')
        ->orderBy('status')
        ->orderBy('serial_number')
        ->get();

        $fh = fopen($out, 'w');

        // BOM for Excel UTF-8
        fwrite($fh, "\xEF\xBB\xBF");

        fputcsv($fh, [
            'Brand',
            'Model',
            'Serial Number',
            'PO / DR Number',
            'PO Order Date',
            'Stock Status',
            'Sold Date',
            'Customer',
            'Invoice #',
        ]);

        foreach ($serials as $s) {
            $product = $s->product;
            $po      = $s->purchaseOrder;
            $sale    = $s->sale;

            fputcsv($fh, [
                $product->brand->name  ?? '—',
                $product->model        ?? '—',
                $s->serial_number,
                $po?->delivery_number  ?? $po?->po_number ?? '—',
                $po?->order_date?->format('Y-m-d') ?? '—',
                strtoupper($s->status),                          // IN_STOCK / SOLD / PENDING
                $s->sold_date          ?? '—',
                $sale?->customer_name  ?? '—',
                $sale?->invoice_number ?? '—',
            ]);
        }

        fclose($fh);

        $this->info("✅  Written → {$out}");
        $this->info('   Rows: ' . $serials->count());

        return self::SUCCESS;
    }
}
