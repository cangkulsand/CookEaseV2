<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bmis', function (Blueprint $table) {
            $table->integer('calorie_target')->nullable();
        });
    }

    public function down()
    {
        Schema::table('bmis', function (Blueprint $table) {
            $table->dropColumn('calorie_target');
        });
    }
};
