<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Support\Facades\Session;

class SubjectController extends Controller
{
    public function index()
    {
        if (session('user_role') !== 'system_admin') {
            return redirect()->route('system_admin.dashboard');
        }
        
        $subjects = Subject::all(); 

        return view('system_admin.manage-subjects', compact('subjects'));
    }

    public function store(Request $request)
    {
        if (session('user_role') !== 'system_admin') {
            return redirect()->route('system_admin.manage-subjects');
        }

        $validated = $request->validate([
            'subject_code' => 'required|string|unique:subjects,subject_code|max:50',
            'subject_title' => 'required|string|max:255',
            'lecture_units' => 'required|integer|min:0|max:9',
            'lab_units' => 'required|integer|min:0|max:9',
        ]);

        $validated['total_units'] = $request->lecture_units + $request->lab_units;
        $validated['subject_code'] = strtoupper($request->subject_code);

        Subject::create($validated);

        return redirect()->back()->with('success', 'Global Subject catalog entry established successfully!');
    }
}