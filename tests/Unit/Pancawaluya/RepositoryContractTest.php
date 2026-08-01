<?php

namespace Tests\Unit\Pancawaluya;

use App\Repositories\Contracts\Pancawaluya\RewardCategoryRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\RewardItemRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\ViolationCategoryRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\ViolationItemRepositoryInterface;
use App\Repositories\Pancawaluya\RewardCategoryRepository;
use App\Repositories\Pancawaluya\RewardItemRepository;
use App\Repositories\Pancawaluya\ViolationCategoryRepository;
use App\Repositories\Pancawaluya\ViolationItemRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepositoryContractTest extends TestCase
{
    #[Test]
    public function reward_category_repository_implements_contract(): void
    {
        $this->assertInstanceOf(RewardCategoryRepositoryInterface::class, new RewardCategoryRepository());
    }

    #[Test]
    public function reward_repository_implements_contract(): void
    {
        $this->assertInstanceOf(RewardItemRepositoryInterface::class, new RewardItemRepository());
    }

    #[Test]
    public function violation_category_repository_implements_contract(): void
    {
        $this->assertInstanceOf(ViolationCategoryRepositoryInterface::class, new ViolationCategoryRepository());
    }

    #[Test]
    public function violation_repository_implements_contract(): void
    {
        $this->assertInstanceOf(ViolationItemRepositoryInterface::class, new ViolationItemRepository());
    }
}
