<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Subject;
use App\Models\Course;

class SystemAdminController extends Controller
{

    public function dashboard()
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $announcements = DB::table('announcements')->orderBy('created_at', 'desc')->get();

        $totalEmployees = User::whereIn('role', ['professor', 'dept_sec', 'dean'])->count();
        $totalStudents = DB::table('students')->count();
        $totalCourses = Course::count();
        $totalSubjects = Subject::count();

        return view('system_admin.dashboard', compact(
            'announcements',
            'totalEmployees', 
            'totalStudents', 
            'totalCourses', 
            'totalSubjects'
        ));
    }
        
    public function profileView()
    {
        $userId = session('user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect('/')->withErrors(['error' => 'Session expired. Please login again.']);
        }

        $isLocked = !Str::startsWith($user->password, '$2y$');

        return view('system_admin.user_details', compact('user', 'isLocked'));
    }   
}
