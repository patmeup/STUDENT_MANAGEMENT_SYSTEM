<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\DocumentRequest;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Support\GradeHelper;

class StudentController extends Controller
{
    public function dashboard()
    {
        $studentNumber = session('student_id');

        $student = DB::table('students')
            ->where('student_id', $studentNumber)
            ->first();

        $announcements = DB::table('announcements')
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $activePeriod = DB::table('academic_periods')->where('is_active', 1)->first();
        $activePeriodId = $activePeriod ? $activePeriod->id : null;

        $enrolledClassesQuery = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('student_subjects.student_number', $studentNumber)
            ->where('student_subjects.status', 'Enrolled');

        if ($activePeriodId) {
            $enrolledClassesQuery->where('class_schedules.academic_period_id', $activePeriodId);
        }

        $enrolledClasses = $enrolledClassesQuery->select(
                'class_schedules.id as schedule_id',
                'subjects.id as subject_id',
                'subjects.total_units',
                'subjects.subject_code'
            )
            ->get();

        $totalEnrolledClasses = $enrolledClasses->unique('subject_id')->count();
        $enrolledScheduleIds = $enrolledClasses->pluck('schedule_id');

        $googleCourseIds = DB::table('google_classroom_courses')
            ->whereIn('linked_class_schedule_id', $enrolledScheduleIds)
            ->pluck('id');

        $googlePendingCount = 0;
        if ($googleCourseIds->isNotEmpty()) {
            $googlePendingCount = DB::table('google_classroom_coursework')
                ->whereIn('course_id', $googleCourseIds)
                ->whereNotNull('due_date')
                ->where('due_date', '!=', '')
                ->whereNotExists(function ($query) use ($studentNumber) {
                    $query->select(DB::raw(1))
                        ->from('task_submissions')
                        ->whereColumn('task_submissions.google_coursework_id', 'google_classroom_coursework.id')
                        ->where('task_submissions.student_id', $studentNumber);
                })
                ->count();
        }

        $localPendingCount = DB::table('student_tasks')
            ->whereIn('class_schedule_id', $enrolledScheduleIds)
            ->whereNull('google_coursework_id')
            ->whereNotNull('due_date')
            ->where('due_date', '!=', '')
            ->whereNotExists(function ($query) use ($studentNumber) {
                $query->select(DB::raw(1))
                    ->from('task_submissions')
                    ->whereColumn('task_submissions.task_id', 'student_tasks.id')
                    ->where('task_submissions.student_id', $studentNumber);
            })
            ->count();

        $pendingTasksCount = $localPendingCount + $googlePendingCount;

        return view('student.dashboard', compact(
            'student',
            'announcements',
            'totalEnrolledClasses',
            'pendingTasksCount'
        ));
    }

    public function updateCredentials(Request $request)
    {
        $studentNumber = session('student_id');

        $student = Student::where('student_id', $studentNumber)->firstOrFail();

        $request->validate([
            'email' => 'required|email|unique:students,email,' . $student->id . ',id',
            'contact_no' => 'required|string|max:20',
            'civil_status' => 'required|in:Single,Married,Divorced,Widowed',
            'city_address' => 'required|string|max:500',
        ]);

        $student->update([
            'email' => $request->email,
            'contact_no' => $request->contact_no,
            'civil_status' => $request->civil_status,
            'city_address' => $request->city_address,
        ]);

        return redirect()->back()->with('success', 'Personal credentials updated successfully.');
    }

    public function viewClassDashboard($schedule_id, Request $request)
    {
        $studentNumber = session('student_id');

        $isEnrolled = DB::table('student_subjects')
            ->where('schedule_id', $schedule_id)
            ->where('student_number', $studentNumber)
            ->where('status', 'Enrolled')
            ->exists();

        if (!$isEnrolled) {
            return redirect()->route('student.my_classes')->with('error', 'Unauthorized access to this class.');
        }

        $activePeriod = DB::table('academic_periods')->where('is_active', 1)->first();
        $academicPeriodId = $activePeriod ? $activePeriod->id : 1;

        $termPeriods = DB::table('term_periods')
            ->where('academic_period_id', $academicPeriodId)
            ->get()
            ->keyBy(function($item) {
                return strtolower(trim($item->term));
            });

        $attendanceRecords = DB::table('attendances')
            ->where('class_schedule_id', $schedule_id)
            ->where('student_id', $studentNumber)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $classDetails = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('class_schedules.id', $schedule_id)
            ->select('class_schedules.*', 'subjects.subject_code', 'subjects.subject_title')
            ->first();

        $activities = DB::table('student_tasks')
            ->leftJoin('task_submissions', function($join) use ($studentNumber) {
                $join->on('student_tasks.id', '=', 'task_submissions.task_id')
                    ->where('task_submissions.student_id', '=', $studentNumber);
            })
            ->where('student_tasks.class_schedule_id', $schedule_id)
            ->select('student_tasks.*', 'task_submissions.grade as student_grade')
            ->orderBy('student_tasks.created_at', 'desc')
            ->get();

        $materials = [];
        if (Schema::hasTable('class_materials')) {
            $materials = DB::table('class_materials')
                ->where('class_schedule_id', $schedule_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $gLinkedCourse = DB::table('google_classroom_courses')
            ->where('linked_class_schedule_id', $schedule_id)
            ->first();

        $gCourseWorkAll = collect();
        $gMaterialsAll = collect();

        if ($gLinkedCourse) {
            $gCourseWorkAll = DB::table('google_classroom_coursework')
                ->where('course_id', $gLinkedCourse->id)
                ->orderBy('due_date', 'desc')
                ->get();

            $gMaterialsAll = DB::table('google_classroom_materials')
                ->where('course_id', $gLinkedCourse->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $resolveTermForItem = function($itemDate) use ($termPeriods) {
            if (!$itemDate) return 'prelim';

            $targetDate = date('Y-m-d', strtotime($itemDate));
            $sortedTerms = $termPeriods->sortBy('start_date');

            foreach ($sortedTerms as $termKey => $period) {
                if ($targetDate >= $period->start_date && $targetDate <= $period->end_date) {
                    return $termKey;
                }
            }

            $nearestTerm = null;
            $minDiff = PHP_INT_MAX;

            foreach ($sortedTerms as $termKey => $period) {
                if ($targetDate < $period->start_date) {
                    $diff = strtotime($period->start_date) - strtotime($targetDate);
                    if ($diff < $minDiff) {
                        $minDiff = $diff;
                        $nearestTerm = $termKey;
                    }
                }
            }

            if ($nearestTerm) {
                return $nearestTerm;
            }

            return $sortedTerms->keys()->last() ?? 'final';
        };

        $defaultActiveTerm = $resolveTermForItem(now()->format('Y-m-d'));
        $activeTab = $request->query('tab', $defaultActiveTerm);

        $termGoogleData = [
            'prelim' => ['materials' => collect(), 'coursework' => collect()],
            'midterm' => ['materials' => collect(), 'coursework' => collect()],
            'semifinal' => ['materials' => collect(), 'coursework' => collect()],
            'final' => ['materials' => collect(), 'coursework' => collect()],
        ];

        foreach ($gMaterialsAll as $mat) {
            $itemDate = $mat->posted_at ?? $mat->created_at;
            $assignedTerm = $resolveTermForItem($itemDate);
            $termGoogleData[$assignedTerm]['materials']->push($mat);
        }

        foreach ($gCourseWorkAll as $cw) {
            $itemDate = $cw->due_date ?? $cw->posted_at ?? $cw->created_at;
            $assignedTerm = $resolveTermForItem($itemDate);
            $termGoogleData[$assignedTerm]['coursework']->push($cw);
        }

        $calculateTermGrades = function($termName) use ($schedule_id, $studentNumber, $activities, $attendanceRecords, $termPeriods) {

            $periodConfig = $termPeriods->get($termName);

            $termTasks = $activities->where('term', $termName);

            $gradedSubmissions = $termTasks->whereNotNull('student_grade')->map(function($item) {
                $item->percentage = ($item->max_score > 0) ? ($item->student_grade / $item->max_score) * 100 : 0;
                return $item;
            });

            $grouped = $gradedSubmissions->groupBy(function($item) {
                return strtolower(trim($item->activity_type));
            });

            $averages = [];
            foreach ($grouped as $type => $items) {
                $averages[$type] = round($items->avg('percentage'), 2);
            }

            $majorExam = $averages['major exam'] ?? $averages['exam'] ?? null;
            $quizAverage = $averages['quiz'] ?? null;
            $activityAverage = $averages['activity'] ?? null;
            $assignmentAverage = $averages['assignment'] ?? $averages['written'] ?? null;

            $taskComponents = array_filter(
                [$quizAverage, $activityAverage, $assignmentAverage],
                fn($val) => !is_null($val)
            );

            $currentTaskAverage = count($taskComponents) > 0 ? (array_sum($taskComponents) / count($taskComponents)) : null;

            $currentAttendance = null;
            $hasAttendanceLogs = false;

            if ($periodConfig) {
                $startDate = $periodConfig->start_date;
                $endDate = $periodConfig->end_date;

                $termAttendance = $attendanceRecords->filter(function($record) use ($startDate, $endDate) {
                    $attendanceDate = date('Y-m-d', strtotime($record->attendance_date));
                    return $attendanceDate >= $startDate && $attendanceDate <= $endDate;
                });

                $totalAttendanceCount = $termAttendance->count();

                if ($totalAttendanceCount > 0) {
                    $earned = $termAttendance->sum(function ($r) {
                        if ($r->status === 'Present') return 1;
                        if ($r->status === 'Late') return 0.5;
                        return 0;
                    });
                    $currentAttendance = round(($earned / $totalAttendanceCount) * 100, 2);
                    $hasAttendanceLogs = true;
                }
            }

            if (!$hasAttendanceLogs && isset($averages['attendance'])) {
                $currentAttendance = $averages['attendance'];
                $hasAttendanceLogs = true;
            }

            $hasTasks = !is_null($currentTaskAverage);
            $hasExam = !is_null($majorExam);

            if (!$hasTasks && !$hasAttendanceLogs && !$hasExam) {
                return [
                    'quizAverage' => null,
                    'activityAverage' => null,
                    'assignmentAverage' => null,
                    'currentAttendance' => null,
                    'majorExam' => null,
                    'currentCalculatedGrade' => null,
                    'equivalent' => null
                ];
            }

            $helperStanding   = GradeHelper::computeClassStanding($schedule_id, $studentNumber, $termName);
            $helperAttendance = GradeHelper::computeAttendanceRate($schedule_id, $studentNumber, $termName);
            $helperExam       = GradeHelper::computeMajorExam($schedule_id, $studentNumber, $termName);

            $standingToUse = $helperStanding ?? $helperAttendance ?? 0;
            $examToUse     = $helperExam ?? 0;

            $finalPercent = GradeHelper::computeFinalPercent($standingToUse, $examToUse);
            $equivalent   = GradeHelper::computeEquivalent($finalPercent);

            return [
                'quizAverage' => $quizAverage,
                'activityAverage' => $activityAverage,
                'assignmentAverage' => $assignmentAverage,
                'currentAttendance' => $currentAttendance,
                'majorExam' => $majorExam,
                'currentCalculatedGrade' => $finalPercent,
                'equivalent' => number_format((float) $equivalent, 2)
            ];
        };

        $termGrades = [
            'prelim' => $calculateTermGrades('prelim'),
            'midterm' => $calculateTermGrades('midterm'),
            'semifinal' => $calculateTermGrades('semifinal'),
            'final' => $calculateTermGrades('final'),
        ];

        $livePrediction = null;
        $activePeriod = DB::table('academic_periods')->where('is_active', 1)->first();

        if ($activePeriod) {
            $predictedSemestral = GradeHelper::predictAndSave($schedule_id, $studentNumber, $defaultActiveTerm, $activePeriod->id);

            if ($predictedSemestral !== null) {
                $livePrediction = (object) [
                    'based_on_term'       => $defaultActiveTerm,
                    'predicted_semestral' => $predictedSemestral,
                ];
            }
        }

        $actualTermGrades = DB::table('student_term_grades')
            ->where('schedule_id', $schedule_id)
            ->where('student_number', $studentNumber)
            ->where('is_sent_to_student', 1)
            ->pluck('equivalent', 'term')
            ->toArray();

        $mlPrediction = DB::table('student_term_grades_predictions')
            ->where('schedule_id', $schedule_id)
            ->where('student_number', $studentNumber)
            ->first();

        $columnKey = 'predicted_' . $activeTab;
        if (!$mlPrediction || is_null($mlPrediction->{$columnKey})) {
            GradeHelper::predictAndSave($schedule_id, $studentNumber, $activeTab);
            $mlPrediction = DB::table('student_term_grades_predictions')
                ->where('schedule_id', $schedule_id)
                ->where('student_number', $studentNumber)
                ->first();
        }

        return view('student.class_details', compact(
            'classDetails',
            'activities',
            'materials',
            'activeTab',
            'termGrades',
            'livePrediction',
            'actualTermGrades',
            'mlPrediction',
            'attendanceRecords',
            'gLinkedCourse',
            'termGoogleData'
        ));
    }

    public function predictGrade(Request $request, $schedule_id)
    {
        $studentNumber = session('student_id');

        $attendanceRecords = DB::table('attendances')
            ->where('class_schedule_id', $schedule_id)
            ->where('student_id', $studentNumber)
            ->get();

        $gradedSubmissions = DB::table('task_submissions')
            ->join('student_tasks', 'task_submissions.task_id', '=', 'student_tasks.id')
            ->where('student_tasks.class_schedule_id', $schedule_id)
            ->where('task_submissions.student_id', $studentNumber)
            ->whereNotNull('task_submissions.grade')
            ->select('task_submissions.grade', 'student_tasks.activity_type')
            ->get();

        $grouped = $gradedSubmissions->groupBy(function($item) {
            return strtolower(trim($item->activity_type));
        });

        $averages = [];
        foreach ($grouped as $type => $items) {
            $averages[$type] = $items->avg('grade');
        }

        $writtenComponents = [];
        if (isset($averages['written'])) $writtenComponents[] = $averages['written'];
        if (isset($averages['quiz'])) $writtenComponents[] = $averages['quiz'];
        if (isset($averages['activity'])) $writtenComponents[] = $averages['activity'];
        if (isset($averages['assignment'])) $writtenComponents[] = $averages['assignment'];

        $currentWrittenWorks = count($writtenComponents) > 0 ? (array_sum($writtenComponents) / count($writtenComponents)) : 0;
        $currentRecitation = $averages['recitation'] ?? 0;

        $taskComponents = array_filter([$currentWrittenWorks, $currentRecitation]);
        $taskAverage = count($taskComponents) > 0 ? (array_sum($taskComponents) / count($taskComponents)) : 0;

        $totalAttendanceCount = $attendanceRecords->count();
        if ($totalAttendanceCount > 0) {
            $presentCount = $attendanceRecords->where('status', 'Present')->count();
            $currentAttendance = ($presentCount / $totalAttendanceCount) * 100;
        } else {
            $currentAttendance = $averages['attendance'] ?? 0;
        }

        if ($totalAttendanceCount > 0 || isset($averages['attendance'])) {
            $classStandingAverage = ($taskAverage * 0.80) + ($currentAttendance * 0.20);
        } else {
            $classStandingAverage = $taskAverage;
        }

        $classStandingContribution = $classStandingAverage * 0.60;

        $possibleExamScores = [75, 80, 85, 90, 95, 100];
        $predictions = [];

        foreach ($possibleExamScores as $examScore) {
            $examContribution = $examScore * 0.40;
            $predictedPercentage = round($classStandingContribution + $examContribution, 2);

            $pointGrade = "5.00";
            if ($predictedPercentage >= 97) $pointGrade = "1.00";
            elseif ($predictedPercentage >= 94) $pointGrade = "1.25";
            elseif ($predictedPercentage >= 91) $pointGrade = "1.50";
            elseif ($predictedPercentage >= 88) $pointGrade = "1.75";
            elseif ($predictedPercentage >= 85) $pointGrade = "2.00";
            elseif ($predictedPercentage >= 82) $pointGrade = "2.25";
            elseif ($predictedPercentage >= 79) $pointGrade = "2.50";
            elseif ($predictedPercentage >= 76) $pointGrade = "2.75";
            elseif ($predictedPercentage >= 75) $pointGrade = "3.00";
            else $pointGrade = "5.00";

            $predictions[] = [
                'exam_score' => $examScore,
                'predicted_percentage' => $predictedPercentage,
                'equivalent_grade' => $pointGrade,
                'status' => $predictedPercentage >= 75 ? 'Passed' : 'At Risk',
                'status_color' => $predictedPercentage >= 75 ? 'text-success' : 'text-danger'
            ];
        }

        return response()->json([
            'current_class_standing' => round($classStandingAverage, 2),
            'predictions' => $predictions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'registration_date' => 'required|date',
            'student_type' => 'required|string',
            'course' => 'required|string',
            'year_level' => 'required|string',
            'semester' => 'required|string',
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'contact_no' => 'required|string',
            'email' => 'required|email|unique:students,email',
        ]);

        $currentYearShort = date('y');
        $prefix = $currentYearShort . "-";

        $lastStudent = DB::table('students')
            ->where('student_id', 'LIKE', $prefix . '%')
            ->orderBy('student_id', 'desc')
            ->first();

        if ($lastStudent) {
            $parts = explode('-', $lastStudent->student_id);
            $lastSequence = intval(end($parts));

            $nextSequence = str_pad($lastSequence + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '00001';
        }

        $generatedStudentNumber = $prefix . $nextSequence;

        $student = new Student();
        $student->student_id = $generatedStudentNumber;
        $student->registration_date = $request->registration_date;
        $student->student_type = $request->student_type;
        $student->course = $request->course;

        $student->year_level = $request->year_level;
        $student->semester = $request->semester;

        $student->last_name = strtoupper($request->last_name);
        $student->first_name = strtoupper($request->first_name);
        $student->middle_name = $request->middle_name ? strtoupper($request->middle_name) : null;

        $student->sex = $request->sex;
        $student->civil_status = $request->civil_status;
        $student->date_of_birth = $request->date_of_birth;
        $student->nationality = strtoupper($request->nationality);
        $student->place_of_birth = $request->place_of_birth;
        $student->city_address = $request->city_address;
        $student->zip_code = $request->zip_code;
        $student->contact_no = $request->contact_no;
        $student->email = $request->email;

        $student->fathers_name = $request->fathers_name;
        $student->fathers_occupation = $request->fathers_occupation;
        $student->mothers_name = $request->mothers_name;
        $student->mothers_occupation = $request->mothers_occupation;

        $student->elementary_school_name = $request->elementary_school_name;
        $student->elementary_school_address = $request->elementary_school_address;
        $student->elementary_school_year = $request->elementary_school_year;

        $student->junior_hs_school_name = $request->junior_hs_school_name;
        $student->junior_hs_school_address = $request->junior_hs_school_address;
        $student->junior_hs_school_year = $request->junior_hs_school_year;

        $student->senior_hs_school_name = $request->senior_hs_school_name;
        $student->senior_hs_school_address = $request->senior_hs_school_address;
        $student->senior_hs_school_year = $request->senior_hs_school_year;

        $student->vocational_school_name = $request->vocational_school_name;
        $student->vocational_school_address = $request->vocational_school_address;
        $student->vocational_school_year = $request->vocational_school_year;

        $student->college_school_name = $request->college_school_name;
        $student->college_school_address = $request->college_school_address;
        $student->college_school_year = $request->college_school_year;

        $student->credentials = [
            'psa_birth_certificate'      => $request->has('check_psa'),
            'id_picture_2x2'             => $request->has('check_2x2'),
            'form_138'                   => $request->has('check_f138'),
            'good_moral_new'             => $request->has('check_good_moral_new'),
            'diploma'                    => $request->has('check_diploma'),
            'transcript_of_records'      => $request->has('check_tor'),
            'honorable_dismissal'        => $request->has('check_dismissal'),
            'good_moral_transferee'      => $request->has('check_good_moral_trans'),
            'old_curriculum'             => $request->has('check_curriculum'),
            'cross_enroll_permit'        => $request->has('check_permit')
        ];

        $defaultPassword = 'Welcome@AU2026';
        $student->password = Hash::make($defaultPassword);

        $student->save();

        try {
            \Illuminate\Support\Facades\Mail::to($student->email)->send(new \App\Mail\NewUserCredentials($student, $defaultPassword));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('success', "Student registration compiled successfully, but email was not sent.")
                ->with('student_id', $generatedStudentNumber); 
        }

        return redirect()->back()
            ->with('success', "Student registration compiled successfully! Login credentials have been sent to the student’s email.")
            ->with('student_id', $generatedStudentNumber);
    }

    public function myClasses()
    {
        $studentId = session('student_id');

        $activeAcademicPeriod = DB::table('academic_periods')
            ->where('is_active', 1)
            ->first();

        $activePeriodId = $activeAcademicPeriod ? $activeAcademicPeriod->id : null;

        $classesQuery = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('employees', 'subjects.professor_id', '=', 'employees.id')
            ->where('student_subjects.student_number', $studentId)
            ->where('student_subjects.status', 'Enrolled');

        if ($activePeriodId) {
            $classesQuery->where('class_schedules.academic_period_id', $activePeriodId);
        }

        $classes = $classesQuery->select(
                'class_schedules.id as schedule_id',
                'class_schedules.section_name',
                'class_schedules.class_days',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'class_schedules.room_assignment',
                DB::raw("COALESCE(class_schedules.schedule_type, 'LEC') as component_type"),
                'subjects.id as subject_id',
                'subjects.subject_code',
                'subjects.subject_title',
                'subjects.total_units',
                'subjects.lecture_units',
                'subjects.lab_units',
                'student_subjects.status',

                'employees.first_name as prof_first_name',
                'employees.last_name as prof_last_name',

                DB::raw('(SELECT COUNT(*)
                        FROM student_subjects as ss
                        JOIN students as s ON ss.student_number = s.student_id
                        WHERE ss.schedule_id = class_schedules.id
                        AND ss.status = "Enrolled") as enrolled_students_count')
            )
            ->get();

        $totalUnits = $classes->unique('subject_id')->sum('total_units');

        return view('student.my_classes', compact('classes', 'totalUnits', 'activeAcademicPeriod'));
    }

    public function viewTaskDetails($id)
    {
        $studentNumber = session('student_id');

        $task = DB::table('student_tasks')
            ->join('class_schedules', 'student_tasks.class_schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('student_tasks.id', $id)
            ->select(
                'student_tasks.*',
                'class_schedules.section_name',
                'subjects.subject_code',
                'subjects.subject_title'
            )
            ->first();

        if (!$task) {
            return redirect()->route('student.my_classes')->with('error', 'Task not found.');
        }

        $isEnrolled = DB::table('student_subjects')
            ->where('schedule_id', $task->class_schedule_id)
            ->where('student_number', $studentNumber)
            ->where('status', 'Enrolled')
            ->exists();

        if (!$isEnrolled) {
            return redirect()->route('student.my_classes')->with('error', 'Unauthorized access to this task.');
        }

        $submission = DB::table('task_submissions')
            ->where('task_id', $id)
            ->where('student_id', $studentNumber)
            ->first();

        return view('student.task_details', compact('task', 'submission'));
    }

    public function viewGoogleTask($id)
    {
        $studentNumber = session('student_id');

        $task = DB::table('google_classroom_coursework')
            ->where('id', $id)
            ->first();

        if (!$task) {
            return redirect()->back()->with('error', 'Task not found.');
        }

        $course = DB::table('google_classroom_courses')
            ->where('id', $task->course_id)
            ->first();

        $scheduleId = $course ? $course->linked_class_schedule_id : null;

        if (!$scheduleId) {
            return redirect()->back()->with('error', 'This task is not linked to any class.');
        }

        $isEnrolled = DB::table('student_subjects')
            ->where('schedule_id', $scheduleId)
            ->where('student_number', $studentNumber)
            ->where('status', 'Enrolled')
            ->exists();

        if (!$isEnrolled) {
            return redirect()->route('student.my_classes')->with('error', 'Unauthorized access to this task.');
        }

        $classDetails = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('class_schedules.id', $scheduleId)
            ->select('class_schedules.*', 'subjects.subject_code', 'subjects.subject_title')
            ->first();

        $resolvedTerm = $this->resolveGoogleClassroomTerm($task->due_date ?? $task->posted_at ?? null);

        $submission = DB::table('task_submissions')
            ->where('google_coursework_id', $id)
            ->where('student_id', $studentNumber)
            ->first();

        $taskData = (object)[
            'id' => 'gclass_' . $id,
            'real_gclass_id' => $id,
            'class_schedule_id' => $scheduleId,
            'subject_code' => $classDetails->subject_code ?? 'GCLASS',
            'section_name' => $classDetails->section_name ?? '',
            'term' => $resolvedTerm,
            'activity_name' => $task->title,
            'activity_type' => $task->work_type ?? 'assignment',
            'max_score' => $task->max_points ?? 100,
            'due_date' => $task->due_date,
            'file_path' => null,
            'original_filename' => null,
        ];

        return view('student.task_details', [
            'task' => $taskData,
            'submission' => $submission
        ]);
    }

    private function resolveGoogleClassroomTerm($referenceDate)
    {
        if (!$referenceDate) {
            return 'prelim';
        }

        $termPeriods = DB::table('term_periods')
            ->get()
            ->keyBy(function ($item) {
                return strtolower(trim($item->term));
            })
            ->sortBy('start_date');

        if ($termPeriods->isEmpty()) {
            return 'prelim';
        }

        $targetDate = date('Y-m-d', strtotime($referenceDate));

        foreach ($termPeriods as $key => $period) {
            if ($targetDate >= $period->start_date && $targetDate <= $period->end_date) {
                return $key;
            }
        }

        $nearestTerm = null;
        $minDiff = PHP_INT_MAX;

        foreach ($termPeriods as $key => $period) {
            if ($targetDate < $period->start_date) {
                $diff = strtotime($period->start_date) - strtotime($targetDate);
                if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $nearestTerm = $key;
                }
            }
        }

        return $nearestTerm ?? $termPeriods->keys()->last() ?? 'prelim';
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $studentNumber = session('student_id');

        if ($request->hasFile('profile_picture')) {
            $student = DB::table('students')->where('student_id', $studentNumber)->first();

            if (!$student) {
                return redirect()->back()->withErrors(['profile_picture' => 'Student record not found.']);
            }

            if (!empty($student->profile_picture) && Storage::disk('public')->exists($student->profile_picture)) {
                Storage::disk('public')->delete($student->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profile_pictures', 'public');

            DB::table('students')->where('student_id', $studentNumber)->update([
                'profile_picture' => $path
            ]);

            return redirect()->back()->with('success', 'Profile picture updated successfully!');
        }

        return redirect()->back()->withErrors(['profile_picture' => 'Failed to upload image.']);
    }

    public function downloadTaskFile($id)
    {
        $studentNumber = session('student_id');

        $task = DB::table('student_tasks')
            ->where('id', $id)
            ->first();

        if (!$task || !$task->file_path) {
            abort(404, 'Task or attachment file record not found.');
        }

        $isEnrolled = DB::table('student_subjects')
            ->where('schedule_id', $task->class_schedule_id)
            ->where('student_number', $studentNumber)
            ->where('status', 'Enrolled')
            ->exists();

        if (!$isEnrolled) {
            abort(403, 'You are not enrolled in this class.');
        }

        $fullPath = storage_path('app/private/' . $task->file_path);

        if (!file_exists($fullPath)) {
            abort(404, 'The physical file does not exist on the server directory: ' . $task->file_path);
        }

        $downloadName = $task->original_filename ?? basename($fullPath);

        return response()->download($fullPath, $downloadName);
    }

    public function downloadGoogleClassroomMaterial($material_id)
    {
        $studentNumber = session('student_id');

        $material = DB::table('google_classroom_materials')->where('id', $material_id)->first();

        if (!$material) {
            return redirect()->back()->with('error', 'Material record not found.');
        }

        $course = DB::table('google_classroom_courses')->where('id', $material->course_id)->first();

        if (!$course || !$course->linked_class_schedule_id) {
            return redirect()->back()->with('error', 'This material is not linked to any class.');
        }

        $isEnrolled = DB::table('student_subjects')
            ->where('schedule_id', $course->linked_class_schedule_id)
            ->where('student_number', $studentNumber)
            ->where('status', 'Enrolled')
            ->exists();

        if (!$isEnrolled) {
            return redirect()->back()->with('error', 'You are not enrolled in this class.');
        }

        $connection = DB::table('google_classroom_connections')->where('id', $course->connection_id)->first();

        if (!$connection) {
            return redirect()->back()->with('error', 'Google Classroom connection for this course no longer exists.');
        }

        $fileId = $material->drive_file_id;
        if (!$fileId && !empty($material->alternate_link)) {
            preg_match('/[-\w]{25,}/', $material->alternate_link, $matches);
            $fileId = $matches[0] ?? null;
        }

        if (!$fileId) {
            return redirect()->back()->with('error', 'No downloadable attachment file found for this material.');
        }

        try {
            $service = new \App\Support\GoogleClassroomService($connection);
            $file = $service->downloadDriveFile($fileId);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Unable to download file from Google Classroom: ' . $e->getMessage());
        }

        return response($file['content'], 200, [
            'Content-Type' => $file['mimeType'],
            'Content-Disposition' => 'attachment; filename="' . addslashes($file['name']) . '"',
        ]);
    }

    public function submitTask(Request $request, $id)
    {
        $studentId = session('student_id');

        $student = DB::table('students')
            ->where('student_id', $studentId)
            ->first();

        if ($student && in_array(trim($student->status ?? ''), ['On-Hold', 'On Hold', 'Purged', 'Dropped'])) {
            return redirect()->back()->with('error', "You cannot submit activities because your account status is currently '{$student->status}'. Please contact the Registrar.");
        }

        if (str_starts_with($id, 'gclass_')) {
            return $this->submitGoogleTask($request, (int) str_replace('gclass_', '', $id));
        }

        $task_id = $id;
        $student_id = session('student_id');

        $task = DB::table('student_tasks')->where('id', $task_id)->first();

        if (!$task) {
            return redirect()->back()->with('error', 'Task details not found.');
        }

        if ($task->due_date && \Carbon\Carbon::parse($task->due_date)->endOfDay()->isPast()) {
            return redirect()->back()->with('error', 'Submission closed. The deadline for this task has passed.');
        }

        $request->validate([
            'attachment' => 'required|file|max:25600',
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            $taskInfo = DB::table('student_tasks')
                ->join('class_schedules', 'student_tasks.class_schedule_id', '=', 'class_schedules.id')
                ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                ->where('student_tasks.id', $task_id)
                ->select('subjects.subject_code', 'class_schedules.section_name', 'student_tasks.term')
                ->first();

            if (!$taskInfo) {
                return redirect()->back()->with('error', 'Task details not found.');
            }

            $classFolder = strtoupper($taskInfo->subject_code . '_' . $taskInfo->section_name);

            $termFolderMap = [
                'prelim' => 'PRELIM_TERM',
                'midterm' => 'MIDTERM_TERM',
                'semifinal' => 'SEMI-FINAL_TERM',
                'final' => 'FINAL_TERM',
            ];
            $termFolder = $termFolderMap[strtolower($taskInfo->term)] ?? strtoupper($taskInfo->term);

            $folderPath = "task_submissions/{$classFolder}/{$termFolder}";

            $finalName = $student_id . '_' . time() . '.' . $extension;

            $file->storeAs($folderPath, $finalName, 'local');

            $filePath = "{$folderPath}/{$finalName}";

            DB::table('task_submissions')->updateOrInsert(
                ['task_id' => $task_id, 'student_id' => $student_id],
                [
                    'file_path' => $filePath,
                    'original_filename' => $originalName,
                    'submitted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            return redirect()->back()->with('success', 'Task submitted successfully!');
        }

        return redirect()->back()->with('error', 'No file selected.');
    }

    private function submitGoogleTask(Request $request, int $courseworkId)
    {
        $student_id = session('student_id');

        $task = DB::table('google_classroom_coursework')
            ->where('id', $courseworkId)
            ->first();

        if (!$task) {
            return redirect()->back()->with('error', 'Task details not found.');
        }

        $course = DB::table('google_classroom_courses')
            ->where('id', $task->course_id)
            ->first();

        $scheduleId = $course ? $course->linked_class_schedule_id : null;

        if (!$scheduleId) {
            return redirect()->back()->with('error', 'This task is not linked to any class.');
        }

        $isEnrolled = DB::table('student_subjects')
            ->where('schedule_id', $scheduleId)
            ->where('student_number', $student_id)
            ->where('status', 'Enrolled')
            ->exists();

        if (!$isEnrolled) {
            return redirect()->back()->with('error', 'You are not enrolled in this class.');
        }

        if ($task->due_date && \Carbon\Carbon::parse($task->due_date)->endOfDay()->isPast()) {
            return redirect()->back()->with('error', 'Submission closed. The deadline for this task has passed.');
        }

        $request->validate([
            'attachment' => 'required|file|max:25600',
        ]);

        $file = $request->file('attachment');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        $classInfo = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('class_schedules.id', $scheduleId)
            ->select('subjects.subject_code', 'class_schedules.section_name')
            ->first();

        $classFolder = strtoupper(($classInfo->subject_code ?? 'GCLASS') . '_' . ($classInfo->section_name ?? ''));

        $resolvedTerm = $this->resolveGoogleClassroomTerm($task->due_date ?? $task->posted_at ?? null);

        $termFolderMap = [
            'prelim' => 'PRELIM_TERM',
            'midterm' => 'MIDTERM_TERM',
            'semifinal' => 'SEMI-FINAL_TERM',
            'final' => 'FINAL_TERM',
        ];
        $termFolder = $termFolderMap[$resolvedTerm] ?? strtoupper($resolvedTerm);

        $folderPath = "task_submissions/{$classFolder}/{$termFolder}";
        $finalName = $student_id . '_' . time() . '.' . $extension;

        $file->storeAs($folderPath, $finalName, 'local');

        $filePath = "{$folderPath}/{$finalName}";

        DB::table('task_submissions')->updateOrInsert(
            ['google_coursework_id' => $courseworkId, 'student_id' => $student_id],
            [
                'task_id' => null,
                'file_path' => $filePath,
                'original_filename' => $originalName,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return redirect()->back()->with('success', 'Task submitted successfully!');
    }

    public function submitDocumentRequest(Request $request)
    {
        $studentId = session('student_id');

        $student = \App\Models\Student::where('student_id', $studentId)->first();

        $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*' => 'string',
            'purpose' => 'required|string|max:500',
        ]);

        if (!$student) {
            return redirect()->route('login')->withErrors(['error' => 'User profile reference not found.']);
        }

        \App\Models\DocumentRequest::create([
            'student_id' => $student->student_id,
            'document_name' => json_encode($request->documents),
            'purpose' => $request->purpose,
            'status' => 'Pending',
        ]);

        if (!empty($student->email)) {
            \Illuminate\Support\Facades\Mail::to($student->email)->send(new \App\Mail\DocumentRequestSubmitted($student, $request->documents));
        }

        return redirect()->back()->with('success', 'Your requested documents have been successfully sent to the Registrar!');
    }

    public function myGrades()
    {
        $studentNumber = session('student_id');

        $activeAcademicPeriod = DB::table('academic_periods')
            ->where('is_active', 1)
            ->first();

        $activePeriodId = $activeAcademicPeriod ? $activeAcademicPeriod->id : null;

        $enrolledClasses = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('employees', 'subjects.professor_id', '=', 'employees.id')
            ->where('student_subjects.student_number', $studentNumber)
            ->where('student_subjects.status', 'Enrolled');

        if ($activePeriodId) {
            $enrolledClasses->where('class_schedules.academic_period_id', $activePeriodId);
        }

        $enrolledClasses = $enrolledClasses->select(
                'class_schedules.id as schedule_id',
                'class_schedules.section_name',
                'class_schedules.schedule_type',
                'subjects.id as subject_id',
                'subjects.subject_code',
                'subjects.subject_title',
                'subjects.total_units',
                'subjects.lecture_units',
                'subjects.lab_units',
                'employees.first_name as prof_first_name',
                'employees.last_name as prof_last_name'
            )
            ->orderBy('subjects.subject_code', 'asc')
            ->orderBy('class_schedules.schedule_type', 'desc')
            ->get();

        $gradesData = [];
        $totalUnits = 0;

        foreach ($enrolledClasses as $class) {
            $getTermGrade = function($termName) use ($class, $studentNumber) {
                $record = DB::table('student_term_grades')
                    ->where('schedule_id', $class->schedule_id)
                    ->where('student_number', $studentNumber)
                    ->where('term', $termName)
                    ->where('is_sent_to_student', 1)
                    ->first();

                return [
                    'grade' => $record ? $record->major_exam : null,
                    'equivalent' => $record ? $record->equivalent : null
                ];
            };

            $prelim = $getTermGrade('prelim');
            $midterm = $getTermGrade('midterm');
            $semifinal = $getTermGrade('semifinal');
            $finals = $getTermGrade('final');

            $semestralGrade = \App\Support\GradeHelper::computeSemestralGrade($class->schedule_id, $studentNumber);

            $semestralReleased = DB::table('student_term_grades')
                ->where('schedule_id', $class->schedule_id)
                ->where('student_number', $studentNumber)
                ->where('term', 'semestral')
                ->where('is_sent_to_student', 1)
                ->exists();

            if (!$semestralReleased) {
                $semestralGrade = null;
            }
            
            $mlPrediction = null;
            if ($activeAcademicPeriod) {
                $mlPrediction = \App\Support\GradeHelper::predict($class->schedule_id, $studentNumber, $activeAcademicPeriod->id);
            }

            $componentType = strtoupper(trim($class->schedule_type ?? 'LEC'));
            $displayCode = $class->subject_code . ' ' . $componentType;
            $displayTitle = $class->subject_title . ' (' . $componentType . ')';

            $lecUnits = ($componentType === 'LEC') ? ($class->lecture_units ?? 3.0) : 0.0;
            $labUnits = ($componentType === 'LAB') ? ($class->lab_units ?? 1.0) : 0.0;

            $gradesData[] = [
                'subject_code' => $displayCode,
                'subject_title' => $displayTitle,
                'lec_units' => $lecUnits,
                'lab_units' => $labUnits,
                'total_units' => $class->total_units,
                'professor' => trim(($class->prof_last_name ?? '') . ', ' . ($class->prof_first_name ?? '')),
                'prelim' => $prelim,
                'midterm' => $midterm,
                'semifinal' => $semifinal,
                'finals' => $finals,
                'semestral_grade' => $semestralGrade,
                'predicted_semestral' => $mlPrediction['value'] ?? null,
            ];

            $totalUnits += $class->total_units;
        }

        return view('student.my_grades', compact('gradesData', 'totalUnits', 'activeAcademicPeriod'));
    }

    public function profileView()
    {
        $studentNumber = session('student_id');

        $user = Student::where('student_id', $studentNumber)->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        $isLocked = Hash::check('Welcome@AU2026', $user->password);

        return view('student.user_details', compact('user', 'isLocked'));
    }
}