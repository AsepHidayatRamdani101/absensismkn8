<?php

namespace App\Policies\Pancawaluya;

use App\Models\RewardCategory;
use App\Models\User;

class RewardCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isReadableRole($user);
    }

    public function view(User $user, RewardCategory $rewardCategory): bool
    {
        return $this->isReadableRole($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, RewardCategory $rewardCategory): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, RewardCategory $rewardCategory): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, RewardCategory $rewardCategory): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, RewardCategory $rewardCategory): bool
    {
        return $user->hasRole('admin');
    }

    private function isReadableRole(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan']);
    }
}
