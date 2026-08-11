<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // 1. Tignan kung umiiral ang email sa students o employees table
        $userExists = DB::table('students')->where('email', $request->email)->exists() ||
                     DB::table('employees')->where('email', $request->email)->exists();

        if (!$userExists) {
            return back()->with('error', 'We could not find a user with that email address.');
        }

        // 2. Gumawa ng random token
        $token = Str::random(64);

        // 3. I-save o i-update ang token sa password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // 4. Magpadala ng email gamit ang Mail facade
        $resetLink = route('password.reset', ['token' => $token, 'email' => $request->email]);

        Mail::send('emails.reset-password', ['resetLink' => $resetLink], function($message) use($request){
            $message->to($request->email);
            $message->subject('Reset Password Notification - Arellano University');
        });

        return back()->with('status', 'We have e-mailed your password reset link!');
    }

    public function showResetForm(Request $request, $token)
    {
        return view('reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $email = trim($request->email);

        // 1. Suriin kung valid ang token sa password_reset_tokens table
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return back()->with('error', 'Invalid or expired password reset token.');
        }

        // 2. I-hash ang bagong password
        $newPassword = Hash::make($request->password);

        // 3. I-update ang password sa students o employees table
        $studentUpdated = DB::table('students')->where('email', $email)->update(['password' => $newPassword]);
        $employeeUpdated = DB::table('employees')->where('email', $email)->update(['password' => $newPassword]);

        // Kung walang nahanap na email sa alinmang table
        if ($studentUpdated === 0 && $employeeUpdated === 0) {
            return back()->with('error', 'Email does not match any record.');
        }

        // 4. Burahin ang ginamit na reset token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // 5. I-redirect pabalik sa login page na may kasamang success message
        return redirect('/')->with('status', 'Your password has been reset successfully! You can now log in.');
    }
}