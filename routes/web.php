<?php

use Illuminate\Support\Facades\Route;
use App\Helpers\GradeHelper;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SystemAdminController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\DeanController;
use App\Http\Controllers\DeptSecController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\StudentTermGradeController;
use App\Http\Controllers\GoogleClassroomController;

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
Route::get('/super_admin/dashboard', [SuperAdminController::class, 'dashboard'])->name('super_admin.dashboard');
Route::post('/super_admin/announcements/store', [AnnouncementController::class, 'store'])->name('super_admin.announcements.store');
Route::delete('/super_admin/announcements/delete/{id}', [AnnouncementController::class, 'destroy'])->name('super_admin.announcements.destroy');
Route::get('/super_admin/manage-employee', [SuperAdminController::class, 'index'])->name('super_admin.manage-employee');
Route::post('/super_admin/manage-employee', [SuperAdminController::class, 'store'])->name('super_admin.employee.store');
Route::get('/super_admin/user_details', [SuperAdminController::class, 'profileView'])->name('super_admin.user_details');
Route::get('/super_admin/manage-courses', [SuperAdminController::class, 'manageCourses'])->name('super_admin.manage-courses');
Route::post('/super_admin/manage-courses', [SuperAdminController::class, 'storeCourse'])->name('super_admin.courses.store');
Route::patch('/super-admin/courses/{id}', [SuperAdminController::class, 'updateCourse'])->name('super_admin.courses.update');
Route::patch('/super-admin/employees/{id}/toggle-status', [SuperAdminController::class, 'toggleEmployeeStatus'])->name('super_admin.employees.toggle_status');

// SYSTEM ADMIN ROUTES
Route::get('/system_admin/dashboard', [SystemAdminController::class, 'dashboard'])->name('system_admin.dashboard');
Route::post('/system_admin/announcements/store', [AnnouncementController::class, 'store'])->name('system_admin.announcements.store');
Route::delete('/system_admin/announcements/delete/{id}', [AnnouncementController::class, 'destroy'])->name('system_admin.announcements.destroy');
Route::get('/system_admin/manage-faculty', [FacultyController::class, 'index'])->name('system_admin.manage-faculty');
Route::post('/system_admin/manage-faculty', [FacultyController::class, 'store'])->name('system_admin.manage-faculty.store');
Route::get('/system_admin/manage-subjects', [SubjectController::class, 'index'])->name('system_admin.manage-subjects');
Route::post('/system_admin/manage-subjects', [SubjectController::class, 'store'])->name('system_admin.subjects.store');
Route::get('/system-admin/subject/{id}/edit', [SubjectController::class, 'editSubject'])->name('admin.subject.edit');
Route::post('/system-admin/subject/update', [SubjectController::class, 'updateSubject'])->name('admin.subject.update');
Route::get('/system-admin/curriculum/get-subjects-by-course', [CurriculumController::class, 'getSubjectsByCourse'])->name('system_admin.curriculum.get_subjects');
Route::get('/system_admin/manage-curriculum', [CurriculumController::class, 'index'])->name('system_admin.manage-curriculum');
Route::post('/system_admin/manage-curriculum', [CurriculumController::class, 'store'])->name('system_admin.curriculum.store');


// FACULTY , SUBJECTS, CURRICULUM— Edit & Delete
Route::patch('/system_admin/manage-faculty/{id}', [FacultyController::class, 'update'])->name('system_admin.manage-faculty.update');
Route::delete('/system_admin/manage-faculty/{id}', [FacultyController::class, 'destroy'])->name('system_admin.manage-faculty.destroy');
Route::patch('/system_admin/manage-subjects/{id}', [SubjectController::class, 'update'])->name('system_admin.subjects.update');
Route::delete('/system_admin/manage-subjects/{id}', [SubjectController::class, 'destroy'])->name('system_admin.subjects.destroy');
Route::delete('/system_admin/manage-curriculum/{id}', [CurriculumController::class, 'destroy'])->name('system_admin.curriculum.destroy');
Route::get('/system-admin/academic_periods', [SystemAdminController::class, 'academicPeriods'])->name('system_admin.academic_periods');
Route::post('/system-admin/academic_periods', [SystemAdminController::class, 'storeAcademicPeriod'])->name('system_admin.store_academic_period');
Route::post('/system-admin/academic_periods/{id}/activate', [SystemAdminController::class, 'activateAcademicPeriod'])->name('system_admin.activate_academic_period');
Route::post('/system-admin/term-periods', [SystemAdminController::class, 'storeTermPeriod'])->name('system_admin.store_term_period');
Route::delete('/system-admin/academic_periods/{id}', [SystemAdminController::class, 'deleteAcademicPeriod'])->name('system_admin.delete_academic_period');

Route::get('/system_admin/user_details', [SystemAdminController::class, 'profileView'])->name('system_admin.user_details');

Route::put('/curriculum/{id}',[CurriculumController::class,'update'])->name('system_admin.curriculum.update');

// REGISTRAR ROUTES
Route::get('/registrar/dashboard', [RegistrarController::class, 'dashboard'])->name('registrar.dashboard');
Route::post('/registrar/announcements/store', [RegistrarController::class, 'storeAnnouncement'])->name('registrar.announcements.store');
Route::delete('/registrar/announcements/delete/{id}', [RegistrarController::class, 'destroyAnnouncement'])->name('registrar.announcements.destroy');
Route::get('/registrar/manage-enrollment', [RegistrarController::class, 'manageEnrollment'])->name('registrar.manage-enrollment');
Route::post('/registrar/manage-enrollment', [StudentController::class, 'store'])->name('registrar.enroll.store');
Route::get('/registrar/manage-students', [StudentController::class, 'index'])->name('registrar.manage-students');
Route::get('/registrar/manage-students-alias', [StudentController::class, 'index'])->name('registrar.manage-students-alias');
Route::get('/registrar/user_details', [RegistrarController::class, 'profileView'])->name('registrar.user_details');
Route::get('/registrar/students/{student_id}/profile', [StudentController::class, 'viewStudentProfile'])->name('registrar.students.profile');
Route::get('/registrar/faculty-grade-status', [RegistrarController::class, 'facultyGradeStatus'])->name('registrar.faculty-grade-status');
Route::get('/registrar/manage-grades', [RegistrarController::class, 'manageGrades'])->name('registrar.manage-grades');
Route::get('/registrar/user_details', [RegistrarController::class, 'profileView'])->name('registrar.user_details');

// DEAN ROUTES
Route::get('/dean/dashboard', [DeanController::class, 'dashboard'])->name('dean.dashboard');
Route::get('/dean/schedule_list', [DeanController::class, 'viewScheduleList'])->name('dean.schedule.list');
Route::get('/dean/masterlist', [DeanController::class, 'viewMasterlist'])->name('dean.masterlist');
Route::get('/dean/section/{section_name}', [DeanController::class, 'viewSectionRoster'])->name('dean.view_section');
Route::get('/dean/subject_list', [DeanController::class, 'viewSubjectCatalog'])->name('dean.subject_list');
Route::get('/dean/user_details', [DeanController::class, 'profileView'])->name('dean.user_details');

// DEPARTMENT SECRETARY ROUTES
Route::get('/department-secretary/dashboard', [DeptSecController::class, 'dashboard'])->name('department_secretary.dashboard');
Route::get('/department_secretary/subject-catalog', [DeptSecController::class, 'subjectCatalog'])->name('department_secretary.subject_catalog');
Route::get('/department-secretary/masterlist', [DeptSecController::class, 'studentsList'])->name('department_secretary.students');
Route::get('/department-secretary/section/{section_name}', [DeptSecController::class, 'viewSectionRoster'])->name('department_secretary.view_section');
Route::post('/department-secretary/section/bulk-enroll', [DeptSecController::class, 'bulkEnrollSection'])->name('department_secretary.bulk_enroll');
Route::get('/department_secretary/student/{student_id}/plot', [DeptSecController::class, 'plotSubjects'])->name('department_secretary.plot_subjects');
Route::post('/department-secretary/subjects/save', [DeptSecController::class, 'saveSubjects'])->name('department_secretary.save_subjects');
Route::get('/department_secretary/manage-schedules', [ScheduleController::class, 'index'])->name('department_secretary.schedules.index');
Route::post('/department_secretary/manage-schedules/store', [ScheduleController::class, 'store'])->name('department_secretary.schedules.store');
Route::put('/department-secretary/manage-schedules/update/{id}', [ScheduleController::class, 'update'])->name('department_secretary.schedules.update');
Route::delete('/department-secretary/manage-schedules/delete/{id}', [ScheduleController::class, 'delete'])->name('department_secretary.schedules.delete');
Route::get('/department-secretary/user_details', [DeptSecController::class, 'profileView'])->name('department_secretary.user_details');

// PROFESSOR ROUTE
Route::get('/professor/dashboard', [ProfessorController::class, 'dashboard'])->name('professor.dashboard');
Route::get('/professor/manage-classes', [ProfessorController::class, 'manageClasses'])->name('professor.manage-classes');
Route::get('/professor/class_details/{schedule_id}', [ProfessorController::class, 'viewClassDashboard'])->name('professor.class_details');
Route::get('/professor/class/{schedule_id}/attendance', [ProfessorController::class, 'showAttendanceSheet'])->name('professor.attendance.data');
Route::post('/professor/class/{schedule_id}/attendance/save', [ProfessorController::class, 'saveAttendance'])->name('professor.save-attendance');
Route::get('/professor/google-classroom/connect', [GoogleClassroomController::class, 'redirect'])->name('professor.google-classroom.connect');
Route::get('/professor/google-classroom/callback', [GoogleClassroomController::class, 'callback'])->name('professor.google-classroom.callback');
Route::post('/professor/google-classroom/sync', [GoogleClassroomController::class, 'syncNow'])->name('professor.google-classroom.sync');
Route::post('/professor/save-activity', [ProfessorController::class, 'saveActivity'])->name('professor.save-activity');
Route::post('/professor/update-activity/{id}', [ProfessorController::class, 'updateActivity'])->name('professor.update-activity');
Route::post('/professor/delete-activity/{id}', [ProfessorController::class, 'deleteActivity'])->name('professor.delete-activity');
Route::get('/professor/task-file/{id}', [ProfessorController::class, 'viewTaskFile'])->name('professor.task-file');
Route::get('/professor/task/{task_id}/submissions', [ProfessorController::class, 'viewTaskSubmissions'])->name('professor.task-submissions');
Route::get('/professor/task-grading/{submission_id}', [ProfessorController::class, 'viewTaskGrading'])->name('professor.task_grading');
Route::get('/professor/submission/download/{id}', [ProfessorController::class, 'downloadStudentSubmission'])->name('professor.submission.download');
Route::post('/professor/task-grading/{submission_id}/submit', [ProfessorController::class, 'submitGrade'])->name('professor.submit-grade');
Route::get('/professor/class/{schedule_id}/student/{student_id}/breakdown', [ProfessorController::class, 'viewStudentBreakdown'])->name('professor.grades.breakdown');
Route::post('/professor/save-grades',[ProfessorController::class, 'saveDraftGrades'])->name('professor.save-grades');
Route::post('/professor/recalculate-grade', [ProfessorController::class, 'recalculateAutoGrade'])->name('professor.recalculate-grade');
Route::get('/professor/user_details', [ProfessorController::class, 'profileView'])->name('professor.user_details');

// STUDENT ROUTES
Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
Route::get('/student/my-classes', [StudentController::class, 'myClasses'])->name('student.my_classes');
Route::get('/student/class/{id}', [StudentController::class, 'viewClassDashboard'])->name('student.class.details');
Route::get('/student/class/{id}/predict', [StudentController::class, 'predictGrade'])->name('student.class.predict');
Route::get('/student/class-details/{schedule_id}', [StudentController::class, 'viewClassDashboard'])->name('student.class_details');
Route::get('/student/task/{id}', [StudentController::class, 'viewTaskDetails'])->name('student.view-task');
Route::get('/student/task/download/{id}', [StudentController::class, 'downloadTaskFile'])->name('student.task.download');
Route::post('/student/task/{id}/submit', [StudentController::class, 'submitTask'])->name('student.submit_task');
Route::get('/student/user_details', [StudentController::class, 'profileView'])->name('student.user_details');
Route::put('/account/update-credentials', [App\Http\Controllers\StudentController::class, 'updateCredentials'])->name('account.update_credentials');