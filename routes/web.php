<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SystemAdminController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\CurriculumController;

// ACCOUNT ROUTES
Route::get('/', function () {
    return view('login');
})->name('login');

Route::post('/login-submit', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/bypass-super', function() {
    session(['user_role' => 'super_admin']);
    return redirect()->route('super_admin.manage-employee')->with('success', 'Root Bypass Injection Successful!');
});

Route::get('/account/security', [AuthController::class, 'showSecurityPage'])->name('account.security');
Route::post('/account/security/update-password', [AuthController::class, 'updatePassword'])->name('account.update_password');
Route::post('/account/security/update', [SuperAdminController::class, 'updatePassword'])->name('password.update');

// SUPER ADMIN ROUTES
Route::get('/super_admin/dashboard', function () {
    if (session('user_role') !== 'super_admin') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('super_admin.dashboard');
})->name('super_admin.dashboard');

Route::get('/super_admin/manage-employee', [SuperAdminController::class, 'index'])->name('super_admin.manage-employee');
Route::post('/super_admin/manage-employee', [SuperAdminController::class, 'store'])->name('super_admin.employee.store');
Route::get('/super_admin/user_details', [SuperAdminController::class, 'profileView'])->name('super_admin.user_details');
Route::get('/super_admin/manage-courses', [SuperAdminController::class, 'manageCourses'])->name('super_admin.manage-courses');
Route::post('/super_admin/manage-courses', [SuperAdminController::class, 'storeCourse'])->name('super_admin.courses.store');

// SYSTEM ADMIN ROUTES
Route::get('/system_admin/dashboard', function () {
    if (session('user_role') !== 'system_admin') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('system_admin.dashboard');
})->name('system_admin.dashboard');

Route::get('/system_admin/manage-faculty', [FacultyController::class, 'index'])->name('system_admin.manage-faculty');
Route::post('/system_admin/manage-faculty', [FacultyController::class, 'store'])->name('system_admin.manage-faculty.store');
Route::get('/system_admin/manage-subjects', [SubjectController::class, 'index'])->name('system_admin.manage-subjects');
Route::post('/system_admin/manage-subjects', [SubjectController::class, 'store'])->name('system_admin.subjects.store');
Route::get('/system_admin/user_details', [SystemAdminController::class, 'profileView'])->name('system_admin.user_details');
Route::get('/system_admin/manage-curriculum', [CurriculumController::class, 'index'])->name('system_admin.manage-curriculum');
Route::post('/system_admin/manage-curriculum', [CurriculumController::class, 'store'])->name('system_admin.curriculum.store');

// REGISTRAR ROUTES
Route::group(['middleware' => function ($request, $next) {
    if (session('user_role') !== 'registrar') {
        return redirect()->route('login')->with('error', 'Unauthorized access. Please login as Registrar.');
    }
    return $next($request);
}], function () {

    Route::get('/registrar/dashboard', function () {
        return view('registrar.dashboard');
    })->name('registrar.dashboard');

    Route::get('/registrar/manage-enrollment', function () {
        return view('registrar.manage-enrollment');
    })->name('registrar.manage-enrollment');

    Route::post('/registrar/manage-enrollment', [StudentController::class, 'store'])
         ->name('registrar.enroll.store');

    Route::get('/registrar/masterlist', [StudentController::class, 'index'])
         ->name('registrar.masterlist');

    Route::get('/registrar/user_details', [RegistrarController::class, 'profileView'])->name('registrar.user_details');
});

// DEAN ROUTES
Route::get('/dean/dashboard', function () {
    if (session('user_role') !== 'dean') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('dean.dashboard');
})->name('dean.dashboard');

Route::get('/dean/user_details', [DeanController::class, 'profileView'])->name('dean.user_details');