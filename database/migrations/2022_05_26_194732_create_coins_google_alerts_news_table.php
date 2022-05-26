<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('coins_google_alerts_news', function (Blueprint $table) {
            $table->unsignedBigInteger('coins_id');
            $table->unsignedBigInteger('google_alerts_news_id');
            $table->unique(['coins_id', 'google_alerts_news_id']);

            $table->foreign('coins_id')
                  ->references('id')
                  ->on('coins')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();;
            $table->foreign('google_alerts_news_id')
                  ->references('id')
                  ->on('google_alerts_news')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down()
    {
        Schema::dropIfExists('coins_google_alerts_news');
    }
};
