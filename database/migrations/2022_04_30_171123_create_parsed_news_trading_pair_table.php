<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parsed_news_trading_pair', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trading_pair_id');
            $table->unsignedBigInteger('parsed_news_id');

            $table->foreign('trading_pair_id')->references('id')->on('trading_pairs');
            $table->foreign('parsed_news_id')->references('id')->on('parsed_news');

            $table->unique(['parsed_news_id', 'trading_pair_id',]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parsed_news_trading_pair');
    }
};
