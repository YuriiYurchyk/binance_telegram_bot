<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('trading_pairs', function (Blueprint $table) {
            $table->timestamp('binance_removed_at')->nullable()->after('binance_added_at');
        });
    }

    public function down()
    {
        Schema::table('trading_pairs', function (Blueprint $table) {
            $table->dropColumn('binance_removed_at');
        });
    }
};
