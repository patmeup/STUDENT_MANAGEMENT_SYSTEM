<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_classroom_coursework', function (Blueprint $table) {
            if (!Schema::hasColumn('google_classroom_coursework', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('due_date');
            }
        });

        Schema::table('google_classroom_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('google_classroom_materials', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('google_classroom_coursework', function (Blueprint $table) {
            $table->dropColumn('posted_at');
        });

        Schema::table('google_classroom_materials', function (Blueprint $table) {
            $table->dropColumn('posted_at');
        });
    }
};