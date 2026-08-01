<?php

namespace Tests\Feature\Pancawaluya;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PancawaluyaRouteRegistrationTest extends TestCase
{
    #[Test]
    public function important_pancawaluya_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('pancawaluya.reward-categories.index'));
        $this->assertTrue(Route::has('pancawaluya.rewards.index'));
        $this->assertTrue(Route::has('pancawaluya.violation-categories.index'));
        $this->assertTrue(Route::has('pancawaluya.violations.index'));
    }
}
