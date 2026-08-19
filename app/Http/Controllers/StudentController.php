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

class StudentController extends Controller
{
    public function dashboard()
    {
        $studentNumber = session('student_id');

        $announcements = DB::table('announcements')->orderBy('created_at', 'desc')->get();

        $enrolledClasses = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('student_subjects.student_number', $studentNumber)
            ->where('student_subjects.status', 'Enrolled')
            ->select(
                'class_schedules.id as schedule_id',
                'subjects.id as subject_id',
                'subjects.total_units',
                'subjects.subject_code'
            )
            ->get();

        $totalUnits = 0;
        $weightedGradesSum = 0;
        $hasGrades = false;

        foreach ($enrolledClasses as $class) {
            $gradedSubmissions = DB::table('task_submissions')
                ->join('student_tasks', 'task_submissions.task_id', '=', 'student_tasks.id')
                ->where('student_tasks.class_schedule_id', $class->schedule_id)
                ->where('task_submissions.student_id', $studentNumber)
                ->whereNotNull('task_submissions.grade')
                ->select('task_submissions.grade', 'student_tasks.activity_type')
                ->get();

            if ($gradedSubmissions->isNotEmpty()) {
                $hasGrades = true;
                $grouped = $gradedSubmissions->groupBy(function($item) {
                    return strtolower(trim($item->activity_type));
                });

                $averages = [];
                foreach ($grouped as $type => $items) {
                    $averages[$type] = $items->avg('grade');
                }

                $majorExam = $averages['major exam'] ?? $averages['exam'] ?? 0;

                $writtenComponents = [];
                if (isset($averages['written'])) $writtenComponents[] = $averages['written'];
                if (isset($averages['quiz'])) $writtenComponents[] = $averages['quiz'];
                if (isset($averages['activity'])) $writtenComponents[] = $averages['activity'];

                $currentWrittenWorks = count($writtenComponents) > 0 ? (array_sum($writtenComponents) / count($writtenComponents)) : 0;
                $currentRecitation = $averages['recitation'] ?? 0;
                $currentAttendance = $averages['attendance'] ?? 0;

                $classStandingComponents = array_filter([$currentWrittenWorks, $currentRecitation, $currentAttendance]);
                $classStandingAverage = count($classStandingComponents) > 0 ? (array_sum($classStandingComponents) / count($classStandingComponents)) : 0;

                $classStandingContribution = $classStandingAverage * 0.60;
                $examContribution = $majorExam * 0.40;

                $subjectGrade = round($classStandingContribution + $examContribution, 2);

                $weightedGradesSum += ($subjectGrade * $class->total_units);
                $totalUnits += $class->total_units;
            } else {
                $totalUnits += $class->total_units;
            }
        }

        $predictedTermGWA = ($hasGrades && $totalUnits > 0) ? round($weightedGradesSum / $totalUnits, 2) : 0;

        $gwaStatus = 'No grades computed yet';
        $statusClass = 'text-secondary';
        if ($predictedTermGWA > 0) {
            if ($predictedTermGWA >= 75) {
                $gwaStatus = 'PASSED (Good Standing)';
                $statusClass = 'text-success';
            } else {
                $gwaStatus = 'AT RISK (Needs Improvement)';
                $statusClass = 'text-danger';
            }
        }

        $totalEnrolledClasses = $enrolledClasses->count();
        $enrolledScheduleIds = $enrolledClasses->pluck('schedule_id');

        $terms = ['prelim', 'midterm', 'semifinal', 'final'];
        $pendingTasksPerTerm = [];
        $totalLocalPending = 0;

        foreach ($terms as $term) {
            $count = DB::table('student_tasks')
                ->whereIn('class_schedule_id', $enrolledScheduleIds)
                ->where('term', $term)
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

            $pendingTasksPerTerm[$term] = $count;
            $totalLocalPending += $count;
        }

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

        $pendingTasksCount = $totalLocalPending + $googlePendingCount;

        return view('student.dashboard', compact(
            'announcements',
            'totalEnrolledClasses',
            'pendingTasksCount',
            'pendingTasksPerTerm',
            'predictedTermGWA',
            'gwaStatus',
            'statusClass'
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

        $termPeriods = DB::table('term_periods')
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
                    'currentCalculatedGrade' => null
                ];
            }

            if ($hasTasks && $hasAttendanceLogs) {
                $classStandingAverage = ($currentTaskAverage * 0.75) + ($currentAttendance * 0.25);
            } elseif ($hasTasks) {
                $classStandingAverage = $currentTaskAverage;
            } elseif ($hasAttendanceLogs) {
                $classStandingAverage = $currentAttendance;
            } else {
                $classStandingAverage = 0;
            }

            $examScoreToUse = $hasExam ? $majorExam : 0;

            $classStandingContribution = $classStandingAverage * 0.60;
            $examContribution = $examScoreToUse * 0.40;

            return [
                'quizAverage' => $quizAverage,
                'activityAverage' => $activityAverage,
                'assignmentAverage' => $assignmentAverage,
                'currentAttendance' => $currentAttendance,
                'majorExam' => $majorExam,
                'currentCalculatedGrade' => round($classStandingContribution + $examContribution, 2)
            ];
        };

        $termGrades = [
            'prelim' => $calculateTermGrades('prelim'),
            'midterm' => $calculateTermGrades('midterm'),
            'semifinal' => $calculateTermGrades('semifinal'),
            'final' => $calculateTermGrades('final'),
        ];

        return view('student.class_details', compact(
            'classDetails',
            'activities',
            'materials',
            'activeTab',
            'termGrades',
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

    public function index(Request $request)
    {
        $search = $request->input('search');

        $students = Student::when($search, function ($query, $search) {
                return $query->where('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
            })
            ->orderBy('last_name', 'asc')
            ->paginate(15);

        return view('registrar.manage-students', compact('students', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'registration_date' => 'required|date',
            'student_type' => 'required|string',
            'course' => 'required|string',
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'contact_no' => 'required|string',
            'email' => 'required|email|unique:students,email',

            'doc_psa' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_2x2' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'doc_f138' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_new' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_diploma' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_tor' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_dismissal' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_trans' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_permit' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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

        $filePaths = [];

        $fileFields = [
            'doc_psa' => 'psa_birth_certificate',
            'doc_2x2' => '2x2_id_picture',
            'doc_f138' => 'form_138_report_card',
            'doc_good_moral_new' => 'good_moral_freshman',
            'doc_diploma' => 'high_school_diploma',
            'doc_tor' => 'transcript_of_records',
            'doc_dismissal' => 'honorable_dismissal',
            'doc_good_moral_trans' => 'good_moral_transferee',
            'doc_permit' => 'cross_enroll_permit'
        ];

        foreach ($fileFields as $inputName => $cleanFileName) {
            if ($request->hasFile($inputName)) {
                $file = $request->file($inputName);
                $extension = $file->getClientOriginalExtension();
                $finalFileName = "{$cleanFileName}.{$extension}";

                $file->storeAs("student_records/{$generatedStudentNumber}", $finalFileName, 'local');

                $filePaths[$inputName] = $finalFileName;
            }
        }

        $student = new Student();
        $student->student_id = $generatedStudentNumber;
        $student->registration_date = $request->registration_date;
        $student->student_type = $request->student_type;
        $student->course = $request->course;

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

        $student->primary_school_name = $request->primary_school_name
            ? implode('; ', array_filter($request->primary_school_name))
            : null;
        $student->primary_school_address = $request->primary_school_address
            ? implode('; ', array_filter($request->primary_school_address))
            : null;
        $student->primary_school_year = $request->primary_school_year
            ? implode('; ', array_filter($request->primary_school_year))
            : null;

        $student->secondary_school_name = $request->secondary_school_name;
        $student->secondary_school_address = $request->secondary_school_address;
        $student->secondary_school_year = $request->secondary_school_year;

        $student->college_school_name = $request->college_school_name;
        $student->college_school_address = $request->college_school_address;
        $student->college_school_year = $request->college_school_year;

        $student->year_level = ($request->student_type === 'Transferee') ? '1st Year' : '1st Year';
        $student->semester = '1st Semester';

        $student->path_psa = $filePaths['doc_psa'] ?? null;
        $student->path_2x2 = $filePaths['doc_2x2'] ?? null;
        $student->path_f138 = $filePaths['doc_f138'] ?? null;
        $student->path_good_moral_new = $filePaths['doc_good_moral_new'] ?? null;
        $student->path_diploma = $filePaths['doc_diploma'] ?? null;
        $student->path_tor = $filePaths['doc_tor'] ?? null;
        $student->path_dismissal = $filePaths['doc_dismissal'] ?? null;
        $student->path_good_moral_trans = $filePaths['doc_good_moral_trans'] ?? null;
        $student->path_permit = $filePaths['doc_permit'] ?? null;
        $student->credentials = array_values($filePaths);
        $defaultPassword = 'Welcome@AU2026';
        $student->password = Hash::make($defaultPassword);

        $student->save();

        try {
            \Illuminate\Support\Facades\Mail::to($student->email)->send(new \App\Mail\NewStudentCredentials($student, $defaultPassword));
        } catch (\Exception $e) {
            return redirect()->back()->with('success', "Student registration compiled successfully! Assigned Student ID: {$generatedStudentNumber}. The welcome email was not sent. (" . $e->getMessage() . ") — Please provide the Student ID and default password to the student manually.");
        }

        return redirect()->back()->with('success', "Student registration compiled successfully! Assigned Student ID: {$generatedStudentNumber}. Login credentials have also been sent to the student’s email.");
    }

    public function myClasses()
    {
        $studentId = session('student_id');

        $classes = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('employees', 'subjects.professor_id', '=', 'employees.id')
            ->where('student_subjects.student_number', $studentId)
            ->where('student_subjects.status', 'Enrolled')
            ->select(
                'class_schedules.id as schedule_id',
                'class_schedules.section_name',
                'class_schedules.class_days',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'class_schedules.room_assignment',
                'subjects.subject_code',
                'subjects.subject_title',
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

        return view('student.my_classes', compact('classes'));
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

        if (!$material || !$material->drive_file_id) {
            abort(404, 'Material or attached file not found.');
        }

        $course = DB::table('google_classroom_courses')->where('id', $material->course_id)->first();

        if (!$course || !$course->linked_class_schedule_id) {
            abort(404, 'This material is not linked to any class.');
        }

        $isEnrolled = DB::table('student_subjects')
            ->where('schedule_id', $course->linked_class_schedule_id)
            ->where('student_number', $studentNumber)
            ->where('status', 'Enrolled')
            ->exists();

        if (!$isEnrolled) {
            abort(403, 'You are not enrolled in this class.');
        }

        $connection = DB::table('google_classroom_connections')->where('id', $course->connection_id)->first();

        if (!$connection) {
            abort(404, 'Google Classroom connection for this course no longer exists.');
        }

        try {
            $service = new \App\Support\GoogleClassroomService($connection);
            $file = $service->downloadDriveFile($material->drive_file_id);
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
            return redirect()->back()->with('error', 'Submission closed. The deadline for this task has already passed.');
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
            return redirect()->back()->with('error', 'Submission closed. The deadline for this task has already passed.');
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

        $enrolledClasses = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('employees', 'subjects.professor_id', '=', 'employees.id')
            ->where('student_subjects.student_number', $studentNumber)
            ->where('student_subjects.status', 'Enrolled')
            ->select(
                'class_schedules.id as schedule_id',
                'subjects.subject_code',
                'subjects.subject_title',
                'subjects.total_units',
                'subjects.lecture_units',
                'subjects.lab_units',
                'employees.first_name as prof_first_name',
                'employees.last_name as prof_last_name'
            )
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
            $finals = $getTermGrade('final');

            $gradesData[] = [
                'subject_code' => $class->subject_code,
                'subject_title' => $class->subject_title,
                'lec_units' => $class->lecture_units ?? 3.0,
                'lab_units' => $class->lab_units ?? 0.0,
                'total_units' => $class->total_units,
                'professor' => trim(($class->prof_last_name ?? '') . ', ' . ($class->prof_first_name ?? '')),
                'prelim' => $prelim,
                'midterm' => $midterm,
                'finals' => $finals,
                'final_equivalent' => $finals['equivalent'] ?? 'N/A'
            ];

            $totalUnits += $class->total_units;
        }

        $activeAcademicPeriod = DB::table('academic_periods')
            ->where('is_active', 1)
            ->first();

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
