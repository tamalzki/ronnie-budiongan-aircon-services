<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sale_item_id')->nullable()->constrained()->onDelete('set null');
            $table->string('serial_number');
            $table->enum('status', ['pending', 'in_stock', 'sold', 'returned', 'defective', 'lost'])
                  ->default('pending');
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // A serial number must be unique per product model
            $table->unique(['product_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_serials');
    }
};