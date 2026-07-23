<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use App\Support\TermHelper;

class GradeHelper
{
    protected static $classStandingTypes = ['quiz', 'assignment', 'project'];

    protected static $equivalentTable = [
        ['min' => 97, 'grade' => 1.00],
        ['min' => 94, 'grade' => 1.25],
        ['min' => 91, 'grade' => 1.50],
        ['min' => 88, 'grade' => 1.75],
        ['min' => 85, 'grade' => 2.00],
        ['min' => 82, 'grade' => 2.25],
        ['min' => 79, 'grade' => 2.50],
        ['min' => 76, 'grade' => 2.75],
        ['min' => 75, 'grade' => 3.00],
    ];

    public static function computeTaskAverage($schedule_id, $student_id, $term)
    {
        $rows = DB::table('student_tasks')
            ->join('task_submissions', function ($join) use ($student_id) {
                $join->on('student_tasks.id', '=', 'task_submissions.task_id')
                    ->where('task_submissions.student_id', '=', $student_id);
            })
            ->where('student_tasks.class_schedule_id', $schedule_id)
            ->where('student_tasks.term', $term)
            ->whereIn('student_tasks.activity_type', self::$classStandingTypes)
            ->whereNotNull('task_submissions.grade')
            ->select('task_submissions.grade', 'student_tasks.max_score')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $percentages = $rows->map(function ($r) {
            return $r->max_score > 0 ? ($r->grade / $r->max_score) * 100 : 0;
        });

        return round($percentages->avg(), 2);
    }

    public static function computeAttendanceRate($schedule_id, $student_id, $term)
    {
        $termPeriods = TermHelper::getActiveTermPeriods();

        $records = DB::table('attendances')
            ->where('class_schedule_id', $schedule_id)
            ->where('student_id', $student_id)
            ->get();

        $filtered = $records->filter(function ($r) use ($termPeriods, $term) {
            return TermHelper::deriveTerm($r->attendance_date, $termPeriods) === $term;
        });

        if ($filtered->isEmpty()) {
            return null;
        }

        $earned = $filtered->sum(function ($r) {
            if ($r->status === 'Present') return 1;
            if ($r->status === 'Late') return 0.5;
            return 0;
        });

        return round(($earned / $filtered->count()) * 100, 2);
    }

    public static function computeClassStanding($schedule_id, $student_id, $term)
    {
        $taskAvg = self::computeTaskAverage($schedule_id, $student_id, $term);
        $attendanceRate = self::computeAttendanceRate($schedule_id, $student_id, $term);

        if ($taskAvg === null && $attendanceRate === null) {
            return null;
        }
        if ($taskAvg === null) {
            return $attendanceRate;
        }
        if ($attendanceRate === null) {
            return $taskAvg;
        }

        return round(($taskAvg + $attendanceRate) / 2, 2);
    }

    public static function computeMajorExam($schedule_id, $student_id, $term)
    {
        $rows = DB::table('student_tasks')
            ->join('task_submissions', function ($join) use ($student_id) {
                $join->on('student_tasks.id', '=', 'task_submissions.task_id')
                    ->where('task_submissions.student_id', '=', $student_id);
            })
            ->where('student_tasks.class_schedule_id', $schedule_id)
            ->where('student_tasks.term', $term)
            ->where('student_tasks.activity_type', 'exam')
            ->whereNotNull('task_submissions.grade')
            ->select('task_submissions.grade', 'student_tasks.max_score')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $percentages = $rows->map(function ($r) {
            return $r->max_score > 0 ? ($r->grade / $r->max_score) * 100 : 0;
        });

        return round($percentages->avg(), 2);
    }

    public static function computeFinalPercent($classStanding, $majorExam)
    {
        if ($classStanding === null || $majorExam === null) {
            return null;
        }

        return round(($classStanding * 0.60) + ($majorExam * 0.40), 2);
    }

    public static function computeEquivalent($percent)
    {
        if ($percent === null) {
            return null;
        }

        if ($percent < 75) {
            return 5.00;
        }

        foreach (self::$equivalentTable as $row) {
            if ($percent >= $row['min']) {
                return $row['grade'];
            }
        }

        return 5.00;
    }

    public static function computeAutoGrades($schedule_id, $student_id, $term)
    {
        $classStanding = self::computeClassStanding($schedule_id, $student_id, $term);
        $majorExam = self::computeMajorExam($schedule_id, $student_id, $term);
        $finalPercent = self::computeFinalPercent($classStanding, $majorExam);
        $equivalent = self::computeEquivalent($finalPercent);

        return [
            'class_standing' => $classStanding,
            'major_exam' => $majorExam,
            'equivalent' => $equivalent,
        ];
    }
}