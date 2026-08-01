<?php

namespace App\Providers;

use App\Events\Pancawaluya\CharacterUpdated;
use App\Events\Pancawaluya\RewardCreated;
use App\Events\Pancawaluya\RewardDeleted;
use App\Events\Pancawaluya\RewardRestored;
use App\Events\Pancawaluya\RewardUpdated;
use App\Events\Pancawaluya\SPGenerated;
use App\Events\Pancawaluya\SPUpdated;
use App\Events\Pancawaluya\ViolationCreated;
use App\Events\Pancawaluya\ViolationDeleted;
use App\Events\Pancawaluya\ViolationRestored;
use App\Events\Pancawaluya\ViolationUpdated;
use App\Listeners\Pancawaluya\RefreshPancawaluyaCache;
use App\Listeners\Pancawaluya\SendRewardTransactionNotification;
use App\Listeners\Pancawaluya\SendSPNotification;
use App\Listeners\Pancawaluya\SendViolationTransactionNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        RewardCreated::class => [SendRewardTransactionNotification::class, RefreshPancawaluyaCache::class],
        RewardUpdated::class => [SendRewardTransactionNotification::class, RefreshPancawaluyaCache::class],
        RewardDeleted::class => [SendRewardTransactionNotification::class, RefreshPancawaluyaCache::class],
        RewardRestored::class => [SendRewardTransactionNotification::class, RefreshPancawaluyaCache::class],
        ViolationCreated::class => [SendViolationTransactionNotification::class, RefreshPancawaluyaCache::class],
        ViolationUpdated::class => [SendViolationTransactionNotification::class, RefreshPancawaluyaCache::class],
        ViolationDeleted::class => [SendViolationTransactionNotification::class, RefreshPancawaluyaCache::class],
        ViolationRestored::class => [SendViolationTransactionNotification::class, RefreshPancawaluyaCache::class],
        SPGenerated::class => [SendSPNotification::class, RefreshPancawaluyaCache::class],
        SPUpdated::class => [SendSPNotification::class, RefreshPancawaluyaCache::class],
        CharacterUpdated::class => [RefreshPancawaluyaCache::class],
    ];
}
