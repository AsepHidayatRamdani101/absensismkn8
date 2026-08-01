<?php

namespace App\Repositories\Contracts\Pancawaluya;

use App\Models\ViolationCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ViolationCategoryRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id, bool $withTrashed = false): ?ViolationCategory;

    public function create(array $data): ViolationCategory;

    public function update(ViolationCategory $category, array $data): ViolationCategory;

    public function softDelete(ViolationCategory $category): void;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    public function bulkSoftDelete(array $ids): int;

    public function bulkRestore(array $ids): int;

    public function allForExport(array $filters = [], bool $withTrashed = false): Collection;
}
