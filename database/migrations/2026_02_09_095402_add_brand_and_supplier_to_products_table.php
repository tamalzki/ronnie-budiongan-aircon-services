<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the old brand column (it was just a string)
            $table->dropColumn('brand');
            
            // Add foreign keys
            $table->foreignId('brand_id')->nullable()->after('name')->constrained()->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->after('brand_id')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['brand_id', 'supplier_id']);
            
            // Restore old brand column
            $table->string('brand')->nullable()->after('name');
        });
    }
};