<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class DeptSecController extends Controller
{
    
    public function index()
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $dept = session('user_department');
    
        $totalStudents = DB::table('students')
            ->join('courses', 'students.course', '=', 'courses.course_code')
            ->where('courses.department', $dept)
            ->count();
        $totalSubjects = DB::table('curricula')->where('course', 'BSIT')->count();

        return view('department_secretary.dashboard', compact('dept', 'totalStudents', 'totalSubjects'));
    }

    // Subject Catalog Reference Page
    public function subjectCatalog(Request $request)
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $dept = session('user_department');
        
        $yearFilter = $request->get('year_level');

        $query = DB::table('curricula')
            ->join('subjects', 'curricula.subject_id', '=', 'subjects.id')
            ->select(
                'curricula.*', 
                'subjects.subject_code', 
                'subjects.subject_title', 
                'subjects.lecture_units', 
                'subjects.lab_units'
            )
            ->where('curricula.course', 'BSIT');

        if ($yearFilter) {
            $query->where('curricula.year_level', $yearFilter);
        }

        $curriculumSubjects = $query->orderBy('curricula.year_level')
                                    ->orderBy('curricula.semester')
                                    ->get();

        return view('department_secretary.subject_catalog', compact('curriculumSubjects', 'dept', 'yearFilter'));
    }

    // 1. Listahan ng mga Estudyante sa ilalim ng kanyang Departamento
    public function studentsList()
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $dept = session('user_department');

        $allowedCourses = [];
        
        if (str_contains(strtolower($dept), 'computer') || str_contains(strtolower($dept), 'information technology')) {
            $allowedCourses = ['BSIT', 'BSCS', 'BSIS'];
        } elseif (str_contains(strtolower($dept), 'business') || str_contains(strtolower($dept), 'hospitality')) {
            $allowedCourses = ['BSBA', 'BSHM', 'BSTM'];
        } else {
            $allowedCourses = ['BSIT']; 
        }

        $students = DB::table('students')
            ->whereIn('course', $allowedCourses)
            ->get();

        return view('department_secretary.masterlist', compact('students', 'dept'));
    }

    public function plotSubjects($student_id)
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $dept = session('user_department');

        $student = DB::table('students')->where('student_id', $student_id)->first();
        
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        $curriculumSubjects = DB::table('curricula')
            ->join('subjects', 'curricula.subject_id', '=', 'subjects.id')
            ->select(
                'curricula.*', 
                'subjects.subject_code', 
                'subjects.subject_title', 
                'subjects.lecture_units', 
                'subjects.lab_units'
            )
            ->where('curricula.course', $student->course)
            ->orderBy('curricula.year_level')
            ->orderBy('curricula.semester')
            ->get();

        $classSchedules = DB::table('class_schedules')
            ->get()
            ->groupBy('subject_id');

        return view('department_secretary.plot_subjects', compact('student', 'curriculumSubjects', 'dept', 'classSchedules'));
    }

    public function saveSubjects(Request $request)
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'student_id' => 'required',
            'schedules'  => 'required|array',
        ], [
            'schedules.required' => 'Maaari lamang pumili ng schedule/section sa kahit isang subject bago mag-save.'
        ]);

        try {
            DB::beginTransaction();

            $studentNumber = $request->input('student_id');
            
            $selectedSchedules = $request->input('schedules'); 

            $filteredSchedules = array_filter($selectedSchedules, function($value) {
                return !empty($value);
            });

            if (empty($filteredSchedules)) {
                return redirect()->back()->withInput()->with('error', 'Maaari lamang pumili ng schedule sa kahit isang subject bago mag-save.');
            }

            DB::table('student_subjects')->where('student_number', $studentNumber)->delete();

            foreach ($filteredSchedules as $subjectId => $scheduleId) {
                
                $scheduleExists = DB::table('schedules')->where('id', $scheduleId)->exists();
                
                if ($scheduleExists) {
                    DB::table('student_subjects')->insert([
                        'student_number' => $studentNumber,
                        'subject_id'     => $subjectId,  
                        'schedule_id'    => $scheduleId,  
                        'status'         => 'Enrolled',
                        'created_at'     => now(),
                        'updated_at'     => now()
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('department_secretary.students')
                            ->with('success', 'Subjects and class schedules have been successfully plotted and saved for this student!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                            ->with('error', 'May naganap na error habang isinusumite ang data: ' . $e->getMessage());
        }
    }

    public function profileView()
    {
        $userId = session('user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect('/')->withErrors(['error' => 'Session expired. Please login again.']);
        }

        $isLocked = !Str::startsWith($user->password, '$2y$');

        return view('system_admin.user_details', compact('user', 'isLocked'));
    } 
}