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

        // Kunin ang active table blueprint structure list
        $mappings = Curriculum::with('subject')
            ->orderBy('course')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->get();

        return view('system_admin.manage-curriculum', compact('courses', 'subjects', 'mappings'));
    }

    public function getSubjectsByCourse(Request $request)
    {
        $course    = $request->query('course');
        $yearLevel = $request->query('year_level');
        $semester  = $request->query('semester');

        if (!$course || !$yearLevel || !$semester) {
            return response()->json([]);
        }

        $alreadyMappedIds = Curriculum::where('course', $course)
            ->where('year_level', $yearLevel)
            ->where('semester', $semester)
            ->pluck('subject_id')
            ->toArray();

        $subjects = Subject::whereNotIn('id', $alreadyMappedIds)
            ->get(['id', 'subject_code', 'subject_title', 'total_units']);

        return response()->json($subjects);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'course'     => 'required|string',
            'year_level' => 'required|string',
            'semester'   => 'required|string',
        ]);

        $exists = Curriculum::where('course', $request->course)
            ->where('year_level', $request->year_level)
            ->where('semester', $request->semester)
            ->where('subject_id', $request->subject_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['error' => 'This specific subject block link configuration already exists.']);
        }

        Curriculum::create($validated);

        return redirect()->back()->with('success', 'Subject successfully mapped to the curriculum structure!');
    }
}