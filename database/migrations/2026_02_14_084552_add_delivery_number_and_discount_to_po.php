<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('delivery_number')->nullable()->after('po_number');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('unit_cost');
            $table->decimal('discounted_cost', 10, 2)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('delivery_number');
        });
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discounted_cost']);
        });
    }
};