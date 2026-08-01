<?php

namespace App\Repositories\Contracts\Pancawaluya;

interface CharacterMappingRepositoryInterface
{
    public function syncForRewardItem(int $rewardItemId, int $dimensionId, float $weight): void;

    public function syncForViolationItem(int $violationItemId, int $dimensionId, float $weight): void;
}
