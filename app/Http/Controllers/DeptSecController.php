<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Subject;
use App\Models\Student;
use App\Models\BlockSection;
use App\Models\ClassSchedule;

class DeptSecController extends Controller
{

    public function dashboard() {
        if (session('user_role') !== 'dept_sec') {
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

        return view('department_secretary.dashboard', compact(
            'dept',
            'announcements',
            'trackedSubjectsCount', 
            'totalStudentsCount', 
            'activeBlocksCount', 
            'deployedSlotsCount'
        ));
    }

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

    public function studentsList(Request $request)
    {
        if (session('user_role') !== 'dept_sec') {
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
                'curricula.year_level',
                'curricula.semester',
                DB::raw('count(distinct class_schedules.subject_id) as total_subjects'),
                DB::raw('(
                    select count(distinct student_subjects.student_number)
                    from student_subjects
                    inner join class_schedules as cs2 on student_subjects.schedule_id = cs2.id
                    where cs2.section_name = class_schedules.section_name
                    and student_subjects.status = "Enrolled"
                ) as enrolled_count')
            )
            ->groupBy('class_schedules.section_name', 'curricula.year_level', 'curricula.semester')
            ->orderBy('class_schedules.section_name', 'asc')
            ->get();

        return view('department_secretary.masterlist', compact('students', 'sections', 'dept'));
    }

    public function deleteStudent($student_id)
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        try {
            DB::beginTransaction();

            DB::table('student_subjects')->where('student_number', $student_id)->delete();

            $deleted = DB::table('students')->where('student_id', $student_id)->delete();

            if (!$deleted) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Student record not found or already deleted.');
            }

            DB::commit();

            return redirect()->back()->with('success', 'Student record successfully deleted from the system.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete student record: ' . $e->getMessage());
        }
    }

    public function viewSectionRoster($section_name)
    {
        if (session('user_role') !== 'dept_sec') {
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

        return view('department_secretary.view_section', compact('students', 'section_name', 'dept'));
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
            'schedules'  => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $studentNumber = $request->input('student_id');
            $selectedSchedules = $request->input('schedules', []); 

            $filteredSchedules = array_filter($selectedSchedules, function($value) {
                return !empty($value);
            });

            if (empty($filteredSchedules)) {
                return redirect()->back()->withInput()->with('error', 'Please select a schedule/section for at least one subject before saving.');
            }

            DB::table('student_subjects')->where('student_number', $studentNumber)->delete();

            foreach ($filteredSchedules as $subjectId => $scheduleId) {
                $scheduleExists = DB::table('class_schedules')->where('id', $scheduleId)->exists();
                
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
                            ->with('Success', 'Subjects and class schedules have been successfully plotted and saved for this student!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                            ->with('Error', 'An error occurred while submitting the data: ' . $e->getMessage());
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