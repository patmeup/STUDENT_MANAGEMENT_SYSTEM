<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Curriculum;

class CurriculumController extends Controller
{
    public function index()
    {
        // Security Check for Dean Role
        if (session('user_role') !== 'dean') {
            return redirect()->route('admin.login');
        }

        // Hakuha ang lahat ng Global Subjects na gawa ni Admin para sa dropdown
        $globalSubjects = Subject::orderBy('subject_code', 'asc')->get();

        // Kunin din ang mga kasalukuyang mapped subjects para makita ni Dean sa listahan sa ibaba
        $mappings = Curriculum::with('subject')->latest()->get();

        return view('dean.encoding', compact('globalSubjects', 'mappings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'course' => 'required|string',
            'year_level' => 'required|string',
            'semester' => 'required|string',
        ]);

        // I-save ang Mapping
        Curriculum::create($request->all());

        return redirect()->back()->with('success', 'Subject successfully mapped to the curriculum!');
    }
}