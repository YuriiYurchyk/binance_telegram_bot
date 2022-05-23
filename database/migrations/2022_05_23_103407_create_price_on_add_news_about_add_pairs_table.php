<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
//        Schema::dropIfExists('price_on_add_news_about_add_pairs');
        Schema::dropIfExists('price_on_add_news_about_add_pairs');
        Schema::create('price_on_add_news_about_add_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('quote_add_coin');
            $table->string('base_add_coin');
            $table->string('quote_analyzed_coin');
            $table->string('base_analyzed_coin');
            $table->timestamp('date_point_0')->nullable();
            $table->float('date_point_0_percent')->nullable();
            $table->timestamp('date_point_1')->nullable();
            $table->float('date_point_1_percent')->nullable();
            $table->timestamp('date_point_2')->nullable();
            $table->float('date_point_2_percent')->nullable();
            $table->timestamp('date_point_3')->nullable();
            $table->float('date_point_3_percent')->nullable();

            $table->unique(['quote_add_coin', 'base_add_coin', 'quote_analyzed_coin', 'base_analyzed_coin'], 'price_on_add_news_about_add_pairs_coins_index');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

//            $table->foreign('quote_add_coin')->references('name')->on('coins')->cascadeOnDelete();
            $table->foreign('base_add_coin')->references('name')->on('coins')->cascadeOnDelete();
            $table->foreign('quote_analyzed_coin')->references('name')->on('coins')->cascadeOnDelete();
            $table->foreign('base_analyzed_coin')->references('name')->on('coins')->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('price_on_add_news_about_add_pairs');
    }
};
