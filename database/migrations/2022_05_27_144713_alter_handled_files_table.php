<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->unsignedBigInteger('monthly_file_id')->nullable();
            $table->foreign('monthly_file_id')->references('id')->on('handled_files')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->dropForeign('monthly_file_id');
            $table->dropColumn('monthly_file_id');
        });
    }
};
