<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class ProfessorController extends Controller
{
    public function manageClasses()
    {
        if (session('user_role') !== 'professor') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $professor_id = session('user_id'); 

        $classes = \DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->select(
                'class_schedules.id as schedule_id',
                'class_schedules.section_name',
                'class_schedules.class_days',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'class_schedules.room_assignment',
                'subjects.subject_code', 
                'subjects.subject_title',
                'subjects.lecture_units',
                'subjects.lab_units'
            )
            ->where('subjects.professor_id', $professor_id) 
            ->orderBy('class_schedules.section_name')
            ->get();

        return view('professor.manage-classes', compact('classes'));
    }

    public function profileView()
    {
        if (session('user_role') !== 'professor') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $professor_id = session('user_id');

        $professor = User::where('id', $professor_id)->first();

        return view('professor.user_details', compact('professor'));
    }
}