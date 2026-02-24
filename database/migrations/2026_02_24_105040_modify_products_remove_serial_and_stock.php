<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop serial_number — serials now live in product_serials table
            if (Schema::hasColumn('products', 'serial_number')) {
                $table->dropColumn('serial_number');
            }

            // Drop stock_quantity — stock is now counted from product_serials
            if (Schema::hasColumn('products', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore columns if rolling back
            $table->string('serial_number')->nullable()->after('unit_type');
            $table->integer('stock_quantity')->default(0)->after('price');
        });
    }
};