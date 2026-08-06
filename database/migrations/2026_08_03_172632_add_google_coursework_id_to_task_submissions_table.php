<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id')->nullable()->change();
            $table->unsignedBigInteger('google_coursework_id')->nullable()->after('task_id');
            $table->index('google_coursework_id');
        });
    }

    public function down(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->dropIndex(['google_coursework_id']);
            $table->dropColumn('google_coursework_id');
            $table->unsignedBigInteger('task_id')->nullable(false)->change();
        });
    }
};