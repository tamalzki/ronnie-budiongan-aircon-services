<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('customer_name');
            $table->string('customer_contact')->nullable();
            $table->text('customer_address')->nullable();
            $table->enum('sale_type', ['product', 'service', 'both'])->default('both');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('payment_type', ['cash', 'installment'])->default('cash');
            $table->integer('installment_months')->nullable();
            $table->decimal('installment_amount', 10, 2)->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->date('sale_date');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};