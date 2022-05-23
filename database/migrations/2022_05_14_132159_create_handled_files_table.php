<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('handled_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->unique()->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('handled_files');
    }
};
