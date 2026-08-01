<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiRouteHardeningTest extends TestCase
{
    #[Test]
    public function api_v1_login_uses_auth_rate_limiter(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/v1/login', 'POST'));

        $this->assertContains('throttle:api-auth', $route->gatherMiddleware());
    }

    #[Test]
    public function api_v1_mutation_routes_use_audit_and_default_throttle(): void
    {
        $logoutRoute = Route::getRoutes()->match(Request::create('/api/v1/logout', 'POST'));
        $attendanceRoute = Route::getRoutes()->match(Request::create('/api/v1/attendance/manual', 'POST'));
        $deviceFaceRoute = Route::getRoutes()->match(Request::create('/api/v1/device/face', 'POST'));

        $this->assertContains('auth:sanctum', $logoutRoute->gatherMiddleware());
        $this->assertContains('throttle:api-default', $logoutRoute->gatherMiddleware());
        $this->assertContains('audit', $logoutRoute->gatherMiddleware());

        $this->assertContains('throttle:api-default', $attendanceRoute->gatherMiddleware());
        $this->assertContains('audit', $attendanceRoute->gatherMiddleware());

        $this->assertContains('throttle:api-default', $deviceFaceRoute->gatherMiddleware());
        $this->assertContains('audit', $deviceFaceRoute->gatherMiddleware());
    }

    #[Test]
    public function web_login_uses_web_login_throttle(): void
    {
        $route = Route::getRoutes()->match(Request::create('/login', 'POST'));

        $this->assertContains('throttle:web-login', $route->gatherMiddleware());
    }
}
