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
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $schedules = DB::table('class_schedules')
            ->leftJoin('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('curricula', 'subjects.id', '=', 'curricula.subject_id')
            ->leftJoin('employees', 'subjects.professor_id', '=', 'employees.id')
            ->select(
                'class_schedules.*',
                'subjects.subject_code',
                'subjects.subject_title',
                'curricula.year_level',
                'curricula.semester',
                'employees.first_name as prof_first_name',  
                'employees.last_name  as prof_last_name'     
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

        return view('department_secretary.manage-schedules', compact('schedules', 'subjects'));
    }

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

    public function update(Request $request, $id)
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'section_name'    => 'required|string',
            'class_days'      => 'required|string',
            'room_assignment' => 'required|string',
            'start_time'      => 'required',
            'end_time'        => 'required',
        ]);

        DB::table('class_schedules')->where('id', $id)->update([
            'section_name'    => strtoupper($request->input('section_name')),
            'class_days'      => $request->input('class_days'),
            'room_assignment' => strtoupper($request->input('room_assignment')),
            'start_time'      => $request->input('start_time'),
            'end_time'        => $request->input('end_time'),
            'updated_at'      => now(),
        ]);

        return redirect()->back()->with('success', 'Class deployment schedule successfully updated!');
    }

    public function delete($id)
    {
        if (session('user_role') !== 'dept_sec') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        DB::table('student_subjects')->where('schedule_id', $id)->delete();

        // Pagkatapos ay burahin na ang mismong parent schedule row
        DB::table('class_schedules')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Class deployment record successfully deleted!');
    }
}