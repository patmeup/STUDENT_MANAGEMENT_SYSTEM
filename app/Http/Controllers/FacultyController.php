<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FacultyController extends Controller
{
    public function index()
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $faculties = User::whereIn('role', ['professor', 'dept_sec', 'dean'])->get();

        return view('system_admin.manage-faculty', compact('faculties'));
    }

    public function store(Request $request)
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex'         => 'required|string',
            'email'       => 'required|email|unique:employees,email',
            'department'  => 'required|string|max:255',
            'role'        => 'required|string|in:dean,dept_sec,professor',
        ]);

        $yearShort = date('y');
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
            'first_name'  => strtoupper($request->first_name),
            'last_name'   => strtoupper($request->last_name),
            'middle_name' => $request->middle_name ? strtoupper($request->middle_name) : null,
            'sex'         => $request->sex,
            'email'       => $request->email,
            'role'        => $request->role,
            'department'  => $request->department,  
            'status'      => 'Active',
            'password'    => Hash::make('Welcome@AU2026'),
        ]);

        return redirect()->back()->with('success', "Account successfully provisioned! ID: {$generatedId}");
    }

    public function update(Request $request, $id)
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $faculty = User::findOrFail($id);

        $request->validate([
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex'         => 'required|string',
            'email'       => 'required|email|unique:employees,email,' . $faculty->id,
            'department'  => 'required|string|max:255',
            'role'        => 'required|string|in:dean,dept_sec,professor',
        ]);

        $faculty->update([
            'first_name'  => strtoupper($request->first_name),
            'last_name'   => strtoupper($request->last_name),
            'middle_name' => $request->middle_name ? strtoupper($request->middle_name) : null,
            'sex'         => $request->sex,
            'email'       => $request->email,
            'role'        => $request->role,
            'department'  => $request->department,
        ]);

        return redirect()->back()->with('success', "Faculty record for {$faculty->first_name} {$faculty->last_name} updated successfully!");
    }

    public function destroy($id)
    {
        if (session('user_role') !== 'system_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $faculty = User::findOrFail($id);
        $name = $faculty->first_name . ' ' . $faculty->last_name;
        $faculty->delete();

        return redirect()->back()->with('success', "Faculty record for {$name} has been removed from the system.");
    }
}
