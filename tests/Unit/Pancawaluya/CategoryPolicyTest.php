<?php

namespace Tests\Unit\Pancawaluya;

use App\Models\RewardCategory;
use App\Models\User;
use App\Models\ViolationCategory;
use App\Policies\Pancawaluya\RewardCategoryPolicy;
use App\Policies\Pancawaluya\ViolationCategoryPolicy;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    private function makeUser(bool $isAdmin, bool $isReadable): User
    {
        return new class($isAdmin, $isReadable) extends User {
            public function __construct(private readonly bool $isAdminFlag, private readonly bool $isReadableFlag) {}

            public function hasRole($roles, ?string $guard = null): bool
            {
                return $this->isAdminFlag;
            }

            public function hasAnyRole(...$roles): bool
            {
                return $this->isReadableFlag;
            }
        };
    }

    public function test_reward_category_admin_permissions(): void
    {
        $user = $this->makeUser(true, true);

        $policy = new RewardCategoryPolicy();
        $model = new RewardCategory();

        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $model));
    }

    public function test_violation_category_read_only_non_admin(): void
    {
        $user = $this->makeUser(false, true);

        $policy = new ViolationCategoryPolicy();
        $model = new ViolationCategory();

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->delete($user, $model));
    }
}
