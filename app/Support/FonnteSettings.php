<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use RuntimeException;
use UnexpectedValueException;

final class FonnteSettings
{
    private const FILE_PATH = 'app/private/fonnte-settings.json';

    private const KEYS = [
        'enabled',
        'token',
        'api_url',
        'default_country_code',
        'reminder_enabled',
        'reminder_time',
        'teacher_reminder_minutes',
        'student_reminder_minutes',
        'school_name',
    ];

    public static function load(): void
    {
        $path = storage_path(self::FILE_PATH);

        if (!File::exists($path)) {
            return;
        }

        $settings = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($settings)) {
            throw new UnexpectedValueException('Stored Fonnte settings must be a JSON object.');
        }

        $settings = array_intersect_key($settings, array_flip(self::KEYS));
        Config::set('services.fonnte', array_merge(config('services.fonnte', []), $settings));
    }

    public static function save(array $settings): void
    {
        $settings = array_intersect_key($settings, array_flip(self::KEYS));
        $path = storage_path(self::FILE_PATH);

        File::ensureDirectoryExists(dirname($path), 0700, true);

        $written = File::put(
            $path,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL,
            true
        );

        if ($written === false) {
            throw new RuntimeException('Unable to save Fonnte settings.');
        }

        Config::set('services.fonnte', array_merge(config('services.fonnte', []), $settings));
    }
}
