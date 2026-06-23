<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeptSecController extends Controller
{
    // Dashboard Summary Page
    public function index()
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $dept = session('user_department');
    
        $totalStudents = DB::table('students')->where('department', $dept)->count();
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

        $dept = session('user_department'); // e.g., "College of Computer Studies"

        // Pagpasyahan kung anong kurso ang kabilang sa departamento ng Secretary
        // Baguhin o dagdagan ang mga kurso sa ibaba depende sa mga kurso sa portal mo
        $allowedCourses = [];
        
        if (str_contains(strtolower($dept), 'computer') || str_contains(strtolower($dept), 'information technology')) {
            $allowedCourses = ['BSIT', 'BSCS', 'BSIS']; // Mga kurso sa ilalim ng Computer Studies
        } elseif (str_contains(strtolower($dept), 'business') || str_contains(strtolower($dept), 'hospitality')) {
            $allowedCourses = ['BSBA', 'BSHM', 'BSTM']; // Mga kurso sa ilalim ng Business/Hospitality
        } else {
            // Fallback: Kung hindi tugma sa itaas, kunin na lang muna lahat ng kurso para may lumabas
            $allowedCourses = ['BSIT']; 
        }

        // Kunin ang mga estudyante gamit ang 'course' column imbis na 'department'
        $students = DB::table('students')
            ->whereIn('course', $allowedCourses)
            ->get();

        return view('department_secretary.masterlist', compact('students', 'dept'));
    }

    // 2. Interface kung saan i-e-encode ang subjects ng napiling estudyante
    public function plotSubjects($student_id)
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $dept = session('user_department');

        // Hanapin ang profile ng estudyante gamit ang student number
        $student = DB::table('students')->where('student_id', $student_id)->first();
        
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        // Kunin ang mga kaukulang subjects ng kurso ng bata base sa 'curricula' table mo
        $curriculumSubjects = DB::table('curricula')
            ->join('subjects', 'curricula.subject_id', '=', 'subjects.id')
            ->select(
                'curricula.*', 
                'subjects.subject_code', 
                'subjects.subject_title', 
                'subjects.lecture_units', 
                'subjects.lab_units'
            )
            ->where('curricula.course', $student->course) // Halimbawa: BSIT
            ->orderBy('curricula.year_level')
            ->orderBy('curricula.semester')
            ->get();

        return view('department_secretary.plot_subjects', compact('student', 'curriculumSubjects', 'dept'));
    }

    // 3. Pag-save ng mga pinasok o tsek na subjects sa student record
    public function saveSubjects(Request $request)
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'student_number' => 'required',
            'subjects' => 'required|array'
        ]);

        $student_number = $request->student_number;
        $subjects = $request->subjects; // Ito ay maglalaman ng mga napiling subject_id array

        // Subukan nating ipasok sa database gamit ang isang transaction para safe
        DB::transaction(function () use ($student_number, $subjects) {
            // I-clear muna ang mga lumang subjects ng estudyante para maiwasan ang duplicate data
            DB::table('student_subjects')->where('student_number', $student_number)->delete();

            // Isa-isahing i-encode ang mga bagong subjects
            foreach ($subjects as $id) {
                DB::table('student_subjects')->insert([
                    'student_number' => $student_number,
                    'subject_id'     => $id,
                    'status'         => 'Enrolled',
                    'created_at'     => now(),
                    'updated_at'     => now()
                ]);
            }
        });

        return redirect()->route('department_secretary.students')->with('success', 'Subjects have been successfully encoded for this student!');
    }
}