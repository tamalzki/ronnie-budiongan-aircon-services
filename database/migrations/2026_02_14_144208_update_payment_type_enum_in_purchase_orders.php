<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN payment_type ENUM('full', '45days') NOT NULL DEFAULT 'full'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN payment_type ENUM('full', 'installment') NOT NULL DEFAULT 'full'");
    }
};