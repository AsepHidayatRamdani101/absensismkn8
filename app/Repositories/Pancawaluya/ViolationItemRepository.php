<?php

namespace App\Repositories\Pancawaluya;

use App\Models\ViolationItem;
use App\Repositories\Contracts\Pancawaluya\ViolationItemRepositoryInterface;
use App\Repositories\Pancawaluya\Concerns\AppliesMasterFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ViolationItemRepository implements ViolationItemRepositoryInterface
{
    use AppliesMasterFilters;

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = ViolationItem::query()->with(['category', 'mappings.dimension']);
        $this->applyBaseFilters($query, $filters, ['code', 'name', 'description']);

        if (!empty($filters['category_id'])) {
            $query->where('violation_category_id', $filters['category_id']);
        }

        if (!empty($filters['dimension_id'])) {
            $query->whereHas('mappings', function ($builder) use ($filters): void {
                $builder->where('character_dimension_id', $filters['dimension_id']);
            });
        }

        return $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id, bool $withTrashed = false): ?ViolationItem
    {
        $query = ViolationItem::query()->with(['category', 'mappings.dimension']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): ViolationItem
    {
        return ViolationItem::query()->create($data);
    }

    public function update(ViolationItem $item, array $data): ViolationItem
    {
        $item->update($data);

        return $item->refresh();
    }

    public function softDelete(ViolationItem $item): void
    {
        $item->delete();
    }

    public function restore(int $id): bool
    {
        return ViolationItem::onlyTrashed()->whereKey($id)->restore() > 0;
    }

    public function forceDelete(int $id): bool
    {
        return ViolationItem::onlyTrashed()->whereKey($id)->forceDelete() > 0;
    }

    public function bulkSoftDelete(array $ids): int
    {
        return ViolationItem::query()->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(array $ids): int
    {
        return ViolationItem::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function allForExport(array $filters = [], bool $withTrashed = false): Collection
    {
        $query = ViolationItem::query()->with(['category', 'mappings.dimension']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        $this->applyBaseFilters($query, $filters, ['code', 'name', 'description']);

        if (!empty($filters['category_id'])) {
            $query->where('violation_category_id', $filters['category_id']);
        }

        if (!empty($filters['dimension_id'])) {
            $query->whereHas('mappings', function ($builder) use ($filters): void {
                $builder->where('character_dimension_id', $filters['dimension_id']);
            });
        }

        return $query->orderBy('code')->get();
    }
}
