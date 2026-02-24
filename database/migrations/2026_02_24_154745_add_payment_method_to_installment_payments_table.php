<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethodToInstallmentPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('installment_payments', function (Blueprint $table) {
        $table->enum('payment_method', [
            'cash',
            'gcash',
            'bank_transfer',
            'cheque'
        ])->nullable()->after('amount_paid');
    });
}

public function down()
{
    Schema::table('installment_payments', function (Blueprint $table) {
        $table->dropColumn('payment_method');
    });
}
}
