<?php

namespace App\Repositories\Pancawaluya;

use App\Models\RewardItem;
use App\Repositories\Contracts\Pancawaluya\RewardItemRepositoryInterface;
use App\Repositories\Pancawaluya\Concerns\AppliesMasterFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RewardItemRepository implements RewardItemRepositoryInterface
{
    use AppliesMasterFilters;

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = RewardItem::query()->with(['category', 'mappings.dimension']);
        $this->applyBaseFilters($query, $filters, ['code', 'name', 'description']);

        if (!empty($filters['category_id'])) {
            $query->where('reward_category_id', $filters['category_id']);
        }

        if (!empty($filters['dimension_id'])) {
            $query->whereHas('mappings', function ($builder) use ($filters): void {
                $builder->where('character_dimension_id', $filters['dimension_id']);
            });
        }

        return $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id, bool $withTrashed = false): ?RewardItem
    {
        $query = RewardItem::query()->with(['category', 'mappings.dimension']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): RewardItem
    {
        return RewardItem::query()->create($data);
    }

    public function update(RewardItem $item, array $data): RewardItem
    {
        $item->update($data);

        return $item->refresh();
    }

    public function softDelete(RewardItem $item): void
    {
        $item->delete();
    }

    public function restore(int $id): bool
    {
        return RewardItem::onlyTrashed()->whereKey($id)->restore() > 0;
    }

    public function forceDelete(int $id): bool
    {
        return RewardItem::onlyTrashed()->whereKey($id)->forceDelete() > 0;
    }

    public function bulkSoftDelete(array $ids): int
    {
        return RewardItem::query()->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(array $ids): int
    {
        return RewardItem::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function allForExport(array $filters = [], bool $withTrashed = false): Collection
    {
        $query = RewardItem::query()->with(['category', 'mappings.dimension']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        $this->applyBaseFilters($query, $filters, ['code', 'name', 'description']);

        if (!empty($filters['category_id'])) {
            $query->where('reward_category_id', $filters['category_id']);
        }

        if (!empty($filters['dimension_id'])) {
            $query->whereHas('mappings', function ($builder) use ($filters): void {
                $builder->where('character_dimension_id', $filters['dimension_id']);
            });
        }

        return $query->orderBy('code')->get();
    }
}
