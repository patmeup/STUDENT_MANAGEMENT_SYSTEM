<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassSchedule;
use App\Models\Subject;

class ScheduleController extends Controller
{
    // 1. Ipakita ang Manage Schedules Page
    public function index()
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $schedules = ClassSchedule::with('subject.professor')->get();
        
        $subjects = Subject::orderBy('subject_code', 'asc')->get();

        return view('system_admin.manage-schedules', compact('schedules', 'subjects'));
    }

    // 2. I-save ang ginawang Schedule
    public function store(Request $request)
    {
        $request->validate([
            'subject_id'      => 'required|exists:subjects,id',
            'section_name'    => 'required|string|max:50',
            'class_days'      => 'required|string|max:20',
            'start_time'      => 'required',
            'end_time'        => 'required',
            'room_assignment' => 'required|string|max:50',
        ]);

        ClassSchedule::create($request->all());

        return redirect()->back()->with('success', 'Class deployment schedule successfully established!');
    }
}