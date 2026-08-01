<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FonnteRouteRateLimitTest extends TestCase
{
    #[Test]
    public function fonnte_test_routes_are_throttled(): void
    {
        $singleTestRoute = Route::getRoutes()->match(Request::create('/app-settings/fonnte/test', 'POST'));
        $sampleTestRoute = Route::getRoutes()->match(Request::create('/app-settings/fonnte/test-guru-sample', 'POST'));

        $this->assertContains('throttle:fonnte-test', $singleTestRoute->gatherMiddleware());
        $this->assertContains('throttle:fonnte-test', $sampleTestRoute->gatherMiddleware());
    }
}
