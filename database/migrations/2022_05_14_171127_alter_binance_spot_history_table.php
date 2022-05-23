<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->dropIndex(['source_file_name']);
            $table->dropColumn('source_file_name');
        });

    }

    public function down()
    {
        throw new Exception();

        Schema::table('binance_spot_history', function (Blueprint $table) {
            $table->string('source_file_name')->nullable();
            $table->index(['source_file_name']);
        });
    }
};
