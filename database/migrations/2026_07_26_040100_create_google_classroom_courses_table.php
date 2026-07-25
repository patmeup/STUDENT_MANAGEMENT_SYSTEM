<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_classroom_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('google_classroom_connections')->onDelete('cascade');
            $table->string('google_course_id')->unique();
            $table->string('course_name');
            $table->string('course_section')->nullable();
            $table->unsignedBigInteger('linked_class_schedule_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_classroom_courses');
    }
};