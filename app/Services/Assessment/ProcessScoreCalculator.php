<?php

namespace App\Services\Assessment;

use App\Models\FinalGrade;
use App\Models\TeacherSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcessScoreCalculator
{
    public const PRESENT_STATUSES = ['Hadir', 'Terlambat', 'Dispen'];

    public function calculate(Collection $assignments, array $studentIds): array
    {
        $assignmentIds = $assignments->pluck('id')->all();

        if ($assignmentIds === [] || $studentIds === []) {
            return [];
        }

        $manualScores = FinalGrade::query()
            ->whereIn('teacher_subject_id', $assignmentIds)
            ->whereIn('student_id', $studentIds)
            ->get(['teacher_subject_id', 'student_id', 'score'])
            ->keyBy(fn (FinalGrade $grade) => $grade->teacher_subject_id . ':' . $grade->student_id);

        $dailyAverages = DB::table('daily_assessment_scores')
            ->join('daily_assessments', 'daily_assessments.id', '=', 'daily_assessment_scores.daily_assessment_id')
            ->whereIn('daily_assessments.teacher_subject_id', $assignmentIds)
            ->whereIn('daily_assessment_scores.student_id', $studentIds)
            ->select('daily_assessments.teacher_subject_id', 'daily_assessment_scores.student_id')
            ->selectRaw('SUM((COALESCE(daily_assessment_scores.score, 0) / daily_assessments.max_score) * 100 * daily_assessments.weight) / NULLIF(SUM(daily_assessments.weight), 0) AS weighted_score')
            ->groupBy('daily_assessments.teacher_subject_id', 'daily_assessment_scores.student_id')
            ->get()
            ->keyBy(fn ($row) => $row->teacher_subject_id . ':' . $row->student_id);

        $presentList = "'" . implode("','", self::PRESENT_STATUSES) . "'";
        $attendance = DB::table('attendance_details')
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->whereIn('teacher_attendances.academic_year_id', $assignments->pluck('academic_year_id')->unique()->all())
            ->whereIn('teacher_attendances.classroom_id', $assignments->pluck('classroom_id')->unique()->all())
            ->whereIn('teacher_attendances.subject_id', $assignments->pluck('subject_id')->unique()->all())
            ->whereIn('attendance_details.student_id', $studentIds)
            ->select(
                'teacher_attendances.academic_year_id',
                'teacher_attendances.subject_id',
                'teacher_attendances.classroom_id',
                'attendance_details.student_id'
            )
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN attendance_details.status IN ({$presentList}) THEN 1 ELSE 0 END) AS hadir")
            ->groupBy(
                'teacher_attendances.academic_year_id',
                'teacher_attendances.subject_id',
                'teacher_attendances.classroom_id',
                'attendance_details.student_id'
            )
            ->get()
            ->keyBy(fn ($row) => implode(':', [$row->academic_year_id, $row->subject_id, $row->classroom_id, $row->student_id]));

        $results = [];

        foreach ($assignments as $assignment) {
            foreach ($studentIds as $studentId) {
                $key = $assignment->id . ':' . $studentId;

                $daily = $dailyAverages->get($key)?->weighted_score;
                $dailyAverage = $daily === null ? null : round((float) $daily, 2);

                $attendanceRow = $attendance->get(implode(':', [
                    $assignment->academic_year_id,
                    $assignment->subject_id,
                    $assignment->classroom_id,
                    $studentId,
                ]));
                $attendancePercentage = $attendanceRow && (int) $attendanceRow->total > 0
                    ? round(((int) $attendanceRow->hadir / (int) $attendanceRow->total) * 100, 2)
                    : null;

                $manual = $manualScores->get($key)?->score;
                $manual = $manual === null ? null : round((float) $manual, 2);
                $automatic = self::blend($assignment, $dailyAverage, $attendancePercentage);
                $method = ($assignment->grading_method ?? 'manual') === 'automatic' ? 'automatic' : 'manual';

                $results[$key] = [
                    'daily_average' => $dailyAverage,
                    'attendance_percentage' => $attendancePercentage,
                    'automatic' => $automatic,
                    'manual' => $manual,
                    'final' => $method === 'automatic' ? $automatic : $manual,
                    'method' => $method,
                ];
            }
        }

        return $results;
    }

    public static function blend(TeacherSubject $assignment, ?float $dailyAverage, ?float $attendancePercentage): ?float
    {
        $weight = max(0.0, min(100.0, (float) ($assignment->attendance_weight ?? 30))) / 100;

        if ($dailyAverage !== null && $attendancePercentage !== null) {
            return round($dailyAverage * (1 - $weight) + $attendancePercentage * $weight, 2);
        }

        return $dailyAverage ?? $attendancePercentage;
    }
}
