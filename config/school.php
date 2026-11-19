<?php

return [

    'force_https' => (bool) env('APP_FORCE_HTTPS', false),

    'trusted_proxies' => env('TRUSTED_PROXIES'),

    'backup' => [
        'db_keep_days' => (int) env('BACKUP_DB_KEEP_DAYS', 14),
        'full_keep_days' => (int) env('BACKUP_FULL_KEEP_DAYS', 56),
    ],

    'shared_report_keep_days' => (int) env('SHARED_REPORT_KEEP_DAYS', 7),

    'seed_admin' => [
        'email' => env('SEED_ADMIN_EMAIL', 'admin@smkn8.sch.id'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

];
