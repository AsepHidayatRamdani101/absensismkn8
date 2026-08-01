<?php

namespace App\Listeners\Pancawaluya;

use App\Events\Pancawaluya\SPGenerated;
use App\Events\Pancawaluya\SPUpdated;
use App\Models\User;
use App\Notifications\Pancawaluya\TransactionActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSPNotification implements ShouldQueue
{
    public function handle(object $event): void
    {
        if (!$event instanceof SPGenerated && !$event instanceof SPUpdated) {
            return;
        }

        $action = $event instanceof SPGenerated ? 'dibuat' : 'diperbarui';
        $title = 'Status SP ' . strtoupper($event->spLevel) . ' ' . ucfirst($action);
        $message = 'Status SP siswa diperbarui menjadi ' . strtoupper($event->spLevel) . '.';

        $users = User::query()->whereHas('roles', function ($query): void {
            $query->whereIn('name', ['admin', 'bk', 'kesiswaan']);
        })->get();

        foreach ($users as $user) {
            $user->notify(new TransactionActivityNotification($title, $message, [
                'student_id' => $event->studentId,
                'sp_level' => $event->spLevel,
                'action' => $action,
            ]));
        }
    }
}
