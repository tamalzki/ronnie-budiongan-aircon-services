<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethodToSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::table('sales', function (Blueprint $table) {
        $table->enum('payment_method', [
            'cash',
            'gcash',
            'bank_transfer',
            'cheque'
        ])->nullable()->after('payment_type');
    });
}

public function down()
{
    Schema::table('sales', function (Blueprint $table) {
        $table->dropColumn('payment_method');
    });
}
}
