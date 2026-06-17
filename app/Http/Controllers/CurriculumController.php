<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Curriculum;
use App\Models\Course;

class CurriculumController extends Controller
{
    public function index()
    {
        if (session('user_role') !== 'system_admin') {
            return redirect()->route('system_admin.dashboard');
        }

        $courses = Course::all();
        $subjects = Subject::all();

        $mappings = Curriculum::with('subject')->orderBy('course')->orderBy('year_level')->orderBy('semester')->get();

        return view('system_admin.manage-curriculum', compact('courses', 'subjects', 'mappings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'course' => 'required|string',
            'year_level' => 'required|string',
            'semester' => 'required|string',
        ]);

        $exists = Curriculum::where('course', $request->course)
                            ->where('year_level', $request->year_level)
                            ->where('semester', $request->semester)
                            ->where('subject_id', $request->subject_id)
                            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['error' => 'This current subject is already mapped to the selected course and semester.']);
        }

        Curriculum::create($validated);

        return redirect()->back()->with('success', 'Subject successfully mapped to the curriculum structure!');
    }
}