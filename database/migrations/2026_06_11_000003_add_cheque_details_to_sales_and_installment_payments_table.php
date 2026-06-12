<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChequeDetailsToSalesAndInstallmentPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('cheque_bank')->nullable()->after('payment_method');
            $table->string('cheque_number')->nullable()->after('cheque_bank');
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->string('cheque_bank')->nullable()->after('payment_method');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['cheque_bank', 'cheque_number']);
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->dropColumn('cheque_bank');
        });
    }
}
