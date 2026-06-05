<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class StudentController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validation Rules (Gamit ang flexible rules para sa optional fields)
        $validated = $request->validate([
            'registration_date' => 'required|date',
            'student_type' => 'required|string',
            'course' => 'required|string',
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex' => 'required|string',
            'civil_status' => 'required|string',
            'date_of_birth' => 'required|date',
            'nationality' => 'required|string',
            'place_of_birth' => 'nullable|string',
            'city_address' => 'required|string',
            'zip_code' => 'nullable|string',
            'contact_no' => 'required|string',
            'fathers_name' => 'nullable|string',
            'fathers_occupation' => 'nullable|string',
            'mothers_name' => 'nullable|string',
            'mothers_occupation' => 'nullable|string',
            'primary_school_name' => 'required|string',
            'primary_school_address' => 'required|string',
            'primary_school_year' => 'required|string',
            'secondary_school_name' => 'required|string',
            'secondary_school_address' => 'required|string',
            'secondary_school_year' => 'required|string',
            
            // Required lang ang College Background kapag pinili ay Transferee
            'college_school_name' => 'nullable|required_if:student_type,Transferee|string',
            'college_school_address' => 'nullable|required_if:student_type,Transferee|string',
            'college_school_year' => 'nullable|required_if:student_type,Transferee|string',
            'credentials' => 'nullable|array',
        ]);

        $regYearFull = date('Y', strtotime($request->registration_date)); // e.g., 2026
        $regYearShort = date('y', strtotime($request->registration_date)); // e.g., 26

        $lastStudent = Student::where('student_number', 'LIKE', "{$regYearShort}-%")->latest()->first();
        
        if ($lastStudent) {
            $lastNumber = intval(substr($lastStudent->student_number, -5));
            $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '00001';
        }
        
        $validated['student_number'] = "{$regYearShort}-{$nextNumber}";

        // Siguraduhing uppercase ang pangalan para pormal sa record
        $validated['last_name'] = strtoupper($request->last_name);
        $validated['first_name'] = strtoupper($request->first_name);
        $validated['middle_name'] = $request->middle_name ? strtoupper($request->middle_name) : null;

        // 3. I-save sa Database Table
        Student::create($validated);

        // Ididirekta pabalik na may kasamang success notification at ang bagong Student ID
        return redirect()->route('registrar.manage-enrollment')->with('success', "Student registered successfully! Student Number allocated: {$validated['student_number']}");
    }

    public function index()
    {
        // Kukunin ang lahat ng estudyante, mauuna ang pinakabagong rehistro
        $students = Student::latest()->get();

        // Ipapasa ang data sa view na gagawin natin sa susunod na hakbang
        return view('registrar.masterlist', compact('students'));
    }
}