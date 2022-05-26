<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->unsignedBigInteger('handled_file_id')->nullable();
            $table->foreign('handled_file_id')->references('id')->on('handled_files')
                                                                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->dropColumn('handled_file_id');
        });
    }
};
