<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Subject;
use App\Models\Student;
use App\Models\BlockSection;
use App\Models\ClassSchedule;

class DeptSecController extends Controller
{
    public function dashboard()
    {
        $dept = session('user_department');

        $announcements = DB::table('announcements')
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

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
        $dept = session('user_department');
        $allowedCourses = [];

        if (str_contains(strtolower($dept), 'computer studies') || str_contains(strtolower($dept), 'ccs')) {
            $allowedCourses = ['BSIT', 'BSCS'];
        } else {
            $allowedCourses = ['BSIT'];
        }

        $students = DB::table('students')
            ->whereIn('course', $allowedCourses)
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get();

        $sections = DB::table('class_schedules as main_cs')
            ->join('subjects', 'main_cs.subject_id', '=', 'subjects.id')
            ->join('curricula', 'subjects.id', '=', 'curricula.subject_id')
            ->select(
                'main_cs.section_name',
                'curricula.year_level',
                'curricula.semester',
                DB::raw('count(distinct main_cs.subject_id) as total_subjects'),
                DB::raw("(
                    select count(*)
                    from students
                    where students.section = main_cs.section_name
                ) as enrolled_count")
            )
            ->groupBy('main_cs.section_name', 'curricula.year_level', 'curricula.semester')
            ->orderBy('main_cs.section_name', 'asc')
            ->get();

        return view('department_secretary.masterlist', compact('students', 'sections', 'dept'));
    }

    public function viewStudentProfile($student_id)
    {
        $dept = session('user_department');
        $student = Student::where('student_id', $student_id)->firstOrFail();
        return view('department_secretary.student_profile', compact('student', 'dept'));
    }

    public function deleteStudent($student_id)
    {
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

    public function secureViewFile($student_id, $filename)
    {
        $path = "student_records/{$student_id}/{$filename}";

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->response($path);
    }

    public function viewSectionRoster($section_name)
    {
        $dept = session('user_department');

        $students = DB::table('student_subjects')
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->where('class_schedules.section_name', $section_name)
            ->where('student_subjects.status', 'Enrolled')
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

        $classSchedules = DB::table('class_schedules')->get();

        $availableSections = DB::table('class_schedules')
            ->select('section_name')
            ->distinct()
            ->orderBy('section_name', 'asc')
            ->pluck('section_name');

        $enrolledSections = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->where('student_subjects.student_number', $student_id)
            ->pluck('class_schedules.section_name', 'student_subjects.subject_id')
            ->toArray();

        return view('department_secretary.plot_subjects', compact(
            'student',
            'curriculumSubjects',
            'dept',
            'classSchedules',
            'availableSections',
            'enrolledSections'
        ));
    }

    public function bulkEnrollSection(Request $request)
    {
        $request->validate([
            'current_section' => 'required|string',
            'target_year'     => 'nullable|string',
            'target_semester' => 'nullable|string',
        ]);

        $sectionName = $request->input('current_section');
        $targetYear  = $request->input('target_year');
        $targetSem   = $request->input('target_semester');

        DB::beginTransaction();
        try {
            $students = DB::table('students')
                ->where('section', $sectionName)
                ->pluck('student_id');

            if ($students->isEmpty()) {
                return redirect()->back()->with('error', 'No students found in section "' . $sectionName . '". Please ensure students are assigned to this block section.');
            }

            $schedulesQuery = DB::table('class_schedules')
                ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                ->leftJoin('curricula', 'subjects.id', '=', 'curricula.subject_id')
                ->where('class_schedules.section_name', $sectionName);

            if ($targetYear) {
                $schedulesQuery->where('curricula.year_level', $targetYear);
            }
            if ($targetSem) {
                $schedulesQuery->where('curricula.semester', $targetSem);
            }

            $schedules = $schedulesQuery->select('class_schedules.id as schedule_id', 'class_schedules.subject_id')
                ->distinct()
                ->get();

            if ($schedules->isEmpty()) {
                return redirect()->back()->with('error', 'No class schedules found for section "' . $sectionName . '" (' . $targetYear . ' - ' . $targetSem . ').');
            }

            $enrollments = [];
            $now = now();

            foreach ($students as $studentNumber) {
                foreach ($schedules as $sched) {
                    $exists = DB::table('student_subjects')
                        ->where('student_number', $studentNumber)
                        ->where('schedule_id', $sched->schedule_id)
                        ->exists();

                    if (!$exists) {
                        $enrollments[] = [
                            'student_number' => $studentNumber,
                            'subject_id'     => $sched->subject_id,
                            'schedule_id'    => $sched->schedule_id,
                            'status'         => 'Enrolled',
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ];
                    }
                }
            }

            if (!empty($enrollments)) {
                DB::table('student_subjects')->insert($enrollments);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Successfully bulk-enrolled ' . count($students) . ' student(s) into section ' . $sectionName . ' (' . $targetYear . ' - ' . $targetSem . ') across all Lecture and Laboratory schedules!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'An error occurred during bulk enrollment: ' . $e->getMessage());
        }
    }

    public function saveSubjects(Request $request)
    {
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

            $primarySection = reset($filteredSchedules);

            $existingCount = DB::table('students')
                ->where('section', $primarySection)
                ->where('student_id', '!=', $studentNumber)
                ->count();

            if ($existingCount >= 30) {
                return redirect()->back()
                                ->withInput()
                                ->with('error', "Section {$primarySection} has already reached the maximum capacity of 30 students (Current: {$existingCount}). Please choose another block section.");
            }

            DB::table('student_subjects')->where('student_number', $studentNumber)->delete();

            foreach ($filteredSchedules as $subjectId => $sectionName) {
                $matchingSchedules = DB::table('class_schedules')
                    ->where('subject_id', $subjectId)
                    ->where('section_name', $sectionName)
                    ->get();

                foreach ($matchingSchedules as $sched) {
                    DB::table('student_subjects')->insert([
                        'student_number' => $studentNumber,
                        'subject_id'     => $subjectId,
                        'schedule_id'    => $sched->id,
                        'status'         => 'Enrolled',
                        'created_at'     => now(),
                        'updated_at'     => now()
                    ]);
                }
            }

            DB::table('students')
                ->where('student_id', $studentNumber)
                ->update([
                    'section' => $primarySection,
                    'updated_at' => now()
                ]);

            DB::commit();

            return redirect()->route('department_secretary.students')
                            ->with('success', "Subjects successfully plotted! Student is now officially enrolled in section {$primarySection}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                            ->with('error', 'An error occurred while saving: ' . $e->getMessage());
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
        return view('department_secretary.user_details', compact('user', 'isLocked'));
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
