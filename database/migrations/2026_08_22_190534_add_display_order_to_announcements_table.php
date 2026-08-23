<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('announcements', function (Blueprint $table) {
        $table->integer('display_order')->default(0)->after('image_path');
    });
}

public function down(): void
{
    Schema::table('announcements', function (Blueprint $table) {
        $table->dropColumn('display_order');
    });
}
};
