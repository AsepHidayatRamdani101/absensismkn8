<?php

namespace App\Services\Pancawaluya;

use App\Repositories\Contracts\Pancawaluya\AuditLogCharacterRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditTrailService
{
    public function __construct(
        private readonly AuditLogCharacterRepositoryInterface $auditRepository,
    ) {}

    public function log(
        string $entityType,
        ?int $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?Request $request = null,
        ?string $source = null,
    ): void {
        $user = Auth::user();

        $this->auditRepository->store([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'before_payload' => $before,
            'after_payload' => $after,
            'performed_by' => $user?->id,
            'performed_role' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() : null,
            'source' => $source,
            'ip_address' => $request?->ip(),
            'browser' => $request?->header('Sec-CH-UA') ?: $request?->header('User-Agent'),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
