<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Curriculum;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class CurriculumController extends Controller
{
    public function index()
    {
        $courses = Course::all();
        $subjects = Subject::all();

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
        $curriculum= $request->query('curriculum_id');

        if (!$course || !$yearLevel || !$semester) {
            return response()->json([]);
        }

        $alreadyMappedIds = Curriculum::where('course', $course)
            ->where('year_level', $yearLevel)
            ->where('semester', $semester)
            ->when($curriculum, function ($query, $curriculum) {
                return $query->where('id', '!=', $curriculum);
            })
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
            return redirect()->back()->withErrors([
                'error' => 'This specific subject block link configuration already exists.'
            ]);
        }

        $dean = DB::table('employees')
            ->where('role', 'dean')
            ->where('status', 'Active')
            ->first();

        if ($dean) {
            Subject::where('id', $request->subject_id)
                ->whereNull('professor_id')
                ->update(['professor_id' => $dean->id]);
        }

        Curriculum::create($validated);

        \App\Support\Notifier::catalogChange('Curriculum', 'updated', "{$request->course} {$request->year_level} {$request->semester} (subject added)", 'department_secretary.subject_catalog');

        return redirect()->back()->with(
            'success',
            'Subject successfully mapped to the curriculum structure (Dean set as default instructor)!'
        );
    }

    public function destroy($id)
    {
        $mapping = Curriculum::findOrFail($id);
        $mapping->delete();

        \App\Support\Notifier::catalogChange('Curriculum', 'updated', "{$mapping->course} {$mapping->year_level} {$mapping->semester} (subject removed)", 'department_secretary.subject_catalog');

        return redirect()->back()->with('success', 'Curriculum link has been removed successfully.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'course'     => 'required|string',
            'year_level' => 'required|string',
            'semester'   => 'required|string',
        ]);

        $duplicate = Curriculum::where('course', $request->course)
            ->where('year_level', $request->year_level)
            ->where('semester', $request->semester)
            ->where('subject_id', $request->subject_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withErrors([
                'error' => 'This curriculum mapping already exists.'
            ]);
        }

        $dean = DB::table('employees')
            ->where('role', 'dean')
            ->where('status', 'Active')
            ->first();

        if ($dean) {
            Subject::where('id', $request->subject_id)
                ->whereNull('professor_id')
                ->update(['professor_id' => $dean->id]);
        }

        $curriculum = Curriculum::findOrFail($id);

        $curriculum->update($validated);

        \App\Support\Notifier::catalogChange('Curriculum', 'updated', "{$request->course} {$request->year_level} {$request->semester}", 'department_secretary.subject_catalog');

        return redirect()->back()->with(
            'success',
            'Curriculum updated successfully.'
        );
    }
}
