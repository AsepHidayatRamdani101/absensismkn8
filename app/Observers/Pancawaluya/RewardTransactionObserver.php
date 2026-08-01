<?php

namespace App\Observers\Pancawaluya;

use App\Events\Pancawaluya\RewardCreated;
use App\Events\Pancawaluya\RewardDeleted;
use App\Events\Pancawaluya\RewardRestored;
use App\Events\Pancawaluya\RewardUpdated;
use App\Models\RewardTransaction;

class RewardTransactionObserver
{
    public function created(RewardTransaction $transaction): void
    {
        event(new RewardCreated($transaction->id, (int) $transaction->student_id));
    }

    public function updated(RewardTransaction $transaction): void
    {
        if ($transaction->wasRecentlyCreated) {
            return;
        }

        event(new RewardUpdated($transaction->id, (int) $transaction->student_id));
    }

    public function deleted(RewardTransaction $transaction): void
    {
        event(new RewardDeleted($transaction->id, (int) $transaction->student_id));
    }

    public function restored(RewardTransaction $transaction): void
    {
        event(new RewardRestored($transaction->id, (int) $transaction->student_id));
    }
}
