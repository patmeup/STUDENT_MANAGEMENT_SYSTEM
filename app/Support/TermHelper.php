<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TermHelper
{
    public static function getActiveTermPeriods()
    {
        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();

        if (!$activePeriod) {
            return collect();
        }

        return DB::table('term_periods')
            ->where('academic_period_id', $activePeriod->id)
            ->get();
    }

    public static function deriveTerm($date, $termPeriods)
    {
        $date = Carbon::parse($date)->toDateString();

        foreach ($termPeriods as $tp) {
            if ($date >= $tp->start_date && $date <= $tp->end_date) {
                return $tp->term;
            }
        }

        return null;
    }
}