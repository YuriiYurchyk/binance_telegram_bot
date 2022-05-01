<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->index(['source_file_name']);
        });
    }

    public function down()
    {
        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->dropIndex(['source_file_name']);
        });
    }
};
