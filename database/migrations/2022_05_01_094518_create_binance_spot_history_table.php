<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('binance_spot_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trading_pair_id');
            $table->string('data_range', 3);
            $table->unsignedBigInteger('open_time');
            $table->unsignedBigInteger('close_time');
            $table->unsignedFloat('open', 20, 8);
            $table->unsignedFloat('high', 20, 8);
            $table->unsignedFloat('low', 20, 8);
            $table->unsignedFloat('close', 20, 8);

            $table->foreign('trading_pair_id')->references('id')->on('trading_pairs')->cascadeOnDelete();
            $table->unique(['trading_pair_id', 'data_range', 'open_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('binance_spot_history');
    }
};
