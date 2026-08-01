<?php

namespace App\Policies\Pancawaluya;

use App\Models\Student;
use App\Models\User;
use App\Models\ViolationTransaction;

class ViolationTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'kesiswaan', 'bk', 'siswa']);
    }

    public function view(User $user, ViolationTransaction $transaction): bool
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

    public function update(User $user, ViolationTransaction $transaction): bool
    {
        if ($user->hasAnyRole(['admin', 'kesiswaan'])) {
            return true;
        }

        return $user->hasAnyRole(['guru', 'wali_kelas']) && (int) $transaction->created_by === (int) $user->id;
    }

    public function delete(User $user, ViolationTransaction $transaction): bool
    {
        if ($user->hasAnyRole(['admin', 'kesiswaan'])) {
            return true;
        }

        return $user->hasAnyRole(['guru', 'wali_kelas']) && (int) $transaction->created_by === (int) $user->id;
    }

    public function restore(User $user, ViolationTransaction $transaction): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, ViolationTransaction $transaction): bool
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
