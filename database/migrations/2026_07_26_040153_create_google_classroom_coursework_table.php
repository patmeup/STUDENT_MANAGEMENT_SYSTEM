<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_classroom_coursework', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('google_classroom_courses')->onDelete('cascade');
            $table->string('google_coursework_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->string('alternate_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_classroom_coursework');
    }
};