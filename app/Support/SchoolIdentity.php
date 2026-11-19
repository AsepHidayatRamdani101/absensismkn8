<?php

namespace App\Support;

use App\Models\StaffTu;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\Auth\Authenticatable;

class SchoolIdentity
{
    private array $teachers = [];

    private array $students = [];

    private array $staff = [];

    public static function teacherFor(?Authenticatable $user): ?Teacher
    {
        return app(self::class)->teacher($user);
    }

    public static function studentFor(?Authenticatable $user, bool $allowNameFallback = false): ?Student
    {
        return app(self::class)->student($user, $allowNameFallback);
    }

    public static function staffTuFor(?Authenticatable $user): ?StaffTu
    {
        return app(self::class)->staffTu($user);
    }

    public static function waliTeacherFor(?Authenticatable $user): ?Teacher
    {
        $teacher = self::teacherFor($user);

        return $teacher && $teacher->is_wali_kelas && $teacher->wali_classroom_id ? $teacher : null;
    }

    public function teacher(?Authenticatable $user): ?Teacher
    {
        if (!$user) {
            return null;
        }

        $key = $this->cacheKey($user);

        if (!array_key_exists($key, $this->teachers)) {
            $this->teachers[$key] = $this->resolveTeacher($user);
        }

        return $this->teachers[$key];
    }

    public function student(?Authenticatable $user, bool $allowNameFallback = false): ?Student
    {
        if (!$user) {
            return null;
        }

        $key = $this->cacheKey($user) . '|' . ($allowNameFallback ? 'name' : 'strict');

        if (!array_key_exists($key, $this->students)) {
            $this->students[$key] = $this->resolveStudent($user, $allowNameFallback);
        }

        return $this->students[$key];
    }

    public function staffTu(?Authenticatable $user): ?StaffTu
    {
        if (!$user) {
            return null;
        }

        $key = $this->cacheKey($user);

        if (!array_key_exists($key, $this->staff)) {
            $this->staff[$key] = $this->resolveStaffTu($user);
        }

        return $this->staff[$key];
    }

    public function flush(): void
    {
        $this->teachers = [];
        $this->students = [];
        $this->staff = [];
    }

    private function resolveTeacher(Authenticatable $user): ?Teacher
    {
        $username = $this->username($user);

        if ($username !== '') {
            $teacher = Teacher::query()->where('nip', $username)->orderBy('id')->first()
                ?? Teacher::query()->where('nuptk', $username)->orderBy('id')->first();

            if ($teacher) {
                return $teacher;
            }
        }

        // Cadangan untuk akun lama: cocokkan nama hanya bila tidak kembar.
        return $this->uniqueByName(Teacher::query(), $user);
    }

    private function resolveStudent(Authenticatable $user, bool $allowNameFallback): ?Student
    {
        $username = $this->username($user);

        if ($username !== '') {
            $student = Student::query()->where('nisn', $username)->orderBy('id')->first()
                ?? Student::query()->where('nis', $username)->orderBy('id')->first();

            if ($student) {
                return $student;
            }
        }

        return $allowNameFallback ? $this->uniqueByName(Student::query(), $user) : null;
    }

    private function resolveStaffTu(Authenticatable $user): ?StaffTu
    {
        $username = $this->username($user);

        if ($username !== '') {
            $staff = StaffTu::query()->where('nip', $username)->orderBy('id')->first();

            if ($staff) {
                return $staff;
            }
        }

        return $this->uniqueByName(StaffTu::query(), $user);
    }

    private function uniqueByName($query, Authenticatable $user)
    {
        $name = trim((string) ($user->name ?? ''));

        if ($name === '') {
            return null;
        }

        $matches = $query->where('nama_lengkap', $name)->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function username(Authenticatable $user): string
    {
        return trim((string) ($user->email ?? ''));
    }

    private function cacheKey(Authenticatable $user): string
    {
        return $user->getAuthIdentifier() . '|' . $this->username($user) . '|' . ($user->name ?? '');
    }
}
