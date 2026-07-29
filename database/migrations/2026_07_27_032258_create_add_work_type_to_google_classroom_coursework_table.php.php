<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_classroom_coursework', function (Blueprint $table) {
            $table->string('work_type')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('google_classroom_coursework', function (Blueprint $table) {
            $table->dropColumn('work_type');
        });
    }
};