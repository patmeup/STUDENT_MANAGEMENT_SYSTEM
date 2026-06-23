<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Course;

class SubjectController extends Controller
{
    public function index()
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $subjects = Subject::all();
        $courses = Course::all();

        return view('system_admin.manage-subjects', compact('subjects', 'courses'));
    }

    public function store(Request $request)
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'subject_code'  => 'required|string|unique:subjects,subject_code|max:50',
            'subject_title' => 'required|string|max:255',
            'lecture_units' => 'required|integer|min:0|max:9',
            'lab_units'     => 'required|integer|min:0|max:9',
        ]);

        Subject::create([
            'subject_code'  => strtoupper($request->subject_code),
            'subject_title' => $request->subject_title,
            'lecture_units' => $request->lecture_units,
            'lab_units'     => $request->lab_units,
            'total_units'   => (int)$request->lecture_units + (int)$request->lab_units,
        ]);

        return redirect()->back()->with('success', 'Global Subject catalog entry established successfully!');
    }

    public function update(Request $request, $id)
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $subject = Subject::findOrFail($id);

        $request->validate([
            'subject_code'  => 'required|string|max:50|unique:subjects,subject_code,' . $subject->id,
            'subject_title' => 'required|string|max:255',
            'lecture_units' => 'required|integer|min:0|max:9',
            'lab_units'     => 'required|integer|min:0|max:9',
        ]);

        $subject->update([
            'subject_code'  => strtoupper($request->subject_code),
            'subject_title' => $request->subject_title,
            'lecture_units' => $request->lecture_units,
            'lab_units'     => $request->lab_units,
            'total_units'   => (int)$request->lecture_units + (int)$request->lab_units,
        ]);

        return redirect()->back()->with('success', "Subject [{$subject->subject_code}] updated successfully!");
    }

    public function destroy($id)
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $subject = Subject::findOrFail($id);
        $code = $subject->subject_code;
        $subject->delete();

        return redirect()->back()->with('success', "Subject [{$code}] has been removed from the catalog.");
    }
}
