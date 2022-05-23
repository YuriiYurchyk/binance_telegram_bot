<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->unique(['trading_pair_id', 'open_time']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        throw new Exception();

        Schema::disableForeignKeyConstraints();

        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->dropUnique(['trading_pair_id', 'open_time']);
        });

        Schema::enableForeignKeyConstraints();
    }
};
