<?php

namespace App\Listeners\Pancawaluya;

use App\Events\Pancawaluya\RewardCreated;
use App\Events\Pancawaluya\RewardDeleted;
use App\Events\Pancawaluya\RewardRestored;
use App\Events\Pancawaluya\RewardUpdated;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Notifications\Pancawaluya\TransactionActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendRewardTransactionNotification implements ShouldQueue
{
    public function handle(object $event): void
    {
        $transaction = RewardTransaction::query()->with('student')->withTrashed()->find($event->transactionId);
        if (!$transaction) {
            return;
        }

        $action = match (true) {
            $event instanceof RewardCreated => 'dibuat',
            $event instanceof RewardUpdated => 'diperbarui',
            $event instanceof RewardDeleted => 'dihapus',
            $event instanceof RewardRestored => 'dipulihkan',
            default => 'diproses',
        };

        $title = 'Transaksi Reward ' . ucfirst($action);
        $message = 'Transaksi reward untuk siswa ' . ($transaction->student?->nama_lengkap ?? '-') . ' telah ' . $action . '.';

        $users = User::query()
            ->where(function ($query) use ($transaction): void {
                $query->where('email', (string) ($transaction->student?->nis ?? ''))
                    ->orWhere('email', (string) ($transaction->student?->nisn ?? ''));
            })
            ->orWhereHas('roles', function ($query): void {
                $query->whereIn('name', ['wali_kelas', 'kesiswaan', 'admin']);
            })
            ->get();

        foreach ($users as $user) {
            $user->notify(new TransactionActivityNotification($title, $message, [
                'transaction_id' => $transaction->id,
                'type' => 'reward',
                'action' => $action,
            ]));
        }
    }
}
