<?php

namespace Tests\Unit\Pancawaluya;

use App\Models\User;
use App\Models\ViolationItem;
use App\Policies\Pancawaluya\ViolationPolicy;
use Tests\TestCase;

class ViolationPolicyTest extends TestCase
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

    public function test_admin_can_manage_violation(): void
    {
        $user = $this->makeUser(true, true);

        $policy = new ViolationPolicy();
        $item = new ViolationItem();

        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $item));
        $this->assertTrue($policy->delete($user, $item));
    }

    public function test_non_admin_read_only_on_violation(): void
    {
        $user = $this->makeUser(false, true);

        $policy = new ViolationPolicy();
        $item = new ViolationItem();

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->delete($user, $item));
    }
}
