<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class StudentController extends Controller
{
    public function dashboard()
    {
        if (session('user_role') !== 'student') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

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

        $pendingTasksCount = DB::table('student_tasks')
            ->join('student_subjects', 'student_tasks.class_schedule_id', '=', 'student_subjects.schedule_id')
            ->leftJoin('task_submissions', function($join) use ($studentNumber) {
                $join->on('student_tasks.id', '=', 'task_submissions.task_id')
                     ->where('task_submissions.student_id', '=', $studentNumber);
            })
            ->where('student_subjects.student_number', $studentNumber)
            ->where('student_subjects.status', 'Enrolled')
            ->whereNull('task_submissions.id')
            ->count();

        return view('student.dashboard', compact(
            'announcements',
            'totalEnrolledClasses',
            'pendingTasksCount',
            'predictedTermGWA',
            'gwaStatus',
            'statusClass'
        ));
    }

    public function updateCredentials(Request $request) 
    {
        if (session('user_role') !== 'student') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $studentNumber = session('student_id');
        $student = Student::where('student_id', $studentNumber)->firstOrFail();

        $request->validate([
            'email' => 'required|email|unique:students,email,' . $student->id,
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

    public function index(Request $request)
    {
        if (session('user_role') !== 'registrar') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $query = Student::query();

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('student_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($request->has('course') && $request->course != '') {
            $query->where('course', $request->course);
        }

        if ($request->has('student_type') && $request->student_type != '') {
            $query->where('student_type', $request->student_type);
        }

        $students = $query->orderBy('student_id', 'desc')->get();
        
        $totalRecords = $students->count();

        return view('registrar.manage-students', compact('students', 'totalRecords'));
    }

    public function viewClassDashboard($schedule_id, Request $request)
    {
        if (session('user_role') !== 'student') {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        $studentNumber = session('student_id');

        $isEnrolled = DB::table('student_subjects')
            ->where('schedule_id', $schedule_id)
            ->where('student_number', $studentNumber)
            ->where('status', 'Enrolled')
            ->exists();

        if (!$isEnrolled) {
            return redirect()->route('student.my_classes')->with('error', 'Unauthorized access to this class.');
        }

        $attendanceRecords = DB::table('attendances')
            ->where('class_schedule_id', $schedule_id)
            ->where('student_id', $studentNumber)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $activeTab = $request->query('tab', 'materials');

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

        // Google Classroom coursework/materials for the course linked to this schedule (if any)
        $gLinkedCourse = DB::table('google_classroom_courses')
            ->where('linked_class_schedule_id', $schedule_id)
            ->first();

        $gCourseWork = collect();
        $gMaterials = collect();

        if ($gLinkedCourse) {
            $gCourseWork = DB::table('google_classroom_coursework')
                ->where('course_id', $gLinkedCourse->id)
                ->orderBy('due_date', 'desc')
                ->get();

            $gMaterials = DB::table('google_classroom_materials')
                ->where('course_id', $gLinkedCourse->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

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

        $majorExam = $averages['major exam'] ?? $averages['exam'] ?? 0;
        
        $quizAverage = $averages['quiz'] ?? 0;
        $activityAverage = $averages['activity'] ?? 0;
        $assignmentAverage = $averages['assignment'] ?? $averages['written'] ?? 0;

        $taskComponents = array_filter([$quizAverage, $activityAverage, $assignmentAverage]);
        $currentTaskAverage = count($taskComponents) > 0 ? (array_sum($taskComponents) / count($taskComponents)) : 0;

        $totalAttendanceCount = $attendanceRecords->count();
        if ($totalAttendanceCount > 0) {
            $presentCount = $attendanceRecords->where('status', 'Present')->count();
            $currentAttendance = ($presentCount / $totalAttendanceCount) * 100;
        } else {
            $currentAttendance = $averages['attendance'] ?? 0; 
        }

        $hasTasks = count($taskComponents) > 0;
        $hasAttendance = ($totalAttendanceCount > 0 || isset($averages['attendance']));

        if ($hasTasks && $hasAttendance) {
            $classStandingAverage = ($currentTaskAverage * 0.75) + ($currentAttendance * 0.25);
        } elseif ($hasTasks) {
            $classStandingAverage = $currentTaskAverage;
        } elseif ($hasAttendance) {
            $classStandingAverage = $currentAttendance;
        } else {
            $classStandingAverage = 0;
        }

        $classStandingContribution = $classStandingAverage * 0.60; 
        $examContribution = $majorExam * 0.40;                  

        $currentCalculatedGrade = round($classStandingContribution + $examContribution, 2);

        return view('student.class_details', compact(
            'classDetails', 
            'activities', 
            'materials', 
            'activeTab',
            'quizAverage',
            'activityAverage',
            'assignmentAverage',
            'currentAttendance',
            'majorExam',
            'currentCalculatedGrade',
            'attendanceRecords',
            'gLinkedCourse',
            'gCourseWork',
            'gMaterials'
        ));
    }

    public function predictGrade(Request $request, $schedule_id)
    {
        if (session('user_role') !== 'student') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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

    public function viewStudentProfile($student_id)
    {
        if (session('user_role') !== 'registrar') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $student = DB::table('students')->where('student_id', $student_id)->first();

        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        return view('registrar.student_profile', compact('student'));
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
        
        $student->fathers_name = $request->fathers_name;
        $student->fathers_occupation = $request->fathers_occupation;
        $student->mothers_name = $request->mothers_name;
        $student->mothers_occupation = $request->mothers_occupation;
        
        $student->primary_school_name = $request->primary_school_name;
        $student->primary_school_address = $request->primary_school_address;
        $student->primary_school_year = $request->primary_school_year;
        $student->secondary_school_name = $request->secondary_school_name;
        $student->secondary_school_address = $request->secondary_school_address;
        $student->secondary_school_year = $request->secondary_school_year;
        $student->college_school_name = $request->college_school_name;
        $student->college_school_address = $request->college_school_address;
        $student->college_school_year = $request->college_school_year;

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
        $student->password = Hash::make('Welcome@AU2026');

        $student->save();

        return redirect()->back()->with('success', "Student registration compiled successfully! Assigned Student ID: {$generatedStudentNumber}");
    }

    public function myClasses()
    {
        if (session('user_role') !== 'student') {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        $studentNumber = session('student_id');

        $enrolledCountQuery = DB::table('student_subjects')
            ->select(DB::raw('count(*)'))
            ->whereColumn('student_subjects.schedule_id', 'class_schedules.id')
            ->where('student_subjects.status', 'Enrolled');

        $classes = DB::table('student_subjects')
            ->join('subjects', 'student_subjects.subject_id', '=', 'subjects.id')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->leftJoin('employees', function($join) {
                $join->on('subjects.professor_id', '=', 'employees.id')
                    ->where('employees.role', '=', 'professor');
            })
            ->where('student_subjects.student_number', $studentNumber)
            ->where('student_subjects.status', 'Enrolled')
            ->select(
                'class_schedules.id as schedule_id',
                'subjects.subject_code',
                'subjects.subject_title',
                'subjects.total_units',
                'student_subjects.status',
                'class_schedules.section_name',
                'class_schedules.class_days',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'class_schedules.room_assignment',
                'employees.first_name as prof_first_name',
                'employees.last_name as prof_last_name'
            )
            ->selectSub($enrolledCountQuery, 'enrolled_students_count')
            ->get();

        return view('student.my_classes', compact('classes'));
    }

    public function viewTaskDetails($id)
    {
        if (session('user_role') !== 'student') {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

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

    public function downloadTaskFile($id)
    {
        if (session('user_role') !== 'student') {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

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
        if (session('user_role') !== 'student') {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

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

    public function profileView()
    {
        if (session('user_role') !== 'student') {
            return redirect()->route('login')->with('error', 'Unauthorized access. Please login as a Student.');
        }

        $userId = session('user_id');
        $user = \App\Models\Student::find($userId);

        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        $isLocked = Hash::check('Welcome@AU2026', $user->password);

        return view('student.user_details', compact('user', 'isLocked'));
    }
}