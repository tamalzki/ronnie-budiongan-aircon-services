<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('delivered_to_customer_no')->nullable()->after('notes');
            $table->string('delivered_to_name')->nullable()->after('delivered_to_customer_no');
            $table->text('delivered_to_address')->nullable()->after('delivered_to_name');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivered_to_customer_no',
                'delivered_to_name',
                'delivered_to_address',
            ]);
        });
    }
};
