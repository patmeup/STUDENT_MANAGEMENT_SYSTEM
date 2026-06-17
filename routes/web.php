<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\RegistrarController;

Route::get('/', function () {
    return view('login');
})->name('login');

Route::post('/login-submit', [AuthController::class, 'login'])->name('login.submit');

Route::get('/bypass-super', function() {
    session(['user_role' => 'super_admin']);
    return redirect()->route('super_admin.manage-employee')->with('success', 'Root Bypass Injection Successful!');
});

Route::get('/account/security', [AuthController::class, 'showSecurityPage'])->name('account.security');
Route::post('/account/security/update-password', [AuthController::class, 'updatePassword'])->name('account.update_password');

Route::get('/super_admin/dashboard', function () {
    if (session('user_role') !== 'super_admin') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('super_admin.dashboard');
})->name('super_admin.dashboard');

Route::get('/super_admin/manage-employee', [SuperAdminController::class, 'index'])->name('super_admin.manage-employee');
Route::post('/super_admin/manage-employee', [SuperAdminController::class, 'store'])->name('super_admin.employee.store');
Route::get('/super_admin/user_details', [SuperAdminController::class, 'profileView'])->name('super_admin.user_details');

Route::get('/system_admin/dashboard', function () {
    if (session('user_role') !== 'system_admin') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('system_admin.dashboard');
})->name('system_admin.dashboard');

Route::get('/system_admin/manage-subjects', [SubjectController::class, 'index'])->name('system_admin.manage-subjects');
Route::post('/system_admin/manage-subjects', [SubjectController::class, 'store'])->name('system_admin.subjects.store');

Route::group(['middleware' => function ($request, $next) {
    if (session('user_role') !== 'registrar') {
        return redirect()->route('login')->with('error', 'Unauthorized access. Please login as Registrar.');
    }
    return $next($request);
}], function () {

    // 1. Registrar Main Dashboard Home View
    Route::get('/registrar/dashboard', function () {
        return view('registrar.dashboard');
    })->name('registrar.dashboard');

    // 2. Enrollment Form Entry UI Panel
    Route::get('/registrar/manage-enrollment', function () {
        return view('registrar.manage-enrollment');
    })->name('registrar.manage-enrollment');

    // 3. Database Capture & Secure Upload Process
    Route::post('/registrar/manage-enrollment', [StudentController::class, 'store'])
         ->name('registrar.enroll.store');

    // 4. Registrar Active Student Registry Ledger (Masterlist View)
    Route::get('/registrar/masterlist', [StudentController::class, 'index'])
         ->name('registrar.masterlist');

});

Route::get('/dean/dashboard', function () {
    if (session('user_role') !== 'dean') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('dean.dashboard');
})->name('dean.dashboard');

Route::get('/dean/encoding', [CurriculumController::class, 'index'])->name('dean.curriculum.index');
Route::post('/dean/encoding', [CurriculumController::class, 'store'])->name('dean.curriculum.store');
Route::post('/account/security/update', [SuperAdminController::class, 'updatePassword'])->name('password.update');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');