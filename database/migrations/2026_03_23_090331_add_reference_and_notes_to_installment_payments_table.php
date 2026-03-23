<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferenceAndNotesToInstallmentPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('installment_payments', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('payment_method');
            $table->text('notes')->nullable()->after('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('installment_payments', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'notes']);
        });
    }
}
