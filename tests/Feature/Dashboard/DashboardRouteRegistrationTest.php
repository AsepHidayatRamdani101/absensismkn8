<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardRouteRegistrationTest extends TestCase
{
    #[Test]
    public function dashboard_dss_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('dashboard'));
        $this->assertTrue(Route::has('dashboard.dss.data'));
        $this->assertTrue(Route::has('dashboard.dss.options'));
        $this->assertTrue(Route::has('dashboard.dss.activities'));
        $this->assertTrue(Route::has('dashboard.dss.export'));
        $this->assertTrue(Route::has('admin.dashboard'));
        $this->assertTrue(Route::has('guru.dashboard'));
        $this->assertTrue(Route::has('wali-kelas.dashboard'));
        $this->assertTrue(Route::has('bk.dashboard'));
        $this->assertTrue(Route::has('kesiswaan.dashboard'));
        $this->assertTrue(Route::has('siswa.dashboard'));
    }

    #[Test]
    public function pjj_routes_are_registered_for_each_school_role(): void
    {
        foreach ([
            'admin.pjj.dashboard',
            'admin.pjj.tasks',
            'admin.pjj.teacher-attendance',
            'admin.pjj.student-attendance',
            'admin.pjj.effectiveness',
            'guru.pjj.index',
            'guru.pjj.assignments.store',
            'guru.pjj.assignments.show',
            'guru.pjj.assignments.update',
            'guru.pjj.assignments.destroy',
            'guru.pjj.submissions.grade',
            'siswa.pjj.index',
            'siswa.pjj.attendance',
            'siswa.pjj.assignments.submit',
            'pjj.assignment.download',
            'pjj.submission.download',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Expected route [{$routeName}] to be registered.");
        }
    }
}
