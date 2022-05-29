<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\TradingPair;

return new class extends Migration {

    public function up()
    {
        Schema::table('trading_pairs', function (Blueprint $table) {
            $table->string('pair_code')->nullable();
        });

        TradingPair::query()->each(function (TradingPair $tradingPair) {
            $tradingPair->update(                [
                    'pair_code' => $tradingPair->getTradingSpotPairCode(),
                ]
            );
        });
    }

    public function down()
    {
        throw new \Exception();

        Schema::table('trading_pairs', function (Blueprint $table) {
            $table->dropColumn('pair_code');
        });
    }
};
