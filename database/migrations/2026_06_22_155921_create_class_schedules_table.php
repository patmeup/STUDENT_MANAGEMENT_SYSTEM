<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('subject_id');
            
            $table->string('section_name');
            
            $table->string('class_days');
            
            $table->time('start_time');
            $table->time('end_time');
            
            $table->string('room_assignment');
            
            $table->timestamps();

            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};