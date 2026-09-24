<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ClassSchedule;
use App\Models\Subject;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = DB::table('class_schedules')
            ->leftJoin('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('curricula', 'subjects.id', '=', 'curricula.subject_id')
            ->leftJoin('employees', 'class_schedules.professor_id', '=', 'employees.id')
            ->select(
                'class_schedules.*',
                'subjects.subject_code',
                'subjects.subject_title',
                'subjects.lecture_units',
                'subjects.lab_units',
                'curricula.year_level',
                'curricula.semester',
                'employees.first_name as prof_first_name',  
                'employees.last_name as prof_last_name'     
            )
            ->orderBy('class_schedules.section_name', 'asc')
            ->get();

        $subjects = DB::table('subjects')
            ->join('curricula', 'subjects.id', '=', 'curricula.subject_id')
            ->select(
                'subjects.id',
                'subjects.subject_code',
                'subjects.subject_title',
                'curricula.year_level',
                'curricula.semester'
            )
            ->orderBy('curricula.year_level')
            ->orderBy('curricula.semester')
            ->get();

        $professors = DB::table('employees')
            ->whereIn('role', ['professor', 'dean'])
            ->where('status', 'Active')
            ->orderBy('last_name', 'asc')
            ->get();

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();

        return view('department_secretary.manage-schedules', compact('schedules', 'subjects', 'professors', 'activePeriod'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id'      => 'required|exists:subjects,id',
            'schedule_type'   => 'required|in:LEC,LAB',
            'section_name'    => 'required|string|max:50',
            'class_days'      => 'required|string|max:20',
            'start_time'      => 'required',
            'end_time'        => 'required',
            'room_assignment' => 'required|string|max:50',
            'professor_id'    => 'required|exists:employees,id',
        ]);

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();

        if (!$activePeriod) {
            return redirect()->back()->with('error', 'Cannot create schedule. No active academic period set in the system.');
        }

        ClassSchedule::create([
            'academic_period_id' => $activePeriod->id,
            'subject_id'         => $request->input('subject_id'),
            'professor_id'       => $request->input('professor_id'),
            'schedule_type'      => strtoupper($request->input('schedule_type')), 
            'section_name'       => strtoupper($request->input('section_name')),
            'class_days'         => $request->input('class_days'),
            'start_time'         => $request->input('start_time'),
            'end_time'           => $request->input('end_time'),
            'room_assignment'    => strtoupper($request->input('room_assignment')),
        ]);

        return redirect()->back()->with('success', 'Class deployment schedule successfully established!');
    }

    public function delete($id)
    {
        DB::table('student_subjects')->where('schedule_id', $id)->delete();
        DB::table('class_schedules')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Class deployment record successfully deleted!');
    }
}