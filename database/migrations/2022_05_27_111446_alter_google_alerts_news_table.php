<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('google_alerts_news', function (Blueprint $table) {
            $table->string('url', 2048)->change();
        });
    }

    public function down()
    {
        Schema::table('google_alerts_news', function (Blueprint $table) {
            $table->string('google_alerts_url')->change();
        });
    }
};
