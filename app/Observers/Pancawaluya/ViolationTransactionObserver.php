<?php

namespace App\Observers\Pancawaluya;

use App\Events\Pancawaluya\ViolationCreated;
use App\Events\Pancawaluya\ViolationDeleted;
use App\Events\Pancawaluya\ViolationRestored;
use App\Events\Pancawaluya\ViolationUpdated;
use App\Models\ViolationTransaction;

class ViolationTransactionObserver
{
    public function created(ViolationTransaction $transaction): void
    {
        event(new ViolationCreated($transaction->id, (int) $transaction->student_id));
    }

    public function updated(ViolationTransaction $transaction): void
    {
        if ($transaction->wasRecentlyCreated) {
            return;
        }

        event(new ViolationUpdated($transaction->id, (int) $transaction->student_id));
    }

    public function deleted(ViolationTransaction $transaction): void
    {
        event(new ViolationDeleted($transaction->id, (int) $transaction->student_id));
    }

    public function restored(ViolationTransaction $transaction): void
    {
        event(new ViolationRestored($transaction->id, (int) $transaction->student_id));
    }
}
