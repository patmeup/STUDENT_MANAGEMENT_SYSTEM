<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_classroom_materials', function (Blueprint $table) {
            $table->string('drive_file_id')->nullable()->after('alternate_link');
            $table->string('drive_file_name')->nullable()->after('drive_file_id');
            $table->string('drive_mime_type')->nullable()->after('drive_file_name');
        });
    }

    public function down(): void
    {
        Schema::table('google_classroom_materials', function (Blueprint $table) {
            $table->dropColumn(['drive_file_id', 'drive_file_name', 'drive_mime_type']);
        });
    }
};