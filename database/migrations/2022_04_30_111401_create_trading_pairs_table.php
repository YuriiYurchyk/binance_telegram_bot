<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\TradingPair;

return new class extends Migration {

    public function up()
    {
        Schema::create('trading_pairs', function (Blueprint $table) {
            $table->id();
            $table->boolean('status');
            $table->timestamp('binance_added_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->foreignIdFor(TradingPair::class, 'base_coin_id')->cascadeOnDelete();
            $table->foreignIdFor(TradingPair::class, 'quote_coin_id')->cascadeOnDelete();

            $table->unique(['base_coin_id', 'quote_coin_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_pairs');
    }
};
