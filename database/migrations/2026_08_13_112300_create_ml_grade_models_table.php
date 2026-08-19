<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_grade_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->onDelete('cascade');
            $table->unsignedTinyInteger('stage');
            $table->json('coefficients'); 
            $table->unsignedInteger('sample_size');
            $table->decimal('r_squared', 5, 4)->nullable(); 
            $table->timestamp('trained_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_grade_models');
    }
};