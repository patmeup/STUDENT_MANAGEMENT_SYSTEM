<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\CurriculumController;

Route::get('/', function () {
    return view('login');
})->name('login');

Route::post('/login-submit', [AuthController::class, 'login'])->name('login.submit');

Route::get('/super_admin/dashboard', function () {
    if (session('user_role') !== 'super_admin') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('super_admin.dashboard');
})->name('super_admin.dashboard');

Route::get('/super_admin/manage-employee', [SuperAdminController::class, 'index'])->name('super_admin.manage-employee');
Route::post('/super_admin/manage-employee', [SuperAdminController::class, 'store'])->name('super_admin.employee.store');

Route::get('/system_admin/dashboard', function () {
    if (session('user_role') !== 'system_admin') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('system_admin.dashboard');
})->name('system_admin.dashboard');

Route::get('/system_admin/manage-subjects', [SubjectController::class, 'index'])->name('system_admin.manage-subjects');
Route::post('/system_admin/manage-subjects', [SubjectController::class, 'store'])->name('system_admin.subjects.store');

Route::get('/registrar/dashboard', function () {
    if (session('user_role') !== 'registrar') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('registrar.dashboard');
})->name('registrar.dashboard');

Route::get('/registrar/manage-enrollment', function () {
    if (session('user_role') !== 'registrar') {
        return redirect()->route('login')->with('error', 'Access denied.');
    }
    return view('registrar.manage-enrollment');
})->name('registrar.manage-enrollment');

Route::post('/registrar/manage-enrollment', [StudentController::class, 'store'])->name('registrar.enroll.store');
Route::get('/registrar/masterlist', [StudentController::class, 'index'])->name('registrar.masterlist');

Route::get('/dean/dashboard', function () {
    if (session('user_role') !== 'dean') {
        return redirect('/')->with('error', 'Unauthorized access.');
    }
    return view('dean.dashboard');
})->name('dean.dashboard');

Route::get('/dean/encoding', [CurriculumController::class, 'index'])->name('dean.curriculum.index');
Route::post('/dean/encoding', [CurriculumController::class, 'store'])->name('dean.curriculum.store');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');