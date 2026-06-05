<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Support\Facades\Session;

class SubjectController extends Controller
{
    // 1. Ipakita ang Form para sa Pag-add ng Subjects
    public function index()
    {
        // Security check para sa admin session role
        if (session('user_role') !== 'system_admin') {
            return redirect()->route('system_admin.dashboard');
        }

        // Ituturo natin sa admin folder -> manage-subjects.blade.php
        return view('system_admin.manage-subjects');
    }

    // 2. I-save ang Subject mula sa Form
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

        // Pagkatapos mag-save, pwede nating ibalik sa form na may success message
        return redirect()->back()->with('success', 'Global Subject catalog entry established successfully!');
    }
}