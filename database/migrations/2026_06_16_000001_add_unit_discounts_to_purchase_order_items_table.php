<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->json('unit_discounts')->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('unit_discounts');
        });
    }
};
