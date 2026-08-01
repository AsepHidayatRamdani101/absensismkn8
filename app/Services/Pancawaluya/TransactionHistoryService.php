<?php

namespace App\Services\Pancawaluya;

use App\Repositories\Contracts\Pancawaluya\TransactionHistoryRepositoryInterface;

class TransactionHistoryService
{
    public function __construct(private readonly TransactionHistoryRepositoryInterface $repository) {}

    public function paginate(array $filters, int $perPage = 10)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function allForExport(array $filters = [])
    {
        return $this->repository->allForExport($filters);
    }
}
