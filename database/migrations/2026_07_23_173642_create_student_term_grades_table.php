<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_term_grades', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('schedule_id');

            $table->string('student_number');

            $table->enum('term', [
                'prelim',
                'midterm',
                'semifinal',
                'final'
            ]);

            $table->decimal('attendance', 5, 2)->nullable();
            $table->decimal('class_standing', 5, 2)->nullable();
            $table->decimal('major_exam', 5, 2)->nullable();

            $table->decimal('equivalent', 4, 2)->nullable();

            $table->boolean('is_manual')->default(false);

            $table->boolean('is_submitted')->default(false);

            $table->timestamps();

            $table->unique(
                ['schedule_id', 'student_number', 'term'],
                'student_term_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_term_grades');
    }
};
