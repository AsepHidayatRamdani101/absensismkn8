<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_assessments', function (Blueprint $table) {
            // Penilaian harian yang dibuat otomatis dari tugas PJJ; ikut terhapus bila tugasnya dihapus.
            $table->foreignId('pjj_assignment_id')->nullable()->unique()->after('teacher_subject_id')
                ->constrained('pjj_assignments')->cascadeOnDelete();
        });

        Schema::table('teacher_subjects', function (Blueprint $table) {
            // Proporsi kehadiran mapel dalam nilai akhir otomatis (persen, sisanya rata-rata nilai harian).
            $table->decimal('attendance_weight', 5, 2)->default(30)->after('grading_method');
        });

        $this->backfillExistingPjjAssignments();
    }

    public function down(): void
    {
        Schema::table('daily_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pjj_assignment_id');
        });

        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropColumn('attendance_weight');
        });
    }

    /**
     * Tugas PJJ lama ikut terekam sebagai penilaian harian beserta nilai
     * pengumpulan yang sudah dinilai, agar rekap nilai langsung menyatu.
     */
    private function backfillExistingPjjAssignments(): void
    {
        $now = now();

        $assignments = DB::table('pjj_assignments')
            ->whereNotIn('id', DB::table('daily_assessments')->whereNotNull('pjj_assignment_id')->pluck('pjj_assignment_id'))
            ->get(['id', 'teacher_subject_id', 'title', 'created_at']);

        foreach ($assignments as $assignment) {
            $assessmentId = DB::table('daily_assessments')->insertGetId([
                'teacher_subject_id' => $assignment->teacher_subject_id,
                'pjj_assignment_id' => $assignment->id,
                'title' => mb_substr('Tugas: ' . $assignment->title, 0, 150),
                'type' => 'task',
                'assessment_date' => substr((string) ($assignment->created_at ?? $now), 0, 10),
                'max_score' => 100,
                'weight' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $submissions = DB::table('pjj_submissions')
                ->where('pjj_assignment_id', $assignment->id)
                ->whereNotNull('score')
                ->get(['student_id', 'score']);

            foreach ($submissions as $submission) {
                DB::table('daily_assessment_scores')->updateOrInsert(
                    ['daily_assessment_id' => $assessmentId, 'student_id' => $submission->student_id],
                    [
                        'score' => min(100, max(0, (float) $submission->score)),
                        'note' => 'Sinkron otomatis dari tugas PJJ.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
};
