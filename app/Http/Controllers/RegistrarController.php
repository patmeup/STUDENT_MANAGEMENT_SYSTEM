<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrarController extends Controller
{
    public function storeEnrollment(Request $request)
    {
        $request->validate([
            'registration_date' => 'required|date',
            'student_type' => 'required|string',
            'course' => 'required|string',
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

        // 2. Automated Generation ng Student ID (Format: 2026-0001)
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

        // 3. Folder Creation & File Storage Engine
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

        // PINAKAKRITIKAL NA BAHAGI: Puwersahing gawin ang subfolder sa local storage disk muna!
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
                    
                    // Mas matatag na approach para sa Windows/Linux local servers
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
        $student->academic_level = '1st Year, 1st Semester';
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

        // I-map ang file columns sa database structure mo
        $student->path_psa               = $filePaths['doc_psa'] ?? null;
        $student->path_2x2               = $filePaths['doc_2x2'] ?? null;
        $student->path_f138              = $filePaths['doc_f138'] ?? null;
        $student->path_good_moral_new    = $filePaths['doc_good_moral_new'] ?? null;
        $student->path_diploma           = $filePaths['doc_diploma'] ?? null;
        $student->path_tor               = $filePaths['doc_tor'] ?? null;
        $student->path_dismissal         = $filePaths['doc_dismissal'] ?? null;
        $student->path_good_moral_trans  = $filePaths['doc_good_moral_trans'] ?? null;
        $student->path_permit            = $filePaths['doc_permit'] ?? null;
        
        $student->save();

        return redirect()->back()->with('success', "Student credentials compiled successfully! Allocated Student ID: {$generatedStudentId}");
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

    public function profileView()
    {
        if (session('user_role') !== 'registrar') {
            return redirect()->route('login')->with('error', 'Unauthorized access. Please login as Registrar.');
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