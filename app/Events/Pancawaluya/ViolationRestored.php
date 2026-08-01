<?php

namespace App\Events\Pancawaluya;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ViolationRestored
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $transactionId,
        public readonly int $studentId,
    ) {}
}
