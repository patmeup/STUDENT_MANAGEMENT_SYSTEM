<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->foreignId('academic_period_id')
                ->nullable()
                ->after('id')
                ->constrained('academic_periods')
                ->nullOnDelete();
        });

        $activePeriod = DB::table('academic_periods')->where('is_active', 1)->first();

        if ($activePeriod) {
            DB::table('class_schedules')
                ->whereNull('academic_period_id')
                ->update(['academic_period_id' => $activePeriod->id]);
        }
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropForeign(['academic_period_id']);
            $table->dropColumn('academic_period_id');
        });
    }
};