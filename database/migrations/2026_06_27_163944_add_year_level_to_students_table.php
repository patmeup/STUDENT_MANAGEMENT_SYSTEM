<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Idagdag ang year_level column pagkatapos ng course column
            $table->string('year_level')->default('1st Year')->after('course');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('year_level');
        });
    }

};
