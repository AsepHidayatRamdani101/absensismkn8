<?php

namespace App\Policies\Pancawaluya;

use App\Models\User;
use App\Models\ViolationCategory;

class ViolationCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isReadableRole($user);
    }

    public function view(User $user, ViolationCategory $violationCategory): bool
    {
        return $this->isReadableRole($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, ViolationCategory $violationCategory): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, ViolationCategory $violationCategory): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, ViolationCategory $violationCategory): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, ViolationCategory $violationCategory): bool
    {
        return $user->hasRole('admin');
    }

    private function isReadableRole(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan']);
    }
}
