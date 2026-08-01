<?php

namespace App\Listeners\Pancawaluya;

use App\Events\Pancawaluya\ViolationCreated;
use App\Events\Pancawaluya\ViolationDeleted;
use App\Events\Pancawaluya\ViolationRestored;
use App\Events\Pancawaluya\ViolationUpdated;
use App\Models\User;
use App\Models\ViolationTransaction;
use App\Notifications\Pancawaluya\TransactionActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendViolationTransactionNotification implements ShouldQueue
{
    public function handle(object $event): void
    {
        $transaction = ViolationTransaction::query()->with('student')->withTrashed()->find($event->transactionId);
        if (!$transaction) {
            return;
        }

        $action = match (true) {
            $event instanceof ViolationCreated => 'dibuat',
            $event instanceof ViolationUpdated => 'diperbarui',
            $event instanceof ViolationDeleted => 'dihapus',
            $event instanceof ViolationRestored => 'dipulihkan',
            default => 'diproses',
        };

        $title = 'Transaksi Pelanggaran ' . ucfirst($action);
        $message = 'Transaksi pelanggaran untuk siswa ' . ($transaction->student?->nama_lengkap ?? '-') . ' telah ' . $action . '.';

        $users = User::query()
            ->where(function ($query) use ($transaction): void {
                $query->where('email', (string) ($transaction->student?->nis ?? ''))
                    ->orWhere('email', (string) ($transaction->student?->nisn ?? ''));
            })
            ->orWhereHas('roles', function ($query): void {
                $query->whereIn('name', ['wali_kelas', 'kesiswaan', 'admin', 'bk']);
            })
            ->get();

        foreach ($users as $user) {
            $user->notify(new TransactionActivityNotification($title, $message, [
                'transaction_id' => $transaction->id,
                'type' => 'violation',
                'action' => $action,
            ]));
        }
    }
}
