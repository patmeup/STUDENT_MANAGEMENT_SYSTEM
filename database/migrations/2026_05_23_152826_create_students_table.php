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
            // Awtomatikong bubuo ng Student Number (halimbawa: 26-00001)
            $table->string('student_id')->unique();
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

            $table->string('path_psa')->nullable();
            $table->string('path_2x2')->nullable();
            
            // Freshman specific files
            $table->string('path_f138')->nullable();
            $table->string('path_good_moral_new')->nullable();
            $table->string('path_diploma')->nullable();
            
            // Transferee specific files
            $table->string('path_tor')->nullable();
            $table->string('path_dismissal')->nullable();
            $table->string('path_good_moral_trans')->nullable();
            
            // Cross Enrollee specific files
            $table->string('path_permit')->nullable();

            // Credentials Checked (JSON array backup ledger)
            $table->json('credentials')->nullable(); // <-- Nullable

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};