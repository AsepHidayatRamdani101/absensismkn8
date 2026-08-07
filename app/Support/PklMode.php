<?php

namespace App\Support;

use App\Models\SchoolSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PklMode
{
    private const CACHE_KEY_FALLBACK = 'mode:pkl:active';
    private const CACHE_KEY_DB = 'mode:pkl:active:db';

    private static ?bool $supportsDatabaseStorage = null;

    public static function isActive(): bool
    {
        if (self::supportsDatabaseStorage()) {
            return (bool) Cache::remember(self::CACHE_KEY_DB, now()->addMinutes(5), function () {
                $fallbackValue = (bool) Cache::get(self::CACHE_KEY_FALLBACK, false);
                $setting = SchoolSetting::query()->first();

                if (!$setting) {
                    return $fallbackValue;
                }

                $dbValue = (bool) $setting->pkl_mode_active;

                // One-time sync: preserve legacy cache-based "active" state after moving to DB storage.
                if (!$dbValue && $fallbackValue) {
                    $setting->update(['pkl_mode_active' => true]);
                    return true;
                }

                return $dbValue;
            });
        }

        return (bool) Cache::get(self::CACHE_KEY_FALLBACK, false);
    }

    public static function setActive(bool $active): void
    {
        if (self::supportsDatabaseStorage()) {
            $setting = SchoolSetting::query()->first();

            if (!$setting) {
                $setting = SchoolSetting::query()->create([
                    'nama_sekolah' => 'SMKN 8 GARUT',
                    'jam_masuk' => '07:00',
                    'batas_terlambat' => 15,
                    'pkl_mode_active' => $active,
                ]);
            } else {
                $setting->update(['pkl_mode_active' => $active]);
            }

            Cache::forget(self::CACHE_KEY_DB);
            ReferenceCache::forgetSchoolSettings();
        }

        Cache::forever(self::CACHE_KEY_FALLBACK, $active);
    }

    public static function applyToScheduleQuery(Builder $query): Builder
    {
        if (!self::isActive()) {
            return $query;
        }

        return $query->whereHas('teacherSubject.classroom', function (Builder $classroomQuery) {
            $classroomQuery->where('tingkat', '!=', 'XII');
        });
    }

    public static function excludesClassroomLevel(?string $tingkat): bool
    {
        return self::isActive() && strtoupper((string) $tingkat) === 'XII';
    }

    private static function supportsDatabaseStorage(): bool
    {
        if (self::$supportsDatabaseStorage !== null) {
            return self::$supportsDatabaseStorage;
        }

        self::$supportsDatabaseStorage = Schema::hasTable('school_settings')
            && Schema::hasColumn('school_settings', 'pkl_mode_active');

        return self::$supportsDatabaseStorage;
    }
}
