<?php

namespace App\Repositories\Pancawaluya;

use App\Models\RewardCategory;
use App\Repositories\Contracts\Pancawaluya\RewardCategoryRepositoryInterface;
use App\Repositories\Pancawaluya\Concerns\AppliesMasterFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RewardCategoryRepository implements RewardCategoryRepositoryInterface
{
    use AppliesMasterFilters;

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = RewardCategory::query();
        $this->applyBaseFilters($query, $filters, ['code', 'name', 'description']);

        return $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id, bool $withTrashed = false): ?RewardCategory
    {
        $query = RewardCategory::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): RewardCategory
    {
        return RewardCategory::query()->create($data);
    }

    public function update(RewardCategory $category, array $data): RewardCategory
    {
        $category->update($data);

        return $category->refresh();
    }

    public function softDelete(RewardCategory $category): void
    {
        $category->delete();
    }

    public function restore(int $id): bool
    {
        return RewardCategory::onlyTrashed()->whereKey($id)->restore() > 0;
    }

    public function forceDelete(int $id): bool
    {
        return RewardCategory::onlyTrashed()->whereKey($id)->forceDelete() > 0;
    }

    public function bulkSoftDelete(array $ids): int
    {
        return RewardCategory::query()->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(array $ids): int
    {
        return RewardCategory::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function allForExport(array $filters = [], bool $withTrashed = false): Collection
    {
        $query = RewardCategory::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        $this->applyBaseFilters($query, $filters, ['code', 'name', 'description']);

        return $query->orderBy('code')->get();
    }
}
