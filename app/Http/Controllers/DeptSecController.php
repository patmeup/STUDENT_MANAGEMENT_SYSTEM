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

    private function allowedCoursesForDept()
    {
        $dept = strtolower((string) session('user_department'));
        if (str_contains($dept, 'computer studies') || str_contains($dept, 'ccs')) {
            return ['BSIT', 'BSCS'];
        }
        return ['BSIT'];
    }

    private function yearLevelFromSection($sectionName)
    {
        if (!preg_match('/(\d)/', (string) $sectionName, $m)) {
            return null;
        }
        $map = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
        return $map[(int) $m[1]] ?? null;
    }

    private function blockYearLevel($sectionName, $periodId)
    {
        $fromName = $this->yearLevelFromSection($sectionName);
        if ($fromName) {
            return $fromName;
        }

        return DB::table('class_schedules')
            ->join('curricula', 'curricula.subject_id', '=', 'class_schedules.subject_id')
            ->where('class_schedules.section_name', $sectionName)
            ->where('class_schedules.academic_period_id', $periodId)
            ->groupBy('curricula.year_level')
            ->orderByRaw('count(*) desc')
            ->value('curricula.year_level');
    }

    public function studentsList(Request $request)
    {
        $dept = session('user_department');
        $allowedCourses = $this->allowedCoursesForDept();

        $students = DB::table('students')
            ->whereIn('course', $allowedCourses)
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get();

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();

        $sections = collect();

        if ($activePeriod) {
            $blocks = DB::table('class_schedules')
                ->where('academic_period_id', $activePeriod->id)
                ->where(function ($q) use ($allowedCourses) {
                    foreach ($allowedCourses as $course) {
                        $q->orWhere('section_name', 'LIKE', $course . '%');
                    }
                })
                ->select(
                    'section_name',
                    DB::raw('count(distinct subject_id) as total_subjects')
                )
                ->groupBy('section_name')
                ->orderBy('section_name', 'asc')
                ->get();

            $sections = $blocks->map(function ($rec) use ($activePeriod, $allowedCourses) {
                $year = $this->blockYearLevel($rec->section_name, $activePeriod->id);

                $assignedQuery = DB::table('students')
                    ->where('section', $rec->section_name)
                    ->whereIn('course', $allowedCourses);
                if ($year) {
                    $assignedQuery->where('year_level', $year);
                }

                $pendingQuery = (clone $assignedQuery)->whereNotExists(function ($q) use ($rec, $activePeriod) {
                    $q->select(DB::raw(1))
                        ->from('student_subjects as ss')
                        ->join('class_schedules as cs', 'cs.id', '=', 'ss.schedule_id')
                        ->whereColumn('ss.student_number', 'students.student_id')
                        ->where('cs.section_name', $rec->section_name)
                        ->where('cs.academic_period_id', $activePeriod->id)
                        ->where('ss.status', 'Enrolled');
                });

                $enrolled = DB::table('student_subjects as ss')
                    ->join('class_schedules as cs', 'cs.id', '=', 'ss.schedule_id')
                    ->join('students as s', 's.student_id', '=', 'ss.student_number')
                    ->where('cs.section_name', $rec->section_name)
                    ->where('cs.academic_period_id', $activePeriod->id)
                    ->where('ss.status', 'Enrolled')
                    ->whereIn('s.course', $allowedCourses)
                    ->distinct()
                    ->count('ss.student_number');

                $rec->block_year_level = $year;
                $rec->assigned_count   = $assignedQuery->count();
                $rec->pending_count    = $pendingQuery->count();
                $rec->enrolled_count   = $enrolled;

                return $rec;
            });
        }

        return view('department_secretary.masterlist', compact('students', 'sections', 'dept', 'activePeriod'));
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

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();

        $students = DB::table('student_subjects')
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->where('class_schedules.section_name', $section_name)
            ->where('class_schedules.academic_period_id', $activePeriod ? $activePeriod->id : 0)
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

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();
        $activePeriodId = $activePeriod ? $activePeriod->id : 0;

        $classSchedules = DB::table('class_schedules')
            ->where('academic_period_id', $activePeriodId)
            ->get();

        $availableSections = DB::table('class_schedules')
            ->where('academic_period_id', $activePeriodId)
            ->select('section_name')
            ->distinct()
            ->orderBy('section_name', 'asc')
            ->pluck('section_name');

        $enrolledSections = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->where('student_subjects.student_number', $student_id)
            ->where('class_schedules.academic_period_id', $activePeriodId)
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
        ]);

        $sectionName = $request->input('current_section');

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();
        if (!$activePeriod) {
            return redirect()->back()->with('error', 'No active academic period set. Cannot bulk enroll.');
        }

        DB::beginTransaction();
        try {
            $blockYear = $this->yearLevelFromSection($sectionName);

            $studentsQuery = DB::table('students')
                ->where('section', $sectionName)
                ->whereIn('course', $this->allowedCoursesForDept());
            if ($blockYear) {
                $studentsQuery->where('year_level', $blockYear);
            }
            $students = $studentsQuery->pluck('student_id');

            if ($students->isEmpty()) {
                DB::rollBack();
                return redirect()->back()->with('error', 'No students found in section "' . $sectionName . '". Please ensure students are assigned to this block section.');
            }

            $schedulesQuery = DB::table('class_schedules')
                ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                ->where('class_schedules.section_name', $sectionName)
                ->where('class_schedules.academic_period_id', $activePeriod->id);

            $schedules = $schedulesQuery->select('class_schedules.id as schedule_id', 'class_schedules.subject_id')
                ->distinct()
                ->get();

            if ($schedules->isEmpty()) {
                DB::rollBack();
                return redirect()->back()->with('error', 'No class schedules found for section "' . $sectionName . '" in the active academic period.');
            }

            $enrollments = [];
            $now = now();

            $alreadyEnrolled = [];
            DB::table('student_subjects')
                ->whereIn('student_number', $students)
                ->whereIn('schedule_id', $schedules->pluck('schedule_id'))
                ->get(['student_number', 'schedule_id'])
                ->each(function ($row) use (&$alreadyEnrolled) {
                    $alreadyEnrolled[$row->student_number . '|' . $row->schedule_id] = true;
                });

            foreach ($students as $studentNumber) {
                foreach ($schedules as $sched) {
                    $exists = isset($alreadyEnrolled[$studentNumber . '|' . $sched->schedule_id]);

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

            $studentUpdate = ['semester' => $activePeriod->semester, 'updated_at' => $now];
            if ($blockYear) {
                $studentUpdate['year_level'] = $blockYear;
            }
            DB::table('students')->whereIn('student_id', $students)->update($studentUpdate);

            DB::commit();

            $added   = count($enrollments);
            $skipped = ($students->count() * $schedules->count()) - $added;

            return redirect()->back()->with('success', "Bulk enrollment done for section {$sectionName}: {$students->count()} student(s), {$added} new subject enrollment(s) added, {$skipped} already enrolled (skipped).");

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

            $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();
            if (!$activePeriod) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', 'No active academic period set. Cannot enroll subjects.');
            }

            $primarySection = reset($filteredSchedules);
            $primaryYear    = $this->yearLevelFromSection($primarySection);

            $capacityQuery = DB::table('students')
                ->where('section', $primarySection)
                ->where('student_id', '!=', $studentNumber);
            if ($primaryYear) {
                $capacityQuery->where('year_level', $primaryYear);
            }
            $existingCount = $capacityQuery->count();

            if ($existingCount >= 30) {
                return redirect()->back()
                                ->withInput()
                                ->with('error', "Section {$primarySection} has already reached the maximum capacity of 30 students (Current: {$existingCount}). Please choose another block section.");
            }

            $activeScheduleIds = DB::table('class_schedules')
                ->where('academic_period_id', $activePeriod->id)
                ->pluck('id');

            DB::table('student_subjects')
                ->where('student_number', $studentNumber)
                ->whereIn('schedule_id', $activeScheduleIds)
                ->delete();

            foreach ($filteredSchedules as $subjectId => $sectionName) {
                $matchingSchedules = DB::table('class_schedules')
                    ->where('academic_period_id', $activePeriod->id)
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

            $years = [];
            foreach ($filteredSchedules as $sec) {
                $years[] = $this->yearLevelFromSection($sec);
            }
            $years = array_unique($years);

            $studentUpdate = [
                'section'    => $primarySection,
                'semester'   => $activePeriod->semester,
                'updated_at' => now(),
            ];
            if (count($years) === 1 && $years[0]) {
                $studentUpdate['year_level'] = $years[0];
            }

            DB::table('students')
                ->where('student_id', $studentNumber)
                ->update($studentUpdate);

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