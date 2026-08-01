<?php

namespace App\Repositories\Pancawaluya;

use App\Models\AuditLogCharacter;
use App\Repositories\Contracts\Pancawaluya\AuditLogCharacterRepositoryInterface;

class AuditLogCharacterRepository implements AuditLogCharacterRepositoryInterface
{
    public function store(array $payload): void
    {
        AuditLogCharacter::query()->create($payload);
    }
}
