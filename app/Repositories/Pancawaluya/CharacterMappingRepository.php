<?php

namespace App\Repositories\Pancawaluya;

use App\Models\CharacterMapping;
use App\Models\RewardItem;
use App\Models\ViolationItem;
use App\Repositories\Contracts\Pancawaluya\CharacterMappingRepositoryInterface;

class CharacterMappingRepository implements CharacterMappingRepositoryInterface
{
    public function syncForRewardItem(int $rewardItemId, int $dimensionId, float $weight): void
    {
        $this->sync(RewardItem::class, $rewardItemId, $dimensionId, $weight);
    }

    public function syncForViolationItem(int $violationItemId, int $dimensionId, float $weight): void
    {
        $this->sync(ViolationItem::class, $violationItemId, $dimensionId, $weight);
    }

    private function sync(string $mappableType, int $mappableId, int $dimensionId, float $weight): void
    {
        CharacterMapping::withTrashed()
            ->where('mappable_type', $mappableType)
            ->where('mappable_id', $mappableId)
            ->forceDelete();

        CharacterMapping::query()->create([
            'mappable_type' => $mappableType,
            'mappable_id' => $mappableId,
            'character_dimension_id' => $dimensionId,
            'weight' => $weight,
            'is_active' => true,
        ]);
    }
}
