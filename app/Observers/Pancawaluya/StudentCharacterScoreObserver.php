<?php

namespace App\Observers\Pancawaluya;

use App\Events\Pancawaluya\CharacterUpdated;
use App\Models\StudentCharacterScore;

class StudentCharacterScoreObserver
{
    public function saved(StudentCharacterScore $score): void
    {
        event(new CharacterUpdated((int) $score->student_id));
    }
}
