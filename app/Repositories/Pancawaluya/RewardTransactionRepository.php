<?php

namespace App\Repositories\Pancawaluya;

use App\Models\RewardTransaction;
use App\Repositories\Contracts\Pancawaluya\RewardTransactionRepositoryInterface;
use App\Repositories\Pancawaluya\Concerns\AppliesMasterFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RewardTransactionRepository implements RewardTransactionRepositoryInterface
{
    use AppliesMasterFilters;

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = RewardTransaction::query()->with([
            'academicYear',
            'student.classroom.major',
            'classroom.major',
            'rewardCategory',
            'rewardItem',
            'teacher',
            'creator',
        ]);

        $this->applyBaseFilters($query, $filters, ['source', 'description', 'status']);
        $this->applyTransactionFilters($query, $filters);

        return $query->orderByDesc('transaction_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id, bool $withTrashed = false): ?RewardTransaction
    {
        $query = RewardTransaction::query()->with([
            'academicYear',
            'student.classroom.major',
            'classroom.major',
            'rewardCategory',
            'rewardItem.mappings.dimension',
            'teacher',
            'creator',
        ]);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): RewardTransaction
    {
        return RewardTransaction::query()->create($data);
    }

    public function update(RewardTransaction $transaction, array $data): RewardTransaction
    {
        $transaction->update($data);

        return $transaction->refresh();
    }

    public function softDelete(RewardTransaction $transaction): void
    {
        $transaction->delete();
    }

    public function restore(int $id): bool
    {
        return RewardTransaction::onlyTrashed()->whereKey($id)->restore() > 0;
    }

    public function forceDelete(int $id): bool
    {
        return RewardTransaction::onlyTrashed()->whereKey($id)->forceDelete() > 0;
    }

    public function bulkSoftDelete(array $ids): int
    {
        return RewardTransaction::query()->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(array $ids): int
    {
        return RewardTransaction::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function existsDuplicate(int $studentId, int $itemId, string $date, ?int $ignoreId = null): bool
    {
        $query = RewardTransaction::query()
            ->where('student_id', $studentId)
            ->where('reward_item_id', $itemId)
            ->whereDate('transaction_date', $date);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->whereNull('deleted_at')->exists();
    }

    public function allForExport(array $filters = [], bool $withTrashed = true): Collection
    {
        $query = RewardTransaction::query()->with([
            'academicYear',
            'student.classroom.major',
            'classroom.major',
            'rewardCategory',
            'rewardItem',
            'teacher',
            'creator',
        ]);

        if ($withTrashed) {
            $query->withTrashed();
        }

        $this->applyBaseFilters($query, $filters, ['source', 'description', 'status']);
        $this->applyTransactionFilters($query, $filters);

        return $query->orderByDesc('transaction_date')->orderByDesc('id')->get();
    }

    private function applyTransactionFilters($query, array $filters): void
    {
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (!empty($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (!empty($filters['classroom_id'])) {
            $query->where('classroom_id', $filters['classroom_id']);
        }

        if (!empty($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('reward_category_id', $filters['category_id']);
        }

        if (!empty($filters['item_id'])) {
            $query->where('reward_item_id', $filters['item_id']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', 'like', '%' . $filters['source'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['to_date']);
        }

        if (!empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        }
    }
}
