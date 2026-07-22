<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            // Checks if column doesn't exist before adding to prevent errors
            if (!Schema::hasColumn('task_submissions', 'feedback')) {
                $table->text('feedback')->nullable()->after('grade');
            }
        });
    }

    public function down()
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->dropColumn('feedback');
        });
    }
};