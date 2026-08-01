<?php

namespace Tests\Unit\Pancawaluya;

use App\Repositories\Contracts\Pancawaluya\CharacterMappingRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\RewardItemRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\ViolationItemRepositoryInterface;
use App\Services\Pancawaluya\AuditTrailService;
use App\Services\Pancawaluya\RewardItemService;
use App\Services\Pancawaluya\ViolationItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MasterItemServiceTest extends TestCase
{
    public function test_reward_item_restore_calls_repository(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($callback) => $callback());

        $repository = \Mockery::mock(RewardItemRepositoryInterface::class);
        $repository->shouldReceive('restore')->once()->with(21)->andReturn(true);

        $mapping = \Mockery::mock(CharacterMappingRepositoryInterface::class);
        $audit = \Mockery::mock(AuditTrailService::class);
        $audit->shouldReceive('log')->once();

        $service = new RewardItemService($repository, $mapping, $audit);

        $this->assertTrue($service->restore(21, Request::create('/')));
    }

    public function test_violation_item_restore_calls_repository(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn($callback) => $callback());

        $repository = \Mockery::mock(ViolationItemRepositoryInterface::class);
        $repository->shouldReceive('restore')->once()->with(31)->andReturn(true);

        $mapping = \Mockery::mock(CharacterMappingRepositoryInterface::class);
        $audit = \Mockery::mock(AuditTrailService::class);
        $audit->shouldReceive('log')->once();

        $service = new ViolationItemService($repository, $mapping, $audit);

        $this->assertTrue($service->restore(31, Request::create('/')));
    }
}
