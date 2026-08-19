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
        $attendance = self::computeAttendanceRate($schedule_id, $student_id, $term);
        $classStanding = self::computeClassStanding($schedule_id, $student_id, $term);
        $majorExam = self::computeMajorExam($schedule_id, $student_id, $term);
        $finalPercent = self::computeFinalPercent($classStanding, $majorExam);
        $equivalent = self::computeEquivalent($finalPercent);

        return [
            'attendance'     => $attendance,
            'class_standing' => $classStanding,
            'major_exam'     => $majorExam,
            'equivalent'     => $equivalent,
        ];
    }

    public static function computeAndSaveAutoGrades($schedule_id, $student_id, $student_number, $term)
    {
        $computedGrades = self::computeAutoGrades($schedule_id, $student_id, $term);

        DB::table('student_term_grades')->updateOrInsert(
            [
                'schedule_id'    => $schedule_id,
                'student_number' => $student_number, 
                'term'           => $term,
            ],
            [
                'attendance'     => $computedGrades['attendance'],
                'class_standing' => $computedGrades['class_standing'],
                'major_exam'     => $computedGrades['major_exam'],
                'equivalent'     => $computedGrades['equivalent'],
                'is_submitted'   => 0, 
                'updated_at'     => now(),
            ]
        );

        return $computedGrades;
    }

    public static function computeSemestralGrade($scheduleId, $studentNumber)
    {
        $grades = DB::table('student_term_grades')
            ->where('schedule_id', $scheduleId)
            ->where('student_number', $studentNumber)
            ->where('is_submitted', 1)
            ->whereNotNull('equivalent')
            ->pluck('equivalent', 'term');

        if ($grades->isEmpty()) {
            return null;
        }

        return round($grades->avg(), 2);
    }

    public static function getSubmittedTermCount($scheduleId, $studentNumber)
    {
        return DB::table('student_term_grades')
            ->where('schedule_id', $scheduleId)
            ->where('student_number', $studentNumber)
            ->where('is_submitted', 1)
            ->whereNotNull('equivalent')
            ->count();
    }

    protected static function getOrderedTermGrades($scheduleId, $studentNumber)
    {
        $rows = DB::table('student_term_grades')
            ->where('schedule_id', $scheduleId)
            ->where('student_number', $studentNumber)
            ->where('is_submitted', 1)
            ->whereNotNull('equivalent')
            ->pluck('equivalent', 'term');

        return [
            'prelim'    => $rows->get('prelim'),
            'midterm'   => $rows->get('midterm'),
            'semifinal' => $rows->get('semifinal'),
            'final'     => $rows->get('final'),
        ];
    }

    public static function trainModels($academicPeriodId)
    {
        $completedStudents = self::getCompletedTrainingData($academicPeriodId);

        if (count($completedStudents) < 5) {
            return [
                'success' => false,
                'message' => 'The training data is insufficient. Required: at least 5 complete records. Current: ' . count($completedStudents),
            ];
        }

        $results = [];

        $results[1] = self::trainStage($academicPeriodId, 1, $completedStudents, ['prelim']);

        $results[2] = self::trainStage($academicPeriodId, 2, $completedStudents, ['prelim', 'midterm']);

        $results[3] = self::trainStage($academicPeriodId, 3, $completedStudents, ['prelim', 'midterm', 'semifinal']);

        return ['success' => true, 'results' => $results];
    }

    protected static function getCompletedTrainingData($academicPeriodId)
    {
        $scheduleIds = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->join('curricula', 'subjects.id', '=', 'curricula.subject_id')
            ->join('academic_periods', 'curricula.semester', '=', 'academic_periods.semester')
            ->where('academic_periods.id', $academicPeriodId)
            ->distinct()
            ->pluck('class_schedules.id');

        $allGrades = DB::table('student_term_grades')
            ->whereIn('schedule_id', $scheduleIds)
            ->where('is_submitted', 1)
            ->whereNotNull('equivalent')
            ->get()
            ->groupBy(fn($row) => $row->schedule_id . '_' . $row->student_number);

        $completed = [];

        foreach ($allGrades as $key => $rows) {
            $byTerm = $rows->pluck('equivalent', 'term');

            if ($byTerm->has('prelim') && $byTerm->has('midterm') &&
                $byTerm->has('semifinal') && $byTerm->has('final')) {

                $semestral = round(
                    ($byTerm['prelim'] + $byTerm['midterm'] + $byTerm['semifinal'] + $byTerm['final']) / 4,
                    2
                );

                $completed[] = [
                    'prelim'    => (float) $byTerm['prelim'],
                    'midterm'   => (float) $byTerm['midterm'],
                    'semifinal' => (float) $byTerm['semifinal'],
                    'semestral' => $semestral,
                ];
            }
        }

        return $completed;
    }

    protected static function trainStage($academicPeriodId, $stage, $data, $featureKeys)
    {
        $X = [];
        $y = [];

        foreach ($data as $row) {
            $features = [1.0]; 
            foreach ($featureKeys as $key) {
                $features[] = $row[$key];
            }
            $X[] = $features;
            $y[] = $row['semestral'];
        }

        $coefficients = self::solveOLS($X, $y);
        $rSquared = self::computeRSquared($X, $y, $coefficients);

        DB::table('ml_grade_models')->updateOrInsert(
            ['academic_period_id' => $academicPeriodId, 'stage' => $stage],
            [
                'coefficients' => json_encode($coefficients),
                'sample_size'  => count($data),
                'r_squared'    => $rSquared,
                'trained_at'   => now(),
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );

        return [
            'stage'        => $stage,
            'coefficients' => $coefficients,
            'sample_size'  => count($data),
            'r_squared'    => $rSquared,
        ];
    }

    public static function predict($scheduleId, $studentNumber, $academicPeriodId)
    {
        $terms = self::getOrderedTermGrades($scheduleId, $studentNumber);
        $submittedCount = collect($terms)->filter(fn($v) => $v !== null)->count();

        if ($submittedCount === 4) {
            $actual = round(($terms['prelim'] + $terms['midterm'] + $terms['semifinal'] + $terms['final']) / 4, 2);
            return [
                'type'       => 'actual',
                'value'      => $actual,
                'stage'      => null,
                'confidence' => null,
            ];
        }

        $stage = null;
        $features = [1.0];

        if ($terms['prelim'] !== null && $terms['midterm'] === null) {
            $stage = 1;
            $features[] = (float) $terms['prelim'];
        } elseif ($terms['prelim'] !== null && $terms['midterm'] !== null && $terms['semifinal'] === null) {
            $stage = 2;
            $features[] = (float) $terms['prelim'];
            $features[] = (float) $terms['midterm'];
        } elseif ($terms['prelim'] !== null && $terms['midterm'] !== null && $terms['semifinal'] !== null) {
            $stage = 3;
            $features[] = (float) $terms['prelim'];
            $features[] = (float) $terms['midterm'];
            $features[] = (float) $terms['semifinal'];
        } else {
            return [
                'type'       => 'unavailable',
                'value'      => null,
                'stage'      => null,
                'confidence' => null,
            ];
        }

        $model = DB::table('ml_grade_models')
            ->where('academic_period_id', $academicPeriodId)
            ->where('stage', $stage)
            ->first();

        if (!$model) {
            return [
                'type'       => 'model_not_trained',
                'value'      => null,
                'stage'      => $stage,
                'confidence' => null,
            ];
        }

        $coefficients = json_decode($model->coefficients, true);
        $predicted = 0;
        foreach ($features as $i => $val) {
            $predicted += $val * $coefficients[$i];
        }

        $predicted = max(1.00, min(5.00, round($predicted, 2)));

        return [
            'type'       => 'predicted',
            'value'      => $predicted,
            'stage'      => $stage,
            'confidence' => $model->r_squared,
            'risk'       => $predicted > 3.00 ? 'at_risk' : 'passing',
        ];
    }

    protected static function solveOLS(array $X, array $y): array
    {
        $Xt = self::transpose($X);
        $XtX = self::matMul($Xt, $X);
        $XtY = self::matVecMul($Xt, $y);
        $XtXInv = self::invertMatrix($XtX);

        return self::matVecMul($XtXInv, $XtY);
    }

    protected static function transpose(array $matrix): array
    {
        $result = [];
        foreach ($matrix as $i => $row) {
            foreach ($row as $j => $val) {
                $result[$j][$i] = $val;
            }
        }
        return $result;
    }

    protected static function matMul(array $A, array $B): array
    {
        $result = [];
        $bRows = count($B);
        $bCols = count($B[0]);

        foreach ($A as $i => $rowA) {
            for ($j = 0; $j < $bCols; $j++) {
                $sum = 0;
                for ($k = 0; $k < $bRows; $k++) {
                    $sum += $rowA[$k] * $B[$k][$j];
                }
                $result[$i][$j] = $sum;
            }
        }

        return $result;
    }

    protected static function matVecMul(array $A, array $v): array
    {
        $result = [];
        foreach ($A as $i => $row) {
            $sum = 0;
            foreach ($row as $j => $val) {
                $sum += $val * $v[$j];
            }
            $result[$i] = $sum;
        }
        return $result;
    }

    protected static function invertMatrix(array $matrix): array
    {
        $n = count($matrix);
        $augmented = [];

        for ($i = 0; $i < $n; $i++) {
            $augmented[$i] = array_merge($matrix[$i], array_fill(0, $n, 0));
            $augmented[$i][$n + $i] = 1;
        }

        for ($i = 0; $i < $n; $i++) {
            $pivot = $augmented[$i][$i];

            if (abs($pivot) < 1e-10) {
                $augmented[$i][$i] += 1e-6;
                $pivot = $augmented[$i][$i];
            }

            for ($j = 0; $j < 2 * $n; $j++) {
                $augmented[$i][$j] /= $pivot;
            }

            for ($k = 0; $k < $n; $k++) {
                if ($k !== $i) {
                    $factor = $augmented[$k][$i];
                    for ($j = 0; $j < 2 * $n; $j++) {
                        $augmented[$k][$j] -= $factor * $augmented[$i][$j];
                    }
                }
            }
        }

        $inverse = [];
        for ($i = 0; $i < $n; $i++) {
            $inverse[$i] = array_slice($augmented[$i], $n, $n);
        }

        return $inverse;
    }

    protected static function computeRSquared(array $X, array $y, array $coefficients): float
    {
        $predictions = self::matVecMul($X, $coefficients);
        $yMean = array_sum($y) / count($y);

        $ssRes = 0;
        $ssTot = 0;

        foreach ($y as $i => $actual) {
            $ssRes += pow($actual - $predictions[$i], 2);
            $ssTot += pow($actual - $yMean, 2);
        }

        if ($ssTot == 0) {
            return 0;
        }

        return round(1 - ($ssRes / $ssTot), 4);
    }
}