<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            // Awtomatikong bubuo ng Student Number (halimbawa: AU-2026-0001)
            $table->string('student_number')->unique();
            $table->date('registration_date');
            $table->string('student_type'); // New, Transferee, Cross-Enroll
            $table->string('course');
            
            // Name Profile
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable(); // <-- Nullable

            // Personal Profile
            $table->string('sex');
            $table->string('civil_status');
            $table->date('date_of_birth');
            $table->string('nationality');
            $table->string('place_of_birth')->nullable(); // <-- Nullable
            $table->text('city_address');
            $table->string('zip_code')->nullable(); // <-- Nullable
            $table->string('contact_no');

            // Parental Matrix
            $table->string('fathers_name')->nullable(); // <-- Nullable
            $table->string('fathers_occupation')->nullable(); // <-- Nullable
            $table->string('mothers_name')->nullable(); // <-- Nullable
            $table->string('mothers_occupation')->nullable(); // <-- Nullable

            // Educational Background (Required)
            $table->string('primary_school_name');
            $table->string('primary_school_address');
            $table->string('primary_school_year');
            $table->string('secondary_school_name');
            $table->string('secondary_school_address');
            $table->string('secondary_school_year');
            
            // College Background (Nullable para sa Freshmen)
            $table->string('college_school_name')->nullable(); // <-- Nullable
            $table->string('college_school_address')->nullable(); // <-- Nullable
            $table->string('college_school_year')->nullable(); // <-- Nullable

            // Credentials Checked (Ise-save natin bilang JSON array)
            $table->json('credentials')->nullable(); // <-- Nullable

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};