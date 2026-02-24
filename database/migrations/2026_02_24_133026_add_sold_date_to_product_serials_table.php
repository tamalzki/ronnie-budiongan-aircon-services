<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoldDateToProductSerialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('product_serials', function (Blueprint $table) {
        $table->date('sold_date')->nullable()->after('received_date');
    });
}

public function down()
{
    Schema::table('product_serials', function (Blueprint $table) {
        $table->dropColumn('sold_date');
    });
}
}
