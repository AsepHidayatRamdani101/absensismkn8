<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    public function remember(string $roleKey, string $scopeKey, array $filters, callable $resolver): array
    {
        $fingerprint = md5(json_encode($filters));
        $version = (int) Cache::get('dashboard:dss:version', 1);
        $key = 'dashboard:dss:v' . $version . ':' . $roleKey . ':' . $scopeKey . ':' . $fingerprint;

        return Cache::remember($key, now()->addMinutes(2), $resolver);
    }

    public function bumpVersion(): void
    {
        if (!Cache::has('dashboard:dss:version')) {
            Cache::forever('dashboard:dss:version', 1);
        }

        Cache::increment('dashboard:dss:version');
    }

    public function flushByPrefix(): void
    {
        // For file/database cache drivers without tags, we rely on short TTL.
        // Explicit flush can be called from maintenance jobs when needed.
    }
}
