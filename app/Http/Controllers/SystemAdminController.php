<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Subject;
use App\Models\Course;

class SystemAdminController extends Controller
{

    public function dashboard()
    {
        $announcements = DB::table('announcements')
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

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

    public function academicPeriods()
    {
        $periods = DB::table('academic_periods')->orderBy('created_at', 'desc')->get();
        $termPeriods = DB::table('term_periods')->get()->groupBy('academic_period_id');

        $curriculumSubjects = DB::table('curricula')
            ->join('subjects', 'curricula.subject_id', '=', 'subjects.id')
            ->select(
                'curricula.semester',
                'curricula.year_level',
                'curricula.course as course_code',
                'subjects.subject_code',
                'subjects.subject_title'
            )
            ->get()
            ->groupBy('semester');

        return view('system_admin.academic_periods', compact('periods', 'termPeriods', 'curriculumSubjects'));
    }

    public function storeAcademicPeriod(Request $request)
    {
        $request->validate([
            'school_year' => 'required|string|max:20',
            'semester' => 'required|string|max:30',
        ]);

        DB::table('academic_periods')->insert([
            'school_year' => $request->school_year,
            'semester' => $request->semester,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Support\Notifier::toRole('super_admin', 'period_created', 'Academic period created',
            "{$request->school_year} - {$request->semester} was created by " . (session('user_name') ?? 'System Admin') . '.',
            \App\Support\Notifier::url('super_admin.dashboard'));

        return redirect()->back()->with('success', 'Academic period created. Set the term dates and activate it when ready.');
    }

    public function activateAcademicPeriod($id)
    {
        // Block activation unless all 4 terms have both start and end dates saved
        $completeTerms = DB::table('term_periods')
            ->where('academic_period_id', $id)
            ->whereIn('term', ['prelim', 'midterm', 'semifinal', 'final'])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->pluck('term')
            ->unique();

        if ($completeTerms->count() < 4) {
            return redirect()->back()->with('error', 'Cannot activate this period yet. Please save the start and end dates for all terms (Prelim, Midterm, Semi-Final, Final) first.');
        }

        // Deactivate all periods first, then activate the selected one
        DB::table('academic_periods')->update(['is_active' => false]);
        DB::table('academic_periods')->where('id', $id)->update(['is_active' => true]);

        // NOTIFICATION: EVERYONE is told a new academic period is active
        $__p = DB::table('academic_periods')->where('id', $id)->first();
        \App\Support\Notifier::toAll('period_activated', 'New academic period is now active',
            $__p ? "{$__p->school_year} - {$__p->semester} is now the active academic period." : 'A new academic period is now active.');

        return redirect()->back()->with('success', 'Academic period activated. System data is now synced to this active period.');
    }

    public function storeTermPeriod(Request $request)
    {
        $request->validate([
            'academic_period_id' => 'required|integer|exists:academic_periods,id',
            'term' => 'required|in:prelim,midterm,semifinal,final',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $existing = DB::table('term_periods')
            ->where('academic_period_id', $request->academic_period_id)
            ->where('term', $request->term)
            ->first();

        if ($existing) {
            DB::table('term_periods')->where('id', $existing->id)->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('term_periods')->insert([
                'academic_period_id' => $request->academic_period_id,
                'term' => $request->term,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $__p = DB::table('academic_periods')->where('id', $request->academic_period_id)->first();
        \App\Support\Notifier::toRole('super_admin', 'term_dates_saved', 'Term dates updated',
            ucfirst($request->term) . ' dates (' . $request->start_date . ' to ' . $request->end_date . ')'
                . ($__p ? " saved for {$__p->school_year} - {$__p->semester}" : '') . ' by ' . (session('user_name') ?? 'System Admin') . '.',
            \App\Support\Notifier::url('super_admin.dashboard'));

        return redirect()->back()->with('success', 'Term dates saved.');
    }

    public function deleteAcademicPeriod($id)
    {
        $__p = DB::table('academic_periods')->where('id', $id)->first();
        DB::table('academic_periods')->where('id', $id)->delete();

        \App\Support\Notifier::toRole('super_admin', 'period_deleted', 'Academic period deleted',
            ($__p ? "{$__p->school_year} - {$__p->semester}" : 'An academic period') . ' was deleted by ' . (session('user_name') ?? 'System Admin') . '.',
            \App\Support\Notifier::url('super_admin.dashboard'));

        return redirect()->back()->with('success', 'Academic period deleted.');
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

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::find(session('user_id'));

        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');

        $user->profile_picture = $path;
        $user->save();

        return redirect()->back()->with('success', 'Profile picture updated successfully!');
    }

    public function updateFaculty(Request $request, $id)
    {
        // Role at Department na lang ang dapat i-validate
        $request->validate([
            'role'       => 'required|string|in:dean,dept_sec,professor',
            'department' => 'required|string|max:255',
        ]);

        $faculty = User::findOrFail($id);

        // Role at Department lang ang babaguhin sa DB
        $faculty->role       = $request->role;
        $faculty->department = $request->department;

        $faculty->save();

        return redirect()->back()->with('success', 'Faculty assignment and role successfully updated!');
    }

}
