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
        $this->assertTrue(Route::has('pancawaluya.reward-transactions.index'));
        $this->assertTrue(Route::has('pancawaluya.reward-transactions.datatable'));
        $this->assertTrue(Route::has('pancawaluya.reward-transactions.students.options'));
        $this->assertTrue(Route::has('pancawaluya.reward-transactions.reward-item-preview'));
        $this->assertTrue(Route::has('pancawaluya.violation-transactions.index'));
        $this->assertTrue(Route::has('pancawaluya.violation-transactions.datatable'));
        $this->assertTrue(Route::has('pancawaluya.violation-transactions.students.options'));
        $this->assertTrue(Route::has('pancawaluya.violation-transactions.violation-item-preview'));
        $this->assertTrue(Route::has('pancawaluya.transaction-histories.index'));
        $this->assertTrue(Route::has('pancawaluya.transaction-histories.datatable'));
    }
}
