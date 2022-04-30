<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('trading_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('base_coin');
            $table->string('quote_coin');
            $table->boolean('status');
            $table->timestamp('binance_added_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->foreign('base_coin')->references('name')->on('coins')->cascadeOnDelete();
            $table->foreign('quote_coin')->references('name')->on('coins')->cascadeOnDelete();

            $table->unique(['base_coin', 'quote_coin']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_pairs');
    }
};
