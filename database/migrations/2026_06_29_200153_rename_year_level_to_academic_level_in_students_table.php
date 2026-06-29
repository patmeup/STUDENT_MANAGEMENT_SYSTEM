<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('year_level', 'academic_level');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('academic_level', 'year_level');
        });
    }

};
