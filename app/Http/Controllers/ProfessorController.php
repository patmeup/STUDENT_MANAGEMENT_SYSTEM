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

        $classes = DB::table('subjects')
            ->where('professor_id', $professor_id)
            ->select('*')
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