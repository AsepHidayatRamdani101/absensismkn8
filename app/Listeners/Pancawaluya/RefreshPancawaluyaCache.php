<?php

namespace App\Listeners\Pancawaluya;

use App\Services\Dashboard\DashboardCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class RefreshPancawaluyaCache implements ShouldQueue
{
    public function __construct(private readonly DashboardCacheService $dashboardCacheService) {}

    public function handle(object $event): void
    {
        $studentId = $event->studentId ?? null;

        if ($studentId === null) {
            return;
        }

        Cache::forget('pancawaluya:character-score:' . $studentId);
        Cache::forget('pancawaluya:statistics:' . $studentId);
        Cache::forget('pancawaluya:sp-status:' . $studentId);
        $this->dashboardCacheService->bumpVersion();
    }
}
