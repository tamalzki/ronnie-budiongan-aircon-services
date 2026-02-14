<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old table
        Schema::dropIfExists('installment_payments');
        
        // Create the correct table
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->integer('installment_number');
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
    }
};