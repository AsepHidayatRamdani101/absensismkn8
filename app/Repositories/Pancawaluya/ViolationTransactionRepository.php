<?php

namespace App\Repositories\Pancawaluya;

use App\Models\ViolationTransaction;
use App\Repositories\Contracts\Pancawaluya\ViolationTransactionRepositoryInterface;
use App\Repositories\Pancawaluya\Concerns\AppliesMasterFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ViolationTransactionRepository implements ViolationTransactionRepositoryInterface
{
    use AppliesMasterFilters;

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = ViolationTransaction::query()->with([
            'academicYear',
            'student.classroom.major',
            'classroom.major',
            'violationCategory',
            'violationItem',
            'teacher',
            'creator',
        ]);

        $this->applyBaseFilters($query, $filters, ['source', 'description', 'status']);
        $this->applyTransactionFilters($query, $filters);

        return $query->orderByDesc('transaction_date')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id, bool $withTrashed = false): ?ViolationTransaction
    {
        $query = ViolationTransaction::query()->with([
            'academicYear',
            'student.classroom.major',
            'classroom.major',
            'violationCategory',
            'violationItem.mappings.dimension',
            'teacher',
            'creator',
        ]);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): ViolationTransaction
    {
        return ViolationTransaction::query()->create($data);
    }

    public function update(ViolationTransaction $transaction, array $data): ViolationTransaction
    {
        $transaction->update($data);

        return $transaction->refresh();
    }

    public function softDelete(ViolationTransaction $transaction): void
    {
        $transaction->delete();
    }

    public function restore(int $id): bool
    {
        return ViolationTransaction::onlyTrashed()->whereKey($id)->restore() > 0;
    }

    public function forceDelete(int $id): bool
    {
        return ViolationTransaction::onlyTrashed()->whereKey($id)->forceDelete() > 0;
    }

    public function bulkSoftDelete(array $ids): int
    {
        return ViolationTransaction::query()->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(array $ids): int
    {
        return ViolationTransaction::onlyTrashed()->whereIn('id', $ids)->restore();
    }

    public function existsDuplicate(int $studentId, int $itemId, string $date, ?int $ignoreId = null): bool
    {
        $query = ViolationTransaction::query()
            ->where('student_id', $studentId)
            ->where('violation_item_id', $itemId)
            ->whereDate('transaction_date', $date);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->whereNull('deleted_at')->exists();
    }

    public function allForExport(array $filters = [], bool $withTrashed = true): Collection
    {
        $query = ViolationTransaction::query()->with([
            'academicYear',
            'student.classroom.major',
            'classroom.major',
            'violationCategory',
            'violationItem',
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
            $query->where('violation_category_id', $filters['category_id']);
        }

        if (!empty($filters['item_id'])) {
            $query->where('violation_item_id', $filters['item_id']);
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
