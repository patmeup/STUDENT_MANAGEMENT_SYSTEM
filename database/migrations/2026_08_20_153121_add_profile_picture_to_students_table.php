<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Inalis ang ->after() para walang kailangang specific column name
            $table->string('profile_picture')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Tinatatanggal ang column kapag nag-rollback
            $table->dropColumn('profile_picture');
        });
    }
};