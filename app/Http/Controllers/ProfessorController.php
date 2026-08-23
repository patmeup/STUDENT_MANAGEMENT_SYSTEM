<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Students;
use App\Models\StudentTermGrade;
use App\Support\TermHelper;
use App\Support\GradeHelper;
use App\Support\GoogleClassroomService;
use App\Support\PredictiveGradeHelper;

class ProfessorController extends Controller
{
    public function dashboard()
    {
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
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->where('subjects.professor_id', $professor_id)
            ->where('student_subjects.status', 'Enrolled')
            ->count('student_subjects.id');

        $pendingGrading = DB::table('task_submissions')
            ->join('student_tasks', 'task_submissions.task_id', '=', 'student_tasks.id')
            ->join('class_schedules', 'student_tasks.class_schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->join('student_subjects', function ($join) {
                $join->on('student_subjects.schedule_id', '=', 'class_schedules.id')
                    ->on('student_subjects.student_number', '=', 'task_submissions.student_id');
            })
            ->where('subjects.professor_id', $professor_id)
            ->where('student_subjects.status', 'Enrolled')
            ->whereNull('task_submissions.grade')
            ->count(DB::raw('DISTINCT CONCAT(task_submissions.task_id, "-", task_submissions.student_id)'));

        return view('professor.dashboard', compact(
            'announcements',
            'totalClasses',
            'totalStudents',
            'pendingGrading'
        ));
    }

    public function manageClasses()
    {
        $professor_id = session('user_id');

        $classes = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->select(
                'class_schedules.id as schedule_id',
                'class_schedules.section_name',
                'class_schedules.schedule_type',
                'class_schedules.class_days',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'class_schedules.room_assignment',
                'subjects.subject_code',
                'subjects.subject_title',
                'subjects.lecture_units',
                'subjects.lab_units',

                DB::raw('(SELECT COUNT(*)
                        FROM student_subjects
                        JOIN students ON student_subjects.student_number = students.student_id
                        WHERE student_subjects.schedule_id = class_schedules.id
                        AND student_subjects.status = "Enrolled") as students_count')
            )
            ->where('subjects.professor_id', $professor_id)
            ->orderBy('class_schedules.section_name')
            ->get();

        $gConnection = DB::table('google_classroom_connections')
            ->where('professor_id', $professor_id)
            ->first();

        return view('professor.manage-classes', compact('classes', 'gConnection'));
    }

    public function viewClassDashboard($schedule_id)
    {
        $professorId = session('user_id');

        $classDetails = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('class_schedules.id', $schedule_id)
            ->where('subjects.professor_id', $professorId)
            ->select(
                'class_schedules.id as schedule_id',
                'class_schedules.section_name',
                'class_schedules.schedule_type',
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

                if ($saved && $saved->is_manual == 1) {
                    $autoGrades[$term][$student->student_id] = [
                        'class_standing' => $saved->class_standing,
                        'major_exam'     => $saved->major_exam,
                        'equivalent'     => $saved->equivalent,
                        'is_manual'      => true,
                    ];
                } else {
                    $computed = GradeHelper::computeAutoGrades(
                        $schedule_id,
                        $student->student_id,
                        $term
                    );
                    $computed['is_manual'] = false;
                    $autoGrades[$term][$student->student_id] = $computed;
                }
            }
        }

        $gConnection = DB::table('google_classroom_connections')
            ->where('professor_id', $professorId)
            ->first();

        $gLinkedCourse = null;
        $gAvailableCourses = collect();
        $gAnnouncements = collect();
        $gCourseWork = collect();
        $gMaterials = collect();

        if ($gConnection) {

            $hasCourses = DB::table('google_classroom_courses')
                ->where('connection_id', $gConnection->id)
                ->exists();

            if (!$hasCourses) {
                try {
                    $service = new \App\Support\GoogleClassroomService($gConnection);
                    $service->syncCourses();
                } catch (\Exception $e) {

                }
            }

            $gLinkedCourse = DB::table('google_classroom_courses')
                ->where('linked_class_schedule_id', $schedule_id)
                ->first();

            $termTasks = DB::table('student_tasks')
                ->where('class_schedule_id', $schedule_id)
                ->orderBy('due_date', 'asc')
                ->get()
                ->groupBy('term');
            $termPeriods = collect();

            $gAvailableCourses = DB::table('google_classroom_courses')
                ->where('connection_id', $gConnection->id)
                ->where(function ($q) use ($schedule_id) {
                    $q->whereNull('linked_class_schedule_id')
                    ->orWhere('linked_class_schedule_id', $schedule_id);
                })
                ->get();

            if ($gLinkedCourse) {
                $gAnnouncements = DB::table('google_classroom_announcements')
                    ->where('course_id', $gLinkedCourse->id)
                    ->orderBy('posted_at', 'desc')
                    ->get();

                $academicPeriodId = DB::table('term_periods')->max('academic_period_id') ?? 1;

                $termPeriods = DB::table('term_periods')
                    ->where('academic_period_id', $academicPeriodId)
                    ->get();

                $gCourseWork = collect();

                $gMaterials = DB::table('google_classroom_materials')
                    ->where('course_id', $gLinkedCourse->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }

        return view('professor.class_details', compact(
            'classDetails',
            'students',
            'tasks',
            'termTasks',
            'termPeriods',
            'autoGrades',
            'draftGrades',
            'gConnection',
            'gLinkedCourse',
            'gAvailableCourses',
            'gAnnouncements',
            'gCourseWork',
            'gMaterials'
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

    public function linkGoogleClassroomCourse(Request $request, $schedule_id)
    {
        $request->validate([
            'google_course_id' => 'required|integer',
        ]);

        $professorId = session('user_id');

        $connection = DB::table('google_classroom_connections')
            ->where('professor_id', $professorId)
            ->first();

        if (!$connection) {
            return redirect()->back()->with('error', 'No connected Google Classroom account found.');
        }

        $course = DB::table('google_classroom_courses')
            ->where('id', $request->google_course_id)
            ->where('connection_id', $connection->id)
            ->first();

        if (!$course) {
            return redirect()->back()->with('error', 'Invalid Google Classroom course selected.');
        }

        DB::table('google_classroom_courses')
            ->where('linked_class_schedule_id', $schedule_id)
            ->update(['linked_class_schedule_id' => null]);

        DB::table('google_classroom_courses')
            ->where('id', $course->id)
            ->update(['linked_class_schedule_id' => $schedule_id]);

        return redirect()->back()->with('success', 'Google Classroom course linked successfully!');
    }

    public function unlinkGoogleClassroomCourse($schedule_id)
    {
        DB::table('google_classroom_courses')
            ->where('linked_class_schedule_id', $schedule_id)
            ->update(['linked_class_schedule_id' => null]);

        return redirect()->back()->with('success', 'Google Classroom course unlinked.');
    }

    public function syncGoogleClassroom(Request $request)
    {
        $professorId = session('user_id');

        $connection = DB::table('google_classroom_connections')
            ->where('professor_id', $professorId)
            ->first();

        if (!$connection) {
            return redirect()->back()->with('error', 'No connected Google Classroom account found.');
        }

        try {
            $service = new \App\Support\GoogleClassroomService($connection);
            $service->syncCourses();

            DB::table('google_classroom_connections')
                ->where('id', $connection->id)
                ->update(['last_synced_at' => now()]);

            return redirect()->back()->with('success', 'Google Classroom synced successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
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
            'due_date' => 'nullable|date',
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

            $formattedDueDate = $request->due_date ? date('Y-m-d H:i:s', strtotime($request->due_date)) : null;

            $taskId = DB::table('student_tasks')->insertGetId([
                'class_schedule_id' => $request->schedule_id,
                'term' => $request->term,
                'activity_name' => $request->title,
                'activity_type' => $request->type,
                'max_score' => $request->type === 'module' ? null : $request->max_score,
                'due_date' => $formattedDueDate,
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
                    'due_date' => $formattedDueDate,
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

            $formattedDueDate = $request->due_date ? date('Y-m-d H:i:s', strtotime($request->due_date)) : null;

            $updateData = [
                'activity_name' => $request->title,
                'activity_type' => $request->type,
                'max_score' => $request->type === 'module' ? null : $request->max_score,
                'due_date' => $formattedDueDate,
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

    public function viewTaskSubmissions($id)
    {
        $task = DB::table('student_tasks')->where('id', $id)->first();

        if (!$task) {
            $gcWork = DB::table('google_classroom_coursework')->where('id', $id)->first();

            if ($gcWork) {
                $task = DB::table('student_tasks')->where('google_coursework_id', $gcWork->google_coursework_id)->first();
            }
        }

        if (!$task) {
            return redirect()->back()->with('error', 'Google Classroom task is not yet synced or set up in the system. Please sync your coursework before attempting to grade.');
        }

        $googleCourseworkLocalId = null;
        if (!empty($task->google_coursework_id)) {
            $gcWork = DB::table('google_classroom_coursework')
                ->where('google_coursework_id', $task->google_coursework_id)
                ->first();
            if ($gcWork) {
                $googleCourseworkLocalId = $gcWork->id;
            }
        }

        $submissions = DB::table('student_subjects')
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->leftJoin('task_submissions', function($join) use ($task, $googleCourseworkLocalId) {
                $join->on('students.student_id', '=', 'task_submissions.student_id')
                    ->where(function($q) use ($task, $googleCourseworkLocalId) {
                        $q->where('task_submissions.task_id', '=', $task->id);

                        if ($googleCourseworkLocalId) {
                            $q->orWhere('task_submissions.google_coursework_id', '=', $googleCourseworkLocalId);
                        }
                    });
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
            ->leftJoin('student_tasks', 'task_submissions.task_id', '=', 'student_tasks.id')
            ->leftJoin('google_classroom_coursework', 'task_submissions.google_coursework_id', '=', 'google_classroom_coursework.id')
            ->where('task_submissions.id', $submission_id)
            ->select(
                'task_submissions.*',
                'students.first_name',
                'students.last_name',
                DB::raw('COALESCE(student_tasks.activity_name, google_classroom_coursework.title, "Untitled Task") as activity_name'),
                DB::raw('COALESCE(student_tasks.max_score, google_classroom_coursework.max_points, 100) as max_score')
            )
            ->first();

        if (!$submission) {
            return redirect()->back()->with('error', 'Submission record not found.');
        }

        return view('professor.task_grading', compact('submission'));
    }

    public function downloadStudentSubmission($id)
    {
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

    public function evaluateStudent($task_id, $student_id)
    {
        $task = DB::table('student_tasks')->where('id', $task_id)->first();

        if (!$task) {
            return redirect()->back()->with('error', 'Task not found.');
        }

        $googleCourseworkLocalId = null;
        if (!empty($task->google_coursework_id)) {
            $gcWork = DB::table('google_classroom_coursework')
                ->where('google_coursework_id', $task->google_coursework_id)
                ->first();
            if ($gcWork) {
                $googleCourseworkLocalId = $gcWork->id;
            }
        }

        $submission = DB::table('task_submissions')
            ->where('student_id', $student_id)
            ->where(function($q) use ($task_id, $googleCourseworkLocalId) {
                $q->where('task_id', $task_id);
                if ($googleCourseworkLocalId) {
                    $q->orWhere('google_coursework_id', $googleCourseworkLocalId);
                }
            })
            ->first();

        if (!$submission) {
            $submissionId = DB::table('task_submissions')->insertGetId([
                'task_id'              => $task_id,
                'google_coursework_id' => $googleCourseworkLocalId,
                'student_id'           => $student_id,
                'file_path'            => '',
                'original_filename'    => '',
                'grade'                => null,
                'feedback'             => null,
                'submitted_at'         => now(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        } else {
            $submissionId = $submission->id;
        }

        return redirect()->route('professor.task_grading', $submissionId);
    }

    public function submitGrade(Request $request, $submission_id)
    {
        $submission = DB::table('task_submissions')->where('id', $submission_id)->first();

        if (!$submission) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Submission record not found.'], 404);
            }
            return redirect()->back()->with('error', 'Submission record not found.');
        }

        if ($submission->task_id) {
            $task = DB::table('student_tasks')->where('id', $submission->task_id)->first();
            $maxScore = $task->max_score ?? null;
        } elseif ($submission->google_coursework_id) {
            $task = DB::table('google_classroom_coursework')->where('id', $submission->google_coursework_id)->first();
            $maxScore = $task->max_points ?? null;
        } else {
            $task = null;
            $maxScore = null;
        }

        if (!$task) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Associated task details not found.'], 404);
            }
            return redirect()->back()->with('error', 'Associated task details not found.');
        }

        $request->validate([
            'grade' => "required|numeric|min:0|max:" . ($maxScore ?? 100),
            'feedback' => 'nullable|string|max:1000'
        ]);

        DB::table('task_submissions')->where('id', $submission_id)->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            'updated_at' => now()
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Grade and feedback updated successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'Grade and feedback updated successfully!');
    }

    public function viewStudentBreakdown($schedule_id, $student_id, Request $request)
    {
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

    public function saveDraftGrades(Request $request)
    {
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
                $isManual = (bool) $grade['is_manual'];

                $updateData = [
                    'is_manual'    => $isManual ? 1 : 0,
                    'is_submitted' => false,
                    'class_standing' => $grade['class_standing'] ?? null,
                    'major_exam'     => $grade['major_exam'] ?? null,
                    'equivalent'     => $grade['equivalent'] ?? null,
                ];

                StudentTermGrade::updateOrCreate(
                    [
                        'schedule_id'    => $grade['schedule_id'],
                        'student_number' => $grade['student_number'],
                        'term'           => $grade['term'],
                    ],
                    $updateData
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Draft grades saved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function recalculateAutoGrade(Request $request)
    {
        $schedule_id = $request->schedule_id;
        $student_number = $request->student_number;
        $term = $request->term;

        $computed = GradeHelper::computeAutoGrades($schedule_id, $student_number, $term);

        StudentTermGrade::updateOrCreate(
            [
                'schedule_id'    => $schedule_id,
                'student_number' => $student_number,
                'term'           => $term,
            ],
            [
                'is_manual'      => 0,
                'class_standing' => $computed['class_standing'],
                'major_exam'     => $computed['major_exam'],
                'equivalent'     => $computed['equivalent'],
            ]
        );

        return response()->json([
            'success' => true,
            'computed' => $computed
        ]);
    }

    public function submitToRegistrar(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|integer',
            'term'        => 'required|in:prelim,midterm,semifinal,final',
        ]);

        try {
            $schedule_id = $request->schedule_id;
            $term = strtolower(trim($request->term));

            $students = DB::table('student_subjects')
                ->where('schedule_id', $schedule_id)
                ->where('status', 'Enrolled')
                ->pluck('student_number');

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No enrolled students found in this class schedule.'
                ], 404);
            }

            foreach ($students as $student_number) {
                $existingGrade = StudentTermGrade::where('schedule_id', $schedule_id)
                    ->where('student_number', $student_number)
                    ->where('term', $term)
                    ->first();

                if ($existingGrade) {
                    $existingGrade->update(['is_submitted' => true]);
                } else {
                    $computed = GradeHelper::computeAutoGrades($schedule_id, $student_number, $term);

                    StudentTermGrade::create([
                        'schedule_id'    => $schedule_id,
                        'student_number' => $student_number,
                        'term'           => $term,
                        'class_standing' => $computed['class_standing'] ?? null,
                        'major_exam'     => $computed['major_exam'] ?? null,
                        'equivalent'     => $computed['equivalent'] ?? null,
                        'is_manual'      => 0,
                        'is_submitted'   => true,
                    ]);
                }
            }

            if ($term === 'final') {
                $activePeriod = DB::table('academic_periods')->where('is_active', 1)->first();

                if ($activePeriod) {
                    try {
                        \App\Support\PredictiveGradeHelper::trainModels($activePeriod->id);
                    } catch (\Exception $e) {
                        \Log::error('ML Auto-Train Error: ' . $e->getMessage());
                    }
                }
            }

            return response()->json([
                'success'     => true,
                'message'     => 'Grades successfully submitted to the registrar!',
                'schedule_id' => $schedule_id,
                'term'        => $term
            ]);

        } catch (\Exception $e) {
            Log::error('Error submitting grades to registrar: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting grades: ' . $e->getMessage()
            ], 500);
        }
    }

    public function profileView()
    {
        $professor_id = session('user_id');

        $user = User::where('id', $professor_id)->first();

        $isLocked = !Str::startsWith($user->password, '$2y$');

        $gConnection = DB::table('google_classroom_connections')
            ->where('professor_id', $professor_id)
            ->first();

        return view('professor.user_details', compact('user', 'isLocked', 'gConnection'));
    }

        public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::find(session('user_id'));

        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');

        $user->profile_picture = $path;
        $user->save();

        return redirect()->back()->with('success', 'Profile picture updated successfully!');
    }

}
