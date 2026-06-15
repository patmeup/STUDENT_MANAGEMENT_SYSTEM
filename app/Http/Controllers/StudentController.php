<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student; // Siguraduhing may Student model ka na ginawa via php artisan make:model Student
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{

    public function index()
    {
        // Kukunin natin lahat ng students mula sa database para ipakita sa Masterlist Table
        $students = Student::orderBy('created_at', 'desc')->get();
        return view('registrar.masterlist', compact('students'));
    }

    public function store(Request $request)
    {
        // 1. Validation Rules (Nananatiling protektado ang files)
        $request->validate([
            'registration_date' => 'required|date',
            'student_type'      => 'required|string',
            'course'            => 'required|string',
            'last_name'         => 'required|string',
            'first_name'        => 'required|string',
            'contact_no'        => 'required|string',
            
            'doc_psa'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_2x2'               => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'doc_f138'              => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_new'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_diploma'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_tor'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_dismissal'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_good_moral_trans'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_permit'            => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // 2. UPDATED SEQUENCE: Custom 5-Digit Student Number Generator (Format: 26-00001)
        $currentYearShort = date('y'); // Kukuha ng huling dalawang numero ng taon (e.g., '26' para sa 2026)
        $prefix = $currentYearShort . "-";

        // Hahanapin ang huling estudyante na may simula na '26-'
        $lastStudent = DB::table('students')
            ->where('student_id', 'LIKE', $prefix . '%')
            ->orderBy('student_id', 'desc')
            ->first();

        if ($lastStudent) {
            // Hahatiin ang string gamit ang '-' (e.g., mula sa "26-00527", kukunin ang "00527")
            $parts = explode('-', $lastStudent->student_id);
            $lastSequence = intval(end($parts));
            
            // Magdadagdag ng isa (+1) at gagawing may 5 zeros sa unahan (5-digit padding)
            $nextSequence = str_pad($lastSequence + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '00001'; // Unang estudyante kung sakaling walang laman ang taon na ito
        }

        $generatedStudentNumber = $prefix . $nextSequence; // Lalabas na halimbawa: 26-00001 o 26-00527

        // 3. SECURE FILE HANDLING ENGINE (Student-Centric Flat Folder)
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

        foreach ($fileFields as $inputName => $cleanFileName) {
            if ($request->hasFile($inputName)) {
                $file = $request->file($inputName);
                $extension = $file->getClientOriginalExtension();
                $finalFileName = "{$cleanFileName}.{$extension}";
                
                // Diretso sa student_records/{STUDENT_NUMBER}/ nang walang course separation para iwas aberya sa shifting!
                // Path: storage/app/student_records/26-00527/psa_birth_certificate.pdf
                $file->storeAs("student_records/{$generatedStudentNumber}", $finalFileName, 'local');
                
                $filePaths[$inputName] = $finalFileName;
            }
        }

        // 4. ELOQUENT DATA RECORD CAPTURE
        $student = new Student();
        $student->student_id = $generatedStudentNumber; // Isasave ang bagong format (e.g., 26-00001)
        $student->registration_date = $request->registration_date;
        $student->student_type = $request->student_type;
        $student->course = $request->course;
        
        $student->last_name = strtoupper($request->last_name);
        $student->first_name = strtoupper($request->first_name);
        $student->middle_name = $request->middle_name ? strtoupper($request->middle_name) : null;
        
        $student->sex = $request->sex;
        $student->civil_status = $request->civil_status;
        $student->date_of_birth = $request->date_of_birth;
        $student->nationality = strtoupper($request->nationality);
        $student->place_of_birth = $request->place_of_birth;
        $student->city_address = $request->city_address;
        $student->zip_code = $request->zip_code;
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

        $student->path_psa               = $filePaths['doc_psa'] ?? null;
        $student->path_2x2               = $filePaths['doc_2x2'] ?? null;
        $student->path_f138              = $filePaths['doc_f138'] ?? null;
        $student->path_good_moral_new    = $filePaths['doc_good_moral_new'] ?? null;
        $student->path_diploma           = $filePaths['doc_diploma'] ?? null;
        $student->path_tor               = $filePaths['doc_tor'] ?? null;
        $student->path_dismissal         = $filePaths['doc_dismissal'] ?? null;
        $student->path_good_moral_trans  = $filePaths['doc_good_moral_trans'] ?? null;
        $student->path_permit            = $filePaths['doc_permit'] ?? null;
        
        $student->credentials = array_values($filePaths);

        $student->save();

        // 5. SUCCESS ALERT WITH REALTIME ENROLLMENT NUMBER
        return redirect()->back()->with('success', "Student registration compiled successfully! Assigned Student ID: {$generatedStudentNumber}");
    }
}