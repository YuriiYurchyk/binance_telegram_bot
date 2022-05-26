<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->unique(['trading_pair_id', 'open_time', 'close_time']);
        });
    }

    public function down()
    {
        throw new Exception();
    }
};
