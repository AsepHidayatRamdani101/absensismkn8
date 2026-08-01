<?php

namespace App\Repositories\Contracts\Pancawaluya;

use App\Models\ViolationTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ViolationTransactionRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id, bool $withTrashed = false): ?ViolationTransaction;

    public function create(array $data): ViolationTransaction;

    public function update(ViolationTransaction $transaction, array $data): ViolationTransaction;

    public function softDelete(ViolationTransaction $transaction): void;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    public function bulkSoftDelete(array $ids): int;

    public function bulkRestore(array $ids): int;

    public function existsDuplicate(int $studentId, int $itemId, string $date, ?int $ignoreId = null): bool;

    public function allForExport(array $filters = [], bool $withTrashed = true): Collection;
}
