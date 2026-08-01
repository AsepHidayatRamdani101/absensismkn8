<?php

namespace App\Observers\Pancawaluya;

use App\Events\Pancawaluya\SPGenerated;
use App\Events\Pancawaluya\SPUpdated;
use App\Models\StudentWarningLetter;

class StudentWarningLetterObserver
{
    public function created(StudentWarningLetter $warningLetter): void
    {
        event(new SPGenerated((int) $warningLetter->student_id, (string) $warningLetter->sp_level));
    }

    public function updated(StudentWarningLetter $warningLetter): void
    {
        event(new SPUpdated((int) $warningLetter->student_id, (string) $warningLetter->sp_level));
    }
}
