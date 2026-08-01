<?php

namespace App\Repositories\Contracts\Pancawaluya;

interface AuditLogCharacterRepositoryInterface
{
    public function store(array $payload): void;
}
