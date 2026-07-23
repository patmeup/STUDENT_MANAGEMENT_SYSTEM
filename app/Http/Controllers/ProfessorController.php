<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Students;
use App\Support\TermHelper;
use App\Support\GradeHelper;
use App\Models\StudentTermGrade;

class ProfessorController extends Controller
{
    public function dashboard()
    {
        if (session('user_role') !== 'professor') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $professor_id = session('user_id');

        $announcements = DB::table('announcements')->orderBy('created_at', 'desc')->get();

        $totalClasses = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('subjects.professor_id', $professor_id)
            ->distinct('class_schedules.id')
            ->count('class_schedules.id');

        $totalStudents = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('subjects.professor_id', $professor_id)
            ->where('student_subjects.status', 'Enrolled')
            ->distinct('student_subjects.student_number')
            ->count('student_subjects.student_number');

        $pendingGrading = DB::table('task_submissions')
            ->join('student_tasks', 'task_submissions.task_id', '=', 'student_tasks.id')
            ->join('class_schedules', 'student_tasks.class_schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('subjects.professor_id', $professor_id)
            ->whereNull('task_submissions.grade')
            ->count('task_submissions.id');

        $classesList = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->select(
                'class_schedules.id as schedule_id',
                'class_schedules.section_name',
                'class_schedules.class_days',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'class_schedules.room_assignment',
                'subjects.subject_code',
                'subjects.subject_title',
                DB::raw('(SELECT COUNT(*) FROM student_subjects WHERE student_subjects.schedule_id = class_schedules.id AND student_subjects.status = "Enrolled") as students_count')
            )
            ->where('subjects.professor_id', $professor_id)
            ->orderBy('class_schedules.section_name')
            ->get();

        return view('professor.dashboard', compact(
            'announcements',
            'totalClasses',
            'totalStudents',
            'pendingGrading',
            'classesList'
        ));
    }

    public function manageClasses()
    {
        if (session('user_role') !== 'professor') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $professor_id = session('user_id');

        $classes = \DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
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

                \DB::raw('(SELECT COUNT(*)
                        FROM student_subjects
                        WHERE student_subjects.schedule_id = class_schedules.id
                        AND student_subjects.status = "Enrolled") as students_count')
            )
            ->where('subjects.professor_id', $professor_id)
            ->orderBy('class_schedules.section_name')
            ->get();

        return view('professor.manage-classes', compact('classes'));
    }

    public function viewClassDashboard($schedule_id)
    {
        if (session('user_role') !== 'professor') {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        $professorId = session('user_id');

        $classDetails = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('class_schedules.id', $schedule_id)
            ->where('subjects.professor_id', $professorId)
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
                'subjects.lab_units'
            )
            ->first();

        if (!$classDetails) {
            return redirect()->route('professor.manage-classes')->with('error', 'Class section not found or unauthorized.');
        }

        $students = DB::table('student_subjects')
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->where('student_subjects.schedule_id', $schedule_id)
            ->where('student_subjects.status', 'Enrolled')
            ->select(
                'students.id as internal_db_id',
                'students.student_id',
                'students.last_name',
                'students.first_name',
                'students.middle_name',
                'student_subjects.status'
            )
            ->orderBy('students.last_name', 'asc')
            ->get();

        $tasks = DB::table('student_tasks')
            ->where('class_schedule_id', $schedule_id)
            ->orderBy('due_date', 'asc')
            ->get();

        $terms = ['prelim', 'midterm', 'semifinal', 'final'];
        $autoGrades = [];

        $savedGrades = StudentTermGrade::where('schedule_id', $schedule_id)
            ->get()
            ->keyBy(function ($grade) {
                return $grade->term . '_' . $grade->student_number;
            });

        $draftGrades = StudentTermGrade::where('schedule_id', $schedule_id)
            ->get()
            ->groupBy('term');

        foreach ($terms as $term) {

            foreach ($students as $student) {

                $saved = $savedGrades->get($term . '_' . $student->student_id);

                if ($saved) {

                    $autoGrades[$term][$student->student_id] = [
                        'attendance'      => $saved->attendance,
                        'class_standing'  => $saved->class_standing,
                        'major_exam'      => $saved->major_exam,
                        'equivalent'      => $saved->equivalent,
                        'is_manual'       => $saved->is_manual,
                    ];

                } else {

                    $autoGrades[$term][$student->student_id] =
                        GradeHelper::computeAutoGrades(
                            $schedule_id,
                            $student->student_id,
                            $term
                        );
                }
            }
        }

        return view('professor.class_details', compact(
            'classDetails',
            'students',
            'tasks',
            'autoGrades',
            'draftGrades'
        ));
    }

    public function showAttendanceSheet($schedule_id, Request $request)
    {
        $date = $request->query('date', date('Y-m-d'));

        $students = DB::table('student_subjects')
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->where('student_subjects.schedule_id', $schedule_id)
            ->where('student_subjects.status', 'Enrolled')
            ->select('students.student_id', 'students.first_name', 'students.last_name')
            ->orderBy('students.last_name', 'asc')
            ->get();

        $existingAttendance = DB::table('attendances')
            ->where('class_schedule_id', $schedule_id)
            ->where('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        return response()->json([
            'students' => $students,
            'existingAttendance' => $existingAttendance
        ]);
    }

    public function saveAttendance(Request $request, $schedule_id)
    {
        $date = $request->input('attendance_date', date('Y-m-d'));
        $attendanceData = $request->input('attendance', []);
        $remarksData = $request->input('remarks', []);

        $request->validate([
            'attendance_date' => 'required|date|before_or_equal:today',
        ]);

        DB::beginTransaction();
        try {
            foreach ($attendanceData as $studentId => $status) {
                $remarks = $remarksData[$studentId] ?? null;

                $record = DB::table('attendances')
                    ->where('class_schedule_id', $schedule_id)
                    ->where('student_id', $studentId)
                    ->where('attendance_date', $date)
                    ->first();

                if ($record) {
                    DB::table('attendances')->where('id', $record->id)->update([
                        'status' => $status,
                        'remarks' => $remarks,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('attendances')->insert([
                        'class_schedule_id' => $schedule_id,
                        'student_id' => $studentId,
                        'attendance_date' => $date,
                        'status' => $status,
                        'remarks' => $remarks,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveActivity(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'max_score' => 'nullable|integer|min:1|required_unless:type,module',
            'term' => 'required|string',
            'schedule_id' => 'required|integer',
            'attachment' => 'nullable|file|max:25600|mimes:pdf,doc,docx,ppt,pptx,zip,jpg,png',
        ]);

        try {
            $classInfo = DB::table('class_schedules')
                ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                ->where('class_schedules.id', $request->schedule_id)
                ->select('subjects.subject_code', 'class_schedules.section_name')
                ->first();

            $classFolder = strtoupper($classInfo->subject_code . '_' . $classInfo->section_name);

            $termFolderMap = [
                'prelim' => 'PRELIM_TERM',
                'midterm' => 'MIDTERM_TERM',
                'semifinal' => 'SEMI-FINAL_TERM',
                'final' => 'FINAL_TERM',
            ];
            $termFolder = $termFolderMap[$request->term] ?? strtoupper($request->term);

            $folderPath = "student_tasks/{$classFolder}/{$termFolder}";

            $filePath = null;
            $originalName = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $finalName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;

                $file->storeAs($folderPath, $finalName, 'local');
                $filePath = "{$folderPath}/{$finalName}";
            }

            $taskId = DB::table('student_tasks')->insertGetId([
                'class_schedule_id' => $request->schedule_id,
                'term' => $request->term,
                'activity_name' => $request->title,
                'activity_type' => $request->type,
                'max_score' => $request->type === 'module' ? null : $request->max_score,
                'due_date' => $request->due_date,
                'file_path' => $filePath,
                'original_filename' => $originalName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'activity' => [
                    'title' => $request->title,
                    'type' => $request->type,
                    'max_score' => $request->max_score,
                    'due_date' => $request->due_date,
                ]
            ]);

        } catch (\Exception $e) {
           return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateActivity(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'max_score' => 'nullable|integer|min:1|required_unless:type,module',
            'due_date' => 'nullable|date',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,zip,jpg,png',
        ]);

        try {
            $task = DB::table('student_tasks')->where('id', $id)->first();

            if (!$task) {
                return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
            }

            $updateData = [
                'activity_name' => $request->title,
                'activity_type' => $request->type,
                'max_score' => $request->type === 'module' ? null : $request->max_score,
                'due_date' => $request->due_date,
                'updated_at' => now(),
            ];

            if ($request->hasFile('attachment')) {

                if ($task->file_path) {
                    Storage::disk('local')->delete($task->file_path);
                }

                $classInfo = DB::table('class_schedules')
                    ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                    ->where('class_schedules.id', $task->class_schedule_id)
                    ->select('subjects.subject_code', 'class_schedules.section_name')
                    ->first();

                $classFolder = strtoupper($classInfo->subject_code . '_' . $classInfo->section_name);

                $termFolderMap = [
                    'prelim' => 'PRELIM_TERM',
                    'midterm' => 'MIDTERM_TERM',
                    'semifinal' => 'SEMI-FINAL_TERM',
                    'final' => 'FINAL_TERM',
                ];
                $termFolder = $termFolderMap[$task->term] ?? strtoupper($task->term);
                $folderPath = "student_tasks/{$classFolder}/{$termFolder}";

                $file = $request->file('attachment');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $finalName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;

                $file->storeAs($folderPath, $finalName, 'local');

                $updateData['original_filename'] = $originalName;
                $updateData['file_path'] = "{$folderPath}/{$finalName}";
            }

            DB::table('student_tasks')->where('id', $id)->update($updateData);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteActivity($id)
    {
        try {
            $task = DB::table('student_tasks')->where('id', $id)->first();

            if (!$task) {
                return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
            }

            if ($task->file_path) {
                Storage::disk('local')->delete($task->file_path);
            }

            DB::table('student_tasks')->where('id', $id)->delete();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function viewTaskSubmissions($task_id)
    {
        if (session('user_role') !== 'professor') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $task = DB::table('student_tasks')->where('id', $task_id)->first();

        if (!$task) {
            return redirect()->back()->with('error', 'Task not found.');
        }

        $submissions = DB::table('student_subjects')
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->leftJoin('task_submissions', function($join) use ($task_id) {
                $join->on('students.student_id', '=', 'task_submissions.student_id')
                    ->where('task_submissions.task_id', '=', $task_id);
            })
            ->where('student_subjects.schedule_id', $task->class_schedule_id)
            ->where('student_subjects.status', 'Enrolled')
            ->select(
                'students.student_id',
                'students.first_name',
                'students.last_name',
                'task_submissions.id as submission_id',
                'task_submissions.original_filename',
                'task_submissions.file_path',
                'task_submissions.submitted_at',
                'task_submissions.grade'
            )
            ->orderBy('students.last_name', 'asc')
            ->get();

        return view('professor.task_submissions', compact('task', 'submissions'));
    }

    public function viewTaskGrading($submission_id)
    {
        $submission = DB::table('task_submissions')
            ->join('students', 'task_submissions.student_id', '=', 'students.student_id')
            ->join('student_tasks', 'task_submissions.task_id', '=', 'student_tasks.id')
            ->where('task_submissions.id', $submission_id)
            ->select(
                'task_submissions.*',
                'students.first_name',
                'students.last_name',
                'student_tasks.activity_name',
                'student_tasks.max_score'
            )
            ->first();

        if (!$submission) {
            return redirect()->back()->with('error', 'Submission record not found.');
        }

        return view('professor.task_grading', compact('submission'));
    }

    public function downloadStudentSubmission($id)
    {

        if (session('user_role') !== 'professor') {
            abort(403, 'Unauthorized access.');
        }

        $submission = DB::table('task_submissions')
            ->where('id', $id)
            ->first();

        if (!$submission || !$submission->file_path) {
            abort(404, 'Submission record or file path not found in database.');
        }

        $fullPath = storage_path('app/private/' . $submission->file_path);

        if (!file_exists($fullPath)) {
            abort(404, 'The physical file does not exist on the server directory.');
        }

        $downloadName = $submission->original_filename ?? basename($fullPath);

        return response()->download($fullPath, $downloadName);
    }

    public function submitGrade(Request $request, $submission_id)
    {
        $submission = DB::table('task_submissions')->where('id', $submission_id)->first();

        if (!$submission) {
            return redirect()->back()->with('error', 'Submission record not found.');
        }

        $task = DB::table('student_tasks')->where('id', $submission->task_id)->first();

        $request->validate([
            'grade' => "required|numeric|min:0|max:{$task->max_score}",
            'feedback' => 'nullable|string|max:1000'
        ]);

        DB::table('task_submissions')->where('id', $submission_id)->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            'updated_at' => now()
        ]);

        return redirect()->back()->with('Success', 'Grade and feedback updated successfully!');
    }

    public function viewStudentBreakdown($schedule_id, $student_id, Request $request)
    {
        if (session('user_role') !== 'professor') {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        $professorId = session('user_id');
        $selectedTerm = $request->query('term');

        $classDetails = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('class_schedules.id', $schedule_id)
            ->where('subjects.professor_id', $professorId)
            ->select(
                'class_schedules.id as id',
                'class_schedules.id as schedule_id',
                'class_schedules.section_name',
                'class_schedules.class_days',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'class_schedules.room_assignment',
                'subjects.subject_code',
                'subjects.subject_title'
            )
            ->first();

        if (!$classDetails) {
            return redirect()->route('professor.manage-classes')->with('error', 'Class section not found or unauthorized.');
        }

        $student = DB::table('students')
            ->where('student_id', $student_id)
            ->first();

        if (!$student) {
            return redirect()->back()->with('error', 'Student not found.');
        }

        $query = DB::table('student_tasks')
            ->where('class_schedule_id', $schedule_id)
            ->leftJoin('task_submissions', function($join) use ($student_id) {
                $join->on('student_tasks.id', '=', 'task_submissions.task_id')
                    ->where('task_submissions.student_id', '=', $student_id);
            })
            ->select(
                'student_tasks.*',
                'task_submissions.grade',
                'task_submissions.feedback',
                'task_submissions.submitted_at',
                'task_submissions.id as submission_id'
            );

        if ($selectedTerm) {
            $query->where('student_tasks.term', $selectedTerm);
        }

        $tasksWithGrades = $query->orderBy('student_tasks.due_date', 'asc')
            ->get()
            ->groupBy('term');

        $termPeriods = TermHelper::getActiveTermPeriods();

        $allAttendances = DB::table('attendances')
            ->where('class_schedule_id', $schedule_id)
            ->where('student_id', $student_id)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $attendances = $allAttendances->map(function ($att) use ($termPeriods) {
            $att->derived_term = TermHelper::deriveTerm($att->attendance_date, $termPeriods);
            return $att;
        });

        if ($selectedTerm) {
            $attendances = $attendances->filter(function ($att) use ($selectedTerm) {
                return $att->derived_term === $selectedTerm;
            })->values();
        }

        $term = $selectedTerm;

        return view('professor.grades_breakdown', compact('classDetails', 'student', 'tasksWithGrades', 'attendances', 'term'));
    }

    public function saveGrades(Request $request)
    {
        //dd($request->all()); //Troubleshooting
        $request->validate([
            'grades'                  => 'required|array',
            'grades.*.schedule_id'    => 'required|integer',
            'grades.*.student_number' => 'required|string',
            'grades.*.term'           => 'required|in:prelim,midterm,semifinal,final',
            'grades.*.class_standing' => 'nullable|numeric|min:0|max:100',
            'grades.*.major_exam'     => 'nullable|numeric|min:0|max:100',
            'grades.*.equivalent'     => 'nullable|numeric|min:1|max:5',
            'grades.*.is_manual'      => 'required|boolean',
        ]);
        try {
            foreach ($request->grades as $grade) {
                $auto = GradeHelper::computeAutoGrades(
                    $grade['schedule_id'],
                    $grade['student_number'],
                    $grade['term']
                );
                StudentTermGrade::updateOrCreate(
                    [
                        'schedule_id'    => $grade['schedule_id'],
                        'student_number' => $grade['student_number'],
                        'term'           => $grade['term'],
                    ],
                    [
                        'attendance'     => $auto['attendance'] ?? null,
                        'class_standing' => $grade['class_standing'] ?? null,
                        'major_exam'     => $grade['major_exam'] ?? null,
                        'equivalent'     => $grade['equivalent'] ?? null,
                        'is_manual'      => $grade['is_manual'] ?? false,
                        'is_submitted'   => false,
                    ]
                );
            }
            return response()->json([
                'success' => true,
                'message' => 'Draft grades saved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false,'message' => $e->getMessage()], 500);
        }
    }

    public function profileView()
    {
        if (session('user_role') !== 'professor') {
            return redirect('/')->with('Error', 'Unauthorized access.');
        }

        $professor_id = session('user_id');

        $user = User::where('id', $professor_id)->first();

        $isLocked = !Str::startsWith($user->password, '$2y$');

        return view('professor.user_details', compact('user', 'isLocked'));
    }

}
