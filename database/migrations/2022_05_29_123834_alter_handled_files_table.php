<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->boolean('file_exists_on_binance')->default(false);
        });
    }

    public function down()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->dropColumn('file_exists_on_binance');
        });
    }
};