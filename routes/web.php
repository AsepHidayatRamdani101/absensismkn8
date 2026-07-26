<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AttendanceDeviceController;
use App\Http\Controllers\GuruAgendaController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolSettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentLeaveRequestController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherLeaveRequestController;
use App\Http\Controllers\TeacherSubjectController;
use App\Http\Controllers\TeacherController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::middleware('admin')->group(function () {
        Route::get('/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');

        Route::get('school-settings', [SchoolSettingController::class, 'index'])->name('school-settings.index');
        Route::put('school-settings', [SchoolSettingController::class, 'update'])->name('school-settings.update');

        Route::resource('majors', MajorController::class)->except(['create', 'show']);
        Route::post('majors/import', [MajorController::class, 'import'])->name('majors.import');
        Route::get('majors/template', [MajorController::class, 'template'])->name('majors.template');
        Route::get('majors/export', [MajorController::class, 'export'])->name('majors.export');

        Route::post('classrooms/import', [ClassroomController::class, 'import'])->name('classrooms.import');
        Route::get('classrooms/template', [ClassroomController::class, 'template'])->name('classrooms.template');
        Route::get('classrooms/export', [ClassroomController::class, 'export'])->name('classrooms.export');
        Route::resource('classrooms', ClassroomController::class);

        Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
        Route::get('students/template', [StudentController::class, 'template'])->name('students.template');
        Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
        Route::post('students/generate-accounts', [StudentController::class, 'generateAccounts'])->name('students.generate-accounts');
        Route::delete('students/destroy-multiple', [StudentController::class, 'destroyMultiple'])->name('students.destroy-multiple');
        Route::resource('students', StudentController::class)->except(['show', 'create']);

        Route::post('teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
        Route::get('teachers/template', [TeacherController::class, 'template'])->name('teachers.template');
        Route::get('teachers/export', [TeacherController::class, 'export'])->name('teachers.export');
        Route::post('teachers/generate-accounts', [TeacherController::class, 'generateAccounts'])->name('teachers.generate-accounts');
        Route::delete('teachers/destroy-multiple', [TeacherController::class, 'destroyMultiple'])->name('teachers.destroy-multiple');
        Route::resource('teachers', TeacherController::class)->except(['show', 'create']);

        Route::post('subjects/import', [SubjectController::class, 'import'])->name('subjects.import');
        Route::get('subjects/template', [SubjectController::class, 'template'])->name('subjects.template');
        Route::get('subjects/export', [SubjectController::class, 'export'])->name('subjects.export');
        Route::delete('subjects/destroy-multiple', [SubjectController::class, 'destroyMultiple'])->name('subjects.destroy-multiple');
        Route::resource('subjects', SubjectController::class)->except(['show', 'create']);

        Route::resource('academic-years', AcademicYearController::class)->except(['show', 'create']);
        Route::resource('attendance-devices', AttendanceDeviceController::class)->except(['show', 'create']);
        Route::resource('attendances', AttendanceController::class)->except(['show', 'create']);
    });

    Route::middleware('role:admin|kurikulum')->group(function () {
        Route::post('teacher-subjects/import', [TeacherSubjectController::class, 'import'])->name('teacher-subjects.import');
        Route::get('teacher-subjects/template', [TeacherSubjectController::class, 'template'])->name('teacher-subjects.template');
        Route::get('teacher-subjects/export', [TeacherSubjectController::class, 'export'])->name('teacher-subjects.export');
        Route::delete('teacher-subjects/destroy-multiple', [TeacherSubjectController::class, 'destroyMultiple'])->name('teacher-subjects.destroy-multiple');
        Route::get('teacher-subjects/classrooms/{teacher}/{subject}/{academicYear}', [TeacherSubjectController::class, 'getClassrooms'])->name('teacher-subjects.classrooms');
        Route::get('teacher-subjects/search-teachers', [TeacherSubjectController::class, 'searchTeachers'])->name('teacher-subjects.search-teachers');
        Route::get('teacher-subjects/search-subjects', [TeacherSubjectController::class, 'searchSubjects'])->name('teacher-subjects.search-subjects');
        Route::get('teacher-subjects/search-classrooms', [TeacherSubjectController::class, 'searchClassrooms'])->name('teacher-subjects.search-classrooms');
        Route::get('teacher-subjects/get-teacher/{id}', [TeacherSubjectController::class, 'getTeacherById'])->name('teacher-subjects.get-teacher');
        Route::get('teacher-subjects/get-subject/{id}', [TeacherSubjectController::class, 'getSubjectById'])->name('teacher-subjects.get-subject');
        Route::get('teacher-subjects/get-classroom/{id}', [TeacherSubjectController::class, 'getClassroomById'])->name('teacher-subjects.get-classroom');
        Route::get('teacher-subjects/assigned-classrooms', [TeacherSubjectController::class, 'getAssignedClassrooms'])->name('teacher-subjects.assigned-classrooms');
        Route::get('teacher-subjects/full-subjects', [TeacherSubjectController::class, 'getFullSubjects'])->name('teacher-subjects.full-subjects');
        Route::resource('teacher-subjects', TeacherSubjectController::class)->except(['show', 'create']);
        Route::resource('schedules', ScheduleController::class)->except(['show', 'create']);
        Route::resource('holidays', HolidayController::class)->except(['show', 'create']);
        Route::post('schedules/import', [ScheduleController::class, 'import'])->name('schedules.import');
        Route::get('schedules/template', [ScheduleController::class, 'template'])->name('schedules.template');
        Route::get('schedules/export', [ScheduleController::class, 'export'])->name('schedules.export');
        Route::resource('teacher-attendances', TeacherAttendanceController::class)->except(['show', 'create']);
        Route::delete('attendance-details/destroy-multiple', [AttendanceDetailController::class, 'destroyMultiple'])
            ->name('attendance-details.destroy-multiple');
        Route::resource('attendance-details', AttendanceDetailController::class)->except(['show', 'create']);

        Route::get('kurikulum/guru-leave-requests', [TeacherLeaveRequestController::class, 'approvalIndex'])
            ->name('kurikulum.teacher-leave-requests.index');
        Route::post('kurikulum/guru-leave-requests/{teacherLeaveRequest}/approve', [TeacherLeaveRequestController::class, 'approve'])
            ->name('kurikulum.teacher-leave-requests.approve');
        Route::post('kurikulum/guru-leave-requests/{teacherLeaveRequest}/reject', [TeacherLeaveRequestController::class, 'reject'])
            ->name('kurikulum.teacher-leave-requests.reject');

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
        Route::get('reports/teacher-agenda', [ReportController::class, 'teacherAgenda'])
            ->name('reports.teacher-agenda');
        Route::get('reports/teacher-agenda/pdf', [ReportController::class, 'teacherAgendaPdf'])
            ->name('reports.teacher-agenda.pdf');
        Route::get('reports/teacher-agenda/excel', [ReportController::class, 'teacherAgendaExcel'])
            ->name('reports.teacher-agenda.excel');
        Route::get('reports/teacher-leave', [ReportController::class, 'teacherLeave'])
            ->name('reports.teacher-leave');
        Route::get('reports/teacher-leave/pdf', [ReportController::class, 'teacherLeavePdf'])
            ->name('reports.teacher-leave.pdf');
        Route::get('reports/teacher-leave/excel', [ReportController::class, 'teacherLeaveExcel'])
            ->name('reports.teacher-leave.excel');
        Route::get('reports/student-leave', [ReportController::class, 'studentLeave'])
            ->name('reports.student-leave');
        Route::get('reports/student-leave/pdf', [ReportController::class, 'studentLeavePdf'])
            ->name('reports.student-leave.pdf');
        Route::get('reports/student-leave/excel', [ReportController::class, 'studentLeaveExcel'])
            ->name('reports.student-leave.excel');
    });
});

Route::middleware(['auth', 'kurikulum'])->group(function () {
    Route::get('/kurikulum', [DashboardController::class, 'kurikulum'])->name('kurikulum.dashboard');
});

Route::middleware(['auth', 'guru'])->group(function () {
    Route::get('/guru', [DashboardController::class, 'guru'])->name('guru.dashboard');
    Route::get('/guru/agenda', [GuruAgendaController::class, 'index'])->name('guru.agenda.index');
    Route::post('/guru/agenda/{schedule}', [GuruAgendaController::class, 'store'])->name('guru.agenda.store');
    Route::get('/guru/attendance-details', [AttendanceDetailController::class, 'guruIndex'])
        ->name('guru.attendance-details.index');
    Route::get('/guru/pengajuan', [TeacherLeaveRequestController::class, 'index'])
        ->name('guru.leave-requests.index');
    Route::post('/guru/pengajuan', [TeacherLeaveRequestController::class, 'store'])
        ->name('guru.leave-requests.store');
    Route::put('/guru/pengajuan/{teacherLeaveRequest}', [TeacherLeaveRequestController::class, 'update'])
        ->name('guru.leave-requests.update');
    Route::delete('/guru/pengajuan/{teacherLeaveRequest}', [TeacherLeaveRequestController::class, 'destroy'])
        ->name('guru.leave-requests.destroy');
    Route::get('/guru/rekap-mapel', [ReportController::class, 'guruMapelRecap'])
        ->name('guru.mapel.rekap');
    Route::get('/guru/rekap-mapel/pdf', [ReportController::class, 'guruMapelRecapPdf'])
        ->name('guru.mapel.rekap.pdf');
    Route::get('/guru/rekap-mapel/excel', [ReportController::class, 'guruMapelRecapExcel'])
        ->name('guru.mapel.rekap.excel');
    Route::get('/guru/wali-kelas/rekap-siswa', [ReportController::class, 'guruWaliKelasRecap'])
        ->middleware('can:guru-wali-kelas')
        ->name('guru.wali-kelas.rekap-siswa');
    Route::get('/guru/wali-kelas/rekap-siswa/pdf', [ReportController::class, 'guruWaliKelasRecapPdf'])
        ->middleware('can:guru-wali-kelas')
        ->name('guru.wali-kelas.rekap-siswa.pdf');
    Route::get('/guru/wali-kelas/rekap-siswa/excel', [ReportController::class, 'guruWaliKelasRecapExcel'])
        ->middleware('can:guru-wali-kelas')
        ->name('guru.wali-kelas.rekap-siswa.excel');
    Route::get('/guru/wali-kelas/pengajuan-siswa', [StudentLeaveRequestController::class, 'waliIndex'])
        ->middleware('can:guru-wali-kelas')
        ->name('guru.wali-kelas.leave-requests.index');
    Route::post('/guru/wali-kelas/pengajuan-siswa/{leaveRequest}/approve', [StudentLeaveRequestController::class, 'waliApprove'])
        ->middleware('can:guru-wali-kelas')
        ->name('guru.wali-kelas.leave-requests.approve');
    Route::post('/guru/wali-kelas/pengajuan-siswa/{leaveRequest}/reject', [StudentLeaveRequestController::class, 'waliReject'])
        ->middleware('can:guru-wali-kelas')
        ->name('guru.wali-kelas.leave-requests.reject');
    Route::post('/guru/wali-kelas/pengajuan-siswa/hardfile', [StudentLeaveRequestController::class, 'waliStoreHardfile'])
        ->middleware('can:guru-wali-kelas')
        ->name('guru.wali-kelas.leave-requests.hardfile');
    Route::post('/guru/attendance-details/bulk-submit', [AttendanceDetailController::class, 'submitBulkForGuru'])
        ->name('guru.attendance-details.bulk-submit');
    Route::post('/guru/attendance-details/{student}/submit', [AttendanceDetailController::class, 'submitForGuru'])
        ->name('guru.attendance-details.submit');
});

Route::middleware(['auth', 'siswa'])->group(function () {
    Route::get('/siswa', [DashboardController::class, 'siswa'])->name('siswa.dashboard');
    Route::get('/siswa/identitas', [StudentController::class, 'editOwnIdentity'])
        ->name('siswa.identity.edit');
    Route::put('/siswa/identitas', [StudentController::class, 'updateOwnIdentity'])
        ->name('siswa.identity.update');
    Route::get('/siswa/riwayat-absen', [AttendanceDetailController::class, 'siswaHistory'])
        ->name('siswa.attendance-history.index');
    Route::get('/siswa/riwayat-absen/pdf', [AttendanceDetailController::class, 'siswaHistoryPdf'])
        ->name('siswa.attendance-history.pdf');
    Route::get('/siswa/riwayat-absen/excel', [AttendanceDetailController::class, 'siswaHistoryExcel'])
        ->name('siswa.attendance-history.excel');
    Route::get('/siswa/teacher-attendances', [TeacherAttendanceController::class, 'siswaIndex'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.teacher-attendances.index');
    Route::post('/siswa/teacher-attendances/{schedule}/submit', [TeacherAttendanceController::class, 'submitForSiswa'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.teacher-attendances.submit');
    Route::get('/siswa/attendance-details', [AttendanceDetailController::class, 'siswaIndex'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.attendance-details.index');
    Route::post('/siswa/attendance-details/bulk-submit', [AttendanceDetailController::class, 'submitBulkForOfficer'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.attendance-details.bulk-submit');
    Route::post('/siswa/attendance-details/{student}/submit', [AttendanceDetailController::class, 'submitForOfficer'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.attendance-details.submit');
    Route::get('/siswa/pengajuan-izin-sakit', [StudentLeaveRequestController::class, 'siswaIndex'])
        ->name('siswa.leave-requests.index');
    Route::post('/siswa/pengajuan-izin-sakit', [StudentLeaveRequestController::class, 'siswaStore'])
        ->name('siswa.leave-requests.store');
});

Route::get('/home', function () {
    return redirect()->route('dashboard');
})->name('home');

require __DIR__ . '/auth.php';
