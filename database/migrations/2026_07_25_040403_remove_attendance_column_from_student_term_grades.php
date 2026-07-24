<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_term_grades', function (Blueprint $table) {
            $table->dropColumn('attendance');
        });
    }

    public function down(): void
    {
        Schema::table('student_term_grades', function (Blueprint $table) {
            $table->decimal('attendance', 5, 2)->nullable();
        });
    }
};