<?php

namespace Tests\Unit\Pancawaluya;

use App\Models\RewardItem;
use App\Models\User;
use App\Policies\Pancawaluya\RewardPolicy;
use Tests\TestCase;

class RewardPolicyTest extends TestCase
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

    public function test_admin_can_create_update_delete_reward(): void
    {
        $user = $this->makeUser(true, true);

        $policy = new RewardPolicy();
        $reward = new RewardItem();

        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $reward));
        $this->assertTrue($policy->delete($user, $reward));
    }

    public function test_teacher_is_read_only_for_reward_master(): void
    {
        $user = $this->makeUser(false, true);

        $policy = new RewardPolicy();
        $reward = new RewardItem();

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->delete($user, $reward));
    }
}
