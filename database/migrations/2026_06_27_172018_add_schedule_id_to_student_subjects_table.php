<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('student_subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('schedule_id')->after('subject_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('student_subjects', function (Blueprint $table) {
            $table->dropColumn('schedule_id');
        });
    }

};
