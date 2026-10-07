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
                
                $studentIdsInBlock = (clone $assignedQuery)->pluck('student_id');

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

                $hasPendingGrades = false;
                if ($studentIdsInBlock->isNotEmpty()) {
                    $unreleasedCount = DB::table('student_subjects as ss')
                        ->join('class_schedules as cs', 'cs.id', '=', 'ss.schedule_id')
                        ->whereIn('ss.student_number', $studentIdsInBlock)
                        ->where('cs.academic_period_id', '!=', $activePeriod->id)
                        ->where('ss.status', 'Enrolled')
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                  ->from('student_term_grades as stg')
                                  ->whereColumn('stg.schedule_id', 'ss.schedule_id')
                                  ->whereColumn('stg.student_number', 'ss.student_number')
                                  ->where('stg.term', 'semestral')
                                  ->where('stg.is_submitted', 1);
                        })->count();
                    $hasPendingGrades = $unreleasedCount > 0;
                }

                $rec->block_year_level   = $year;
                $rec->assigned_count     = $assignedQuery->count();
                $rec->pending_count      = $pendingQuery->count();
                $rec->enrolled_count     = $enrolled;
                $rec->has_pending_grades = $hasPendingGrades;

                return $rec;
            });
        }

        return view('department_secretary.masterlist', compact('students', 'sections', 'dept', 'activePeriod'));
    }

    private function studentPassedLatestSemester($studentNumber)
    {
        $latestPeriodId = DB::table('student_subjects as ss')
            ->join('class_schedules as cs', 'cs.id', '=', 'ss.schedule_id')
            ->where('ss.student_number', $studentNumber)
            ->where('ss.status', 'Enrolled')
            ->max('cs.academic_period_id');

        if (!$latestPeriodId) {
            return false;
        }

        $scheduleIds = DB::table('student_subjects as ss')
            ->join('class_schedules as cs', 'cs.id', '=', 'ss.schedule_id')
            ->where('ss.student_number', $studentNumber)
            ->where('ss.status', 'Enrolled')
            ->where('cs.academic_period_id', $latestPeriodId)
            ->distinct()
            ->pluck('cs.id');

        if ($scheduleIds->isEmpty()) {
            return false;
        }

        $passed = DB::table('student_term_grades')
            ->where('student_number', $studentNumber)
            ->whereIn('schedule_id', $scheduleIds)
            ->where('term', 'semestral')
            ->where('is_submitted', 1)
            ->whereNotNull('equivalent')
            ->whereRaw("equivalent REGEXP '^[0-9]+(\\\\.[0-9]+)?$'")
            ->whereRaw('CAST(equivalent AS DECIMAL(4,2)) <= 3.00')
            ->distinct()
            ->count('schedule_id');

        return $passed >= $scheduleIds->count();
    }

    public function promoteBlocks(Request $request)
    {
        $yearLabels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        $allowedCourses = $this->allowedCoursesForDept();

        $promoted = 0;
        $graduated = 0;
        $heldBack = [];
        $skipped = 0;
        $now = now();

        DB::beginTransaction();
        try {
            for ($i = count($yearLabels) - 1; $i >= 0; $i--) {
                $currentLabel = $yearLabels[$i];
                $currentDigit = $i + 1;
                $isLastYear   = ($i === count($yearLabels) - 1);

                $students = DB::table('students')
                    ->whereIn('course', $allowedCourses)
                    ->where('year_level', $currentLabel)
                    ->whereNotNull('section')
                    ->where('section', '!=', '')
                    ->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', '!=', 'Graduated');
                    })
                    ->get(['student_id', 'section', 'last_name', 'first_name']);

                foreach ($students as $stu) {
                    if ($this->yearLevelFromSection($stu->section) !== $currentLabel) {
                        $skipped++;
                        continue;
                    }

                    if (!$this->studentPassedLatestSemester($stu->student_id)) {
                        $heldBack[] = strtoupper($stu->last_name) . ', ' . $stu->first_name;
                        continue;
                    }

                    if ($isLastYear) {
                        DB::table('students')->where('student_id', $stu->student_id)->update([
                            'status'     => 'Graduated',
                            'section'    => null,
                            'updated_at' => $now,
                        ]);
                        $graduated++;
                        continue;
                    }

                    $newSection = preg_replace('/' . $currentDigit . '/', (string)($currentDigit + 1), $stu->section, 1);

                    DB::table('students')->where('student_id', $stu->student_id)->update([
                        'section'    => $newSection,
                        'year_level' => $yearLabels[$i + 1],
                        'updated_at' => $now,
                    ]);
                    $promoted++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Promotion failed, nothing was changed: ' . $e->getMessage());
        }

        $msg = "Promotion done: {$promoted} student(s) moved up, {$graduated} graduated, " . count($heldBack) . ' held back (failed or no final grade yet).';
        if ($skipped > 0) {
            $msg .= " {$skipped} skipped (section name does not match year level).";
        }
        if (!empty($heldBack)) {
            $msg .= ' Held back: ' . implode('; ', array_slice($heldBack, 0, 5)) . (count($heldBack) > 5 ? '; ...' : '');
        }

        return redirect()->back()->with('success', $msg);
    }

    public function viewStudentProfile($student_id)
    {
        $dept = session('user_department');
        $student = Student::where('student_id', $student_id)->firstOrFail();
        return view('department_secretary.student_profile', compact('student', 'dept'));
    }

    public function deleteStudent($student_id)
    {
        $__del = DB::table('students')->where('student_id', $student_id)->first();

        try {
            DB::beginTransaction();

            DB::table('student_subjects')->where('student_number', $student_id)->delete();

            $deleted = DB::table('students')->where('student_id', $student_id)->delete();

            if (!$deleted) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Student record not found or already deleted.');
            }

            DB::commit();

            // NOTIFICATION: registrar is told a student record was deleted
            \App\Support\Notifier::toRole('registrar', 'student_deleted', 'Student record deleted',
                "Student {$student_id}" . ($__del ? " ({$__del->last_name}, {$__del->first_name})" : '') . ' was deleted by ' . (session('user_name') ?? 'the Department Secretary') . '.',
                \App\Support\Notifier::url('registrar.manage-students'));

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
        $periodId = $activePeriod ? $activePeriod->id : 0;

        $students = DB::table('student_subjects')
            ->join('students', 'student_subjects.student_number', '=', 'students.student_id')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->where('class_schedules.section_name', $section_name)
            ->where('class_schedules.academic_period_id', $periodId)
            ->where('student_subjects.status', 'Enrolled')
            ->groupBy(
                'students.student_id',
                'students.first_name',
                'students.last_name',
                'students.course',
                'students.year_level',
                'students.section'
            )
            ->select(
                'students.student_id',
                'students.first_name',
                'students.last_name',
                'students.course',
                'students.year_level',
                'students.section',
                DB::raw('COUNT(DISTINCT class_schedules.subject_id) as block_subjects')
            )
            ->orderBy('students.last_name', 'asc')
            ->orderBy('students.first_name', 'asc')
            ->get();

        $totalBlockSubjects = DB::table('class_schedules')
            ->where('section_name', $section_name)
            ->where('academic_period_id', $periodId)
            ->distinct()
            ->count('subject_id');

        return view('department_secretary.view_section', compact(
            'students', 'section_name', 'dept', 'activePeriod', 'totalBlockSubjects'
        ));
    }

    public function plotSubjects($student_id)
    {
        $dept = session('user_department');

        $student = DB::table('students')->where('student_id', $student_id)->first();

        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();
        if (!$activePeriod) {
            return redirect()->back()->with('error', 'No active academic period set. Set one before plotting subjects.');
        }
        $activePeriodId = $activePeriod->id;

        $classSchedules = DB::table('class_schedules')
            ->where('academic_period_id', $activePeriodId)
            ->get();

        $enrolledSections = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->where('student_subjects.student_number', $student_id)
            ->where('class_schedules.academic_period_id', $activePeriodId)
            ->pluck('class_schedules.section_name', 'student_subjects.subject_id')
            ->toArray();

        $scheduledSubjectIds = $classSchedules->pluck('subject_id')->unique()->all();
        $enrolledSubjectIds  = array_keys($enrolledSections);

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
            ->orderBy('subjects.subject_code')
            ->get()
            ->filter(function ($row) use ($activePeriod, $scheduledSubjectIds, $enrolledSubjectIds) {
                return $row->semester === $activePeriod->semester
                    || in_array($row->subject_id, $scheduledSubjectIds)
                    || in_array($row->subject_id, $enrolledSubjectIds);
            })
            ->values();

        $availableSections = DB::table('class_schedules')
            ->where('academic_period_id', $activePeriodId)
            ->where('section_name', 'LIKE', $student->course . '%')
            ->select('section_name')
            ->distinct()
            ->orderBy('section_name', 'asc')
            ->pluck('section_name');

        return view('department_secretary.plot_subjects', compact(
            'student',
            'curriculumSubjects',
            'dept',
            'activePeriod',
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

            // NOTIFICATION: only students who were newly enrolled
            \App\Support\Notifier::toStudents(collect($enrollments)->pluck('student_number'), 'subjects_plotted', 'You have been enrolled',
                "You were enrolled in all subjects of section {$sectionName}.",
                \App\Support\Notifier::url('student.my_classes'));

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

        $studentNumber     = $request->input('student_id');
        $selectedSchedules = $request->input('schedules', []);

        $filteredSchedules = array_filter($selectedSchedules, function ($value) {
            return !empty($value);
        });

        if (empty($filteredSchedules)) {
            return redirect()->back()->withInput()->with('error', 'Please select a schedule/section for at least one subject before saving.');
        }

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();
        if (!$activePeriod) {
            return redirect()->back()->withInput()->with('error', 'No active academic period set. Cannot enroll subjects.');
        }

        $student = DB::table('students')->where('student_id', $studentNumber)->first();
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        $sectionTally = array_count_values(array_values($filteredSchedules));
        arsort($sectionTally);
        $topCount = reset($sectionTally);
        $topSections = array_keys(array_filter($sectionTally, function ($c) use ($topCount) {
            return $c === $topCount;
        }));
        $primarySection = in_array($student->section, $topSections, true)
            ? $student->section
            : $topSections[0];
        $primaryYear = $this->yearLevelFromSection($primarySection);

        foreach (array_keys($sectionTally) as $sectionName) {
            $sectionYear = $this->yearLevelFromSection($sectionName);

            $assignedQuery = DB::table('students')
                ->where('section', $sectionName)
                ->where('student_id', '!=', $studentNumber);
            if ($sectionYear) {
                $assignedQuery->where('year_level', $sectionYear);
            }
            $assigned = $assignedQuery->count();

            $enrolled = DB::table('student_subjects as ss')
                ->join('class_schedules as cs', 'cs.id', '=', 'ss.schedule_id')
                ->join('students as st', 'st.student_id', '=', 'ss.student_number')
                ->where('cs.section_name', $sectionName)
                ->where('cs.academic_period_id', $activePeriod->id)
                ->where('ss.status', 'Enrolled')
                ->where('ss.student_number', '!=', $studentNumber)
                ->distinct()
                ->count('ss.student_number');

            $existingCount = max($assigned, $enrolled);

            if ($existingCount >= 30) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Section {$sectionName} has already reached the maximum capacity of 30 students (Current: {$existingCount}). Please choose another block section.");
            }
        }

        try {
            DB::beginTransaction();

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
                        'updated_at'     => now(),
                    ]);
                }
            }

            $studentUpdate = [
                'section'    => $primarySection,
                'semester'   => $activePeriod->semester,
                'updated_at' => now(),
            ];
            if ($primaryYear) {
                $studentUpdate['year_level'] = $primaryYear;
            }

            DB::table('students')
                ->where('student_id', $studentNumber)
                ->update($studentUpdate);

            DB::commit();

            \App\Support\Notifier::toStudent($studentNumber, 'subjects_plotted', 'You have been enrolled',
                "Your subjects were plotted. You are now enrolled in section {$primarySection}.",
                \App\Support\Notifier::url('student.my_classes'));

            return redirect()->route('department_secretary.students')
                ->with('success', "Subjects successfully plotted! Home block is {$primarySection}{$mixed}.");

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
