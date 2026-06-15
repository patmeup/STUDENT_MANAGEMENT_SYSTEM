<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function index()
    {
        if (session('user_role') !== 'super_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }
        $currentAdmin = User::find(session('user_id'));
        if ($currentAdmin && !Str::startsWith($currentAdmin->password, '$2y$')) {
            return redirect()->route('account.security')
                ->withErrors(['error' => 'Access Restricted! Mangyaring i-secure muna ang iyong account sa pamamagitan ng pagpapalit ng default password upang ma-unlock ang Manage Employee feature.']);
        }

        $employees = User::where('role', '!=', 'super_admin')
                         ->orderBy('employee_id', 'desc')
                         ->get();

        return view('super_admin.manage-employee', compact('employees'));
    }

    public function store(Request $request)
    {
        if (session('user_role') !== 'super_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex' => 'required|string',
            'email' => 'required|email|unique:employees,email',
            'role' => 'required|string|in:system_admin,dean,registrar',
        ]);

        // ALGORITHM: Awtomatikong pag-generate ng Employee ID (Format: EMP-26-XXXX)
        $yearShort = date('y'); // Salo ang '26' para sa kasalukuyang taon na 2026
        $lastEmployee = User::where('employee_id', 'LIKE', "EMP-{$yearShort}-%")->latest()->first();

        if ($lastEmployee) {
            $lastNumber = intval(substr($lastEmployee->employee_id, -4));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
        
        $generatedId = "EMP-{$yearShort}-{$nextNumber}";

        User::create([
            'employee_id' => $generatedId,
            'first_name' => strtoupper($request->first_name),
            'last_name' => strtoupper($request->last_name),
            'middle_name' => $request->middle_name ? strtoupper($request->middle_name) : null,
            'sex' => $request->sex,
            'email' => $request->email,
            'role' => $request->role,
            'status' => 'Active',
            'password' => Hash::make('Welcome@AU2026'), // Naka-encrypt na default temporary password
        ]);

        return redirect()->back()->with('success', "Personnel account successfully created! ID: {$generatedId} | Default Pass: Welcome@AU2026");
    }

    public function profileView()
    {
        $userId = session('user_id');

        // 2. Hanapin ang buong record ng user sa database
        $user = User::find($userId);

        // Safety net: Kung walang nahanap na user, ibalik sa login page
        if (!$user) {
            return redirect('/')->withErrors(['error' => 'Session expired. Please login again.']);
        }

        $isLocked = !Str::startsWith($user->password, '$2y$');

        // 4. I-serve ang view kasama ang data ng user
        return view('super_admin.user_details', compact('user', 'isLocked'));
    }    
}