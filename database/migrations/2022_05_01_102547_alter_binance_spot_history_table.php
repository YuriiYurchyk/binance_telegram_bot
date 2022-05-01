<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->string('source_file_name')->nullable();
        });
    }

    public function down()
    {
        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->dropColumn('source_file_name');
        });
    }
};
