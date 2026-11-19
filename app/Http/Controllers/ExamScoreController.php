<?php

namespace App\Http\Controllers;

use App\Exports\ExamScoresTemplateExport;
use App\Imports\ExamScoresImport;
use App\Models\AcademicYear;
use App\Models\ExamScore;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Services\Assessment\ProcessScoreCalculator;
use App\Services\Assessment\ReportCardService;
use App\Support\SchoolIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ExamScoreController extends Controller
{
    public function __construct(
        private readonly ProcessScoreCalculator $calculator,
        private readonly ReportCardService $reportCards,
    ) {
    }

    public function index(Request $request)
    {
        $teacher = $this->teacher();
        $assignments = $this->assignments($teacher);
        $assignmentId = $request->integer('teacher_subject_id');
        $assignment = $assignments->firstWhere('id', $assignmentId);
        abort_if($assignmentId && !$assignment, 403, 'Kelas atau mata pelajaran tidak termasuk penugasan Anda.');

        $settings = $this->reportCards->settings();
        $rows = $assignment ? $this->rows($assignment, $settings) : collect();

        return view('assessment.exam-scores', compact('assignments', 'assignment', 'rows', 'settings'));
    }

    public function update(Request $request, TeacherSubject $teacherSubject): RedirectResponse
    {
        $assignment = $this->ownedAssignment($teacherSubject);

        $validated = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*.uts' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.uas' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'scores.*.uts.numeric' => 'Nilai UTS harus berupa angka.',
            'scores.*.uas.numeric' => 'Nilai UAS harus berupa angka.',
            'scores.*.uts.min' => 'Nilai UTS minimal 0.',
            'scores.*.uas.min' => 'Nilai UAS minimal 0.',
            'scores.*.uts.max' => 'Nilai UTS maksimal 100.',
            'scores.*.uas.max' => 'Nilai UAS maksimal 100.',
        ]);

        $allowedIds = $this->students($assignment)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $submittedIds = array_map('intval', array_keys($validated['scores']));
        $unknown = array_diff($submittedIds, $allowedIds);
        abort_if($unknown !== [], 422, 'Terdapat siswa yang tidak termasuk kelas penugasan ini.');

        $saved = DB::transaction(function () use ($validated, $assignment) {
            $count = 0;

            foreach ($validated['scores'] as $studentId => $score) {
                $count += $this->store($assignment, (int) $studentId, [
                    'uts_score' => $this->normalize($score['uts'] ?? null),
                    'uas_score' => $this->normalize($score['uas'] ?? null),
                ]) ? 1 : 0;
            }

            return $count;
        });

        return redirect()
            ->route('guru.assessments.exams', ['teacher_subject_id' => $assignment->id])
            ->with('success', "Nilai UTS & UAS tersimpan ({$saved} siswa diperbarui).");
    }

    public function template(TeacherSubject $teacherSubject)
    {
        $assignment = $this->ownedAssignment($teacherSubject);
        $scores = ExamScore::query()->where('teacher_subject_id', $assignment->id)->get()->keyBy('student_id');

        $rows = $this->students($assignment)->map(fn (Student $student) => [
            (string) $student->nis,
            $student->nama_lengkap,
            $scores->get($student->id)?->uts_score,
            $scores->get($student->id)?->uas_score,
        ])->all();

        $fileName = 'nilai-uts-uas-' . Str::slug($assignment->classroom?->nama_kelas . '-' . $assignment->subject?->nama_mapel) . '.xlsx';

        return Excel::download(new ExamScoresTemplateExport($rows, $assignment), $fileName);
    }

    public function import(Request $request, TeacherSubject $teacherSubject): RedirectResponse
    {
        $assignment = $this->ownedAssignment($teacherSubject);
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $sheet = Excel::toCollection(new ExamScoresImport(), $request->file('file'))->first() ?? collect();
        $students = $this->students($assignment);
        $studentsByNis = $students->keyBy(fn (Student $student) => trim((string) $student->nis));
        $studentsByLooseNis = $students->keyBy(fn (Student $student) => ltrim(trim((string) $student->nis), '0'));
        $updates = [];
        $errors = [];

        foreach ($sheet as $index => $row) {
            $line = $index + 2;
            $nis = trim((string) ($row['nis'] ?? ''));

            if ($nis === '' && blank($row['uts'] ?? null) && blank($row['uas'] ?? null)) {
                continue;
            }

            $student = $studentsByNis->get($nis) ?? ($nis !== '' ? $studentsByLooseNis->get(ltrim($nis, '0')) : null);

            if (!$student) {
                $errors[] = "Baris {$line}: NIS \"{$nis}\" tidak ditemukan di kelas ini.";
                continue;
            }

            $values = [];

            foreach (['uts' => 'uts_score', 'uas' => 'uas_score'] as $column => $field) {
                $raw = $row[$column] ?? null;

                // Sel kosong = nilai tidak diubah.
                if (blank($raw)) {
                    continue;
                }

                $number = $this->parseNumber($raw);

                if ($number === null || $number < 0 || $number > 100) {
                    $errors[] = 'Baris ' . $line . ': nilai ' . strtoupper($column) . " \"{$raw}\" tidak valid (harus 0–100).";
                    continue;
                }

                $values[$field] = $number;
            }

            if ($values !== []) {
                $updates[$student->id] = ($updates[$student->id] ?? []) + $values;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => array_merge(['Import dibatalkan, tidak ada nilai yang disimpan. Perbaiki baris berikut:'], array_slice($errors, 0, 15)),
            ]);
        }

        $saved = DB::transaction(function () use ($updates, $assignment) {
            $count = 0;

            foreach ($updates as $studentId => $values) {
                $count += $this->store($assignment, (int) $studentId, $values) ? 1 : 0;
            }

            return $count;
        });

        return redirect()
            ->route('guru.assessments.exams', ['teacher_subject_id' => $assignment->id])
            ->with('success', "Import selesai: nilai {$saved} siswa diperbarui.");
    }

    private function store(TeacherSubject $assignment, int $studentId, array $values): bool
    {
        $score = ExamScore::query()->firstOrNew([
            'teacher_subject_id' => $assignment->id,
            'student_id' => $studentId,
        ]);

        if (!$score->exists && collect($values)->every(fn ($value) => $value === null)) {
            return false;
        }

        $score->fill($values);

        if (!$score->isDirty()) {
            return false;
        }

        $score->updated_by = auth()->id();
        $score->save();

        return true;
    }

    private function rows(TeacherSubject $assignment, array $settings): Collection
    {
        $students = $this->students($assignment);
        $process = $this->calculator->calculate(collect([$assignment]), $students->pluck('id')->all());
        $scores = ExamScore::query()->where('teacher_subject_id', $assignment->id)->get()->keyBy('student_id');

        return $students->map(function (Student $student) use ($assignment, $process, $scores, $settings) {
            $daily = $process[$assignment->id . ':' . $student->id]['final'] ?? null;
            $exam = $scores->get($student->id);
            $uts = $exam?->uts_score === null ? null : (float) $exam->uts_score;
            $uas = $exam?->uas_score === null ? null : (float) $exam->uas_score;
            $composed = ReportCardService::compose($daily, $uts, $uas, $settings);
            $rounded = $composed['score'] === null ? null : (int) round($composed['score']);

            return [
                'student' => $student,
                'daily' => $daily,
                'uts' => $uts,
                'uas' => $uas,
                'final' => $composed['score'],
                'predicate' => ReportCardService::predicate($rounded === null ? null : (float) $rounded, $settings['kkm']),
                'missing' => $composed['missing'],
            ];
        });
    }

    private function students(TeacherSubject $assignment): Collection
    {
        return Student::query()
            ->where('classroom_id', $assignment->classroom_id)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nis', 'nisn', 'nama_lengkap', 'classroom_id']);
    }

    private function teacher(): Teacher
    {
        $teacher = SchoolIdentity::teacherFor(auth()->user());
        abort_unless($teacher, 403, 'Data guru tidak ditemukan untuk akun ini.');

        return $teacher;
    }

    private function assignments(Teacher $teacher): Collection
    {
        return TeacherSubject::query()
            ->with(['classroom:id,nama_kelas,major_id', 'classroom.major:id,nama_jurusan', 'subject:id,nama_mapel', 'academicYear:id,tahun_ajaran,semester'])
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', AcademicYear::query()->where('is_active', true)->value('id'))
            ->orderBy('classroom_id')
            ->get();
    }

    private function ownedAssignment(TeacherSubject $teacherSubject): TeacherSubject
    {
        $assignment = $this->assignments($this->teacher())->firstWhere('id', $teacherSubject->id);
        abort_unless($assignment, 403, 'Kelas atau mata pelajaran tidak termasuk penugasan Anda pada semester aktif.');

        return $assignment;
    }

    private function normalize(mixed $value): ?float
    {
        return blank($value) ? null : round((float) $value, 2);
    }

    private function parseNumber(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $text = str_replace(',', '.', trim((string) $value));

        return is_numeric($text) ? round((float) $text, 2) : null;
    }
}
