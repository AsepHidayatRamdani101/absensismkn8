<?php

namespace App\Services\Pancawaluya;

use App\Models\ViolationCategory;
use App\Repositories\Contracts\Pancawaluya\ViolationCategoryRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViolationCategoryService
{
    public function __construct(
        private readonly ViolationCategoryRepositoryInterface $repository,
        private readonly AuditTrailService $auditTrailService,
    ) {}

    public function paginate(array $filters, int $perPage = 10)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findForEdit(int $id, bool $withTrashed = false): ?ViolationCategory
    {
        return $this->repository->findById($id, $withTrashed);
    }

    public function create(array $data, Request $request): ViolationCategory
    {
        return DB::transaction(function () use ($data, $request): ViolationCategory {
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $category = $this->repository->create($data);

            $this->auditTrailService->log(ViolationCategory::class, $category->id, 'CREATE', null, $category->toArray(), $request, 'WEB');

            return $category;
        });
    }

    public function update(ViolationCategory $category, array $data, Request $request): ViolationCategory
    {
        return DB::transaction(function () use ($category, $data, $request): ViolationCategory {
            $before = $category->toArray();
            $data['updated_by'] = Auth::id();

            $updated = $this->repository->update($category, $data);

            $this->auditTrailService->log(ViolationCategory::class, $updated->id, 'UPDATE', $before, $updated->toArray(), $request, 'WEB');

            return $updated;
        });
    }

    public function softDelete(ViolationCategory $category, Request $request): void
    {
        DB::transaction(function () use ($category, $request): void {
            $before = $category->toArray();
            $category->update(['deleted_by' => Auth::id()]);
            $this->repository->softDelete($category);

            $this->auditTrailService->log(ViolationCategory::class, $category->id, 'DELETE', $before, null, $request, 'WEB');
        });
    }

    public function restore(int $id, Request $request): bool
    {
        return DB::transaction(function () use ($id, $request): bool {
            $restored = $this->repository->restore($id);

            if ($restored) {
                $this->auditTrailService->log(ViolationCategory::class, $id, 'RESTORE', null, ['id' => $id], $request, 'WEB');
            }

            return $restored;
        });
    }

    public function forceDelete(int $id, Request $request): bool
    {
        return DB::transaction(function () use ($id, $request): bool {
            $deleted = $this->repository->forceDelete($id);

            if ($deleted) {
                $this->auditTrailService->log(ViolationCategory::class, $id, 'FORCE_DELETE', null, null, $request, 'WEB');
            }

            return $deleted;
        });
    }

    public function bulkSoftDelete(array $ids, Request $request): int
    {
        return DB::transaction(function () use ($ids, $request): int {
            $count = $this->repository->bulkSoftDelete($ids);
            $this->auditTrailService->log(ViolationCategory::class, null, 'BULK_DELETE', ['ids' => $ids], ['count' => $count], $request, 'WEB');

            return $count;
        });
    }

    public function bulkRestore(array $ids, Request $request): int
    {
        return DB::transaction(function () use ($ids, $request): int {
            $count = $this->repository->bulkRestore($ids);
            $this->auditTrailService->log(ViolationCategory::class, null, 'BULK_RESTORE', ['ids' => $ids], ['count' => $count], $request, 'WEB');

            return $count;
        });
    }

    public function allForExport(array $filters = [], bool $withTrashed = false)
    {
        return $this->repository->allForExport($filters, $withTrashed);
    }
}
