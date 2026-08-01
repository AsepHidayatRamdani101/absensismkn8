<?php

namespace Tests\Unit\Pancawaluya;

use App\Repositories\Contracts\Pancawaluya\RewardCategoryRepositoryInterface;
use App\Services\Pancawaluya\AuditTrailService;
use App\Services\Pancawaluya\RewardCategoryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RewardCategoryServiceTest extends TestCase
{
    public function test_paginate_delegates_to_repository(): void
    {
        $repository = \Mockery::mock(RewardCategoryRepositoryInterface::class);
        $paginator = new LengthAwarePaginator([], 0, 10, 1);

        $repository->shouldReceive('paginate')
            ->once()
            ->with(['search' => 'abc'], 10)
            ->andReturn($paginator);

        $audit = \Mockery::mock(AuditTrailService::class);

        $service = new RewardCategoryService($repository, $audit);

        $this->assertSame($paginator, $service->paginate(['search' => 'abc'], 10));
    }

    public function test_restore_calls_repository_restore(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($callback) => $callback());

        $repository = \Mockery::mock(RewardCategoryRepositoryInterface::class);
        $repository->shouldReceive('restore')->once()->with(10)->andReturn(true);

        $audit = \Mockery::mock(AuditTrailService::class);
        $audit->shouldReceive('log')->once();

        $service = new RewardCategoryService($repository, $audit);

        $result = $service->restore(10, Request::create('/'));

        $this->assertTrue($result);
    }
}
