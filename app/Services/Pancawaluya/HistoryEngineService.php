<?php

namespace App\Services\Pancawaluya;

use App\Repositories\Contracts\Pancawaluya\TransactionHistoryRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryEngineService
{
    public function __construct(private readonly TransactionHistoryRepositoryInterface $repository) {}

    public function log(string $referenceType, int $referenceId, array $context, ?array $before, ?array $after, string $action, Request $request): void
    {
        $this->repository->create([
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'student_id' => $context['student_id'] ?? null,
            'classroom_id' => $context['classroom_id'] ?? null,
            'academic_year_id' => $context['academic_year_id'] ?? null,
            'semester' => $context['semester'] ?? null,
            'transaction_date' => $context['transaction_date'] ?? null,
            'action' => $action,
            'status' => $context['status'] ?? null,
            'score_before' => $context['score_before'] ?? null,
            'score_after' => $context['score_after'] ?? null,
            'payload_before' => $before,
            'payload_after' => $after,
            'reason' => $context['reason'] ?? null,
            'actor_id' => Auth::id(),
            'actor_role' => Auth::user()?->roles?->pluck('name')?->implode(', '),
            'source' => $context['source'] ?? 'WEB',
        ]);
    }
}
