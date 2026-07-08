<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class ProfessorController extends Controller
{
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

        return view('professor.class_details', compact('classDetails', 'students', 'tasks'));
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
        // 1. Inayos ang validation para tanggapin ang module (nullable) at due_date
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
                // Burahin ang lumang file kung mayroon man
                if ($task->file_path) {
                    Storage::disk('local')->delete($task->file_path);
                }
                
                // 2. Ginawang dynamic ang folder structure kapareho ng saveActivity para organisado pa rin ang path
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

        // Tinanggal natin ang task_submissions.grade para mawala ang SQL error
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

        // 2. Kunin ang record mula sa task_submissions table
        $submission = DB::table('task_submissions')
            ->where('id', $id)
            ->first();

        if (!$submission || !$submission->file_path) {
            abort(404, 'Submission record or file path not found in database.');
        }

        // 3. Buuin ang absolute system path papunta sa private storage folder natin
        $fullPath = storage_path('app/private/' . $submission->file_path);

        // 4. Siguraduhing umiiral talaga ang file sa file system ng computer/server niyo
        if (!file_exists($fullPath)) {
            abort(404, 'The physical file does not exist on the server directory.');
        }

        // 5. I-download gamit ang orihinal na file name na ipinasa ng student (e.g., Thesis_Draft.pdf)
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

        return redirect()->back()->with('success', 'Grade and feedback updated successfully!');
    }

    public function profileView()
    {
        if (session('user_role') !== 'professor') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $professor_id = session('user_id');

        $user = User::where('id', $professor_id)->first();

        $isLocked = !Str::startsWith($user->password, '$2y$');

        return view('professor.user_details', compact('user', 'isLocked'));
    }
}