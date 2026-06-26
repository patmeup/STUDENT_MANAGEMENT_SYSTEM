<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginId = $request->input('login_id');
        $password = $request->input('password');

        $user = User::where('employee_id', $loginId)->first();

        if ($user) {
            $isPasswordCorrect = false;

            if ($user->role === 'super_admin') {
                if (Str::startsWith($user->password, '$2y$')) {
                    $isPasswordCorrect = Hash::check($password, $user->password);
                } else {
                    $isPasswordCorrect = ($password === $user->password);
                }
            } else {
                $isPasswordCorrect = Hash::check($password, $user->password);
            }

            if ($isPasswordCorrect) {
                
                if ($user->status !== 'Active') {
                    return back()->withErrors(['error' => 'This account has been deactivated. Please contact HR.']);
                }
                
                session([
                    'user_id'     => $user->id,
                    'employee_id' => $user->employee_id,
                    'user_role'   => $user->role, 
                    'user_name'   => $user->first_name . ' ' . $user->last_name
                ]);

                if ($user->role === 'super_admin') {
                    return redirect()->route('super_admin.dashboard');
                } 
                
                if ($user->role === 'system_admin') {
                    return redirect()->route('system_admin.dashboard');
                }

                if ($user->role === 'registrar') {
                    return redirect()->route('registrar.dashboard');
                }

                if ($user->role === 'dean') {
                    return redirect()->route('dean.dashboard');
                }
                
                if ($user->role === 'dept_sec') {
                    return redirect()->route('department_secretary.dashboard');
                }

                if ($user->role === 'professor') {
                    return redirect()->route('professor.dashboard');
                }
            }
        }

        return back()->withErrors(['error' => 'Invalid Employee ID or Security Password.']);
    }

    public function logout()
    {
        session()->forget(['user_id', 'employee_id', 'user_role', 'user_name']);
        return redirect('/');
    }

    public function showSecurityPage()
    {
        $user = User::where('employee_id', session('employee_id'))->first();

        if (!$user) {
            return redirect('/')->withErrors(['error' => 'Your session has expired. Please log in again.']);
        }

        $isLocked = ($user->role === 'super_admin' && !Str::startsWith($user->password, '$2y$'));

        return view('update_password', compact('user', 'isLocked'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('employee_id', session('employee_id'))->first();

        if (!$user) {
            return redirect('/')->withErrors(['error' => 'User profile reference not found.']);
        }

        $isCurrentCorrect = false;
        if ($user->role === 'super_admin' && !Str::startsWith($user->password, '$2y$')) {
            $isCurrentCorrect = ($request->current_password === $user->password);
        } else {
            $isCurrentCorrect = Hash::check($request->current_password, $user->password);
        }

        if (!$isCurrentCorrect) {
            return redirect()->back()->withErrors(['current_password' => 'The current password you provided is invalid.']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        $role = session('user_role');

        if ($role === 'super_admin') {
            return redirect()->route('super_admin.user_details')
                ->with('success', 'Security records updated! All sidebar tabs are now unlocked.');
        } 
        
        if ($role === 'system_admin') {
            return redirect()->route('system_admin.user_details')
                ->with('success', 'Your password has been changed successfully.');
        }

        if ($role === 'dean') {
            return redirect()->route('dean.user_details')
                ->with('success', 'Your password has been changed successfully.');
        }
        
        if ($role === 'registrar') {
            return redirect()->route('registrar.user_details')
                ->with('success', 'Your password has been changed successfully.');
        }

        return redirect('/')->with('success', 'Password updated successfully. Please log in again.');
    }
}