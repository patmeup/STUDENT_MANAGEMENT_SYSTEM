<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class PredictiveGradeHelper
{
    /**
     * Kunin ang semestral grade (average ng 4 term equivalents) ng isang
     * schedule_id + student_id. Descriptive lang ito, HINDI ML — simpleng
     * average ng mga submitted term grades.
     */
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

    /**
     * Ilang terms na ba ang naisumite (submitted) para sa student na ito?
     */
    public static function getSubmittedTermCount($scheduleId, $studentNumber)
    {
        return DB::table('student_term_grades')
            ->where('schedule_id', $scheduleId)
            ->where('student_number', $studentNumber)
            ->where('is_submitted', 1)
            ->whereNotNull('equivalent')
            ->count();
    }

    /**
     * Kunin ang mga equivalent grades ng student, naka-order:
     * [prelim, midterm, semifinal, final]
     */
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

    // ===================== TRAINING =====================

    /**
     * I-train ang tatlong stage models para sa isang academic period.
     * Ginagamit lang ang mga student na may KUMPLETONG apat na terms
     * (para malaman natin ang totoong "ground truth" outcome).
     */
    public static function trainModels($academicPeriodId)
    {
        $completedStudents = self::getCompletedTrainingData($academicPeriodId);

        if (count($completedStudents) < 5) {
            return [
                'success' => false,
                'message' => 'Hindi pa sapat ang training data (kailangan ng at least 5 kumpletong records). Kasalukuyan: ' . count($completedStudents),
            ];
        }

        $results = [];

        // Stage 1: Prelim lang -> predict semestral
        $results[1] = self::trainStage($academicPeriodId, 1, $completedStudents, ['prelim']);

        // Stage 2: Prelim + Midterm -> predict semestral
        $results[2] = self::trainStage($academicPeriodId, 2, $completedStudents, ['prelim', 'midterm']);

        // Stage 3: Prelim + Midterm + Semifinal -> predict semestral
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
        // Bumuo ng X matrix (may bias column na 1.0) at y vector
        $X = [];
        $y = [];

        foreach ($data as $row) {
            $features = [1.0]; // intercept term
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
            'stage'         => $stage,
            'coefficients'  => $coefficients,
            'sample_size'   => count($data),
            'r_squared'     => $rSquared,
        ];
    }

    /**
     * Ordinary Least Squares via Normal Equation: β = (XᵀX)⁻¹Xᵀy
     * Pure PHP matrix math — walang external library.
     */
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

    /**
     * Matrix inversion gamit ang Gauss-Jordan elimination.
     * Gagana ito para sa maliliit na matrices (2x2 hanggang 4x4),
     * sapat na para sa 1-3 features + intercept natin.
     */
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
                // Regularization: dagdagan ng maliit na value para maiwasan
                // ang singular matrix (nangyayari kapag limitado pa ang data)
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

    public static function predict($scheduleId, $studentNumber, $academicPeriodId)
    {
        $terms = self::getOrderedTermGrades($scheduleId, $studentNumber);
        $submittedCount = collect($terms)->filter(fn($v) => $v !== null)->count();

        if ($submittedCount === 4) {
            $actual = round(($terms['prelim'] + $terms['midterm'] + $terms['semifinal'] + $terms['final']) / 4, 2);
            return [
                'type' => 'actual',
                'value' => $actual,
                'stage' => null,
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
                'type' => 'unavailable',
                'value' => null,
                'stage' => null,
                'confidence' => null,
            ];
        }

        $model = DB::table('ml_grade_models')
            ->where('academic_period_id', $academicPeriodId)
            ->where('stage', $stage)
            ->first();

        if (!$model) {
            return [
                'type' => 'model_not_trained',
                'value' => null,
                'stage' => $stage,
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
            'type' => 'predicted',
            'value' => $predicted,
            'stage' => $stage,
            'confidence' => $model->r_squared,
            'risk' => $predicted > 3.00 ? 'at_risk' : 'passing',
        ];
    }
}