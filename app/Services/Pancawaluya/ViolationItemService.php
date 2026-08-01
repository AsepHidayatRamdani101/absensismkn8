<?php

namespace App\Services\Pancawaluya;

use App\Exceptions\Pancawaluya\MasterDataException;
use App\Models\CharacterDimension;
use App\Models\ViolationItem;
use App\Repositories\Contracts\Pancawaluya\CharacterMappingRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\ViolationItemRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViolationItemService
{
    public function __construct(
        private readonly ViolationItemRepositoryInterface $repository,
        private readonly CharacterMappingRepositoryInterface $mappingRepository,
        private readonly AuditTrailService $auditTrailService,
    ) {}

    public function paginate(array $filters, int $perPage = 10)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findForEdit(int $id, bool $withTrashed = false): ?ViolationItem
    {
        return $this->repository->findById($id, $withTrashed);
    }

    public function create(array $data, Request $request): ViolationItem
    {
        return DB::transaction(function () use ($data, $request): ViolationItem {
            $this->assertDimensionExists((int) $data['character_dimension_id']);

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $dimensionId = (int) $data['character_dimension_id'];
            $weight = (float) $data['weight'];

            unset($data['character_dimension_id'], $data['weight']);

            $item = $this->repository->create($data);
            $this->mappingRepository->syncForViolationItem($item->id, $dimensionId, $weight);

            $this->auditTrailService->log(ViolationItem::class, $item->id, 'CREATE', null, $item->toArray(), $request, 'WEB');

            return $item->refresh()->load(['category', 'mappings.dimension']);
        });
    }

    public function update(ViolationItem $item, array $data, Request $request): ViolationItem
    {
        return DB::transaction(function () use ($item, $data, $request): ViolationItem {
            $this->assertDimensionExists((int) $data['character_dimension_id']);

            $before = $item->load(['category', 'mappings.dimension'])->toArray();
            $data['updated_by'] = Auth::id();

            $dimensionId = (int) $data['character_dimension_id'];
            $weight = (float) $data['weight'];

            unset($data['character_dimension_id'], $data['weight']);

            $updated = $this->repository->update($item, $data);
            $this->mappingRepository->syncForViolationItem($updated->id, $dimensionId, $weight);

            $updated = $updated->refresh()->load(['category', 'mappings.dimension']);

            $this->auditTrailService->log(ViolationItem::class, $updated->id, 'UPDATE', $before, $updated->toArray(), $request, 'WEB');

            return $updated;
        });
    }

    public function softDelete(ViolationItem $item, Request $request): void
    {
        DB::transaction(function () use ($item, $request): void {
            $before = $item->toArray();
            $item->update(['deleted_by' => Auth::id()]);
            $this->repository->softDelete($item);

            $this->auditTrailService->log(ViolationItem::class, $item->id, 'DELETE', $before, null, $request, 'WEB');
        });
    }

    public function restore(int $id, Request $request): bool
    {
        return DB::transaction(function () use ($id, $request): bool {
            $restored = $this->repository->restore($id);

            if ($restored) {
                $this->auditTrailService->log(ViolationItem::class, $id, 'RESTORE', null, ['id' => $id], $request, 'WEB');
            }

            return $restored;
        });
    }

    public function forceDelete(int $id, Request $request): bool
    {
        return DB::transaction(function () use ($id, $request): bool {
            $deleted = $this->repository->forceDelete($id);

            if ($deleted) {
                $this->auditTrailService->log(ViolationItem::class, $id, 'FORCE_DELETE', null, null, $request, 'WEB');
            }

            return $deleted;
        });
    }

    public function bulkSoftDelete(array $ids, Request $request): int
    {
        return DB::transaction(function () use ($ids, $request): int {
            $count = $this->repository->bulkSoftDelete($ids);
            $this->auditTrailService->log(ViolationItem::class, null, 'BULK_DELETE', ['ids' => $ids], ['count' => $count], $request, 'WEB');

            return $count;
        });
    }

    public function bulkRestore(array $ids, Request $request): int
    {
        return DB::transaction(function () use ($ids, $request): int {
            $count = $this->repository->bulkRestore($ids);
            $this->auditTrailService->log(ViolationItem::class, null, 'BULK_RESTORE', ['ids' => $ids], ['count' => $count], $request, 'WEB');

            return $count;
        });
    }

    public function allForExport(array $filters = [], bool $withTrashed = false)
    {
        return $this->repository->allForExport($filters, $withTrashed);
    }

    private function assertDimensionExists(int $dimensionId): void
    {
        if (!CharacterDimension::query()->whereKey($dimensionId)->where('is_active', true)->exists()) {
            throw new MasterDataException('Dimensi karakter tidak ditemukan atau tidak aktif.');
        }
    }
}
