<?php

namespace App\Repositories\Contracts\Pancawaluya;

use App\Models\PancawaluyaTransactionHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TransactionHistoryRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function create(array $data): PancawaluyaTransactionHistory;

    public function bulkCreate(array $rows): void;

    public function allForExport(array $filters = []): Collection;
}
