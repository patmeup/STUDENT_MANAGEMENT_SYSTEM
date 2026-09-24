<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_term_grades_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->string('student_number');
            $table->string('based_on_term'); 

            $table->decimal('predicted_midterm', 5, 2)->nullable();
            $table->decimal('predicted_semifinal', 5, 2)->nullable();
            $table->decimal('predicted_final', 5, 2)->nullable();
            $table->decimal('predicted_semestral', 5, 2)->nullable();

            $table->timestamps();

            $table->index(['schedule_id', 'student_number']);
            $table->unique(['schedule_id', 'student_number', 'based_on_term'], 'unique_student_prediction_stage');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_term_grades_predictions');
    }
};