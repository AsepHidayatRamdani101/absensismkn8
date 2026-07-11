<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AttendanceDeviceController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolSettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherSubjectController;
use App\Http\Controllers\TeacherController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');

    Route::get('school-settings', [SchoolSettingController::class, 'index'])->name('school-settings.index');
    Route::put('school-settings', [SchoolSettingController::class, 'update'])->name('school-settings.update');

    Route::resource('majors', MajorController::class)->except(['create', 'show']);
    Route::post('majors/import', [MajorController::class, 'import'])->name('majors.import');
    Route::get('majors/template', [MajorController::class, 'template'])->name('majors.template');
    Route::get('majors/export', [MajorController::class, 'export'])->name('majors.export');

    Route::resource('classrooms', ClassroomController::class);
    Route::post('classrooms/import', [ClassroomController::class, 'import'])->name('classrooms.import');
    Route::get('classrooms/template', [ClassroomController::class, 'template'])->name('classrooms.template');
    Route::get('classrooms/export', [ClassroomController::class, 'export'])->name('classrooms.export');

    Route::resource('students', StudentController::class)->except(['show', 'create']);
    Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('students/template', [StudentController::class, 'template'])->name('students.template');
    Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
    Route::post('students/generate-accounts', [StudentController::class, 'generateAccounts'])->name('students.generate-accounts');

    Route::resource('teachers', TeacherController::class)->except(['show', 'create']);
    Route::post('teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
    Route::get('teachers/template', [TeacherController::class, 'template'])->name('teachers.template');
    Route::get('teachers/export', [TeacherController::class, 'export'])->name('teachers.export');
    Route::post('teachers/generate-accounts', [TeacherController::class, 'generateAccounts'])->name('teachers.generate-accounts');

    Route::resource('subjects', SubjectController::class)->except(['show', 'create']);
    Route::post('subjects/import', [SubjectController::class, 'import'])->name('subjects.import');
    Route::get('subjects/template', [SubjectController::class, 'template'])->name('subjects.template');
    Route::get('subjects/export', [SubjectController::class, 'export'])->name('subjects.export');

    Route::resource('academic-years', AcademicYearController::class)->except(['show', 'create']);
    Route::resource('teacher-subjects', TeacherSubjectController::class)->except(['show', 'create']);
    Route::post('teacher-subjects/import', [TeacherSubjectController::class, 'import'])->name('teacher-subjects.import');
    Route::get('teacher-subjects/template', [TeacherSubjectController::class, 'template'])->name('teacher-subjects.template');
    Route::get('teacher-subjects/export', [TeacherSubjectController::class, 'export'])->name('teacher-subjects.export');
    Route::resource('schedules', ScheduleController::class)->except(['show', 'create']);
    Route::resource('holidays', HolidayController::class)->except(['show', 'create']);
    Route::post('schedules/import', [ScheduleController::class, 'import'])->name('schedules.import');
    Route::get('schedules/template', [ScheduleController::class, 'template'])->name('schedules.template');
    Route::get('schedules/export', [ScheduleController::class, 'export'])->name('schedules.export');
    Route::resource('attendance-devices', AttendanceDeviceController::class)->except(['show', 'create']);
    Route::resource('attendances', AttendanceController::class)->except(['show', 'create']);
    Route::resource('teacher-attendances', TeacherAttendanceController::class)->except(['show', 'create']);
    Route::resource('attendance-details', AttendanceDetailController::class)->except(['show', 'create']);

    Route::get('reports/teacher-attendance', [ReportController::class, 'teacherAttendance'])
        ->name('reports.teacher-attendance');
    Route::get('reports/teacher-attendance/pdf', [ReportController::class, 'teacherAttendancePdf'])
        ->name('reports.teacher-attendance.pdf');
    Route::get('reports/teacher-attendance/excel', [ReportController::class, 'teacherAttendanceExcel'])
        ->name('reports.teacher-attendance.excel');

    Route::get('reports/student-attendance', [ReportController::class, 'studentAttendance'])
        ->name('reports.student-attendance');
    Route::get('reports/student-attendance/pdf', [ReportController::class, 'studentAttendancePdf'])
        ->name('reports.student-attendance.pdf');
    Route::get('reports/student-attendance/excel', [ReportController::class, 'studentAttendanceExcel'])
        ->name('reports.student-attendance.excel');
});

Route::middleware(['auth', 'guru'])->group(function () {
    Route::get('/guru', [DashboardController::class, 'guru'])->name('guru.dashboard');
    Route::get('/guru/attendance-details', [AttendanceDetailController::class, 'guruIndex'])
        ->name('guru.attendance-details.index');
    Route::post('/guru/attendance-details/bulk-submit', [AttendanceDetailController::class, 'submitBulkForGuru'])
        ->name('guru.attendance-details.bulk-submit');
    Route::post('/guru/attendance-details/{student}/submit', [AttendanceDetailController::class, 'submitForGuru'])
        ->name('guru.attendance-details.submit');
});

Route::middleware(['auth', 'siswa'])->group(function () {
    Route::get('/siswa', [DashboardController::class, 'siswa'])->name('siswa.dashboard');
    Route::get('/siswa/teacher-attendances', [TeacherAttendanceController::class, 'siswaIndex'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.teacher-attendances.index');
    Route::post('/siswa/teacher-attendances/{schedule}/submit', [TeacherAttendanceController::class, 'submitForSiswa'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.teacher-attendances.submit');
});

Route::get('/home', function () {
    return redirect()->route('dashboard');
})->name('home');

require __DIR__ . '/auth.php';
