<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrarController extends Controller
{
    public function dashboard()
    {
        $announcements = DB::table('announcements')->orderBy('created_at', 'desc')->get();
        $totalStudents = DB::table('students')->count();
        
        return view('registrar.dashboard', compact('announcements', 'totalStudents'));
    }

    public function storeAnnouncement(Request $request)
    {
        if (session('user_role') !== 'registrar') {
            abort(403, 'Unauthorized Access.');
        }

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
        if (session('user_role') !== 'registrar') {
            abort(403, 'Unauthorized Access.');
        }

        $announcement = DB::table('announcements')->where('id', $id)->first();

        if ($announcement) {
            Storage::disk('public')->delete($announcement->image_path);
            DB::table('announcements')->where('id', $id)->delete();

            return redirect()->back()->with('Success', 'Announcement banner removed successfully.');
        }

        return redirect()->back()->with('Error', 'Announcement not found.');
    }

    public function manageEnrollment()
    {
        if (session('user_role') !== 'registrar') {
            return redirect()->route('login')->with('Error', 'Unauthorized access. Please login as Registrar.');
        }

        return view('registrar.manage-enrollment');
    }

    public function storeEnrollment(Request $request)
    {
        $request->validate([
            'registration_date' => 'required|date',
            'student_type' => 'required|string',
            'course' => 'required|string',
            'year_level' => 'required|string',
            'semester' => 'required|string',
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'contact_no' => 'required|string',

            'doc_psa' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_2x2' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'doc_f138' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_new' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_diploma' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_tor' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_dismissal' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_trans' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_permit' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

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

        $filePaths = [];
        
        $fileFields = [
            'doc_psa'               => 'psa_birth_certificate',
            'doc_2x2'               => '2x2_id_picture',
            'doc_f138'              => 'form_138_report_card',
            'doc_good_moral_new'    => 'good_moral_freshman',
            'doc_diploma'           => 'high_school_diploma',
            'doc_tor'               => 'transcript_of_records',
            'doc_dismissal'         => 'honorable_dismissal',
            'doc_good_moral_trans'  => 'good_moral_transferee',
            'doc_permit'            => 'cross_enroll_permit'
        ];

        $folderPath = "student_records/{$generatedStudentId}";
        if (!Storage::disk('local')->exists($folderPath)) {
            Storage::disk('local')->makeDirectory($folderPath);
        }

        foreach ($fileFields as $inputName => $cleanFileName) {
            if ($request->hasFile($inputName)) {
                $file = $request->file($inputName);
                
                if ($file->isValid()) {
                    $extension = $file->getClientOriginalExtension();
                    $finalName = "{$cleanFileName}.{$extension}";
                    
                    $file->storeAs($folderPath, $finalName, 'local');
                    
                    $filePaths[$inputName] = $finalName;
                }
            }
        }

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
        
        $student->fathers_name = $request->fathers_name;
        $student->fathers_occupation = $request->fathers_occupation;
        $student->mothers_name = $request->mothers_name;
        $student->mothers_occupation = $request->mothers_occupation;
        
        $student->primary_school_name = $request->primary_school_name;
        $student->primary_school_address = $request->primary_school_address;
        $student->primary_school_year = $request->primary_school_year;
        $student->secondary_school_name = $request->secondary_school_name;
        $student->secondary_school_address = $request->secondary_school_address;
        $student->secondary_school_year = $request->secondary_school_year;
        $student->college_school_name = $request->college_school_name;
        $student->college_school_address = $request->college_school_address;
        $student->college_school_year = $request->college_school_year;

        $student->path_psa = $filePaths['doc_psa'] ?? null;
        $student->path_2x2 = $filePaths['doc_2x2'] ?? null;
        $student->path_f138 = $filePaths['doc_f138'] ?? null;
        $student->path_good_moral_new = $filePaths['doc_good_moral_new'] ?? null;
        $student->path_diploma = $filePaths['doc_diploma'] ?? null;
        $student->path_tor = $filePaths['doc_tor'] ?? null;
        $student->path_dismissal = $filePaths['doc_dismissal'] ?? null;
        $student->path_good_moral_trans = $filePaths['doc_good_moral_trans'] ?? null;
        $student->path_permit = $filePaths['doc_permit'] ?? null;
        
        $student->save();

        return redirect()->back()->with('Success', "Student credentials compiled successfully! Allocated Student ID: {$generatedStudentId}");
    }

    public function secureViewFile($student_id, $filename)
    {
        if (session('user_role') !== 'registrar') {
            abort(403, 'Unauthorized Access.');
        }

        $fullStoragePath = "student_records/{$student_id}/{$filename}";

        if (!Storage::disk('local')->exists($fullStoragePath)) {
            abort(404, 'The requested credential file was not found.');
        }

        return Storage::disk('local')->response($fullStoragePath);
    }

    public function viewStudentProfile($student_id)
    {
        if (session('user_role') !== 'registrar') {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        $student = Student::where('student_id', $student_id)->firstOrFail();

        return view('registrar.student_profile', compact('student'));
    }

    public function facultyGradeStatus(Request $request)
    {
        if (session('user_role') !== 'registrar') {
            return redirect()->route('login')->with('Error', 'Unauthorized access. Please login as Registrar.');
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
                'employees.last_name as prof_last_name'
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
            $enrolledCount = DB::table('student_subjects')
                ->where('schedule_id', $class->schedule_id)
                ->where('status', 'Enrolled')
                ->count();

            $submittedCount = DB::table('student_term_grades')
                ->where('schedule_id', $class->schedule_id)
                ->where('term', $termFilter)
                ->where('is_submitted', 1)
                ->count();

            if ($enrolledCount === 0) {
                $status = 'empty';
            } elseif ($submittedCount === 0) {
                $status = 'not_started';
            } elseif ($submittedCount < $enrolledCount) {
                $status = 'partial';
            } else {
                $status = 'complete';
            }

            $class->enrolled_count = $enrolledCount;
            $class->submitted_count = $submittedCount;
            $class->status = $status;

            return $class;
        });

        return view('registrar.faculty-grade-status', compact('report', 'termPeriods', 'termFilter', 'activeAcademicPeriod', 'search'));
    }

    public function manageGrades(Request $request)
    {
        if (session('user_role') !== 'registrar') {
            return redirect()->route('login')->with('Error', 'Unauthorized access. Please login as Registrar.');
        }

        $search = $request->input('search');

        $activeAcademicPeriod = DB::table('academic_periods')
            ->where('is_active', 1)
            ->first();

        $academicPeriodId = $activeAcademicPeriod ? $activeAcademicPeriod->id : 1;

        $termPeriods = DB::table('term_periods')
            ->where('academic_period_id', $activeAcademicPeriod->id)
            ->orderBy('start_date', 'asc')
            ->get();

        $currentDate = now()->toDateString(); 
        $defaultTerm = 'prelim';

        foreach ($termPeriods as $tp) {
            if ($currentDate >= $tp->start_date && $currentDate <= $tp->end_date) {
                $defaultTerm = $tp->term;
                break;
            }
        }

        $termFilter = $request->input('term', $defaultTerm);

        $students = Student::when($search, function ($query, $search) {
                return $query->where('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
            })
            ->orderBy('last_name', 'asc')
            ->paginate(15);

        foreach ($students as $student) {
            $student->term_grade = DB::table('student_term_grades')
                ->where('student_number', $student->student_id)
                ->where('term', $termFilter)
                ->where('is_submitted', 1)
                ->first();
        }

        return view('registrar.manage-grades', compact('students', 'termPeriods', 'termFilter', 'search', 'activeAcademicPeriod'));
    }

    public function profileView()
    {
        if (session('user_role') !== 'registrar') {
            return redirect()->route('login')->with('Error', 'Unauthorized access. Please login as Registrar.');
        }

        $userId = session('user_id');
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        $isLocked = !Str::startsWith($user->password, '$2y$');

        return view('registrar.user_details', compact('user', 'isLocked'));
    }
}