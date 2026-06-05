<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. I-validate ang input fields mula sa login form
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginId = $request->input('login_id');
        $password = $request->input('password');

        // 2. Hahanapin ang employee sa database gamit ang employee_id
        $user = User::where('employee_id', $loginId)->first();

        // 3. I-verify gamit ang Hash::check kung tugma ang password sa database record
        if ($user && Hash::check($password, $user->password)) {
            
            // Proteksyon: Siguraduhing active ang status ng account
            if ($user->status !== 'Active') {
                return back()->withErrors(['error' => 'This account has been deactivated. Please contact HR.']);
            }
            
            // I-save ang mga mahahalagang impormasyon sa session
            session([
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'user_role' => $user->role, 
                'user_name' => $user->first_name . ' ' . $user->last_name
            ]);

            // 4. DYNAMIC REDIRECTS DEPENDING ON ROLES
            if ($user->role === 'super_admin') {
                return redirect()->route('super_admin.dashboard');
            } 
            
            if ($user->role === 'system_admin') {
                return redirect()->route('admin.manage-subjects');
            }

            if ($user->role === 'dean') {
                return redirect()->route('dean.curriculum.index');
            }
            
            if ($user->role === 'registrar') {
                return redirect()->route('registrar.dashboard');
            }
        }

        // Fail state kung hindi nag-match ang credentials sa DB
        return back()->withErrors(['error' => 'Invalid Employee ID or Security Password.']);
    }

    public function logout()
    {
        session()->forget(['user_role', 'user_name']);
        return redirect('/');
    }
}