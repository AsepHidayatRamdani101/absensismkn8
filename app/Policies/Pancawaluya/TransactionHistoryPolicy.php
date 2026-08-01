<?php

namespace App\Policies\Pancawaluya;

use App\Models\User;

class TransactionHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'kesiswaan', 'bk', 'siswa']);
    }

    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'kesiswaan', 'bk']);
    }
}
