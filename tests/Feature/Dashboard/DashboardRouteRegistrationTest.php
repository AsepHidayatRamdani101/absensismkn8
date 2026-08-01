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
}
