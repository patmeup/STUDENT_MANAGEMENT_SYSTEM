<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Course;
use App\Models\User;

class SubjectController extends Controller
{
    public function index()
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $subjects = Subject::with('professor')->get();
        $courses = Course::all();

        $professors = User::where('role', 'professor')
                          ->where('status', 'Active')
                          ->orderBy('last_name', 'asc')
                          ->get();

        return view('system_admin.manage-subjects', compact('subjects', 'courses', 'professors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_code'  => 'required|string|unique:subjects,subject_code|max:50',
            'subject_title' => 'required|string|max:255',
            'lecture_units' => 'required|integer|min:0|max:9',
            'lab_units'     => 'required|integer|min:0|max:9',
            'professor_id'  => 'nullable|exists:employees,id',
        ]);

        Subject::create([
            'subject_code'  => strtoupper($request->subject_code),
            'subject_title' => $request->subject_title,
            'lecture_units' => $request->lecture_units,
            'lab_units'     => $request->lab_units,
            'total_units'   => (int)$request->lecture_units + (int)$request->lab_units,
            'professor_id'  => $request->professor_id, 
        ]);

        return redirect()->back()->with('success', 'Global Subject catalog entry established successfully!');
    }
}