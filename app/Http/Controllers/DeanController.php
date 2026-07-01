<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;

class DeanController extends Controller
{
    public function index()
    {
        if (session('user_role') !== 'dean') {
            return redirect('/')->with('error', 'Unauthorized access. Please login as Dean.');
        }

        return view('dean.dashboard'); 
    }
    
    public function profileView()
    {
        $userId = session('user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect('/')->withErrors(['error' => 'Session expired. Please login again.']);
        }

        $isLocked = !Str::startsWith($user->password, '$2y$');

        return view('dean.user_details', compact('user', 'isLocked'));
    }  
}