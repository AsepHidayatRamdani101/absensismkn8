<?php

namespace App\Services\Pancawaluya;

use App\Exceptions\Pancawaluya\TransactionRuleException;
use App\Models\RewardItem;
use App\Models\RewardTransaction;
use App\Models\Student;
use App\Models\Teacher;
use App\Repositories\Contracts\Pancawaluya\RewardTransactionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RewardTransactionService
{
    public function __construct(
        private readonly RewardTransactionRepositoryInterface $repository,
        private readonly CharacterScoreEngineService $characterScoreEngine,
        private readonly StatisticsEngineService $statisticsEngine,
        private readonly HistoryEngineService $historyEngine,
        private readonly AuditTrailService $auditTrailService,
    ) {}

    public function paginate(array $filters, int $perPage = 10)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findForEdit(int $id, bool $withTrashed = false): ?RewardTransaction
    {
        return $this->repository->findById($id, $withTrashed);
    }

    public function create(array $data, Request $request): RewardTransaction
    {
        return DB::transaction(function () use ($data, $request): RewardTransaction {
            $student = Student::query()->with('classroom')->findOrFail((int) $data['student_id']);
            $reward = RewardItem::query()->with(['category', 'mappings.dimension'])->findOrFail((int) $data['reward_item_id']);

            $this->ensureValidContext($data, $student, $reward->reward_category_id);
            $this->ensureNoDuplicate((int) $data['student_id'], $reward->id, (string) $data['transaction_date']);

            $payload = $this->buildDimensionPayload($reward);

            $row = $this->repository->create([
                'academic_year_id' => (int) $data['academic_year_id'],
                'semester' => (string) $data['semester'],
                'transaction_date' => (string) $data['transaction_date'],
                'student_id' => $student->id,
                'classroom_id' => $student->classroom_id,
                'reward_category_id' => $reward->reward_category_id,
                'reward_item_id' => $reward->id,
                'point' => $reward->point,
                'weight_total' => array_sum(array_column($payload, 'weight')),
                'weighted_point' => array_sum(array_column($payload, 'weighted_point')),
                'dimension_payload' => $payload,
                'source' => (string) $data['source'],
                'teacher_id' => $this->resolveTeacherId(),
                'actor_role' => Auth::user()?->roles?->pluck('name')?->implode(', '),
                'description' => Arr::get($data, 'description'),
                'attachment_path' => $this->storeAttachmentIfExists($request, 'attachment', 'pancawaluya/reward-attachments'),
                'status' => (string) Arr::get($data, 'status', 'pending'),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $beforeScore = $this->characterScoreEngine->totalScore($student->id);
            $this->characterScoreEngine->applyRewardPayload($student->id, $payload);
            $afterScore = $this->characterScoreEngine->totalScore($student->id);
            $stats = $this->statisticsEngine->refreshStudentPeriod($student->id, (int) $row->academic_year_id, (string) $row->semester, $afterScore);

            $historyContext = [
                'student_id' => $row->student_id,
                'classroom_id' => $row->classroom_id,
                'academic_year_id' => $row->academic_year_id,
                'semester' => $row->semester,
                'transaction_date' => optional($row->transaction_date)->toDateString(),
                'status' => $row->status,
                'score_before' => $beforeScore,
                'score_after' => $afterScore,
                'reason' => 'Reward transaction created',
                'source' => 'WEB',
            ];

            $this->historyEngine->log('reward_transaction', $row->id, $historyContext, null, $row->toArray(), 'CREATE', $request);
            $this->auditTrailService->log(RewardTransaction::class, $row->id, 'CREATE', null, [
                'transaction' => $row->toArray(),
                'statistics' => $stats->toArray(),
            ], $request, 'WEB');

            return $row->refresh();
        });
    }

    public function update(RewardTransaction $transaction, array $data, Request $request): RewardTransaction
    {
        return DB::transaction(function () use ($transaction, $data, $request): RewardTransaction {
            $this->assertCanMutate($transaction);

            $student = Student::query()->with('classroom')->findOrFail((int) $data['student_id']);
            $reward = RewardItem::query()->with(['category', 'mappings.dimension'])->findOrFail((int) $data['reward_item_id']);

            $this->ensureValidContext($data, $student, $reward->reward_category_id);
            $this->ensureNoDuplicate((int) $data['student_id'], $reward->id, (string) $data['transaction_date'], $transaction->id);

            $before = $transaction->toArray();
            $oldPayload = (array) ($transaction->dimension_payload ?? []);
            $newPayload = $this->buildDimensionPayload($reward);

            $beforeScore = $this->characterScoreEngine->totalScore($student->id);
            $this->characterScoreEngine->rollbackRewardPayload($student->id, $oldPayload);
            $this->characterScoreEngine->applyRewardPayload($student->id, $newPayload);
            $afterScore = $this->characterScoreEngine->totalScore($student->id);

            $updated = $this->repository->update($transaction, [
                'academic_year_id' => (int) $data['academic_year_id'],
                'semester' => (string) $data['semester'],
                'transaction_date' => (string) $data['transaction_date'],
                'student_id' => $student->id,
                'classroom_id' => $student->classroom_id,
                'reward_category_id' => $reward->reward_category_id,
                'reward_item_id' => $reward->id,
                'point' => $reward->point,
                'weight_total' => array_sum(array_column($newPayload, 'weight')),
                'weighted_point' => array_sum(array_column($newPayload, 'weighted_point')),
                'dimension_payload' => $newPayload,
                'source' => (string) $data['source'],
                'description' => Arr::get($data, 'description'),
                'status' => (string) Arr::get($data, 'status', $transaction->status),
                'updated_by' => Auth::id(),
            ]);

            $stats = $this->statisticsEngine->refreshStudentPeriod($student->id, (int) $updated->academic_year_id, (string) $updated->semester, $afterScore);

            $historyContext = [
                'student_id' => $updated->student_id,
                'classroom_id' => $updated->classroom_id,
                'academic_year_id' => $updated->academic_year_id,
                'semester' => $updated->semester,
                'transaction_date' => optional($updated->transaction_date)->toDateString(),
                'status' => $updated->status,
                'score_before' => $beforeScore,
                'score_after' => $afterScore,
                'reason' => 'Reward transaction updated',
                'source' => 'WEB',
            ];

            $this->historyEngine->log('reward_transaction', $updated->id, $historyContext, $before, $updated->toArray(), 'UPDATE', $request);
            $this->auditTrailService->log(RewardTransaction::class, $updated->id, 'UPDATE', $before, [
                'transaction' => $updated->toArray(),
                'statistics' => $stats->toArray(),
            ], $request, 'WEB');

            return $updated;
        });
    }

    public function softDelete(RewardTransaction $transaction, Request $request): void
    {
        DB::transaction(function () use ($transaction, $request): void {
            $this->assertCanMutate($transaction);

            $before = $transaction->toArray();
            $beforeScore = $this->characterScoreEngine->totalScore((int) $transaction->student_id);
            $this->characterScoreEngine->rollbackRewardPayload((int) $transaction->student_id, (array) ($transaction->dimension_payload ?? []));
            $afterScore = $this->characterScoreEngine->totalScore((int) $transaction->student_id);

            $transaction->update(['deleted_by' => Auth::id()]);
            $this->repository->softDelete($transaction);

            $stats = $this->statisticsEngine->refreshStudentPeriod((int) $transaction->student_id, (int) $transaction->academic_year_id, (string) $transaction->semester, $afterScore);

            $this->historyEngine->log('reward_transaction', $transaction->id, [
                'student_id' => $transaction->student_id,
                'classroom_id' => $transaction->classroom_id,
                'academic_year_id' => $transaction->academic_year_id,
                'semester' => $transaction->semester,
                'transaction_date' => optional($transaction->transaction_date)->toDateString(),
                'status' => 'deleted',
                'score_before' => $beforeScore,
                'score_after' => $afterScore,
                'reason' => 'Reward transaction soft deleted',
                'source' => 'WEB',
            ], $before, null, 'DELETE', $request);

            $this->auditTrailService->log(RewardTransaction::class, $transaction->id, 'DELETE', $before, [
                'statistics' => $stats->toArray(),
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
            $this->characterScoreEngine->applyRewardPayload((int) $transaction->student_id, (array) ($transaction->dimension_payload ?? []));
            $afterScore = $this->characterScoreEngine->totalScore((int) $transaction->student_id);
            $stats = $this->statisticsEngine->refreshStudentPeriod((int) $transaction->student_id, (int) $transaction->academic_year_id, (string) $transaction->semester, $afterScore);

            $this->historyEngine->log('reward_transaction', $transaction->id, [
                'student_id' => $transaction->student_id,
                'classroom_id' => $transaction->classroom_id,
                'academic_year_id' => $transaction->academic_year_id,
                'semester' => $transaction->semester,
                'transaction_date' => optional($transaction->transaction_date)->toDateString(),
                'status' => $transaction->status,
                'score_before' => $beforeScore,
                'score_after' => $afterScore,
                'reason' => 'Reward transaction restored',
                'source' => 'WEB',
            ], null, $transaction->toArray(), 'RESTORE', $request);

            $this->auditTrailService->log(RewardTransaction::class, $transaction->id, 'RESTORE', null, [
                'transaction' => $transaction->toArray(),
                'statistics' => $stats->toArray(),
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
                $this->auditTrailService->log(RewardTransaction::class, $id, 'FORCE_DELETE', $transaction->toArray(), null, $request, 'WEB');
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

        if ((int) $data['reward_category_id'] !== $categoryId) {
            throw new TransactionRuleException('Kategori reward tidak sesuai dengan reward item terpilih.');
        }
    }

    private function ensureNoDuplicate(int $studentId, int $itemId, string $date, ?int $ignoreId = null): void
    {
        if ($this->repository->existsDuplicate($studentId, $itemId, $date, $ignoreId)) {
            throw new TransactionRuleException('Transaksi reward duplikat untuk siswa, item, dan tanggal yang sama.');
        }
    }

    private function buildDimensionPayload(RewardItem $reward): array
    {
        $payload = [];

        foreach ($reward->mappings->where('is_active', true) as $mapping) {
            $weight = (float) $mapping->weight;
            $payload[] = [
                'dimension_id' => $mapping->character_dimension_id,
                'dimension_name' => (string) ($mapping->dimension?->name ?? '-'),
                'weight' => $weight,
                'point' => (int) $reward->point,
                'weighted_point' => (float) $reward->point * $weight,
            ];
        }

        if ($payload === []) {
            throw new TransactionRuleException('Reward belum memiliki mapping dimensi karakter aktif.');
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

    private function assertCanMutate(RewardTransaction $transaction): void
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
