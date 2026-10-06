<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistrarController extends Controller
{
    public function dashboard()
    {
        $announcements = DB::table('announcements')
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalStudents = DB::table('students')->count();

        $pendingApprovals = DB::table('document_requests')->where('status', 'Pending')->count();
        $academicRecords = DB::table('student_subjects')->count();

        return view('registrar.dashboard', compact('announcements', 'totalStudents', 'pendingApprovals', 'academicRecords'));
    }

    public function manageEnrollment()
    {
        return view('registrar.manage-enrollment');
    }

    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('banner_image')) {
            $image = $request->file('banner_image');
            $path = $image->store('announcements', 'public');

            DB::table('announcements')->insert([
                'image_path' => $path,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('Success', 'Announcement banner uploaded successfully!');
        }

        return redirect()->back()->with('Error', 'Failed to upload announcement.');
    }

    public function destroyAnnouncement($id)
    {
        $announcement = DB::table('announcements')->where('id', $id)->first();

        if ($announcement) {
            Storage::disk('public')->delete($announcement->image_path);
            DB::table('announcements')->where('id', $id)->delete();

            return redirect()->back()->with('Success', 'Announcement banner removed successfully.');
        }

        return redirect()->back()->with('Error', 'Announcement not found.');
    }

    public function storeEnrollment(Request $request)
    {
        $rules = [
            'registration_date' => 'required|date',
            'student_type' => 'required|string',
            'course' => 'required|string',
            'year_level' => 'required|string',
            'semester' => 'required|string',
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'contact_no' => 'required|string',
            'email' => 'required|email|unique:students,email',
        ];

        $request->validate($rules);

        try {
            $currentYear = date('Y');

            $lastStudent = DB::table('students')
                ->where('student_id', 'LIKE', $currentYear . '-%')
                ->orderBy('student_id', 'desc')
                ->first();

            if ($lastStudent) {
                $lastSequence = intval(explode('-', $lastStudent->student_id)[1]);
                $nextSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $nextSequence = '0001';
            }

            $generatedStudentId = $currentYear . '-' . $nextSequence;

            $student = new Student();
            $student->student_id = $generatedStudentId;
            $student->registration_date = $request->registration_date;
            $student->student_type = $request->student_type;
            $student->course = $request->course;
            $student->year_level = $request->year_level;
            $student->semester = $request->semester;
            $student->last_name = strtoupper($request->last_name);
            $student->first_name = strtoupper($request->first_name);
            $student->middle_name = $request->middle_name ? strtoupper($request->middle_name) : null;

            $student->sex = $request->sex;
            $student->civil_status = $request->civil_status;
            $student->date_of_birth = $request->date_of_birth;
            $student->nationality = strtoupper($request->nationality);
            $student->city_address = $request->city_address;
            $student->zip_code = $request->zip_code;
            $student->place_of_birth = $request->place_of_birth;
            $student->contact_no = $request->contact_no;
            $student->email = $request->email;

            $student->fathers_name = $request->fathers_name;
            $student->fathers_occupation = $request->fathers_occupation;
            $student->mothers_name = $request->mothers_name;
            $student->mothers_occupation = $request->mothers_occupation;

            $student->elementary_school_name    = array_values(array_filter($request->input('elementary_school_name', [])));
            $student->elementary_school_address = array_values(array_filter($request->input('elementary_school_address', [])));
            $student->elementary_school_year    = array_values(array_filter($request->input('elementary_school_year', [])));

            $student->junior_hs_school_name    = array_values(array_filter($request->input('junior_hs_school_name', [])));
            $student->junior_hs_school_address = array_values(array_filter($request->input('junior_hs_school_address', [])));
            $student->junior_hs_school_year    = array_values(array_filter($request->input('junior_hs_school_year', [])));

            $student->senior_hs_school_name    = array_values(array_filter($request->input('senior_hs_school_name', [])));
            $student->senior_hs_school_address = array_values(array_filter($request->input('senior_hs_school_address', [])));
            $student->senior_hs_school_year    = array_values(array_filter($request->input('senior_hs_school_year', [])));

            $student->vocational_school_name    = array_values(array_filter($request->input('vocational_school_name', [])));
            $student->vocational_school_address = array_values(array_filter($request->input('vocational_school_address', [])));
            $student->vocational_school_year    = array_values(array_filter($request->input('vocational_school_year', [])));

            $student->college_school_name    = $request->college_school_name;
            $student->college_school_address = $request->college_school_address;
            $student->college_school_year    = $request->college_school_year;

            $student->credentials = [
                'psa_birth_certificate'      => $request->has('check_psa'),
                'id_picture_2x2'             => $request->has('check_2x2'),
                'form_138'                   => $request->has('check_f138'),
                'good_moral_new'             => $request->has('check_good_moral_new'),
                'diploma'                    => $request->has('check_diploma'),
                'transcript_of_records'      => $request->has('check_tor'),
                'honorable_dismissal'        => $request->has('check_dismissal'),
                'good_moral_transferee'      => $request->has('check_good_moral_trans'),
                'old_curriculum'             => $request->has('check_curriculum'),
                'cross_enroll_permit'        => $request->has('check_permit')
            ];

            $student->save();

            return redirect()->back()->with([
                'success' => 'Student registration successful!',
                'student_id' => $generatedStudentId
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Database Error: ' . $e->getMessage());
        }
    }

    public function manageStudents(Request $request)
    {
        $search = $request->input('search');
        $course = $request->input('course');
        $studentType = $request->input('student_type');

        $students = Student::when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('student_id', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($course, function ($query, $course) {
                return $query->where('course', $course);
            })
            ->when($studentType, function ($query, $studentType) {
                return $query->where('student_type', $studentType);
            })
            ->orderBy('last_name', 'asc')
            ->get();

        return view('registrar.manage-students', compact('students'));
    }

    public function viewStudentProfile($student_id)
    {
        $student = Student::where('student_id', $student_id)->firstOrFail();
        return view('registrar.student_profile', compact('student'));
    }

    public function updateStudentInfo(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'status' => 'required|string|in:Enrolled,On-Hold,Purged,Dropped',
        ]);

        $student->status = $request->status;
        $student->save();

        return redirect()->back()->with('Success', "Student academic status updated successfully for ID: {$student->student_id}");
    }

    public function updateStudentDocuments(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'doc_psa'              => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_2x2'              => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'doc_f138'             => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_new'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_diploma'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_tor'              => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_dismissal'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_trans' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_curriculum'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_permit'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $folderPath = "student_records/{$student->student_id}";
        if (!Storage::disk('local')->exists($folderPath)) {
            Storage::disk('local')->makeDirectory($folderPath);
        }

        $fileFields = [
            'doc_psa'              => ['column' => 'path_psa', 'name' => 'psa_birth_certificate'],
            'doc_2x2'              => ['column' => 'path_2x2', 'name' => '2x2_id_picture'],
            'doc_f138'             => ['column' => 'path_f138', 'name' => 'form_138_report_card'],
            'doc_good_moral_new'   => ['column' => 'path_good_moral_new', 'name' => 'good_moral_freshman'],
            'doc_diploma'          => ['column' => 'path_diploma', 'name' => 'high_school_diploma'],
            'doc_tor'              => ['column' => 'path_tor', 'name' => 'transcript_of_records'],
            'doc_dismissal'        => ['column' => 'path_dismissal', 'name' => 'honorable_dismissal'],
            'doc_good_moral_trans' => ['column' => 'path_good_moral_trans', 'name' => 'good_moral_transferee'],
            'doc_curriculum'       => ['column' => 'path_curriculum', 'name' => 'old_curriculum'],
            'doc_permit'           => ['column' => 'path_permit', 'name' => 'cross_enroll_permit']
        ];

        foreach ($fileFields as $inputName => $info) {
            if ($request->hasFile($inputName)) {
                $file = $request->file($inputName);

                if ($file->isValid()) {
                    $extension = $file->getClientOriginalExtension();
                    $finalName = "{$info['name']}.{$extension}";

                    if ($student->{$info['column']} && Storage::disk('local')->exists("{$folderPath}/{$student->{$info['column']}}")) {
                         Storage::disk('local')->delete("{$folderPath}/{$student->{$info['column']}}");
                    }

                    $file->storeAs($folderPath, $finalName, 'local');

                    $student->{$info['column']} = $finalName;
                }
            }
        }

        $student->save();

        return redirect()->back()->with('Success', "Student documents updated successfully for ID: {$student->student_id}");
    }

    public function destroyStudent($student_id)
    {
        if (session('user_role') !== 'registrar') {
            abort(403, 'Unauthorized Access.');
        }

        $student = Student::where('student_id', $student_id)->firstOrFail();

        $sid = $student->student_id;
        $fullName = strtoupper($student->last_name . ', ' . $student->first_name);

        DB::table('document_requests')->where('student_id', $sid)->delete();
        DB::table('student_subjects')->where('student_number', $sid)->delete();
        DB::table('student_term_grades')->where('student_number', $sid)->delete();

        $student->delete();

        return redirect()->route('registrar.manage-students')
            ->with('success', "Student record {$sid} ({$fullName}) has been permanently removed from the system.");
    }

    public function manageGrades(Request $request)
    {
        $search = $request->input('search');

        $activeAcademicPeriod = DB::table('academic_periods')->where('is_active', 1)->first();
        $activePeriodId = $activeAcademicPeriod ? $activeAcademicPeriod->id : 1;

        $termPeriods = DB::table('term_periods')
            ->where('academic_period_id', $activePeriodId)
            ->orderBy('start_date', 'asc')
            ->get();

        $currentDate = now()->toDateString();
        $defaultTerm = $termPeriods->first()->term ?? 'prelim';

        foreach ($termPeriods as $tp) {
            if ($currentDate >= $tp->start_date && $currentDate <= $tp->end_date) {
                $defaultTerm = $tp->term;
                break;
            }
        }

        $termFilter = strtolower(trim($request->input('term', $defaultTerm)));

        $students = Student::when($search, function ($query, $search) {
                return $query->where('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
            })
            ->orderBy('last_name', 'asc')
            ->paginate(15);

        foreach ($students as $student) {
            $enrolledSchedulesData = DB::table('student_subjects')
                ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
                ->where('student_subjects.student_number', $student->student_id)
                ->where('student_subjects.status', 'Enrolled')
                ->where('class_schedules.academic_period_id', $activePeriodId)
                ->select('class_schedules.id', 'class_schedules.schedule_type', 'class_schedules.subject_id')
                ->get();

            $student->total_enrolled = $enrolledSchedulesData->count();
            $student->total_required = $student->total_enrolled;

            if ($termFilter === 'semestral') {
                $submittedCount = 0;
                $semEquivalents = [];
                
                foreach ($enrolledSchedulesData as $sched) {
                    $completedTermsCount = DB::table('student_term_grades')
                        ->where('schedule_id', $sched->id)
                        ->where('student_number', $student->student_id)
                        ->where('is_submitted', 1)
                        ->whereIn('term', ['prelim', 'midterm', 'semifinal', 'final'])
                        ->whereNotNull('equivalent')
                        ->count();

                    $isSubmitted = ($completedTermsCount === 4);
                    $semGrade = $isSubmitted ? \App\Support\GradeHelper::computeSemestralGrade($sched->id, $student->student_id) : null;
                    
                    if (!$isSubmitted && strtoupper(trim($sched->schedule_type)) === 'LAB') {
                        $lecSched = $enrolledSchedulesData->first(function ($val) use ($sched) {
                            return $val->subject_id == $sched->subject_id && strtoupper(trim($val->schedule_type)) === 'LEC';
                        });
                        if ($lecSched) {
                            $lecCompletedCount = DB::table('student_term_grades')
                                ->where('schedule_id', $lecSched->id)
                                ->where('student_number', $student->student_id)
                                ->where('is_submitted', 1)
                                ->whereIn('term', ['prelim', 'midterm', 'semifinal', 'final'])
                                ->whereNotNull('equivalent')
                                ->count();

                            $isSubmitted = ($lecCompletedCount === 4);
                            $semGrade = $isSubmitted ? \App\Support\GradeHelper::computeSemestralGrade($lecSched->id, $student->student_id) : null;
                        }
                    }

                    if ($isSubmitted) {
                        $submittedCount++;
                        if ($semGrade !== null) {
                            $semEquivalents[] = $semGrade;
                        }
                    }
                }

                // Semestral display: number of terms (out of 4) fully submitted for ALL enrolled subjects
                $semTerms = ['prelim', 'midterm', 'semifinal', 'final'];
                $termGrades = DB::table('student_term_grades')
                    ->where('student_number', $student->student_id)
                    ->whereIn('schedule_id', $enrolledSchedulesData->pluck('id'))
                    ->whereIn(DB::raw('LOWER(term)'), $semTerms)
                    ->where('is_submitted', 1)
                    ->whereNotNull('equivalent')
                    ->get();

                $termsCompleted = 0;
                foreach ($semTerms as $t) {
                    $submittedSchedIds = $termGrades
                        ->filter(function ($g) use ($t) { return strtolower($g->term) === $t; })
                        ->pluck('schedule_id')->all();

                    $allDone = $enrolledSchedulesData->count() > 0;
                    foreach ($enrolledSchedulesData as $sched) {
                        $done = in_array($sched->id, $submittedSchedIds);
                        if (!$done && strtoupper(trim($sched->schedule_type)) === 'LAB') {
                            $lecSched = $enrolledSchedulesData->first(function ($val) use ($sched) {
                                return $val->subject_id == $sched->subject_id && strtoupper(trim($val->schedule_type)) === 'LEC';
                            });
                            $done = $lecSched && in_array($lecSched->id, $submittedSchedIds);
                        }
                        if (!$done) { $allDone = false; break; }
                    }
                    if ($allDone) { $termsCompleted++; }
                }

                $student->total_submitted = $termsCompleted;
                $student->total_required = 4;

                if ($student->total_enrolled > 0 && $submittedCount >= $student->total_enrolled) {
                    $student->grade_status = 'Complete';
                    $rawAvg = count($semEquivalents) > 0 ? array_sum($semEquivalents) / count($semEquivalents) : null;
                    $officialAvg = \App\Support\GradeHelper::snapToOfficialGrade($rawAvg);
                    $student->average_equivalent = $officialAvg !== null ? number_format($officialAvg, 2) : null;
                } elseif ($submittedCount > 0) {
                    $student->grade_status = 'Partial';
                    $student->average_equivalent = null;
                } else {
                    $student->grade_status = 'Pending';
                    $student->average_equivalent = null;
                }

                $semestralSentCount = DB::table('student_term_grades')
                    ->where('student_number', $student->student_id)
                    ->whereIn('schedule_id', $enrolledSchedulesData->pluck('id'))
                    ->where(DB::raw('LOWER(term)'), 'semestral')
                    ->where('is_sent_to_student', 1)
                    ->count();

                $student->is_fully_sent = ($student->total_enrolled > 0 && $semestralSentCount >= $student->total_enrolled);
            } else {
                $grades = DB::table('student_term_grades')
                    ->where('student_number', $student->student_id)
                    ->whereIn('schedule_id', $enrolledSchedulesData->pluck('id'))
                    ->where(DB::raw('LOWER(term)'), $termFilter)
                    ->where('is_submitted', 1)
                    ->get();

                $submittedCount = 0;
                $sentCount = 0;
                $computedAverages = [];

                foreach ($enrolledSchedulesData as $sched) {
                    $isSubmitted = false;
                    $isSent = false;
                    $gradeVal = null;
                    
                    $dbGrade = $grades->firstWhere('schedule_id', $sched->id);
                    
                    if ($dbGrade) {
                        $isSubmitted = true;
                        $isSent = $dbGrade->is_sent_to_student == 1;
                        $gradeVal = $dbGrade->equivalent;
                    } 
                    else if (strtoupper(trim($sched->schedule_type)) === 'LAB') {
                        $lecSched = $enrolledSchedulesData->first(function ($val) use ($sched) {
                            return $val->subject_id == $sched->subject_id && strtoupper(trim($val->schedule_type)) === 'LEC';
                        });
                        
                        if ($lecSched) {
                            $lecGrade = $grades->firstWhere('schedule_id', $lecSched->id);
                            if ($lecGrade) {
                                $isSubmitted = true;
                                $isSent = $lecGrade->is_sent_to_student == 1;
                                $gradeVal = $lecGrade->equivalent;
                            }
                        }
                    }

                    if ($isSubmitted) {
                        $submittedCount++;
                        if ($gradeVal !== null) {
                            $computedAverages[] = $gradeVal;
                        }
                    }
                    if ($isSent) {
                        $sentCount++;
                    }
                }

                $student->total_submitted = $submittedCount;

                if ($student->total_enrolled > 0 && $submittedCount >= $student->total_enrolled) {
                    $student->grade_status = 'Complete';
                    $rawAvg = count($computedAverages) > 0 ? array_sum($computedAverages) / count($computedAverages) : null;
                    $officialAvg = \App\Support\GradeHelper::snapToOfficialGrade($rawAvg);
                    $student->average_equivalent = $officialAvg !== null ? number_format($officialAvg, 2) : null;
                } elseif ($submittedCount > 0) {
                    $student->grade_status = 'Partial';
                    $student->average_equivalent = null;
                } else {
                    $student->grade_status = 'Pending';
                    $student->average_equivalent = null;
                }

                $student->is_fully_sent = ($student->total_enrolled > 0 && $sentCount >= $student->total_enrolled);
            }
        }

        return view('registrar.manage-grades', compact('students', 'termPeriods', 'termFilter', 'search', 'activeAcademicPeriod'));
    }

    public function getStudentTermGradesJson(Request $request, $student_id)
    {
        $term = strtolower(trim($request->input('term', 'prelim')));
        $activeAcademicPeriod = DB::table('academic_periods')->where('is_active', 1)->first();
        $activePeriodId = $activeAcademicPeriod ? $activeAcademicPeriod->id : null;

        $query = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('student_term_grades', function($join) use ($student_id, $term) {
                $join->on('student_term_grades.schedule_id', '=', 'class_schedules.id')
                    ->where('student_term_grades.student_number', '=', $student_id)
                    ->where(DB::raw('LOWER(student_term_grades.term)'), '=', $term);
            })
            ->where('student_subjects.student_number', $student_id)
            ->where('student_subjects.status', 'Enrolled');

        if ($activePeriodId) {
            $query->where('class_schedules.academic_period_id', $activePeriodId);
        }

        $subjects = $query->select(
                'subjects.id as subject_id',
                'subjects.subject_code',
                'subjects.subject_title',
                'class_schedules.section_name',
                'class_schedules.schedule_type',
                'class_schedules.id as schedule_id',
                'student_term_grades.class_standing',
                'student_term_grades.major_exam',
                'student_term_grades.equivalent',
                'student_term_grades.is_submitted'
            )
            ->orderBy('subjects.subject_code', 'asc')
            ->orderBy('class_schedules.schedule_type', 'desc') // Uunahin ang LEC kaysa LAB
            ->get();

        $lecGrades = [];
        foreach ($subjects as $item) {
            $type = strtoupper(trim($item->schedule_type ?? 'LEC'));
            
            if ($type === 'LEC' && $item->is_submitted) {
                $lecGrades[$item->subject_id] = $item;
            }
        }

        $mappedSubjects = $subjects->map(function($item) use ($lecGrades, $student_id, $term) {
            $type = strtoupper(trim($item->schedule_type ?? 'LEC'));
            $item->subject_code_display = $item->subject_code . ' ' . $type;
            $item->subject_title_display = $item->subject_title . ' (' . $type . ')';

            if ($term === 'semestral') {
                $semGrade = \App\Support\GradeHelper::computeSemestralGrade($item->schedule_id, $student_id);
                
                if ($semGrade === null && $type === 'LAB' && isset($lecGrades[$item->subject_id])) {
                    $semGrade = \App\Support\GradeHelper::computeSemestralGrade($lecGrades[$item->subject_id]->schedule_id, $student_id);
                }
                
                $item->equivalent = $semGrade;
                $item->is_submitted = $semGrade !== null ? 1 : 0;

                $termGrades = DB::table('student_term_grades')
                    ->where('schedule_id', $item->schedule_id)
                    ->where('student_number', $student_id)
                    ->get()
                    ->keyBy(function ($r) { return strtolower(trim($r->term)); });

                $lecTermGrades = null;
                if ($termGrades->isEmpty() && $type === 'LAB' && isset($lecGrades[$item->subject_id])) {
                     $lecTermGrades = DB::table('student_term_grades')
                        ->where('schedule_id', $lecGrades[$item->subject_id]->schedule_id)
                        ->where('student_number', $student_id)
                        ->get()
                        ->keyBy(function ($r) { return strtolower(trim($r->term)); });
                }

                $sourceGrades = $termGrades->isNotEmpty() ? $termGrades : ($lecTermGrades ?? collect());

                $item->prelim    = optional($sourceGrades->get('prelim'))->equivalent;
                $item->midterm   = optional($sourceGrades->get('midterm'))->equivalent;
                $item->semifinal = optional($sourceGrades->get('semifinal'))->equivalent;
                $item->final     = optional($sourceGrades->get('final'))->equivalent;

            } else {
                if (!$item->is_submitted && $type === 'LAB' && isset($lecGrades[$item->subject_id])) {
                    $lecGrade = $lecGrades[$item->subject_id];
                    $item->class_standing = $lecGrade->class_standing;
                    $item->major_exam     = $lecGrade->major_exam;
                    $item->equivalent     = $lecGrade->equivalent;
                    $item->is_submitted   = 1;
                }
            }

            return $item;
        });

        return response()->json($mappedSubjects);
    }

    private function releaseSemestralGrade($student_id)
    {
        $activePeriod = DB::table('academic_periods')->where('is_active', 1)->first();

        $query = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->where('student_subjects.student_number', $student_id)
            ->where('student_subjects.status', 'Enrolled');

        if ($activePeriod) {
            $query->where('class_schedules.academic_period_id', $activePeriod->id);
        }

        $schedules = $query
            ->select('class_schedules.id', 'class_schedules.schedule_type', 'class_schedules.subject_id')
            ->get();

        if ($schedules->isEmpty()) {
            return redirect()->back()->with('Error', 'No enrolled subjects found for this student.');
        }

        $terms = ['prelim', 'midterm', 'semifinal', 'final'];

        $countCompleted = function ($scheduleId) use ($student_id, $terms) {
            return DB::table('student_term_grades')
                ->where('schedule_id', $scheduleId)
                ->where('student_number', $student_id)
                ->where('is_submitted', 1)
                ->whereIn(DB::raw('LOWER(term)'), $terms)
                ->whereNotNull('equivalent')
                ->count();
        };

        $semestralGrades = [];

        foreach ($schedules as $sched) {
            $sourceId = $sched->id;

            if ($countCompleted($sourceId) !== 4 && strtoupper(trim($sched->schedule_type)) === 'LAB') {
                $lecSched = $schedules->first(function ($val) use ($sched) {
                    return $val->subject_id == $sched->subject_id && strtoupper(trim($val->schedule_type)) === 'LEC';
                });
                if ($lecSched) {
                    $sourceId = $lecSched->id;
                }
            }

            if ($countCompleted($sourceId) !== 4) {
                return redirect()->back()->with('Error', 'Cannot release semestral grade: not all subjects have complete Prelim to Finals grades yet.');
            }

            $semGrade = \App\Support\GradeHelper::computeSemestralGrade($sourceId, $student_id);
            if ($semGrade === null) {
                return redirect()->back()->with('Error', 'Cannot release semestral grade: unable to compute the semestral grade for one or more subjects.');
            }

            $semestralGrades[$sched->id] = $semGrade;
        }

        $now = now();
        foreach ($semestralGrades as $scheduleId => $semGrade) {
            DB::table('student_term_grades')->updateOrInsert(
                [
                    'schedule_id'    => $scheduleId,
                    'student_number' => $student_id,
                    'term'           => 'semestral',
                ],
                [
                    'equivalent'         => $semGrade,
                    'is_manual'          => 0,
                    'is_submitted'       => 1,
                    'is_sent_to_student' => 1,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]
            );
        }

        return redirect()->back()->with('Success', "Semestral grades successfully released to student portal for ID: {$student_id}!");
    }

    public function releaseGradesToStudent(Request $request, $student_id)
    {
        $term = strtolower(trim($request->input('term', 'prelim')));

        if ($term === 'semestral') {
            return $this->releaseSemestralGrade($student_id);
        }

        $enrolledSchedules = DB::table('student_subjects')
            ->where('student_number', $student_id)
            ->where('status', 'Enrolled')
            ->pluck('schedule_id');

        $updated = DB::table('student_term_grades')
            ->where('student_number', $student_id)
            ->whereIn('schedule_id', $enrolledSchedules)
            ->where(DB::raw('LOWER(term)'), $term)
            ->where('is_submitted', 1)
            ->update(['is_sent_to_student' => 1]);

        if ($updated) {
            return redirect()->back()->with('Success', ucfirst($term) . " term grades successfully released to student portal for ID: {$student_id}!");
        }

        return redirect()->back()->with('Error', 'No submitted grades found for this term to release.');
    }

    public function profileView()
    {
        $userId = session('user_id');
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        $isLocked = !Str::startsWith($user->password, '$2y$');

        return view('registrar.user_details', compact('user', 'isLocked'));
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        $user = \App\Models\User::find(session('user_id'));
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