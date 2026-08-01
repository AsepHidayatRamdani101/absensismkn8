<?php

namespace App\Services\Pancawaluya;

use App\Exceptions\Pancawaluya\TransactionRuleException;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ViolationItem;
use App\Models\ViolationTransaction;
use App\Repositories\Contracts\Pancawaluya\ViolationTransactionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ViolationTransactionService
{
    public function __construct(
        private readonly ViolationTransactionRepositoryInterface $repository,
        private readonly CharacterScoreEngineService $characterScoreEngine,
        private readonly StatisticsEngineService $statisticsEngine,
        private readonly SPEngineService $spEngine,
        private readonly HistoryEngineService $historyEngine,
        private readonly AuditTrailService $auditTrailService,
    ) {}

    public function paginate(array $filters, int $perPage = 10)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findForEdit(int $id, bool $withTrashed = false): ?ViolationTransaction
    {
        return $this->repository->findById($id, $withTrashed);
    }

    public function create(array $data, Request $request): ViolationTransaction
    {
        return DB::transaction(function () use ($data, $request): ViolationTransaction {
            $student = Student::query()->with('classroom')->findOrFail((int) $data['student_id']);
            $violation = ViolationItem::query()->with(['category', 'mappings.dimension'])->findOrFail((int) $data['violation_item_id']);

            $this->ensureValidContext($data, $student, $violation->violation_category_id);
            $this->ensureNoDuplicate((int) $data['student_id'], $violation->id, (string) $data['transaction_date']);

            $payload = $this->buildDimensionPayload($violation);

            $row = $this->repository->create([
                'academic_year_id' => (int) $data['academic_year_id'],
                'semester' => (string) $data['semester'],
                'transaction_date' => (string) $data['transaction_date'],
                'student_id' => $student->id,
                'classroom_id' => $student->classroom_id,
                'violation_category_id' => $violation->violation_category_id,
                'violation_item_id' => $violation->id,
                'point' => $violation->point,
                'weight_total' => array_sum(array_column($payload, 'weight')),
                'weighted_point' => array_sum(array_column($payload, 'weighted_point')),
                'dimension_payload' => $payload,
                'source' => (string) $data['source'],
                'teacher_id' => $this->resolveTeacherId(),
                'actor_role' => Auth::user()?->roles?->pluck('name')?->implode(', '),
                'description' => Arr::get($data, 'description'),
                'evidence_path' => $this->storeAttachmentIfExists($request, 'evidence_photo', 'pancawaluya/violation-evidences'),
                'status' => (string) Arr::get($data, 'status', 'pending'),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $beforeScore = $this->characterScoreEngine->totalScore($student->id);
            $this->characterScoreEngine->applyViolationPayload($student->id, $payload);
            $afterScore = $this->characterScoreEngine->totalScore($student->id);
            $stats = $this->statisticsEngine->refreshStudentPeriod($student->id, (int) $row->academic_year_id, (string) $row->semester, $afterScore);
            $sp = $this->spEngine->recalculate($student->id, (int) $row->academic_year_id, (string) $row->semester, (float) $stats->violation_weighted_total);

            $historyContext = [
                'student_id' => $row->student_id,
                'classroom_id' => $row->classroom_id,
                'academic_year_id' => $row->academic_year_id,
                'semester' => $row->semester,
                'transaction_date' => optional($row->transaction_date)->toDateString(),
                'status' => $row->status,
                'score_before' => $beforeScore,
                'score_after' => $afterScore,
                'reason' => 'Violation transaction created',
                'source' => 'WEB',
            ];

            $this->historyEngine->log('violation_transaction', $row->id, $historyContext, null, $row->toArray(), 'CREATE', $request);
            $this->auditTrailService->log(ViolationTransaction::class, $row->id, 'CREATE', null, [
                'transaction' => $row->toArray(),
                'statistics' => $stats->toArray(),
                'warning_letter' => $sp?->toArray(),
            ], $request, 'WEB');

            return $row->refresh();
        });
    }

    public function update(ViolationTransaction $transaction, array $data, Request $request): ViolationTransaction
    {
        return DB::transaction(function () use ($transaction, $data, $request): ViolationTransaction {
            $this->assertCanMutate($transaction);

            $student = Student::query()->with('classroom')->findOrFail((int) $data['student_id']);
            $violation = ViolationItem::query()->with(['category', 'mappings.dimension'])->findOrFail((int) $data['violation_item_id']);

            $this->ensureValidContext($data, $student, $violation->violation_category_id);
            $this->ensureNoDuplicate((int) $data['student_id'], $violation->id, (string) $data['transaction_date'], $transaction->id);

            $before = $transaction->toArray();
            $oldPayload = (array) ($transaction->dimension_payload ?? []);
            $newPayload = $this->buildDimensionPayload($violation);

            $beforeScore = $this->characterScoreEngine->totalScore($student->id);
            $this->characterScoreEngine->rollbackViolationPayload($student->id, $oldPayload);
            $this->characterScoreEngine->applyViolationPayload($student->id, $newPayload);
            $afterScore = $this->characterScoreEngine->totalScore($student->id);

            $updated = $this->repository->update($transaction, [
                'academic_year_id' => (int) $data['academic_year_id'],
                'semester' => (string) $data['semester'],
                'transaction_date' => (string) $data['transaction_date'],
                'student_id' => $student->id,
                'classroom_id' => $student->classroom_id,
                'violation_category_id' => $violation->violation_category_id,
                'violation_item_id' => $violation->id,
                'point' => $violation->point,
                'weight_total' => array_sum(array_column($newPayload, 'weight')),
                'weighted_point' => array_sum(array_column($newPayload, 'weighted_point')),
                'dimension_payload' => $newPayload,
                'source' => (string) $data['source'],
                'description' => Arr::get($data, 'description'),
                'status' => (string) Arr::get($data, 'status', $transaction->status),
                'updated_by' => Auth::id(),
            ]);

            $stats = $this->statisticsEngine->refreshStudentPeriod($student->id, (int) $updated->academic_year_id, (string) $updated->semester, $afterScore);
            $sp = $this->spEngine->recalculate($student->id, (int) $updated->academic_year_id, (string) $updated->semester, (float) $stats->violation_weighted_total);

            $this->historyEngine->log('violation_transaction', $updated->id, [
                'student_id' => $updated->student_id,
                'classroom_id' => $updated->classroom_id,
                'academic_year_id' => $updated->academic_year_id,
                'semester' => $updated->semester,
                'transaction_date' => optional($updated->transaction_date)->toDateString(),
                'status' => $updated->status,
                'score_before' => $beforeScore,
                'score_after' => $afterScore,
                'reason' => 'Violation transaction updated',
                'source' => 'WEB',
            ], $before, $updated->toArray(), 'UPDATE', $request);

            $this->auditTrailService->log(ViolationTransaction::class, $updated->id, 'UPDATE', $before, [
                'transaction' => $updated->toArray(),
                'statistics' => $stats->toArray(),
                'warning_letter' => $sp?->toArray(),
            ], $request, 'WEB');

            return $updated;
        });
    }

    public function softDelete(ViolationTransaction $transaction, Request $request): void
    {
        DB::transaction(function () use ($transaction, $request): void {
            $this->assertCanMutate($transaction);

            $before = $transaction->toArray();
            $beforeScore = $this->characterScoreEngine->totalScore((int) $transaction->student_id);
            $this->characterScoreEngine->rollbackViolationPayload((int) $transaction->student_id, (array) ($transaction->dimension_payload ?? []));
            $afterScore = $this->characterScoreEngine->totalScore((int) $transaction->student_id);

            $transaction->update(['deleted_by' => Auth::id()]);
            $this->repository->softDelete($transaction);

            $stats = $this->statisticsEngine->refreshStudentPeriod((int) $transaction->student_id, (int) $transaction->academic_year_id, (string) $transaction->semester, $afterScore);
            $sp = $this->spEngine->recalculate((int) $transaction->student_id, (int) $transaction->academic_year_id, (string) $transaction->semester, (float) $stats->violation_weighted_total);

            $this->historyEngine->log('violation_transaction', $transaction->id, [
                'student_id' => $transaction->student_id,
                'classroom_id' => $transaction->classroom_id,
                'academic_year_id' => $transaction->academic_year_id,
                'semester' => $transaction->semester,
                'transaction_date' => optional($transaction->transaction_date)->toDateString(),
                'status' => 'deleted',
                'score_before' => $beforeScore,
                'score_after' => $afterScore,
                'reason' => 'Violation transaction soft deleted',
                'source' => 'WEB',
            ], $before, null, 'DELETE', $request);

            $this->auditTrailService->log(ViolationTransaction::class, $transaction->id, 'DELETE', $before, [
                'statistics' => $stats->toArray(),
                'warning_letter' => $sp?->toArray(),
            ], $request, 'WEB');
        });
    }

    public function restore(int $id, Request $request): bool
    {
        return DB::transaction(function () use ($id, $request): bool {
            $transaction = $this->repository->findById($id, true);

            if (!$transaction || !$transaction->trashed()) {
                return false;
            }

            $restored = $this->repository->restore($id);

            if (!$restored) {
                return false;
            }

            $transaction = $this->repository->findById($id);
            if (!$transaction) {
                return false;
            }

            $beforeScore = $this->characterScoreEngine->totalScore((int) $transaction->student_id);
            $this->characterScoreEngine->applyViolationPayload((int) $transaction->student_id, (array) ($transaction->dimension_payload ?? []));
            $afterScore = $this->characterScoreEngine->totalScore((int) $transaction->student_id);
            $stats = $this->statisticsEngine->refreshStudentPeriod((int) $transaction->student_id, (int) $transaction->academic_year_id, (string) $transaction->semester, $afterScore);
            $sp = $this->spEngine->recalculate((int) $transaction->student_id, (int) $transaction->academic_year_id, (string) $transaction->semester, (float) $stats->violation_weighted_total);

            $this->historyEngine->log('violation_transaction', $transaction->id, [
                'student_id' => $transaction->student_id,
                'classroom_id' => $transaction->classroom_id,
                'academic_year_id' => $transaction->academic_year_id,
                'semester' => $transaction->semester,
                'transaction_date' => optional($transaction->transaction_date)->toDateString(),
                'status' => $transaction->status,
                'score_before' => $beforeScore,
                'score_after' => $afterScore,
                'reason' => 'Violation transaction restored',
                'source' => 'WEB',
            ], null, $transaction->toArray(), 'RESTORE', $request);

            $this->auditTrailService->log(ViolationTransaction::class, $transaction->id, 'RESTORE', null, [
                'transaction' => $transaction->toArray(),
                'statistics' => $stats->toArray(),
                'warning_letter' => $sp?->toArray(),
            ], $request, 'WEB');

            return true;
        });
    }

    public function forceDelete(int $id, Request $request): bool
    {
        return DB::transaction(function () use ($id, $request): bool {
            $transaction = $this->repository->findById($id, true);

            if (!$transaction || !$transaction->trashed()) {
                return false;
            }

            $deleted = $this->repository->forceDelete($id);

            if ($deleted) {
                $this->auditTrailService->log(ViolationTransaction::class, $id, 'FORCE_DELETE', $transaction->toArray(), null, $request, 'WEB');
            }

            return $deleted;
        });
    }

    public function bulkSoftDelete(array $ids, Request $request): int
    {
        return DB::transaction(function () use ($ids, $request): int {
            $count = 0;
            foreach ($ids as $id) {
                $transaction = $this->repository->findById((int) $id);
                if (!$transaction) {
                    continue;
                }
                $this->softDelete($transaction, $request);
                $count++;
            }

            return $count;
        });
    }

    public function bulkRestore(array $ids, Request $request): int
    {
        return DB::transaction(function () use ($ids, $request): int {
            $count = 0;
            foreach ($ids as $id) {
                if ($this->restore((int) $id, $request)) {
                    $count++;
                }
            }

            return $count;
        });
    }

    public function allForExport(array $filters = [], bool $withTrashed = true)
    {
        return $this->repository->allForExport($filters, $withTrashed);
    }

    private function ensureValidContext(array $data, Student $student, int $categoryId): void
    {
        if ((int) $data['classroom_id'] !== (int) $student->classroom_id) {
            throw new TransactionRuleException('Kelas siswa tidak sesuai dengan data siswa aktif.');
        }

        if ((int) $data['violation_category_id'] !== $categoryId) {
            throw new TransactionRuleException('Kategori pelanggaran tidak sesuai dengan item pelanggaran terpilih.');
        }
    }

    private function ensureNoDuplicate(int $studentId, int $itemId, string $date, ?int $ignoreId = null): void
    {
        if ($this->repository->existsDuplicate($studentId, $itemId, $date, $ignoreId)) {
            throw new TransactionRuleException('Transaksi pelanggaran duplikat untuk siswa, item, dan tanggal yang sama.');
        }
    }

    private function buildDimensionPayload(ViolationItem $violation): array
    {
        $payload = [];

        foreach ($violation->mappings->where('is_active', true) as $mapping) {
            $weight = (float) $mapping->weight;
            $payload[] = [
                'dimension_id' => $mapping->character_dimension_id,
                'dimension_name' => (string) ($mapping->dimension?->name ?? '-'),
                'weight' => $weight,
                'point' => (int) $violation->point,
                'weighted_point' => (float) $violation->point * $weight,
            ];
        }

        if ($payload === []) {
            throw new TransactionRuleException('Item pelanggaran belum memiliki mapping dimensi karakter aktif.');
        }

        return $payload;
    }

    private function resolveTeacherId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return Teacher::query()
            ->where('nip', (string) $user->email)
            ->orWhere('nama_lengkap', (string) $user->name)
            ->value('id');
    }

    private function assertCanMutate(ViolationTransaction $transaction): void
    {
        $user = Auth::user();
        if (!$user) {
            throw new TransactionRuleException('User tidak terautentikasi.');
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'kesiswaan'])) {
            return;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['guru', 'wali_kelas']) && (int) $transaction->created_by === (int) $user->id) {
            return;
        }

        throw new TransactionRuleException('Anda tidak memiliki hak mengubah transaksi ini.');
    }

    private function storeAttachmentIfExists(Request $request, string $key, string $path): ?string
    {
        if (!$request->hasFile($key)) {
            return null;
        }

        return Storage::disk('public')->put($path, $request->file($key));
    }
}
