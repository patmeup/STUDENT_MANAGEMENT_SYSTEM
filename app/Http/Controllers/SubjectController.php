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
        $subjects = Subject::with('professor')->get();
        $courses  = Course::all();

        $dean = DB::table('employees')
            ->where('role', 'dean')
            ->where('status', 'Active')
            ->first();

        $professors = User::whereIn('role', ['professor', 'dean'])
                        ->where('status', 'Active')
                        ->orderBy('last_name', 'asc')
                        ->get();

        return view('system_admin.manage-subjects', compact('subjects', 'courses', 'professors', 'dean'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_code'  => 'required|string|unique:subjects,subject_code|max:50',
            'subject_title' => 'required|string|max:255',
            'lecture_units' => 'required|integer|min:0|max:9',
            'lab_units'     => 'required|integer|min:0|max:9',
            'professor_id'  => 'nullable',
        ]);

        $dean = DB::table('employees')
            ->where('role', 'dean')
            ->where('status', 'Active')
            ->first();

        $professorId = $request->professor_id ?: ($dean ? $dean->id : null);

        Subject::create([
            'subject_code'  => strtoupper($request->subject_code),
            'subject_title' => $request->subject_title,
            'lecture_units' => $request->lecture_units,
            'lab_units'     => $request->lab_units,
            'total_units'   => (int)$request->lecture_units + (int)$request->lab_units,
            'professor_id'  => $professorId,
        ]);

        \App\Support\Notifier::catalogChange('Subject', 'created', strtoupper($request->subject_code) . ' - ' . $request->subject_title, 'department_secretary.subject_catalog');

        return redirect()->back()->with('success', 'Global Subject catalog entry established successfully!');
    }

    public function editSubject($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json(['error' => 'Subject not found inside core records.'], 404);
        }

        return response()->json($subject);
    }

    public function updateSubject(Request $request)
    {
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

        $dean = DB::table('employees')
            ->where('role', 'dean')
            ->where('status', 'Active')
            ->first();

        $professorId = $request->professor_id ?: ($subject->professor_id ?: ($dean ? $dean->id : null));

        $subject->update([
            'subject_code'  => strtoupper($request->subject_code),
            'subject_title' => $request->subject_title,
            'lecture_units' => $request->lecture_units,
            'lab_units'     => $request->lab_units,
            'total_units'   => (int)$request->lecture_units + (int)$request->lab_units,
            'professor_id'  => $professorId,
        ]);

        \App\Support\Notifier::catalogChange('Subject', 'updated', strtoupper($request->subject_code) . ' - ' . $request->subject_title, 'department_secretary.subject_catalog');

        return redirect()->back()->with('success', 'Global Subject entry updated successfully!');
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $request->validate([
            'subject_code'  => 'required|string|max:50|unique:subjects,subject_code,' . $subject->id,
            'subject_title' => 'required|string|max:255',
            'lecture_units' => 'required|integer|min:0|max:9',
            'lab_units'     => 'required|integer|min:0|max:9',
            'professor_id'  => 'nullable',
        ]);

        $dean = DB::table('employees')
            ->where('role', 'dean')
            ->where('status', 'Active')
            ->first();

        $professorId = $request->professor_id ?: ($subject->professor_id ?: ($dean ? $dean->id : null));

        $subject->update([
            'subject_code'  => strtoupper($request->subject_code),
            'subject_title' => $request->subject_title,
            'lecture_units' => $request->lecture_units,
            'lab_units'     => $request->lab_units,
            'total_units'   => (int)$request->lecture_units + (int)$request->lab_units,
            'professor_id'  => $professorId,
        ]);

        \App\Support\Notifier::catalogChange('Subject', 'updated', $subject->subject_code . ' - ' . $subject->subject_title, 'department_secretary.subject_catalog');

        return redirect()->back()->with(
            'success',
            "Subject [{$subject->subject_code}] updated successfully!"
        );
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $code = $subject->subject_code;

        $subject->delete();

        \App\Support\Notifier::catalogChange('Subject', 'removed', $code, 'department_secretary.subject_catalog');

        return redirect()->back()->with('success', "Subject [{$code}] has been removed from the catalog.");
    }
}
