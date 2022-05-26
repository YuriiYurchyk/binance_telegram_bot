<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\HandledFiles;

return new class extends Migration {
    public function up()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->boolean('handled_success')->default(0);
        });
    }

    public function down()
    {
        Schema::table('handled_files', function (Blueprint $table) {
            $table->dropColumn('handled_success');
        });
    }
};
