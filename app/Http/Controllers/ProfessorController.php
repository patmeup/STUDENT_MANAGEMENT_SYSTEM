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
            // --- ADDED THIS SUBQUERY SUB-SELECT TO COUNT THE ENROLLED STUDENTS ---
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
            'max_score' => 'required|integer|min:1',
            'term' => 'required|string',
            'schedule_id' => 'required|integer',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,zip,jpg,png',
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
                'max_score' => $request->max_score,
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
            'max_score' => 'required|integer|min:1',
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
                'max_score' => $request->max_score,
                'due_date' => $request->due_date,
                'updated_at' => now(),
            ];

            if ($request->hasFile('attachment')) {
                if ($task->file_path) {
                    Storage::disk('local')->delete($task->file_path);
                }
                $file = $request->file('attachment');
                $updateData['original_filename'] = $file->getClientOriginalName();
                $updateData['file_path'] = $file->store('student_tasks', 'local');
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

    public function viewTaskFile($id)
    {
        if (session('user_role') !== 'professor') {
            abort(403, 'Unauthorized Access.');
        }

        $task = DB::table('student_tasks')->where('id', $id)->first();

        if (!$task || !$task->file_path || !Storage::disk('local')->exists($task->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->response($task->file_path, $task->original_filename);
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