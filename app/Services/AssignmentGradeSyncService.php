<?php

namespace App\Services;

use App\Models\DailyAssessment;
use App\Models\DailyAssessmentScore;
use App\Models\PjjAssignment;
use App\Models\PjjSubmission;
use Carbon\Carbon;

/**
 * Menyinkronkan tugas (PJJ maupun pembelajaran biasa) ke modul Penilaian Harian
 * sehingga nilai tugas otomatis terekam ke nilai harian dan ikut terhitung
 * pada nilai akhir (rujukan rapor).
 */
class AssignmentGradeSyncService
{
    /**
     * Satu tugas = satu penilaian harian (type "task", skala 0–100, bobot 1).
     * Dipanggil saat tugas dibuat/diubah guru.
     */
    public function syncAssignment(PjjAssignment $assignment): DailyAssessment
    {
        return DailyAssessment::query()->updateOrCreate(
            ['pjj_assignment_id' => $assignment->id],
            [
                'teacher_subject_id' => $assignment->teacher_subject_id,
                'title' => mb_substr('Tugas: ' . $assignment->title, 0, 150),
                'type' => 'task',
                'assessment_date' => Carbon::parse($assignment->created_at ?? now())->toDateString(),
                'max_score' => 100,
                'weight' => 1,
            ]
        );
    }

    /**
     * Nilai pengumpulan siswa (otomatis maupun manual oleh guru) direkam
     * sebagai skor penilaian harian pada penilaian milik tugas tersebut.
     */
    public function syncSubmissionScore(PjjSubmission $submission): void
    {
        if ($submission->score === null) {
            return;
        }

        $assignment = $submission->assignment ?? PjjAssignment::query()->find($submission->pjj_assignment_id);

        if (!$assignment) {
            return;
        }

        $assessment = $this->syncAssignment($assignment);

        DailyAssessmentScore::query()->updateOrCreate(
            ['daily_assessment_id' => $assessment->id, 'student_id' => $submission->student_id],
            [
                'score' => min(100, max(0, (float) $submission->score)),
                'note' => 'Sinkron otomatis dari nilai tugas.',
            ]
        );
    }
}
