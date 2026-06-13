<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('service_type');
            $table->string('other_service')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('status', ['paid', 'unpaid'])->default('unpaid');
            $table->date('service_date');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_customers');
    }
};
