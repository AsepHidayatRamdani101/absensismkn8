<?php

namespace App\Policies\Pancawaluya;

use App\Models\RewardItem;
use App\Models\User;

class RewardPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isReadableRole($user);
    }

    public function view(User $user, RewardItem $rewardItem): bool
    {
        return $this->isReadableRole($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, RewardItem $rewardItem): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, RewardItem $rewardItem): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, RewardItem $rewardItem): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, RewardItem $rewardItem): bool
    {
        return $user->hasRole('admin');
    }

    private function isReadableRole(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan']);
    }
}
