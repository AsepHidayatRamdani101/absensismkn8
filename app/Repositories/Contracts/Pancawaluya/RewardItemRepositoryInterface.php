<?php

namespace App\Repositories\Contracts\Pancawaluya;

use App\Models\RewardItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RewardItemRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id, bool $withTrashed = false): ?RewardItem;

    public function create(array $data): RewardItem;

    public function update(RewardItem $item, array $data): RewardItem;

    public function softDelete(RewardItem $item): void;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    public function bulkSoftDelete(array $ids): int;

    public function bulkRestore(array $ids): int;

    public function allForExport(array $filters = [], bool $withTrashed = false): Collection;
}
