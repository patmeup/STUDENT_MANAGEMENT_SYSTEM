<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            // Grade na lang muna ang idadagdag natin at pwedeng NULL ang simula
            $table->decimal('grade', 5, 2)->nullable()->after('original_filename');
        });
    }

    public function down(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
