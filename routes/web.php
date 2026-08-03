<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardDssController;
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
use App\Http\Controllers\OfficerAttendancePermitController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherLeaveRequestController;
use App\Http\Controllers\TeacherSubjectController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\Pancawaluya\RewardCategoryController;
use App\Http\Controllers\Pancawaluya\RewardController;
use App\Http\Controllers\Pancawaluya\RewardTransactionController;
use App\Http\Controllers\Pancawaluya\TransactionHistoryController;
use App\Http\Controllers\Pancawaluya\ViolationCategoryController;
use App\Http\Controllers\Pancawaluya\ViolationController;
use App\Http\Controllers\Pancawaluya\ViolationTransactionController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/scan/siswa/{token}', [StudentController::class, 'showPublicByQr'])
    ->name('students.qr.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard-dss', [DashboardDssController::class, 'landing'])->name('dashboard.dss.landing');
    Route::prefix('dashboard/dss')->name('dashboard.dss.')->group(function () {
        Route::get('data', [DashboardDssController::class, 'data'])->middleware('throttle:analytics-read')->name('data');
        Route::get('options', [DashboardDssController::class, 'options'])->middleware('throttle:analytics-read')->name('options');
        Route::get('activities', [DashboardDssController::class, 'activities'])->middleware('throttle:analytics-read')->name('activities');
        Route::get('export/{format}', [DashboardDssController::class, 'export'])->middleware(['throttle:analytics-export', 'audit'])->name('export');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::middleware('admin')->group(function () {
        Route::get('/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');
        Route::get('/admin/dss', [DashboardDssController::class, 'admin'])->name('admin.dashboard.dss');

        Route::get('school-settings', [SchoolSettingController::class, 'index'])->name('school-settings.index');
        Route::put('school-settings', [SchoolSettingController::class, 'update'])->name('school-settings.update');

        Route::get('app-settings', [AppSettingController::class, 'index'])->name('app-settings.index');
        Route::post('app-settings/fonnte', [AppSettingController::class, 'updateFonnte'])->name('app-settings.fonnte.update');
        Route::post('app-settings/fonnte/test', [AppSettingController::class, 'sendFonnteTest'])->middleware('throttle:fonnte-test')->name('app-settings.fonnte.test');
        Route::post('app-settings/fonnte/test-guru-sample', [AppSettingController::class, 'sendFonnteTestToTeacherSamples'])->middleware('throttle:fonnte-test')->name('app-settings.fonnte.test-guru-sample');
        Route::post('app-settings/clear-all', [AppSettingController::class, 'clearAll'])->name('app-settings.clear-all');
        Route::post('app-settings/clear-cache', [AppSettingController::class, 'clearCache'])->name('app-settings.clear-cache');
        Route::post('app-settings/clear-view', [AppSettingController::class, 'clearView'])->name('app-settings.clear-view');
        Route::post('app-settings/clear-config', [AppSettingController::class, 'clearConfig'])->name('app-settings.clear-config');
        Route::post('app-settings/clear-route', [AppSettingController::class, 'clearRoute'])->name('app-settings.clear-route');
        Route::post('app-settings/clear-session', [AppSettingController::class, 'clearSession'])->name('app-settings.clear-session');

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
        Route::get('students/export-accounts-pdf', [StudentController::class, 'exportAccountsPdf'])->name('students.export-accounts-pdf');
        Route::get('students/datatable', [StudentController::class, 'datatable'])->name('students.datatable');
        Route::get('students/qr-card/{student}', [StudentController::class, 'qrCard'])->name('students.qr-card');
        Route::get('students/qr-cards/classroom', [StudentController::class, 'qrCardsByClassroom'])->name('students.qr-cards.classroom');
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

    Route::prefix('pancawaluya')->name('pancawaluya.')->middleware(['verified'])->group(function () {
        Route::get('reward-categories/datatable', [RewardCategoryController::class, 'datatable'])
            ->name('reward-categories.datatable')
            ->middleware('permission:pancawaluya.reward-category.view');
        Route::post('reward-categories/bulk-delete', [RewardCategoryController::class, 'bulkDelete'])
            ->name('reward-categories.bulk-delete')
            ->middleware('permission:pancawaluya.reward-category.delete');
        Route::post('reward-categories/bulk-restore', [RewardCategoryController::class, 'bulkRestore'])
            ->name('reward-categories.bulk-restore')
            ->middleware('permission:pancawaluya.reward-category.restore');
        Route::post('reward-categories/{id}/restore', [RewardCategoryController::class, 'restore'])
            ->name('reward-categories.restore')
            ->middleware('permission:pancawaluya.reward-category.restore');
        Route::delete('reward-categories/{id}/force-delete', [RewardCategoryController::class, 'forceDelete'])
            ->name('reward-categories.force-delete')
            ->middleware('permission:pancawaluya.reward-category.force-delete');
        Route::post('reward-categories/import', [RewardCategoryController::class, 'import'])
            ->name('reward-categories.import')
            ->middleware('permission:pancawaluya.reward-category.create');
        Route::get('reward-categories/template', [RewardCategoryController::class, 'template'])
            ->name('reward-categories.template')
            ->middleware('permission:pancawaluya.reward-category.view');
        Route::get('reward-categories/export/excel', [RewardCategoryController::class, 'exportExcel'])
            ->name('reward-categories.export.excel')
            ->middleware('permission:pancawaluya.reward-category.view');
        Route::get('reward-categories/export/csv', [RewardCategoryController::class, 'exportCsv'])
            ->name('reward-categories.export.csv')
            ->middleware('permission:pancawaluya.reward-category.view');
        Route::get('reward-categories/export/pdf', [RewardCategoryController::class, 'exportPdf'])
            ->name('reward-categories.export.pdf')
            ->middleware('permission:pancawaluya.reward-category.view');
        Route::get('reward-categories/print', [RewardCategoryController::class, 'print'])
            ->name('reward-categories.print')
            ->middleware('permission:pancawaluya.reward-category.view');
        Route::get('reward-categories', [RewardCategoryController::class, 'index'])
            ->name('reward-categories.index')
            ->middleware('permission:pancawaluya.reward-category.view');
        Route::get('reward-categories/create', [RewardCategoryController::class, 'create'])
            ->name('reward-categories.create')
            ->middleware('permission:pancawaluya.reward-category.create');
        Route::post('reward-categories', [RewardCategoryController::class, 'store'])
            ->name('reward-categories.store')
            ->middleware('permission:pancawaluya.reward-category.create');
        Route::get('reward-categories/{reward_category}/edit', [RewardCategoryController::class, 'edit'])
            ->name('reward-categories.edit')
            ->middleware('permission:pancawaluya.reward-category.update');
        Route::put('reward-categories/{reward_category}', [RewardCategoryController::class, 'update'])
            ->name('reward-categories.update')
            ->middleware('permission:pancawaluya.reward-category.update');
        Route::delete('reward-categories/{reward_category}', [RewardCategoryController::class, 'destroy'])
            ->name('reward-categories.destroy')
            ->middleware('permission:pancawaluya.reward-category.delete');

        Route::get('rewards/datatable', [RewardController::class, 'datatable'])
            ->name('rewards.datatable')
            ->middleware('permission:pancawaluya.reward.view');
        Route::post('rewards/bulk-delete', [RewardController::class, 'bulkDelete'])
            ->name('rewards.bulk-delete')
            ->middleware('permission:pancawaluya.reward.delete');
        Route::post('rewards/bulk-restore', [RewardController::class, 'bulkRestore'])
            ->name('rewards.bulk-restore')
            ->middleware('permission:pancawaluya.reward.restore');
        Route::post('rewards/{id}/restore', [RewardController::class, 'restore'])
            ->name('rewards.restore')
            ->middleware('permission:pancawaluya.reward.restore');
        Route::delete('rewards/{id}/force-delete', [RewardController::class, 'forceDelete'])
            ->name('rewards.force-delete')
            ->middleware('permission:pancawaluya.reward.force-delete');
        Route::post('rewards/import', [RewardController::class, 'import'])
            ->name('rewards.import')
            ->middleware('permission:pancawaluya.reward.create');
        Route::get('rewards/template', [RewardController::class, 'template'])
            ->name('rewards.template')
            ->middleware('permission:pancawaluya.reward.view');
        Route::get('rewards/export/excel', [RewardController::class, 'exportExcel'])
            ->name('rewards.export.excel')
            ->middleware('permission:pancawaluya.reward.view');
        Route::get('rewards/export/csv', [RewardController::class, 'exportCsv'])
            ->name('rewards.export.csv')
            ->middleware('permission:pancawaluya.reward.view');
        Route::get('rewards/export/pdf', [RewardController::class, 'exportPdf'])
            ->name('rewards.export.pdf')
            ->middleware('permission:pancawaluya.reward.view');
        Route::get('rewards/print', [RewardController::class, 'print'])
            ->name('rewards.print')
            ->middleware('permission:pancawaluya.reward.view');
        Route::get('rewards', [RewardController::class, 'index'])
            ->name('rewards.index')
            ->middleware('permission:pancawaluya.reward.view');
        Route::get('rewards/create', [RewardController::class, 'create'])
            ->name('rewards.create')
            ->middleware('permission:pancawaluya.reward.create');
        Route::post('rewards', [RewardController::class, 'store'])
            ->name('rewards.store')
            ->middleware('permission:pancawaluya.reward.create');
        Route::get('rewards/{reward}/edit', [RewardController::class, 'edit'])
            ->name('rewards.edit')
            ->middleware('permission:pancawaluya.reward.update');
        Route::put('rewards/{reward}', [RewardController::class, 'update'])
            ->name('rewards.update')
            ->middleware('permission:pancawaluya.reward.update');
        Route::delete('rewards/{reward}', [RewardController::class, 'destroy'])
            ->name('rewards.destroy')
            ->middleware('permission:pancawaluya.reward.delete');

        Route::get('violation-categories/datatable', [ViolationCategoryController::class, 'datatable'])
            ->name('violation-categories.datatable')
            ->middleware('permission:pancawaluya.violation-category.view');
        Route::post('violation-categories/bulk-delete', [ViolationCategoryController::class, 'bulkDelete'])
            ->name('violation-categories.bulk-delete')
            ->middleware('permission:pancawaluya.violation-category.delete');
        Route::post('violation-categories/bulk-restore', [ViolationCategoryController::class, 'bulkRestore'])
            ->name('violation-categories.bulk-restore')
            ->middleware('permission:pancawaluya.violation-category.restore');
        Route::post('violation-categories/{id}/restore', [ViolationCategoryController::class, 'restore'])
            ->name('violation-categories.restore')
            ->middleware('permission:pancawaluya.violation-category.restore');
        Route::delete('violation-categories/{id}/force-delete', [ViolationCategoryController::class, 'forceDelete'])
            ->name('violation-categories.force-delete')
            ->middleware('permission:pancawaluya.violation-category.force-delete');
        Route::post('violation-categories/import', [ViolationCategoryController::class, 'import'])
            ->name('violation-categories.import')
            ->middleware('permission:pancawaluya.violation-category.create');
        Route::get('violation-categories/template', [ViolationCategoryController::class, 'template'])
            ->name('violation-categories.template')
            ->middleware('permission:pancawaluya.violation-category.view');
        Route::get('violation-categories/export/excel', [ViolationCategoryController::class, 'exportExcel'])
            ->name('violation-categories.export.excel')
            ->middleware('permission:pancawaluya.violation-category.view');
        Route::get('violation-categories/export/csv', [ViolationCategoryController::class, 'exportCsv'])
            ->name('violation-categories.export.csv')
            ->middleware('permission:pancawaluya.violation-category.view');
        Route::get('violation-categories/export/pdf', [ViolationCategoryController::class, 'exportPdf'])
            ->name('violation-categories.export.pdf')
            ->middleware('permission:pancawaluya.violation-category.view');
        Route::get('violation-categories/print', [ViolationCategoryController::class, 'print'])
            ->name('violation-categories.print')
            ->middleware('permission:pancawaluya.violation-category.view');
        Route::get('violation-categories', [ViolationCategoryController::class, 'index'])
            ->name('violation-categories.index')
            ->middleware('permission:pancawaluya.violation-category.view');
        Route::get('violation-categories/create', [ViolationCategoryController::class, 'create'])
            ->name('violation-categories.create')
            ->middleware('permission:pancawaluya.violation-category.create');
        Route::post('violation-categories', [ViolationCategoryController::class, 'store'])
            ->name('violation-categories.store')
            ->middleware('permission:pancawaluya.violation-category.create');
        Route::get('violation-categories/{violation_category}/edit', [ViolationCategoryController::class, 'edit'])
            ->name('violation-categories.edit')
            ->middleware('permission:pancawaluya.violation-category.update');
        Route::put('violation-categories/{violation_category}', [ViolationCategoryController::class, 'update'])
            ->name('violation-categories.update')
            ->middleware('permission:pancawaluya.violation-category.update');
        Route::delete('violation-categories/{violation_category}', [ViolationCategoryController::class, 'destroy'])
            ->name('violation-categories.destroy')
            ->middleware('permission:pancawaluya.violation-category.delete');

        Route::get('violations/datatable', [ViolationController::class, 'datatable'])
            ->name('violations.datatable')
            ->middleware('permission:pancawaluya.violation.view');
        Route::post('violations/bulk-delete', [ViolationController::class, 'bulkDelete'])
            ->name('violations.bulk-delete')
            ->middleware('permission:pancawaluya.violation.delete');
        Route::post('violations/bulk-restore', [ViolationController::class, 'bulkRestore'])
            ->name('violations.bulk-restore')
            ->middleware('permission:pancawaluya.violation.restore');
        Route::post('violations/{id}/restore', [ViolationController::class, 'restore'])
            ->name('violations.restore')
            ->middleware('permission:pancawaluya.violation.restore');
        Route::delete('violations/{id}/force-delete', [ViolationController::class, 'forceDelete'])
            ->name('violations.force-delete')
            ->middleware('permission:pancawaluya.violation.force-delete');
        Route::post('violations/import', [ViolationController::class, 'import'])
            ->name('violations.import')
            ->middleware('permission:pancawaluya.violation.create');
        Route::get('violations/template', [ViolationController::class, 'template'])
            ->name('violations.template')
            ->middleware('permission:pancawaluya.violation.view');
        Route::get('violations/export/excel', [ViolationController::class, 'exportExcel'])
            ->name('violations.export.excel')
            ->middleware('permission:pancawaluya.violation.view');
        Route::get('violations/export/csv', [ViolationController::class, 'exportCsv'])
            ->name('violations.export.csv')
            ->middleware('permission:pancawaluya.violation.view');
        Route::get('violations/export/pdf', [ViolationController::class, 'exportPdf'])
            ->name('violations.export.pdf')
            ->middleware('permission:pancawaluya.violation.view');
        Route::get('violations/print', [ViolationController::class, 'print'])
            ->name('violations.print')
            ->middleware('permission:pancawaluya.violation.view');
        Route::get('violations', [ViolationController::class, 'index'])
            ->name('violations.index')
            ->middleware('permission:pancawaluya.violation.view');
        Route::get('violations/create', [ViolationController::class, 'create'])
            ->name('violations.create')
            ->middleware('permission:pancawaluya.violation.create');
        Route::post('violations', [ViolationController::class, 'store'])
            ->name('violations.store')
            ->middleware('permission:pancawaluya.violation.create');
        Route::get('violations/{violation}/edit', [ViolationController::class, 'edit'])
            ->name('violations.edit')
            ->middleware('permission:pancawaluya.violation.update');
        Route::put('violations/{violation}', [ViolationController::class, 'update'])
            ->name('violations.update')
            ->middleware('permission:pancawaluya.violation.update');
        Route::delete('violations/{violation}', [ViolationController::class, 'destroy'])
            ->name('violations.destroy')
            ->middleware('permission:pancawaluya.violation.delete');

        Route::get('reward-transactions/datatable', [RewardTransactionController::class, 'datatable'])
            ->name('reward-transactions.datatable')
            ->middleware('permission:pancawaluya.transaction.reward.view');
        Route::get('reward-transactions/students/options', [RewardTransactionController::class, 'studentOptions'])
            ->name('reward-transactions.students.options')
            ->middleware('permission:pancawaluya.transaction.reward.view');
        Route::get('reward-transactions/reward-item-preview', [RewardTransactionController::class, 'rewardItemPreview'])
            ->name('reward-transactions.reward-item-preview')
            ->middleware('permission:pancawaluya.transaction.reward.view');
        Route::post('reward-transactions/bulk-delete', [RewardTransactionController::class, 'bulkDelete'])
            ->name('reward-transactions.bulk-delete')
            ->middleware('permission:pancawaluya.transaction.reward.delete');
        Route::post('reward-transactions/bulk-restore', [RewardTransactionController::class, 'bulkRestore'])
            ->name('reward-transactions.bulk-restore')
            ->middleware('permission:pancawaluya.transaction.reward.restore');
        Route::post('reward-transactions/{id}/restore', [RewardTransactionController::class, 'restore'])
            ->name('reward-transactions.restore')
            ->middleware('permission:pancawaluya.transaction.reward.restore');
        Route::delete('reward-transactions/{id}/force-delete', [RewardTransactionController::class, 'forceDelete'])
            ->name('reward-transactions.force-delete')
            ->middleware('permission:pancawaluya.transaction.reward.force-delete');
        Route::post('reward-transactions/import', [RewardTransactionController::class, 'import'])
            ->name('reward-transactions.import')
            ->middleware('permission:pancawaluya.transaction.reward.import');
        Route::get('reward-transactions/template', [RewardTransactionController::class, 'template'])
            ->name('reward-transactions.template')
            ->middleware('permission:pancawaluya.transaction.reward.import');
        Route::get('reward-transactions/export/excel', [RewardTransactionController::class, 'exportExcel'])
            ->name('reward-transactions.export.excel')
            ->middleware('permission:pancawaluya.transaction.reward.export');
        Route::get('reward-transactions/export/csv', [RewardTransactionController::class, 'exportCsv'])
            ->name('reward-transactions.export.csv')
            ->middleware('permission:pancawaluya.transaction.reward.export');
        Route::get('reward-transactions/export/pdf', [RewardTransactionController::class, 'exportPdf'])
            ->name('reward-transactions.export.pdf')
            ->middleware('permission:pancawaluya.transaction.reward.export');
        Route::get('reward-transactions/print', [RewardTransactionController::class, 'print'])
            ->name('reward-transactions.print')
            ->middleware('permission:pancawaluya.transaction.reward.export');
        Route::get('reward-transactions', [RewardTransactionController::class, 'index'])
            ->name('reward-transactions.index')
            ->middleware('permission:pancawaluya.transaction.reward.view');
        Route::get('reward-transactions/create', [RewardTransactionController::class, 'create'])
            ->name('reward-transactions.create')
            ->middleware('permission:pancawaluya.transaction.reward.create');
        Route::post('reward-transactions', [RewardTransactionController::class, 'store'])
            ->name('reward-transactions.store')
            ->middleware('permission:pancawaluya.transaction.reward.create');
        Route::get('reward-transactions/{reward_transaction}/edit', [RewardTransactionController::class, 'edit'])
            ->name('reward-transactions.edit')
            ->middleware('permission:pancawaluya.transaction.reward.update');
        Route::match(['PUT', 'PATCH'], 'reward-transactions/{reward_transaction}', [RewardTransactionController::class, 'update'])
            ->name('reward-transactions.update')
            ->middleware('permission:pancawaluya.transaction.reward.update');
        Route::delete('reward-transactions/{reward_transaction}', [RewardTransactionController::class, 'destroy'])
            ->name('reward-transactions.destroy')
            ->middleware('permission:pancawaluya.transaction.reward.delete');

        Route::get('violation-transactions/datatable', [ViolationTransactionController::class, 'datatable'])
            ->name('violation-transactions.datatable')
            ->middleware('permission:pancawaluya.transaction.violation.view');
        Route::get('violation-transactions/students/options', [ViolationTransactionController::class, 'studentOptions'])
            ->name('violation-transactions.students.options')
            ->middleware('permission:pancawaluya.transaction.violation.view');
        Route::get('violation-transactions/violation-item-preview', [ViolationTransactionController::class, 'violationItemPreview'])
            ->name('violation-transactions.violation-item-preview')
            ->middleware('permission:pancawaluya.transaction.violation.view');
        Route::post('violation-transactions/bulk-delete', [ViolationTransactionController::class, 'bulkDelete'])
            ->name('violation-transactions.bulk-delete')
            ->middleware('permission:pancawaluya.transaction.violation.delete');
        Route::post('violation-transactions/bulk-restore', [ViolationTransactionController::class, 'bulkRestore'])
            ->name('violation-transactions.bulk-restore')
            ->middleware('permission:pancawaluya.transaction.violation.restore');
        Route::post('violation-transactions/{id}/restore', [ViolationTransactionController::class, 'restore'])
            ->name('violation-transactions.restore')
            ->middleware('permission:pancawaluya.transaction.violation.restore');
        Route::delete('violation-transactions/{id}/force-delete', [ViolationTransactionController::class, 'forceDelete'])
            ->name('violation-transactions.force-delete')
            ->middleware('permission:pancawaluya.transaction.violation.force-delete');
        Route::post('violation-transactions/import', [ViolationTransactionController::class, 'import'])
            ->name('violation-transactions.import')
            ->middleware('permission:pancawaluya.transaction.violation.import');
        Route::get('violation-transactions/template', [ViolationTransactionController::class, 'template'])
            ->name('violation-transactions.template')
            ->middleware('permission:pancawaluya.transaction.violation.import');
        Route::get('violation-transactions/export/excel', [ViolationTransactionController::class, 'exportExcel'])
            ->name('violation-transactions.export.excel')
            ->middleware('permission:pancawaluya.transaction.violation.export');
        Route::get('violation-transactions/export/csv', [ViolationTransactionController::class, 'exportCsv'])
            ->name('violation-transactions.export.csv')
            ->middleware('permission:pancawaluya.transaction.violation.export');
        Route::get('violation-transactions/export/pdf', [ViolationTransactionController::class, 'exportPdf'])
            ->name('violation-transactions.export.pdf')
            ->middleware('permission:pancawaluya.transaction.violation.export');
        Route::get('violation-transactions/print', [ViolationTransactionController::class, 'print'])
            ->name('violation-transactions.print')
            ->middleware('permission:pancawaluya.transaction.violation.export');
        Route::get('violation-transactions', [ViolationTransactionController::class, 'index'])
            ->name('violation-transactions.index')
            ->middleware('permission:pancawaluya.transaction.violation.view');
        Route::get('violation-transactions/create', [ViolationTransactionController::class, 'create'])
            ->name('violation-transactions.create')
            ->middleware('permission:pancawaluya.transaction.violation.create');
        Route::post('violation-transactions', [ViolationTransactionController::class, 'store'])
            ->name('violation-transactions.store')
            ->middleware('permission:pancawaluya.transaction.violation.create');
        Route::get('violation-transactions/{violation_transaction}/edit', [ViolationTransactionController::class, 'edit'])
            ->name('violation-transactions.edit')
            ->middleware('permission:pancawaluya.transaction.violation.update');
        Route::match(['PUT', 'PATCH'], 'violation-transactions/{violation_transaction}', [ViolationTransactionController::class, 'update'])
            ->name('violation-transactions.update')
            ->middleware('permission:pancawaluya.transaction.violation.update');
        Route::delete('violation-transactions/{violation_transaction}', [ViolationTransactionController::class, 'destroy'])
            ->name('violation-transactions.destroy')
            ->middleware('permission:pancawaluya.transaction.violation.delete');

        Route::get('transaction-histories/datatable', [TransactionHistoryController::class, 'datatable'])
            ->name('transaction-histories.datatable')
            ->middleware('permission:pancawaluya.transaction.history.view');
        Route::get('transaction-histories/export/excel', [TransactionHistoryController::class, 'exportExcel'])
            ->name('transaction-histories.export.excel')
            ->middleware('permission:pancawaluya.transaction.history.export');
        Route::get('transaction-histories/export/csv', [TransactionHistoryController::class, 'exportCsv'])
            ->name('transaction-histories.export.csv')
            ->middleware('permission:pancawaluya.transaction.history.export');
        Route::get('transaction-histories/export/pdf', [TransactionHistoryController::class, 'exportPdf'])
            ->name('transaction-histories.export.pdf')
            ->middleware('permission:pancawaluya.transaction.history.export');
        Route::get('transaction-histories/print', [TransactionHistoryController::class, 'print'])
            ->name('transaction-histories.print')
            ->middleware('permission:pancawaluya.transaction.history.export');
        Route::get('transaction-histories', [TransactionHistoryController::class, 'index'])
            ->name('transaction-histories.index')
            ->middleware('permission:pancawaluya.transaction.history.view');
    });

    Route::middleware('role:admin|kurikulum')->group(function () {
        Route::post('teacher-subjects/import', [TeacherSubjectController::class, 'import'])->name('teacher-subjects.import');
        Route::get('teacher-subjects/template', [TeacherSubjectController::class, 'template'])->name('teacher-subjects.template');
        Route::get('teacher-subjects/export', [TeacherSubjectController::class, 'export'])->name('teacher-subjects.export');
        Route::delete('teacher-subjects/destroy-multiple', [TeacherSubjectController::class, 'destroyMultiple'])->name('teacher-subjects.destroy-multiple');
        Route::get('teacher-subjects/classrooms/{teacher}/{subject}/{academicYear}', [TeacherSubjectController::class, 'getClassrooms'])->name('teacher-subjects.classrooms');
        Route::get('teacher-subjects/all-classrooms', [TeacherSubjectController::class, 'getAllClassrooms'])->name('teacher-subjects.all-classrooms');
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
        Route::post('schedules/reset', [ScheduleController::class, 'reset'])->name('schedules.reset');
        Route::get('schedules/template', [ScheduleController::class, 'template'])->name('schedules.template');
        Route::get('schedules/export', [ScheduleController::class, 'export'])->name('schedules.export');
        Route::resource('teacher-attendances', TeacherAttendanceController::class)->except(['show', 'create']);
        Route::delete('attendance-details/destroy-multiple', [AttendanceDetailController::class, 'destroyMultiple'])
            ->name('attendance-details.destroy-multiple');
        Route::get('attendance-details/datatable', [AttendanceDetailController::class, 'adminDatatable'])
            ->name('attendance-details.datatable');
        Route::get('attendance-details/teacher-attendance-detail', [AttendanceDetailController::class, 'adminTeacherAttendanceDetail'])
            ->name('attendance-details.teacher-attendance-detail');
        Route::resource('attendance-details', AttendanceDetailController::class)->except(['show', 'create']);

        Route::get('kurikulum/guru-leave-requests', [TeacherLeaveRequestController::class, 'approvalIndex'])
            ->name('kurikulum.teacher-leave-requests.index');
        Route::post('kurikulum/guru-leave-requests/input-langsung', [TeacherLeaveRequestController::class, 'kurikulumStoreDirect'])
            ->name('kurikulum.teacher-leave-requests.direct-store');
        Route::post('kurikulum/guru-leave-requests/{teacherLeaveRequest}/approve', [TeacherLeaveRequestController::class, 'approve'])
            ->name('kurikulum.teacher-leave-requests.approve');
        Route::post('kurikulum/guru-leave-requests/{teacherLeaveRequest}/reject', [TeacherLeaveRequestController::class, 'reject'])
            ->name('kurikulum.teacher-leave-requests.reject');
        Route::get('kurikulum/officer-attendance-permits', [OfficerAttendancePermitController::class, 'index'])
            ->name('kurikulum.officer-attendance-permits.index');
        Route::post('kurikulum/officer-attendance-permits/{permit}/approve', [OfficerAttendancePermitController::class, 'approve'])
            ->name('kurikulum.officer-attendance-permits.approve');
        Route::post('kurikulum/officer-attendance-permits/{permit}/reject', [OfficerAttendancePermitController::class, 'reject'])
            ->name('kurikulum.officer-attendance-permits.reject');

        Route::get('reports/teacher-attendance', [ReportController::class, 'teacherAttendance'])
            ->name('reports.teacher-attendance');
        Route::get('reports/teacher-attendance/datatable', [ReportController::class, 'teacherAttendanceDatatable'])
            ->name('reports.teacher-attendance.datatable');
        Route::get('reports/teacher-attendance-recognition', [ReportController::class, 'teacherAttendanceRecognition'])
            ->name('reports.teacher-attendance-recognition');
        Route::get('reports/teacher-attendance-recognition/missing-teachers/{type}', [ReportController::class, 'teacherAttendanceRecognitionMissingTeachers'])
            ->name('reports.teacher-attendance-recognition.missing-teachers');
        Route::get('reports/teacher-attendance-recognition/missing-teachers/{type}/{teacher}', [ReportController::class, 'teacherAttendanceRecognitionMissingTeacherSessions'])
            ->name('reports.teacher-attendance-recognition.missing-teacher-sessions');
        Route::get('reports/teacher-attendance-recognition/missing-teachers/{type}/{teacher}/pdf', [ReportController::class, 'teacherAttendanceRecognitionMissingTeacherSessionsPdf'])
            ->name('reports.teacher-attendance-recognition.missing-teacher-sessions.pdf');
        Route::get('reports/teacher-attendance-recognition/missing-teachers/{type}/{teacher}/excel', [ReportController::class, 'teacherAttendanceRecognitionMissingTeacherSessionsExcel'])
            ->name('reports.teacher-attendance-recognition.missing-teacher-sessions.excel');
        Route::get('reports/teacher-attendance/pdf', [ReportController::class, 'teacherAttendancePdf'])
            ->name('reports.teacher-attendance.pdf');
        Route::get('reports/teacher-attendance/excel', [ReportController::class, 'teacherAttendanceExcel'])
            ->name('reports.teacher-attendance.excel');

        Route::get('reports/student-attendance', [ReportController::class, 'studentAttendance'])
            ->name('reports.student-attendance');
        Route::get('reports/student-attendance/datatable', [ReportController::class, 'studentAttendanceDatatable'])
            ->name('reports.student-attendance.datatable');
        Route::get('reports/student-attendance/pdf', [ReportController::class, 'studentAttendancePdf'])
            ->name('reports.student-attendance.pdf');
        Route::get('reports/student-attendance/excel', [ReportController::class, 'studentAttendanceExcel'])
            ->name('reports.student-attendance.excel');
        Route::get('reports/teacher-agenda', [ReportController::class, 'teacherAgenda'])
            ->name('reports.teacher-agenda');
        Route::get('reports/teacher-agenda/datatable', [ReportController::class, 'teacherAgendaDatatable'])
            ->name('reports.teacher-agenda.datatable');
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
    Route::get('/kurikulum/dss', [DashboardDssController::class, 'kurikulum'])->name('kurikulum.dashboard.dss');
});

Route::middleware(['auth', 'guru'])->group(function () {
    Route::get('/guru', [DashboardController::class, 'guru'])->name('guru.dashboard');
    Route::get('/guru/dss', [DashboardDssController::class, 'guru'])->name('guru.dashboard.dss');
    Route::get('/guru/agenda', [GuruAgendaController::class, 'index'])->name('guru.agenda.index');
    Route::post('/guru/agenda/{schedule}', [GuruAgendaController::class, 'store'])->name('guru.agenda.store');
    Route::get('/guru/attendance-details', [AttendanceDetailController::class, 'guruIndex'])
        ->name('guru.attendance-details.index');
    Route::get('/guru/attendance-details/datatable', [AttendanceDetailController::class, 'guruDatatable'])
        ->name('guru.attendance-details.datatable');
    Route::get('/guru/attendance-details/class-options', [AttendanceDetailController::class, 'guruClassOptions'])
        ->name('guru.attendance-details.class-options');
    Route::get('/guru/attendance-details/teacher-attendance-detail', [AttendanceDetailController::class, 'guruTeacherAttendanceDetail'])
        ->name('guru.attendance-details.teacher-attendance-detail');
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
    Route::get('/guru/wali-kelas/rekap-siswa/detail/{student}', [ReportController::class, 'guruWaliKelasRecapDetail'])
        ->middleware('can:guru-wali-kelas')
        ->name('guru.wali-kelas.rekap-siswa.detail')
        ->where('student', '[0-9]+');
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
    Route::post('/guru/attendance-details/count-hadir', [AttendanceDetailController::class, 'submitCountPresentForGuru'])
        ->name('guru.attendance-details.count-hadir');
    Route::post('/guru/attendance-details/{student}/submit', [AttendanceDetailController::class, 'submitForGuru'])
        ->name('guru.attendance-details.submit');
});

Route::middleware(['auth', 'siswa'])->group(function () {
    Route::get('/siswa', [DashboardController::class, 'siswa'])->name('siswa.dashboard');
    Route::get('/siswa/dss', [DashboardDssController::class, 'siswa'])->name('siswa.dashboard.dss');
    Route::get('/siswa/identitas', [StudentController::class, 'editOwnIdentity'])
        ->name('siswa.identity.edit');
    Route::get('/siswa/identitas/qr/download', [StudentController::class, 'downloadOwnQrCode'])
        ->name('siswa.identity.qr.download');
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
    Route::post('/siswa/attendance-details/leave-input', [StudentLeaveRequestController::class, 'officerStoreDirect'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.attendance-details.leave-input.store');
    Route::post('/siswa/attendance-details/permit', [OfficerAttendancePermitController::class, 'store'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.attendance-details.permit.store');
    Route::get('/siswa/pengajuan-izin-sakit', [StudentLeaveRequestController::class, 'siswaIndex'])
        ->name('siswa.leave-requests.index');
    Route::post('/siswa/pengajuan-izin-sakit', [StudentLeaveRequestController::class, 'siswaStore'])
        ->name('siswa.leave-requests.store');
    Route::get('/siswa/izin-sakit-kelas', [StudentLeaveRequestController::class, 'officerLeaveIndex'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.officer-leave.index');
    Route::post('/siswa/izin-sakit-kelas', [StudentLeaveRequestController::class, 'officerLeaveStore'])
        ->middleware('can:siswa-absen-guru')
        ->name('siswa.officer-leave.store');
});

Route::middleware(['auth', 'role:wali_kelas'])->group(function () {
    Route::get('/wali-kelas', [DashboardDssController::class, 'waliKelas'])->name('wali-kelas.dashboard');
    Route::get('/wali-kelas/dss', [DashboardDssController::class, 'waliKelas'])->name('wali-kelas.dashboard.dss');
});

Route::middleware(['auth', 'role:bk'])->group(function () {
    Route::get('/bk', [DashboardDssController::class, 'bk'])->name('bk.dashboard');
    Route::get('/bk/dss', [DashboardDssController::class, 'bk'])->name('bk.dashboard.dss');
});

Route::middleware(['auth', 'role:kesiswaan'])->group(function () {
    Route::get('/kesiswaan', [DashboardDssController::class, 'kesiswaan'])->name('kesiswaan.dashboard');
    Route::get('/kesiswaan/dss', [DashboardDssController::class, 'kesiswaan'])->name('kesiswaan.dashboard.dss');
});

Route::get('/home', function () {
    return redirect()->route('dashboard');
})->name('home');

require __DIR__ . '/auth.php';
