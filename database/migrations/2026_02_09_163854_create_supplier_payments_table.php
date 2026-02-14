<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->string('payment_number');
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};