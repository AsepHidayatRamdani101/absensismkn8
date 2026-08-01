<?php

namespace App\Repositories\Pancawaluya;

use App\Models\ViolationCategory;
use App\Repositories\Contracts\Pancawaluya\ViolationCategoryRepositoryInterface;
use App\Repositories\Pancawaluya\Concerns\AppliesMasterFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ViolationCategoryRepository implements ViolationCategoryRepositoryInterface
{
    use AppliesMasterFilters;

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = ViolationCategory::query();
        $this->applyBaseFilters($query, $filters, ['code', 'name', 'description']);

        return $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id, bool $withTrashed = false): ?ViolationCategory
    {
        $query = ViolationCategory::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): ViolationCategory
    {
        return ViolationCategory::query()->create($data);
    }

    public function update(ViolationCategory $category, array $data): ViolationCategory
    {
        $category->update($data);

        return $category->refresh();
    }

    public function softDelete(ViolationCategory $category): void
    {
        $category->delete();
    }

    public function restore(int $id): bool
    {
        return ViolationCategory::onlyTrashed()->whereKey($id)->restore() > 0;
    }

    public function forceDelete(int $id): bool
    {
        return ViolationCategory::onlyTrashed()->whereKey($id)->forceDelete() > 0;
    }

    public function bulkSoftDelete(array $ids): int
    {
        return ViolationCategory::query()->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(array $ids): int
    {
        return ViolationCategory::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function allForExport(array $filters = [], bool $withTrashed = false): Collection
    {
        $query = ViolationCategory::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        $this->applyBaseFilters($query, $filters, ['code', 'name', 'description']);

        return $query->orderBy('code')->get();
    }
}
