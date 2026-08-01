<?php

namespace App\Repositories\Contracts\Pancawaluya;

use App\Models\RewardCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RewardCategoryRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id, bool $withTrashed = false): ?RewardCategory;

    public function create(array $data): RewardCategory;

    public function update(RewardCategory $category, array $data): RewardCategory;

    public function softDelete(RewardCategory $category): void;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    public function bulkSoftDelete(array $ids): int;

    public function bulkRestore(array $ids): int;

    public function allForExport(array $filters = [], bool $withTrashed = false): Collection;
}
