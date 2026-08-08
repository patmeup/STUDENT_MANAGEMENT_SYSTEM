<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class DeanController extends Controller
{

    public function dashboard() {
        if (session('user_role') !== 'dean') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $dept = session('user_department');

        $announcements = DB::table('announcements')->orderBy('created_at', 'desc')->get();

        $allowedCourses = ['BSIT']; 
        if (str_contains(strtolower($dept), 'computer studies') || str_contains(strtolower($dept), 'ccs')) {
            $allowedCourses = ['BSIT', 'BSCS'];
        } elseif (str_contains(strtolower($dept), 'business') || str_contains(strtolower($dept), 'cba')) {
            $allowedCourses = ['BSBA'];
        } elseif (str_contains(strtolower($dept), 'hospitality') || str_contains(strtolower($dept), 'chm')) {
            $allowedCourses = ['BSHM', 'BSTM'];
        }

        $trackedSubjectsCount = DB::table('curricula')
            ->whereIn('course', $allowedCourses)
            ->count();

        $totalStudentsCount = DB::table('students')
            ->whereIn('course', $allowedCourses)
            ->count();

        $activeBlocksCount = DB::table('class_schedules')
            ->where(function($query) use ($allowedCourses) {
                foreach ($allowedCourses as $course) {
                    $query->orWhere('section_name', 'LIKE', $course . '%');
                }
            })
            ->distinct('section_name')
            ->count('section_name');

        $deployedSlotsCount = DB::table('class_schedules')
            ->where(function($query) use ($allowedCourses) {
                foreach ($allowedCourses as $course) {
                    $query->orWhere('section_name', 'LIKE', $course . '%');
                }
            })
            ->count();

        $blockSummaryList = DB::table('class_schedules')
            ->where(function($query) use ($allowedCourses) {
                foreach ($allowedCourses as $course) {
                    $query->orWhere('class_schedules.section_name', 'LIKE', $course . '%');
                }
            })
            ->select(
                'class_schedules.section_name',
                DB::raw('(
                    select count(distinct student_subjects.student_number)
                    from student_subjects
                    inner join class_schedules as cs2 on student_subjects.schedule_id = cs2.id
                    where cs2.section_name = class_schedules.section_name
                    and student_subjects.status = "Enrolled"
                ) as total_students')
            )
            ->groupBy('class_schedules.section_name')
            ->orderBy('class_schedules.section_name', 'asc')
            ->take(5)
            ->get();

        return view('dean.dashboard', compact(
            'dept',
            'announcements',
            'trackedSubjectsCount', 
            'totalStudentsCount', 
            'activeBlocksCount', 
            'deployedSlotsCount',
            'blockSummaryList'
        ));
    }

    public function viewMasterlist(Request $request)
    {
        if (session('user_role') !== 'dean') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $dept = session('user_department');
        $allowedCourses = [];

        if (str_contains(strtolower($dept), 'computer studies') || str_contains(strtolower($dept), 'ccs')) {
            $allowedCourses = ['BSIT', 'BSCS'];
        } elseif (str_contains(strtolower($dept), 'business') || str_contains(strtolower($dept), 'cba')) {
            $allowedCourses = ['BSBA'];
        } elseif (str_contains(strtolower($dept), 'hospitality') || str_contains(strtolower($dept), 'chm')) {
            $allowedCourses = ['BSHM', 'BSTM'];
        } else {
            $allowedCourses = ['BSIT']; 
        }

        $students = DB::table('students')
            ->whereIn('course', $allowedCourses)
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get();

        $sections = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->join('curricula', 'subjects.id', '=', 'curricula.subject_id')
            ->select(
                'class_schedules.section_name',
                DB::raw('count(distinct class_schedules.subject_id) as total_subjects'),
                DB::raw('(
                    select count(distinct student_subjects.student_number)
                    from student_subjects
                    inner join class_schedules as cs2 on student_subjects.schedule_id = cs2.id
                    where cs2.section_name = class_schedules.section_name
                    and student_subjects.status = "Enrolled"
                ) as enrolled_count')
            )
            ->groupBy('class_schedules.section_name')
            ->orderBy('class_schedules.section_name', 'asc')
            ->get();

        return view('dean.masterlist', compact('students', 'sections', 'dept'));
    }

    public function viewSectionRoster($section_name)
    {
        if (session('user_role') !== 'dean') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $dept = session('user_department');

        $students = DB::table('student_subjects')
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->where('class_schedules.section_name', $section_name)
            ->select(
                'students.student_id',
                'students.first_name',
                'students.last_name',
                'students.course',
                'students.year_level'
            )
            ->distinct()
            ->orderBy('students.last_name', 'asc')
            ->orderBy('students.first_name', 'asc')
            ->get();

        return view('dean.view_section', compact('students', 'section_name', 'dept'));
    }

    public function viewScheduleList()
    {
        if (session('user_role') !== 'dean') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $schedules = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->join('curricula', 'subjects.id', '=', 'curricula.subject_id')
            ->leftJoin('employees', 'subjects.professor_id', '=', 'employees.id') 
            ->select(
                'class_schedules.id',
                'class_schedules.subject_id',
                'class_schedules.section_name',
                'class_schedules.class_days',
                'class_schedules.room_assignment',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'subjects.subject_code',
                'subjects.subject_title',
                'curricula.year_level',
                'curricula.semester',
                'employees.first_name as prof_first',
                'employees.last_name as prof_last',
                'employees.employee_id as prof_emp_id'
            )
            ->orderBy('curricula.year_level', 'asc')
            ->orderBy('class_schedules.section_name', 'asc')
            ->get();

        return view('dean.schedule_list', compact('schedules'));
    }

    public function viewSubjectCatalog()
    {
        $dept = session('user_department');

        if (str_contains(strtolower($dept), 'computer studies') || str_contains(strtolower($dept), 'ccs')) {
            $course = 'BSIT';
        } else {
            $course = 'BSIT';
        }

        $curriculumSubjects = DB::table('curricula')
            ->join('subjects', 'curricula.subject_id', '=', 'subjects.id')
            ->where('curricula.course', $course)
            ->select(
                'curricula.year_level',
                'curricula.semester',
                'subjects.subject_code',
                'subjects.subject_title',
                'subjects.lecture_units',
                'subjects.lab_units'
            )
            ->orderBy('curricula.year_level', 'asc')
            ->orderBy('curricula.semester', 'asc')
            ->orderBy('subjects.subject_code', 'asc')
            ->get();

        return view('dean.subject_list', compact('curriculumSubjects', 'dept'));
    }

    public function facultyGradeStatus(Request $request)
    {
        if (session('user_role') !== 'dean') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $search = $request->input('search');

        $activeAcademicPeriod = DB::table('academic_periods')->where('is_active', 1)->first();

        $termPeriods = $activeAcademicPeriod
            ? DB::table('term_periods')->where('academic_period_id', $activeAcademicPeriod->id)->orderBy('start_date', 'asc')->get()
            : collect();

        $currentDate = now()->toDateString();
        $defaultTerm = 'prelim';
        foreach ($termPeriods as $tp) {
            if ($currentDate >= $tp->start_date && $currentDate <= $tp->end_date) {
                $defaultTerm = $tp->term;
                break;
            }
        }
        
        $termFilter = $request->input('term', $defaultTerm);

        $classesQuery = DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('employees', function ($join) {
                $join->on('subjects.professor_id', '=', 'employees.id')
                    ->where('employees.role', '=', 'professor');
            })
            ->select(
                'class_schedules.id as schedule_id',
                'class_schedules.section_name',
                'subjects.subject_code',
                'subjects.subject_title',
                'employees.first_name as prof_first_name',
                'employees.last_name as prof_last_name',
                DB::raw('(SELECT COUNT(*)
                        FROM student_subjects
                        JOIN students ON student_subjects.student_number = students.student_id
                        WHERE student_subjects.schedule_id = class_schedules.id
                        AND student_subjects.status = "Enrolled") as enrolled_count')
            );

        if ($search) {
            $classesQuery->where(function ($q) use ($search) {
                $q->where('employees.first_name', 'like', "%{$search}%")
                ->orWhere('employees.last_name', 'like', "%{$search}%")
                ->orWhere('subjects.subject_code', 'like', "%{$search}%")
                ->orWhere('subjects.subject_title', 'like', "%{$search}%")
                ->orWhere('class_schedules.section_name', 'like', "%{$search}%");
            });
        }

        $classes = $classesQuery
            ->orderBy('employees.last_name')
            ->orderBy('class_schedules.section_name')
            ->get();

        $report = $classes->map(function ($class) use ($termFilter) {
            $submittedCount = DB::table('student_term_grades')
                ->where('schedule_id', $class->schedule_id)
                ->where('term', $termFilter)
                ->where('is_submitted', 1)
                ->count();

            if ($class->enrolled_count === 0) {
                $status = 'empty';
            } elseif ($submittedCount === 0) {
                $status = 'not_started';
            } elseif ($submittedCount < $class->enrolled_count) {
                $status = 'partial';
            } else {
                $status = 'complete';
            }

            $class->submitted_count = $submittedCount;
            $class->status = $status;

            return $class;
        });

        return view('dean.faculty-grade-status', compact('report', 'termPeriods', 'termFilter', 'activeAcademicPeriod', 'search'));
    }

    public function getGradeBreakdown(Request $request, $scheduleId)
    {
        if (session('user_role') !== 'dean') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $term = $request->input('term', 'prelim');

        $grades = DB::table('student_subjects')
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->leftJoin('student_term_grades', function ($join) use ($scheduleId, $term) {
                $join->on('student_subjects.student_number', '=', 'student_term_grades.student_number')
                    ->where('student_term_grades.schedule_id', '=', $scheduleId)
                    ->where('student_term_grades.term', '=', $term);
            })
            ->where('student_subjects.schedule_id', $scheduleId)
            ->where('student_subjects.status', 'Enrolled')
            ->select(
                'students.first_name',
                'students.last_name',
                'student_term_grades.class_standing',
                'student_term_grades.major_exam',
                'student_term_grades.equivalent',
                'student_term_grades.is_submitted'
            )
            ->orderBy('students.last_name', 'asc')
            ->get();

        return response()->json($grades);
    }

    public function profileView()
    {
        $userId = session('user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect('/')->withErrors(['error' => 'Session expired. Please login again.']);
        }

        $isLocked = !Str::startsWith($user->password, '$2y$');

        return view('dean.user_details', compact('user', 'isLocked'));
    }  
}