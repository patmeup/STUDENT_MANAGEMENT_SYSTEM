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
        $announcements = DB::table('announcements')->orderBy('created_at', 'desc')->get();
        $totalStudents = DB::table('students')->count();

        $pendingApprovals = DB::table('document_requests')->where('status', 'Pending')->count();
        $academicRecords = DB::table('student_subjects')->count();
        
        return view('registrar.dashboard', compact('announcements', 'totalStudents', 'pendingApprovals', 'academicRecords'));
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
            'email' => 'required|email', 

            'doc_psa' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_2x2' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'doc_f138' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_new' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_diploma' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_tor' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_dismissal' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_trans' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_permit' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        $studentType = $request->input('student_type');

        if ($studentType === 'New') {
            $rules['doc_psa'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['doc_2x2'] = 'required|file|mimes:jpg,jpeg,png|max:5120';
            $rules['doc_f138'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['doc_good_moral_new'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['doc_diploma'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
        } elseif ($studentType === 'Transferee') {
            $rules['doc_psa'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['doc_2x2'] = 'required|file|mimes:jpg,jpeg,png|max:5120';
            $rules['doc_tor'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['doc_dismissal'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['doc_good_moral_trans'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
        } elseif ($studentType === 'Cross-Enrollee') {
            $rules['doc_psa'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['doc_2x2'] = 'required|file|mimes:jpg,jpeg,png|max:5120';
            $rules['doc_permit'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }

        $request->validate($rules);

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
            'doc_psa'              => 'psa_birth_certificate',
            'doc_2x2'              => '2x2_id_picture',
            'doc_f138'             => 'form_138_report_card',
            'doc_good_moral_new'   => 'good_moral_freshman',
            'doc_diploma'          => 'high_school_diploma',
            'doc_tor'              => 'transcript_of_records',
            'doc_dismissal'        => 'honorable_dismissal',
            'doc_good_moral_trans' => 'good_moral_transferee',
            'doc_permit'           => 'cross_enroll_permit'
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
        $student->email = $request->email;
        
        $student->fathers_name = $request->fathers_name;
        $student->fathers_occupation = $request->fathers_occupation;
        $student->mothers_name = $request->mothers_name;
        $student->mothers_occupation = $request->mothers_occupation;
        
        $student->primary_school_name    = array_values(array_filter($request->input('primary_school_name', [])));
        $student->primary_school_address = array_values(array_filter($request->input('primary_school_address', [])));
        $student->primary_school_year    = array_values(array_filter($request->input('primary_school_year', [])));

        $student->secondary_school_name    = array_values(array_filter($request->input('secondary_school_name', [])));
        $student->secondary_school_address = array_values(array_filter($request->input('secondary_school_address', [])));
        $student->secondary_school_year    = array_values(array_filter($request->input('secondary_school_year', [])));

        $student->college_school_name    = $request->college_school_name;
        $student->college_school_address = $request->college_school_address;
        $student->college_school_year    = $request->college_school_year;

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

        return redirect()->back()->with([
            'success' => 'Student registration successful!',
            'student_id' => $student->student_id
        ]);
    }

    public function secureViewFile($student_id, $filename)
    {
        $fullStoragePath = "student_records/{$student_id}/{$filename}";

        if (!Storage::disk('local')->exists($fullStoragePath)) {
            abort(404, 'The requested credential file was not found.');
        }

        return Storage::disk('local')->response($fullStoragePath);
    }

    public function viewStudentProfile($student_id)
    {
        $student = Student::where('student_id', $student_id)->firstOrFail();

        return view('registrar.student_profile', compact('student'));
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

        $folderPath = "student_records/{$sid}";
        if (Storage::disk('local')->exists($folderPath)) {
            Storage::disk('local')->deleteDirectory($folderPath);
        }

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

        $activeAcademicPeriod = DB::table('academic_periods')
            ->where('is_active', 1)
            ->first();

        $termPeriods = DB::table('term_periods')
            ->where('academic_period_id', $activeAcademicPeriod->id ?? 1)
            ->where('term', 'not like', '%semi%')
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
            $enrolledSchedules = DB::table('student_subjects')
                ->where('student_number', $student->student_id)
                ->where('status', 'Enrolled')
                ->pluck('schedule_id');

            $totalEnrolledSubjects = $enrolledSchedules->count();

            $grades = DB::table('student_term_grades')
                ->where('student_number', $student->student_id)
                ->whereIn('schedule_id', $enrolledSchedules)
                ->where(DB::raw('LOWER(term)'), $termFilter)
                ->where('is_submitted', 1)
                ->get();

            $submittedCount = $grades->count();
            
            $student->total_enrolled = $totalEnrolledSubjects;
            $student->total_submitted = $submittedCount;

            if ($totalEnrolledSubjects > 0 && $submittedCount >= $totalEnrolledSubjects) {
                $student->grade_status = 'Complete';
                $student->average_equivalent = $grades->avg('equivalent');
            } elseif ($submittedCount > 0) {
                $student->grade_status = 'Partial';
                $student->average_equivalent = null; 
            } else {
                $student->grade_status = 'Pending';
                $student->average_equivalent = null;
            }

            $sentCount = $grades->where('is_sent_to_student', 1)->count();
            $student->is_fully_sent = ($totalEnrolledSubjects > 0 && $sentCount >= $totalEnrolledSubjects);
        }

        return view('registrar.manage-grades', compact('students', 'termPeriods', 'termFilter', 'search', 'activeAcademicPeriod'));
    }

    public function getStudentTermGradesJson(Request $request, $student_id)
    {
        $term = strtolower(trim($request->input('term', 'prelim')));

        $subjects = DB::table('student_subjects')
            ->join('class_schedules', 'student_subjects.schedule_id', '=', 'class_schedules.id')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('student_term_grades', function($join) use ($student_id, $term) {
                $join->on('student_term_grades.schedule_id', '=', 'class_schedules.id')
                    ->where('student_term_grades.student_number', '=', $student_id)
                    ->where(DB::raw('LOWER(student_term_grades.term)'), '=', $term);
            })
            ->where('student_subjects.student_number', $student_id)
            ->where('student_subjects.status', 'Enrolled')
            ->select(
                'subjects.subject_code',
                'subjects.subject_title',
                'class_schedules.section_name',
                'student_term_grades.class_standing',
                'student_term_grades.major_exam',
                'student_term_grades.equivalent',
                'student_term_grades.is_submitted'
            )
            ->get();

        return response()->json($subjects);
    }

    public function releaseGradesToStudent(Request $request, $student_id)
    {
        $term = strtolower(trim($request->input('term', 'prelim')));

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

    public function manageDocumentRequests()
    {
        $requests = DB::table('document_requests')
            ->join('students', 'document_requests.student_id', '=', 'students.student_id')
            ->select(
                'document_requests.id as request_id',
                'document_requests.document_name',
                'document_requests.purpose',
                'document_requests.status',
                'document_requests.created_at',
                'students.id as student_pk',
                'students.student_id',
                'students.first_name',
                'students.last_name',
                'students.email',
                'students.path_psa',
                'students.path_2x2',
                'students.path_f138',
                'students.path_good_moral_new',
                'students.path_diploma',
                'students.path_tor',
                'students.path_dismissal',
                'students.path_good_moral_trans',
                'students.path_permit'
            )
            ->orderBy('document_requests.created_at', 'desc')
            ->get();

        $docMapping = [
            'PSA Birth Certificate'    => ['column' => 'path_psa',              'input' => 'doc_psa'],
            '2x2 ID Picture'           => ['column' => 'path_2x2',              'input' => 'doc_2x2'],
            'Form 138 (F138)'          => ['column' => 'path_f138',             'input' => 'doc_f138'],
            'Form 138'                 => ['column' => 'path_f138',             'input' => 'doc_f138'],
            'Good Moral Character'     => ['column' => 'path_good_moral_new',   'input' => 'doc_good_moral_new'],
            'Good Moral'               => ['column' => 'path_good_moral_new',   'input' => 'doc_good_moral_new'],
            'High School Diploma'      => ['column' => 'path_diploma',          'input' => 'doc_diploma'],
            'Diploma'                  => ['column' => 'path_diploma',          'input' => 'doc_diploma'],
            'Transcript of Records'    => ['column' => 'path_tor',              'input' => 'doc_tor'],
            'TOR'                      => ['column' => 'path_tor',              'input' => 'doc_tor'],
            'Honorable Dismissal'      => ['column' => 'path_dismissal',        'input' => 'doc_dismissal'],
            'Cross-Enrollment Permit'  => ['column' => 'path_permit',           'input' => 'doc_permit'],
        ];

        return view('registrar.document_requests', compact('requests', 'docMapping'));
    }

    public function sendDocument(Request $request, $requestId)
    {
        $docRequest = DB::table('document_requests')->where('id', $requestId)->first();

        if (!$docRequest) {
            return redirect()->back()->with('Error', 'There is no document request found with the provided ID.');
        }

        $student = DB::table('students')->where('student_id', $docRequest->student_id)->first();

        if (!$student || empty($student->email)) {
            return redirect()->back()->with('Error', 'Student or email not found.');
        }

        $requestedDocs = json_decode($docRequest->document_name, true);
        
        if (!is_array($requestedDocs) || empty($requestedDocs)) {
            return redirect()->back()->with('Error', 'There are no documents listed in this request.');
        }

        $docMapping = [
            'PSA Birth Certificate' => 'path_psa',
            '2x2 ID Picture' => 'path_2x2',
            'Form 138 (F138)' => 'path_f138',
            'Form 138' => 'path_f138',
            'Good Moral Character' => 'path_good_moral_new',
            'Good Moral' => 'path_good_moral_new',
            'High School Diploma' => 'path_diploma',
            'Diploma' => 'path_diploma',
            'Transcript of Records' => 'path_tor',
            'TOR' => 'path_tor',
            'Honorable Dismissal' => 'path_dismissal',
            'Cross-Enrollment Permit' => 'path_permit'
        ];

        $filesToAttach = [];
        $foundDocs = [];

        foreach ($requestedDocs as $docName) {
            if (array_key_exists($docName, $docMapping)) {
                $columnName = $docMapping[$docName];
                $fileName = $student->{$columnName}; 

                if ($fileName) {
                    $fullStoragePath = "student_records/{$student->student_id}/{$fileName}";
                    
                    if (Storage::disk('local')->exists($fullStoragePath)) {
                        $filesToAttach[] = [
                            'path' => Storage::disk('local')->path($fullStoragePath),
                            'name' => $docName . '.' . pathinfo($fileName, PATHINFO_EXTENSION)
                        ];
                        $foundDocs[] = $docName;
                    }
                }
            }
        }

        if (empty($filesToAttach)) {
            return redirect()->back()->with('Error', 'There are no files found on the server for this request.');
        }

        $displayDocNames = implode(', ', $foundDocs);

        try {
            Mail::send('emails.send_document', [
                'studentName' => $student->first_name,
                'documentName' => $displayDocNames
            ], function ($message) use ($student, $displayDocNames, $filesToAttach) {
                $message->to($student->email)
                        ->subject("Your Requested Documents: " . $displayDocNames);
                
                foreach ($filesToAttach as $file) {
                    $message->attach($file['path'], [
                        'as' => $file['name']
                    ]);
                }
            });

            DB::table('document_requests')
                ->where('id', $requestId)
                ->update([
                    'status' => 'Completed',
                    'updated_at' => now()
                ]);

            return redirect()->back()->with('Success', "The documents have been successfully sent to {$student->first_name}!");
        } catch (\Exception $e) {
            return redirect()->back()->with('Error', "Failed to send email: " . $e->getMessage());
        }
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
}