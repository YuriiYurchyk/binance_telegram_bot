<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->tinyInteger('period', unsigned: true)->nullable();
        });
    }

    public function down()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->dropColumn('period');
        });
    }
};
