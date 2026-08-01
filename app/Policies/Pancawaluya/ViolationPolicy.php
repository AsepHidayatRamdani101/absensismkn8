<?php

namespace App\Policies\Pancawaluya;

use App\Models\User;
use App\Models\ViolationItem;

class ViolationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isReadableRole($user);
    }

    public function view(User $user, ViolationItem $violationItem): bool
    {
        return $this->isReadableRole($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, ViolationItem $violationItem): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, ViolationItem $violationItem): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, ViolationItem $violationItem): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, ViolationItem $violationItem): bool
    {
        return $user->hasRole('admin');
    }

    private function isReadableRole(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan']);
    }
}
