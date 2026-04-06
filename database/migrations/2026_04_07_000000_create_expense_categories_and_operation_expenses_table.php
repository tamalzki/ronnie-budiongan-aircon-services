<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('expense_categories')->insert([
            ['name' => 'Utilities', 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rent', 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Salaries & Wages', 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Transport / Fuel', 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Supplies', 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Marketing', 'sort_order' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Maintenance', 'sort_order' => 70, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Other', 'sort_order' => 100, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('operation_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_expenses');
        Schema::dropIfExists('expense_categories');
    }
};
