<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align supplier_payments.payment_method with App\Support\PaymentMethod.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('supplier_payments')
            ->where('payment_method', 'check')
            ->update(['payment_method' => 'cheque']);

        DB::statement("ALTER TABLE supplier_payments MODIFY COLUMN payment_method ENUM(
            'cash',
            'gcash',
            'bank_transfer',
            'cheque'
        ) NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('supplier_payments')
            ->whereIn('payment_method', ['gcash', 'cheque'])
            ->update(['payment_method' => 'cash']);

        DB::statement("ALTER TABLE supplier_payments MODIFY COLUMN payment_method ENUM(
            'cash',
            'bank_transfer',
            'check'
        ) NOT NULL DEFAULT 'cash'");
    }
};
