<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Nagdadagdag ng unique at nullable na email column pagkatapos ng contact_no
            $table->string('email')->nullable()->unique()->after('contact_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Tinatanggal ang column kapag ni-rollback ang migration
            $table->dropColumn('email');
        });
    }
};