<?php

namespace App\Repositories\Pancawaluya;

use App\Models\PancawaluyaTransactionHistory;
use App\Repositories\Contracts\Pancawaluya\TransactionHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TransactionHistoryRepository implements TransactionHistoryRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = PancawaluyaTransactionHistory::query()->with([
            'student.classroom.major',
            'classroom.major',
            'academicYear',
            'actor',
        ]);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('transaction_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): PancawaluyaTransactionHistory
    {
        return PancawaluyaTransactionHistory::query()->create($data);
    }

    public function bulkCreate(array $rows): void
    {
        PancawaluyaTransactionHistory::query()->insert($rows);
    }

    public function allForExport(array $filters = []): Collection
    {
        $query = PancawaluyaTransactionHistory::query()->with([
            'student.classroom.major',
            'classroom.major',
            'academicYear',
            'actor',
        ]);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('transaction_date')->orderByDesc('id')->get();
    }

    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('reference_type', 'like', '%' . $search . '%')
                    ->orWhere('action', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', '%' . $search . '%')
                    ->orWhereHas('student', function ($s) use ($search): void {
                        $s->where('nama_lengkap', 'like', '%' . $search . '%')
                            ->orWhere('nis', 'like', '%' . $search . '%')
                            ->orWhere('nisn', 'like', '%' . $search . '%');
                    });
            });
        }

        foreach (['academic_year_id', 'semester', 'student_id', 'classroom_id', 'status', 'source'] as $filterKey) {
            if (!empty($filters[$filterKey])) {
                $query->where($filterKey, $filters[$filterKey]);
            }
        }

        if (!empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['to_date']);
        }
    }
}
