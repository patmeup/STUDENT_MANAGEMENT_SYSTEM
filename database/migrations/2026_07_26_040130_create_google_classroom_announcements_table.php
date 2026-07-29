<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_classroom_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('google_classroom_courses')->onDelete('cascade');
            $table->string('google_announcement_id')->unique();
            $table->text('text')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_classroom_announcements');
    }
};