<?php

use App\Models\PurchaseOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupplierPoNumberToPurchaseOrders extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // The PO number on the supplier's documents (e.g. "698" on Daikin DRs),
            // distinct from the auto-generated internal po_number.
            $table->string('supplier_po_number')->nullable()->after('po_number');
        });

        // Backfill from the migrated DR notes: "... PO No: 698 | SO No: ..."
        PurchaseOrder::whereNotNull('notes')
            ->where('notes', 'like', '%PO No:%')
            ->get()
            ->each(function ($po) {
                if (preg_match('/PO No:\s*([^\s|]+)/', $po->notes, $m) && $m[1] !== '—') {
                    $po->update(['supplier_po_number' => $m[1]]);
                }
            });
    }

    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('supplier_po_number');
        });
    }
}
