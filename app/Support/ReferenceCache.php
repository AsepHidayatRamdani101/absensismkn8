<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReferenceCache
{
    public static function forgetAcademicReferences(): void
    {
        $keys = [
            'students:majors:list',
            'students:classrooms:list',
            'reports:filters:majors',
            'reports:filters:classrooms',
            'reports:filters:students',
        ];

        self::forgetMany($keys, 'academic_references');
    }

    public static function forgetStudentReferences(): void
    {
        self::forgetMany(['reports:filters:students'], 'student_references');
    }

    public static function forgetTeacherReferences(): void
    {
        self::forgetMany(['reports:filters:teachers'], 'teacher_references');
    }

    public static function forgetSchoolSettings(): void
    {
        self::forgetMany(['school-settings:first'], 'school_settings');
    }

    /**
     * @param array<int, string> $keys
     */
    private static function forgetMany(array $keys, string $group): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        if (app()->environment(['local', 'development']) || (bool) config('app.debug')) {
            Log::debug('reference-cache-invalidated', [
                'group' => $group,
                'keys' => $keys,
            ]);
        }
    }
}
