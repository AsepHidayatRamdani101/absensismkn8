<?php

namespace App\Events\Pancawaluya;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SPGenerated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly int $studentId, public readonly string $spLevel) {}
}
