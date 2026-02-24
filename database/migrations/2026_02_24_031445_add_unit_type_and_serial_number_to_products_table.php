<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'unit_type')) {
                $table->enum('unit_type', ['indoor', 'outdoor'])->after('model')->nullable();
            }
            if (!Schema::hasColumn('products', 'serial_number')) {
                $table->string('serial_number')->nullable()->after('unit_type');
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'serial_number']);
        });
    }
};