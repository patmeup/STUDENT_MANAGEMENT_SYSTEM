<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

    // 1. Kinukuha ang data ng subject para i-feed sa AJAX (Edit Mode)
    public function editSubject($id)
    {
        $subject = Subject::find($id);
        
        if (!$subject) {
            return response()->json(['error' => 'Subject not found inside core records.'], 404);
        }
        
        return response()->json($subject);
    }

    // 2. Nagse-save ng pagbabago mula sa specifications form (Update Mode)
    public function updateSubject(Request $request)
    {
        // Inayos ang validation rules para mag-match sa input fields ng blade mo
        $request->validate([
            'subject_id'    => 'required|exists:subjects,id',
            'subject_code'  => 'required|string|max:50',
            'subject_title' => 'required|string|max:255',
            'lecture_units' => 'required|integer|min:0|max:9',
            'lab_units'     => 'required|integer|min:0|max:9',
            'professor_id'  => 'nullable',
        ]);

        $subject = Subject::find($request->subject_id);

        if (!$subject) {
            return redirect()->back()->with('error', 'Target subject entry could not be found.');
        }

        // Ini-update gamit ang tamang column names base sa iyong store logic
        $subject->update([
            'subject_code'  => strtoupper($request->subject_code),
            'subject_title' => $request->subject_title,
            'lecture_units' => $request->lecture_units,
            'lab_units'     => $request->lab_units,
            'total_units'   => (int)$request->lecture_units + (int)$request->lab_units,
            'professor_id'  => $request->professor_id ? $request->professor_id : null,
        ]);

        return redirect()->back()->with('success', 'Global Subject entry updated successfully!');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'subject_id', 'id'); 
    }
}