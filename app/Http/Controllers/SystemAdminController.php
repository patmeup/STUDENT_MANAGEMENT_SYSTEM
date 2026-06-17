<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;

class SystemAdminController extends Controller
{
    public function index()
    {

        if (session('user_role') !== 'system_admin') {
            return redirect()->route('system_admin.dashboard');
        }

        return view('system_admin.manage-subjects');
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
