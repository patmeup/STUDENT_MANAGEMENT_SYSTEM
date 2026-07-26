<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use App\Support\GoogleClassroomService;

class GoogleClassroomController extends Controller
{
    public function redirect()
    {
        if (session('user_role') !== 'professor') {
            abort(403, 'Unauthorized access.');
        }

        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/classroom.courses.readonly',
                'https://www.googleapis.com/auth/classroom.rosters.readonly',
                'https://www.googleapis.com/auth/classroom.announcements.readonly',
                'https://www.googleapis.com/auth/classroom.coursework.me',
                'https://www.googleapis.com/auth/classroom.courseworkmaterials.readonly',
                'https://www.googleapis.com/auth/classroom.coursework.students',
                'https://www.googleapis.com/auth/drive.readonly',
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    public function callback()
    {
        if (session('user_role') !== 'professor') {
            return redirect()->route('professor.dashboard')->with('error', 'Session expired or unauthorized access during callback.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('professor.dashboard')->with('error', 'Google authorization failed: ' . $e->getMessage());
        }

        if (!$googleUser->token || !$googleUser->refreshToken) {
            return redirect()->route('professor.dashboard')->with('error', 'Google did not return a refresh token. Please try connecting again.');
        }

        DB::table('google_classroom_connections')->updateOrInsert(
            ['professor_id' => session('user_id')],
            [
                'google_email' => $googleUser->getEmail(),
                'access_token' => $googleUser->token,
                'refresh_token' => $googleUser->refreshToken,
                'token_expires_at' => now()->addSeconds($googleUser->expiresIn ?? 3600),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return redirect()->route('professor.dashboard')->with('success', 'Google Classroom connected successfully!');
    }

    public function syncNow()
    {
        if (session('user_role') !== 'professor') {
            abort(403, 'Unauthorized access.');
        }

        $connection = DB::table('google_classroom_connections')
            ->where('professor_id', session('user_id'))
            ->first();

        if (!$connection) {
            return redirect()->back()->with('error', 'No connected Google Classroom account found.');
        }

        try {
            $service = new GoogleClassroomService($connection);
            $service->syncCourses();

            DB::table('google_classroom_connections')
                ->where('id', $connection->id)
                ->update(['last_synced_at' => now()]);

            return redirect()->back()->with('success', 'Google Classroom data synced successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}