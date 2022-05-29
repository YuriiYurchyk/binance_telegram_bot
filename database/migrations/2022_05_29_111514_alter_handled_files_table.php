<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->unsignedBigInteger('trading_pair_id')->nullable();
            $table->foreign('trading_pair_id', 'handled_files_trading_pair_id_foreign')
                  ->references('id')
                  ->on('trading_pairs')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->dropForeign('handled_files_trading_pair_id_foreign');
            $table->dropColumn('trading_pair_id');
        });
    }
};
