<?php

namespace App\Policies\Pancawaluya;

use App\Models\RewardTransaction;
use App\Models\Student;
use App\Models\User;

class RewardTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'kesiswaan', 'bk', 'siswa']);
    }

    public function view(User $user, RewardTransaction $transaction): bool
    {
        if ($user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'kesiswaan', 'bk'])) {
            return true;
        }

        return $this->isStudentOwner($user, (int) $transaction->student_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'kesiswaan']);
    }

    public function update(User $user, RewardTransaction $transaction): bool
    {
        if ($user->hasAnyRole(['admin', 'kesiswaan'])) {
            return true;
        }

        return $user->hasAnyRole(['guru', 'wali_kelas']) && (int) $transaction->created_by === (int) $user->id;
    }

    public function delete(User $user, RewardTransaction $transaction): bool
    {
        if ($user->hasAnyRole(['admin', 'kesiswaan'])) {
            return true;
        }

        return $user->hasAnyRole(['guru', 'wali_kelas']) && (int) $transaction->created_by === (int) $user->id;
    }

    public function restore(User $user, RewardTransaction $transaction): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, RewardTransaction $transaction): bool
    {
        return $user->hasRole('admin');
    }

    public function approve(User $user): bool
    {
        return $user->hasRole('admin');
    }

    private function isStudentOwner(User $user, int $studentId): bool
    {
        if (!$user->hasRole('siswa')) {
            return false;
        }

        return Student::query()
            ->whereKey($studentId)
            ->where(function ($query) use ($user): void {
                $query->where('nisn', (string) $user->email)
                    ->orWhere('nis', (string) $user->email);
            })
            ->exists();
    }
}
