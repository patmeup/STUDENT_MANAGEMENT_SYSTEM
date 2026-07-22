<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_schedule_id'); 
            $table->string('student_id'); 
            $table->date('attendance_date'); 
            $table->enum('status', ['Present', 'Absent', 'Late', 'Excused'])->default('Present');
            $table->text('remarks')->nullable(); 
            $table->timestamps();

            $table->foreign('class_schedule_id')->references('id')->on('class_schedules')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            
            $table->unique(['class_schedule_id', 'student_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};