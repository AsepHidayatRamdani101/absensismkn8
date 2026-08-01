<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardAuthorizationMiddlewareTest extends TestCase
{
    #[Test]
    public function dashboard_dss_endpoints_require_authentication(): void
    {
        $route = Route::getRoutes()->getByName('dashboard.dss.data');

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
    }
}
