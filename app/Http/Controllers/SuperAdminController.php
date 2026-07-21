<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
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
                ->withErrors(['error' => 'Access Restricted!']);
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
            'department' => 'required_if:role,dean|string|nullable|max:255',
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
            'first_name' => strtoupper($request->first_name),
            'last_name' => strtoupper($request->last_name),
            'middle_name' => $request->middle_name ? strtoupper($request->middle_name) : null,
            'sex' => $request->sex,
            'email' => $request->email,
            'role' => $request->role,
            'department' => $request->role === 'dean' ? $request->department : null,
            'status' => 'Active',
            'password' => 'Welcome@AU2026',
        ]);

        return redirect()->back()->with('success', "Personnel account successfully created! ID: {$generatedId} | Default Pass: Welcome@AU2026");
    }

    public function toggleEmployeeStatus($id)
{
    // 1. Siguraduhing super_admin lang ang may karapatang pumindot nito
    if (session('user_role') !== 'super_admin') {
        return redirect()->back()->withErrors(['error' => 'You do not have the required permissions for this action.']);
    }

    $employee = \App\Models\User::findOrFail($id);

    // 2. SECURITY GUARD: Bawal i-disable ang sarili o ang ibang kapwa Super Admin
    // I-match natin gamit ang email o primary id mula sa kasalukuyang session mo
    if ($employee->email === session('user_email') || $employee->role === 'super_admin') {
        return redirect()->back()->with('error', 'For system security, you are not permitted to disable a Super Admin account.');
    }

    // 3. I-toggle ang status (Gawing kabaligtaran ng kasalukuyan)
    // Ginawa nating case-insensitive (.toLowerCase type approach) gamit ang ucfirst para malinis sa DB
    if (ucfirst($employee->status) === 'Active') {
        $employee->status = 'Disabled';
        $message = "The account of {$employee->first_name} {$employee->last_name} has been successfully locked out of the system.";
    } else {
        $employee->status = 'Active';
        $message = "The account of {$employee->first_name} {$employee->last_name} has been successfully restored.";
    }

    $employee->save();

    return redirect()->back()->with('success', $message);
}

    public function manageCourses()
    {
        if (session('user_role') !== 'super_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $courses = Course::all();
        return view('super_admin.manage-courses', compact('courses'));
    }

    public function updateCourse(Request $request, $id)
    {
        $request->validate([
            'course_code'  => 'required|string|max:20',
            'department'   => 'required|string|max:50',
            'course_title' => 'required|string|max:255',
        ]);

        // Gamitin kung anong pangalan ng Model mo (halimbawa ay Course)
        $course = \App\Models\Course::findOrFail($id); 
        $course->update([
            'course_code'  => strtoupper($request->course_code),
            'department'   => strtoupper($request->department),
            'course_title' => $request->course_title,
        ]);

        return redirect()->back()->with('success', 'Course registry blueprint successfully modified.');
    }

    public function storeCourse(Request $request)
    {
        if (session('user_role') !== 'super_admin') {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        $validated = $request->validate([
            'course_code'  => 'required|string|unique:courses,course_code|max:50',
            'course_title' => 'required|string|max:255',
            'department'   => 'required|string|max:100',
        ]);

        $validated['course_code'] = strtoupper($request->course_code);
        $validated['department'] = strtoupper($request->department);

        Course::create($validated);

        return redirect()->back()->with('success', 'New institutional course program successfully registered!');
    }

    public function profileView()
    {
        $userId = session('user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect('/')->withErrors(['error' => 'Session expired. Please login again.']);
        }

        $isLocked = !Str::startsWith($user->password, '$2y$');

        return view('super_admin.user_details', compact('user', 'isLocked'));
    }    

    public function updatePassword(Request $request)
    {
        $userId = session('user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect('/')->withErrors(['error' => 'Session expired. Please login again.']);
        }

        if (session('user_role') === 'super_admin') {
            $minCharacters = 8;
        } else {
            $minCharacters = 6;
        }

        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password) && $value !== $user->password) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => ['required', 'string', 'min:' . $minCharacters, 'confirmed'],
        ], [
            'password.min' => "The new password must be at least {$minCharacters} characters.",
            'password.confirmed' => 'The new password and confirmation do not match.',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->back()->with('success', 'Password successfully updated security requirements!');
    }
}