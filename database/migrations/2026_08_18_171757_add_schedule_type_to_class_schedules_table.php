<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->enum('schedule_type', ['LEC', 'LAB'])->default('LEC')->after('subject_id');
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('schedule_type');
        });
    }

};
