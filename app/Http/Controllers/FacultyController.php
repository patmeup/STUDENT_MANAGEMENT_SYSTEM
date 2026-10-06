<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewUserCredentials;

class FacultyController extends Controller
{
    public function index()
    {
        $faculties = User::whereIn('role', ['professor', 'dept_sec', 'dean'])->get();

        return view('system_admin.manage-faculty', compact('faculties'));
    }

    public function store(Request $request)
    {
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
        $defaultPassword = 'Welcome@AU2026';

        $faculty = User::create([
            'employee_id' => $generatedId,
            'first_name'  => strtoupper($request->first_name),
            'last_name'   => strtoupper($request->last_name),
            'middle_name' => $request->middle_name ? strtoupper($request->middle_name) : null,
            'sex'         => $request->sex,
            'email'       => $request->email,
            'role'        => $request->role,
            'department'  => $request->department,
            'status'      => 'Active',
            'password'    => Hash::make($defaultPassword),
        ]);

        // NOTIFICATION: super admin sees faculty accounts created by the system admin
        \App\Support\Notifier::toRole('super_admin', 'faculty_created', 'Faculty account created',
            "{$faculty->last_name}, {$faculty->first_name} ({$generatedId}) was added as " . \App\Support\Notifier::roleLabel($faculty->role) . ' by ' . (session('user_name') ?? 'System Admin') . '.',
            \App\Support\Notifier::url('super_admin.manage-employee'));

        try {
            Mail::to($faculty->email)->send(
                new NewUserCredentials($faculty, $defaultPassword, 'employee')
            );
        } catch (\Exception $e) {
            // NOTIFICATION: system admin is told the credential email failed
            \App\Support\Notifier::toEmployee(session('user_id'), 'email_failed', 'Credential email failed',
                "Account {$generatedId} was created but the credentials email to {$faculty->email} could not be sent.",
                \App\Support\Notifier::url('system_admin.manage-faculty'));
            return redirect()->back()->with('success', "Account successfully provisioned! ID: {$generatedId}, but email failed to send: " . $e->getMessage());
        }

        return redirect()->back()->with('success', "Account successfully provisioned! ID: {$generatedId} and credentials sent to email.");
    }

    public function update(Request $request, $id)
    {
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

        $oldRole = $faculty->role;
        $oldDept = $faculty->department;

        $faculty->update([
            'first_name'  => strtoupper($request->first_name),
            'last_name'   => strtoupper($request->last_name),
            'middle_name' => $request->middle_name ? strtoupper($request->middle_name) : null,
            'sex'         => $request->sex,
            'email'       => $request->email,
            'role'        => $request->role,
            'department'  => $request->department,
        ]);

        // NOTIFICATIONS: the faculty member (if role/department changed) + super admin
        if ($oldRole !== $faculty->role || $oldDept !== $faculty->department) {
            \App\Support\Notifier::toEmployee($faculty->id, 'role_changed', 'Your assignment was updated',
                'You are now ' . \App\Support\Notifier::roleLabel($faculty->role) . ' - ' . $faculty->department . '.',
                \App\Support\Notifier::url($faculty->role === 'dean' ? 'dean.dashboard' : ($faculty->role === 'dept_sec' ? 'department_secretary.dashboard' : 'professor.dashboard')));
        }
        \App\Support\Notifier::toRole('super_admin', 'faculty_updated', 'Faculty record updated',
            "{$faculty->last_name}, {$faculty->first_name} was updated by " . (session('user_name') ?? 'System Admin') . '.',
            \App\Support\Notifier::url('super_admin.manage-employee'));

        return redirect()->back()->with('success', "Faculty record for {$faculty->first_name} {$faculty->last_name} updated successfully!");
    }

    public function destroy($id)
    {
        $faculty = User::findOrFail($id);
        $name = $faculty->first_name . ' ' . $faculty->last_name;
        $faculty->delete();

        // NOTIFICATION: super admin is told a faculty record was removed
        \App\Support\Notifier::toRole('super_admin', 'faculty_removed', 'Faculty record removed',
            "{$name} was removed by " . (session('user_name') ?? 'System Admin') . '.',
            \App\Support\Notifier::url('super_admin.manage-employee'));

        return redirect()->back()->with('success', "Faculty record for {$name} has been removed from the system.");
    }
}
