<?php

namespace App\Repositories\Contracts\Pancawaluya;

use App\Models\ViolationItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ViolationItemRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id, bool $withTrashed = false): ?ViolationItem;

    public function create(array $data): ViolationItem;

    public function update(ViolationItem $item, array $data): ViolationItem;

    public function softDelete(ViolationItem $item): void;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    public function bulkSoftDelete(array $ids): int;

    public function bulkRestore(array $ids): int;

    public function allForExport(array $filters = [], bool $withTrashed = false): Collection;
}
