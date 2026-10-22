<?php

namespace App\Http\Controllers;

use App\Exports\AssessmentReportExport;
use App\Models\AcademicYear;
use App\Models\AttendanceDetail;
use App\Models\Classroom;
use App\Models\DailyAssessment;
use App\Models\DailyAssessmentScore;
use App\Models\FinalGrade;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AssessmentController extends Controller
{
    public function teacherDaily(Request $request)
    {
        $teacher = $this->teacherIdentity();
        $assignments = $this->teacherAssignments($teacher);
        $assignmentId = $request->integer('teacher_subject_id');
        $assignment = $assignments->firstWhere('id', $assignmentId);
        abort_if($assignmentId && !$assignment, 403, 'Kelas atau mata pelajaran tidak termasuk penugasan Anda.');
        $assessments = $assignment
            ? DailyAssessment::query()->where('teacher_subject_id', $assignment->id)->orderByDesc('assessment_date')->get()
            : collect();

        return view('assessment.teacher-daily', compact('assignments', 'assignment', 'assessments'));
    }

    public function storeDaily(Request $request)
    {
        $teacher = $this->teacherIdentity();
        $validated = $request->validate([
            'teacher_subject_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:project,task'],
            'assessment_date' => ['required', 'date', 'before_or_equal:today'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:1000'],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:1000'],
        ]);
        $assignment = $this->teacherAssignments($teacher)->firstWhere('id', (int) $validated['teacher_subject_id']);
        abort_unless($assignment, 403, 'Kelas atau mata pelajaran tidak termasuk penugasan Anda.');

        $assessment = DailyAssessment::query()->create($validated);
        foreach (Student::query()->where('classroom_id', $assignment->classroom_id)->pluck('id') as $studentId) {
            DailyAssessmentScore::query()->create([
                'daily_assessment_id' => $assessment->id,
                'student_id' => $studentId,
            ]);
        }

        return redirect()->route('guru.assessments.daily', ['teacher_subject_id' => $assignment->id])
            ->with('success', 'Penilaian harian berhasil dibuat.');
    }

    public function teacherDailyData(Request $request)
    {
        $teacher = $this->teacherIdentity();
        $assignment = $this->teacherAssignments($teacher)->firstWhere('id', $request->integer('teacher_subject_id'));
        abort_unless($assignment, 403, 'Penugasan tidak valid.');
        $assessment = DailyAssessment::query()
            ->where('teacher_subject_id', $assignment->id)
            ->findOrFail($request->integer('assessment_id'));

        $query = Student::query()->where('classroom_id', $assignment->classroom_id);
        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $query->where(fn($q) => $q->where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('nis', 'like', "%{$search}%"));
        }
        $filtered = (clone $query)->count();
        $orderColumn = (int) $request->input('order.0.column', 1);
        $query->orderBy(
            $orderColumn === 1 ? 'nis' : 'nama_lengkap',
            $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc'
        );
        $start = max(0, $request->integer('start'));
        $length = $request->integer('length', 10);
        $students = $length === -1 ? $query->get() : $query->skip($start)->take(max(0, $length))->get();
        $scores = DailyAssessmentScore::query()
            ->where('daily_assessment_id', $assessment->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()->keyBy('student_id');

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $students->map(fn(Student $student) => [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'student' => $student->nama_lengkap,
                'score' => $scores->get($student->id)?->score,
            ]),
        ]);
    }

    public function saveDailyScore(Request $request, DailyAssessment $assessment, Student $student)
    {
        $teacher = $this->teacherIdentity();
        abort_unless($this->teacherAssignments($teacher)->contains('id', $assessment->teacher_subject_id), 403);
        abort_unless((int) $student->classroom_id === (int) $assessment->teacherSubject->classroom_id, 404);
        $validated = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:' . $assessment->max_score],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $score = DailyAssessmentScore::query()->updateOrCreate(
            ['daily_assessment_id' => $assessment->id, 'student_id' => $student->id],
            $validated
        );

        return response()->json(['message' => 'Nilai harian tersimpan.', 'score' => $score->fresh()]);
    }

    public function teacherFinal(Request $request)
    {
        $teacher = $this->teacherIdentity();
        $assignments = $this->teacherAssignments($teacher);
        $assignmentId = $request->integer('teacher_subject_id');
        $assignment = $assignments->firstWhere('id', $assignmentId);
        abort_if($assignmentId && !$assignment, 403, 'Kelas atau mata pelajaran tidak termasuk penugasan Anda.');

        return view('assessment.teacher-final', compact('assignments', 'assignment'));
    }

    public function teacherFinalData(Request $request)
    {
        $teacher = $this->teacherIdentity();
        $assignment = $this->teacherAssignments($teacher)->firstWhere('id', $request->integer('teacher_subject_id'));
        abort_unless($assignment, 403, 'Penugasan tidak valid.');
        return $this->finalStudentData($request, $assignment);
    }

    public function saveFinal(Request $request, TeacherSubject $teacherSubject)
    {
        $teacher = $this->teacherIdentity();
        abort_unless($this->teacherAssignments($teacher)->contains('id', $teacherSubject->id), 403);
        $validated = $request->validate([
            'grading_method' => ['required', 'in:manual,automatic'],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::transaction(function () use ($teacherSubject, $validated) {
            $teacherSubject->update(['grading_method' => $validated['grading_method']]);
            if ($validated['grading_method'] === 'manual') {
                foreach ($validated['scores'] ?? [] as $studentId => $score) {
                    abort_unless(Student::query()
                        ->where('classroom_id', $teacherSubject->classroom_id)
                        ->whereKey($studentId)->exists(), 422, 'Siswa tidak termasuk kelas penugasan.');
                    FinalGrade::query()->updateOrCreate(
                        ['teacher_subject_id' => $teacherSubject->id, 'student_id' => $studentId],
                        ['score' => $score]
                    );
                }
            }
        });

        return back()->with('success', 'Metode dan nilai akhir berhasil disimpan.');
    }

    public function saveFinalScore(Request $request, TeacherSubject $teacherSubject, Student $student)
    {
        $teacher = $this->teacherIdentity();
        abort_unless($this->teacherAssignments($teacher)->contains('id', $teacherSubject->id), 403);
        abort_unless((int) $student->classroom_id === (int) $teacherSubject->classroom_id, 404);
        abort_if(($teacherSubject->grading_method ?? 'manual') !== 'manual', 422, 'Metode nilai akhir otomatis sedang dipilih.');
        $validated = $request->validate(['score' => ['nullable', 'numeric', 'min:0', 'max:100']]);
        $grade = FinalGrade::query()->updateOrCreate(
            ['teacher_subject_id' => $teacherSubject->id, 'student_id' => $student->id],
            ['score' => $validated['score']]
        );
        return response()->json(['message' => 'Nilai akhir tersimpan.', 'grade' => $grade]);
    }

    public function adminDaily(Request $request)
    {
        return $this->adminReportView($request, 'daily');
    }

    public function adminFinal(Request $request)
    {
        return $this->adminReportView($request, 'final');
    }

    private function adminReportView(Request $request, string $kind)
    {
        $years = AcademicYear::query()->orderByDesc('id')->get();
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);

        return view('assessment.admin-report', [
            'kind' => $kind,
            'years' => $years,
            'classrooms' => Classroom::query()->orderBy('nama_kelas')->get(['id', 'nama_kelas']),
            'teachers' => Teacher::query()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']),
            'filters' => $filters,
            'hasFilters' => $request->boolean('apply_filters'),
            'routePrefix' => $kind === 'daily' ? 'admin.assessments.daily' : 'admin.assessments.final',
        ]);
    }

    public function adminData(Request $request, string $kind)
    {
        abort_unless(in_array($kind, ['daily', 'final'], true), 404);
        $filters = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);
        return $this->adminRows($kind, $filters, $request);
    }

    public function adminExcel(Request $request, string $kind)
    {
        abort_unless(in_array($kind, ['daily', 'final'], true), 404);
        $filters = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);
        $rows = $this->reportRows($kind, $filters);
        return Excel::download(new AssessmentReportExport($kind, $rows), "laporan-penilaian-{$kind}.xlsx");
    }

    public function adminPdf(Request $request, string $kind)
    {
        abort_unless(in_array($kind, ['daily', 'final'], true), 404);
        $filters = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);
        return Pdf::loadView('assessment.export', [
            'kind' => $kind,
            'rows' => $this->reportRows($kind, $filters),
        ])->setPaper('a4', 'landscape')->download("laporan-penilaian-{$kind}.pdf");
    }

    public function homeroom(Request $request)
    {
        $teacher = $this->teacherIdentity();
        abort_unless($teacher->is_wali_kelas && $teacher->wali_classroom_id, 403);
        $yearId = $request->integer('academic_year_id') ?: (int) AcademicYear::query()->where('is_active', true)->value('id');
        abort_if(!$yearId, 422, 'Tahun ajaran aktif belum ditentukan.');
        $year = AcademicYear::query()->findOrFail($yearId);
        $rows = $this->homeroomRows((int) $teacher->wali_classroom_id, $yearId);
        $years = AcademicYear::query()->orderByDesc('id')->get();

        return view('assessment.homeroom', compact('teacher', 'year', 'years', 'rows'));
    }

    public function homeroomDownload(Request $request, string $format)
    {
        $teacher = $this->teacherIdentity();
        abort_unless($teacher->is_wali_kelas && $teacher->wali_classroom_id, 403);
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        $yearId = $request->integer('academic_year_id') ?: (int) AcademicYear::query()->where('is_active', true)->value('id');
        abort_if(!$yearId, 422, 'Tahun ajaran aktif belum ditentukan.');
        $rows = $this->homeroomRows((int) $teacher->wali_classroom_id, $yearId);
        if ($format === 'excel') {
            return Excel::download(new AssessmentReportExport('homeroom', $rows), 'rekap-rapor-dan-absensi.xlsx');
        }

        return Pdf::loadView('assessment.export', ['kind' => 'homeroom', 'rows' => $rows])
            ->setPaper('a4', 'landscape')->download('rekap-rapor-dan-absensi.pdf');
    }

    public function student(Request $request)
    {
        $student = $this->studentIdentity();
        $years = AcademicYear::query()->orderByDesc('id')->get();
        $yearId = $request->integer('academic_year_id') ?: (int) AcademicYear::query()->where('is_active', true)->value('id');
        $year = AcademicYear::query()->findOrFail($yearId);
        $assignments = TeacherSubject::query()
            ->with(['subject:id,nama_mapel', 'teacher:id,nama_lengkap'])
            ->where('classroom_id', $student->classroom_id)
            ->where('academic_year_id', $yearId)
            ->get();
        $rows = $assignments->map(fn(TeacherSubject $assignment) => $this->studentGradeRow($assignment, $student));
        $cumulativeGrade = $rows->whereNotNull('final_score')->avg('final_score');
        $attendance = $this->attendanceSummary($student->id, $yearId);
        $dailyAssessments = DailyAssessment::query()
            ->with(['teacherSubject.subject:id,nama_mapel', 'scores' => fn($query) => $query->where('student_id', $student->id)])
            ->whereIn('teacher_subject_id', $assignments->pluck('id'))
            ->orderBy('assessment_date')
            ->get();

        return view('assessment.student', compact(
            'student',
            'years',
            'year',
            'rows',
            'cumulativeGrade',
            'attendance',
            'dailyAssessments'
        ));
    }

    private function teacherIdentity(): Teacher
    {
        $user = auth()->user();
        $teacher = Teacher::query()->where('nip', $user->email)->orWhere('nama_lengkap', $user->name)->first();
        abort_unless($teacher, 403, 'Data guru tidak ditemukan.');
        return $teacher;
    }

    private function studentIdentity(): Student
    {
        $user = auth()->user();
        $student = Student::query()->where('nisn', $user->email)->orWhere('nis', $user->email)->first();
        abort_unless($student, 403, 'Data siswa tidak ditemukan.');
        return $student;
    }

    private function teacherAssignments(Teacher $teacher): Collection
    {
        $activeYearId = AcademicYear::query()->where('is_active', true)->value('id');
        return TeacherSubject::query()
            ->with(['classroom:id,nama_kelas', 'subject:id,nama_mapel', 'academicYear:id,tahun_ajaran,semester'])
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $activeYearId)
            ->orderBy('classroom_id')
            ->get();
    }

    private function finalStudentData(Request $request, TeacherSubject $assignment)
    {
        $query = Student::query()->where('classroom_id', $assignment->classroom_id);
        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $query->where(fn($q) => $q->where('nama_lengkap', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"));
        }
        $filtered = (clone $query)->count();
        $orderColumn = (int) $request->input('order.0.column', 2);
        $orderBy = $orderColumn === 1 ? 'nis' : 'nama_lengkap';
        $query->orderBy($orderBy, $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc');
        $start = max(0, $request->integer('start'));
        $length = $request->integer('length', 10);
        $students = $length === -1 ? $query->get() : $query->skip($start)->take(max(0, $length))->get();
        $data = $students->map(fn(Student $student) => $this->studentGradeRow($assignment, $student));

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    private function studentGradeRow(TeacherSubject $assignment, Student $student): array
    {
        $daily = DailyAssessmentScore::query()
            ->join('daily_assessments', 'daily_assessments.id', '=', 'daily_assessment_scores.daily_assessment_id')
            ->where('daily_assessments.teacher_subject_id', $assignment->id)
            ->where('daily_assessment_scores.student_id', $student->id)
            ->whereNotNull('daily_assessment_scores.score')
            ->get(['daily_assessment_scores.score', 'daily_assessments.max_score', 'daily_assessments.weight']);
        $weightTotal = $daily->sum(fn($row) => (float) $row->weight);
        $automaticScore = $weightTotal > 0
            ? round($daily->sum(fn($row) => ((float) $row->score / (float) $row->max_score) * 100 * (float) $row->weight) / $weightTotal, 2)
            : null;
        $manualScore = FinalGrade::query()->where('teacher_subject_id', $assignment->id)
            ->where('student_id', $student->id)->value('score');
        $score = $assignment->grading_method === 'automatic' ? $automaticScore : $manualScore;

        return [
            'student_id' => $student->id,
            'nis' => $student->nis,
            'student' => $student->nama_lengkap,
            'subject' => $assignment->subject?->nama_mapel ?? '-',
            'teacher' => $assignment->teacher?->nama_lengkap ?? '-',
            'grading_method' => $assignment->grading_method ?? 'manual',
            'daily_average' => $automaticScore,
            'final_score' => $score,
        ];
    }

    private function adminRows(string $kind, array $filters, Request $request)
    {
        $rows = $this->reportRows($kind, $filters);
        $total = $rows->count();
        $search = mb_strtolower(trim((string) $request->input('search.value', '')));
        if ($search !== '') {
            $rows = $rows->filter(fn(array $row) => str_contains(mb_strtolower(implode(' ', array_map('strval', $row))), $search))->values();
        }
        $filtered = $rows->count();
        $orderIndex = max(0, $request->integer('order.0.column'));
        $orderField = (string) $request->input("columns.{$orderIndex}.data", '');
        $headers = $this->reportHeaders($kind);
        if (in_array($orderField, $headers, true)) {
            $descending = $request->input('order.0.dir') === 'desc';
            $rows = $descending
                ? $rows->sortByDesc($orderField, SORT_NATURAL | SORT_FLAG_CASE)->values()
                : $rows->sortBy($orderField, SORT_NATURAL | SORT_FLAG_CASE)->values();
        }
        $start = max(0, $request->integer('start'));
        $length = $request->integer('length', 10);
        $page = $length === -1 ? $rows->slice($start) : $rows->slice($start, max(0, $length));

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $page->values(),
        ]);
    }

    private function reportHeaders(string $kind): array
    {
        return match ($kind) {
            'daily' => ['Kelas', 'Guru', 'Mata Pelajaran', 'NIS', 'Siswa', 'Penilaian', 'Jenis', 'Tanggal', 'Nilai', 'Skor Maksimum', 'Bobot'],
            'final' => ['Kelas', 'Guru', 'Mata Pelajaran', 'NIS', 'Siswa', 'Metode Nilai', 'Nilai Akhir', 'Hadir/Terlambat/Dispen', 'Sakit', 'Izin', 'Alpa', 'Bolos'],
            default => ['NIS', 'Siswa', 'Mata Pelajaran', 'Guru', 'Metode Nilai', 'Nilai Akhir', 'Hadir/Terlambat/Dispen', 'Sakit', 'Izin', 'Alpa', 'Bolos'],
        };
    }

    private function reportRows(string $kind, array $filters): Collection
    {
        $assignments = TeacherSubject::query()
            ->with(['teacher:id,nama_lengkap', 'classroom:id,nama_kelas', 'subject:id,nama_mapel'])
            ->where('academic_year_id', $filters['academic_year_id'])
            ->when($filters['classroom_id'] ?? null, fn($q, $id) => $q->where('classroom_id', $id))
            ->when($filters['teacher_id'] ?? null, fn($q, $id) => $q->where('teacher_id', $id))
            ->get();

        $rows = collect();
        if ($kind === 'final') {
            $classroomIds = $assignments->pluck('classroom_id')->unique()->all();
            $assignmentIds = $assignments->pluck('id')->all();
            $studentsByClass = Student::query()->whereIn('classroom_id', $classroomIds)
                ->orderBy('nama_lengkap')->get()->groupBy('classroom_id');
            $studentIds = $studentsByClass->flatten()->pluck('id')->all();
            $manualScores = FinalGrade::query()->whereIn('teacher_subject_id', $assignmentIds)
                ->whereIn('student_id', $studentIds)->get()
                ->keyBy(fn(FinalGrade $grade) => $grade->teacher_subject_id . ':' . $grade->student_id);
            $automaticScores = DB::table('daily_assessment_scores')
                ->join('daily_assessments', 'daily_assessments.id', '=', 'daily_assessment_scores.daily_assessment_id')
                ->whereIn('daily_assessments.teacher_subject_id', $assignmentIds)
                ->whereIn('daily_assessment_scores.student_id', $studentIds)
                ->whereNotNull('daily_assessment_scores.score')
                ->select('daily_assessments.teacher_subject_id', 'daily_assessment_scores.student_id')
                ->selectRaw('SUM((daily_assessment_scores.score / daily_assessments.max_score) * 100 * daily_assessments.weight) / NULLIF(SUM(daily_assessments.weight), 0) AS weighted_score')
                ->groupBy('daily_assessments.teacher_subject_id', 'daily_assessment_scores.student_id')
                ->get()
                ->keyBy(fn($row) => $row->teacher_subject_id . ':' . $row->student_id);
            $attendanceByStudent = DB::table('attendance_details')
                ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
                ->where('teacher_attendances.academic_year_id', $filters['academic_year_id'])
                ->whereIn('attendance_details.student_id', $studentIds)
                ->select('attendance_details.student_id')
                ->selectRaw("SUM(CASE WHEN attendance_details.status IN ('Hadir','Terlambat','Dispen') THEN 1 ELSE 0 END) as hadir")
                ->selectRaw("SUM(CASE WHEN attendance_details.status = 'Sakit' THEN 1 ELSE 0 END) as sakit")
                ->selectRaw("SUM(CASE WHEN attendance_details.status = 'Izin' THEN 1 ELSE 0 END) as izin")
                ->selectRaw("SUM(CASE WHEN attendance_details.status IN ('Alpha','Alpa') THEN 1 ELSE 0 END) as alpa")
                ->selectRaw("SUM(CASE WHEN attendance_details.status = 'Bolos' THEN 1 ELSE 0 END) as bolos")
                ->groupBy('attendance_details.student_id')
                ->get()->keyBy('student_id');

            foreach ($assignments as $assignment) {
                foreach ($studentsByClass->get($assignment->classroom_id, collect()) as $student) {
                    $key = $assignment->id . ':' . $student->id;
                    $manual = $manualScores->get($key)?->score;
                    $automatic = $automaticScores->get($key)?->weighted_score;
                    $grade = ($assignment->grading_method ?? 'manual') === 'automatic' ? $automatic : $manual;
                    $counts = $attendanceByStudent->get($student->id);
                    $rows->push([
                        'Kelas' => $assignment->classroom?->nama_kelas,
                        'Guru' => $assignment->teacher?->nama_lengkap,
                        'Mata Pelajaran' => $assignment->subject?->nama_mapel,
                        'NIS' => $student->nis,
                        'Siswa' => $student->nama_lengkap,
                        'Metode Nilai' => ($assignment->grading_method ?? 'manual') === 'automatic' ? 'Otomatis' : 'Manual',
                        'Nilai Akhir' => $grade === null ? null : round((float) $grade, 2),
                        'Hadir/Terlambat/Dispen' => (int) ($counts->hadir ?? 0),
                        'Sakit' => (int) ($counts->sakit ?? 0),
                        'Izin' => (int) ($counts->izin ?? 0),
                        'Alpa' => (int) ($counts->alpa ?? 0),
                        'Bolos' => (int) ($counts->bolos ?? 0),
                    ]);
                }
            }

            return $rows;
        }

        foreach ($assignments as $assignment) {
            if ($kind === 'daily') {
                $assessments = DailyAssessment::query()->where('teacher_subject_id', $assignment->id)
                    ->with(['scores.student:id,nis,nama_lengkap'])->orderBy('assessment_date')->get();
                foreach ($assessments as $assessment) {
                    foreach ($assessment->scores as $score) {
                        $rows->push([
                            'Kelas' => $assignment->classroom?->nama_kelas,
                            'Guru' => $assignment->teacher?->nama_lengkap,
                            'Mata Pelajaran' => $assignment->subject?->nama_mapel,
                            'NIS' => $score->student?->nis,
                            'Siswa' => $score->student?->nama_lengkap,
                            'Penilaian' => $assessment->title,
                            'Jenis' => $assessment->type === 'project' ? 'Proyek' : 'Tugas',
                            'Tanggal' => $assessment->assessment_date?->format('d-m-Y'),
                            'Nilai' => $score->score,
                            'Skor Maksimum' => $assessment->max_score,
                            'Bobot' => $assessment->weight,
                        ]);
                    }
                }
            }
        }

        return $rows;
    }

    private function homeroomRows(int $classroomId, int $academicYearId): Collection
    {
        return $this->reportRows('final', [
            'academic_year_id' => $academicYearId,
            'classroom_id' => $classroomId,
        ]);
    }

    private function attendanceSummary(int $studentId, int $academicYearId): array
    {
        $counts = AttendanceDetail::query()
            ->join('teacher_attendances', 'teacher_attendances.id', '=', 'attendance_details.teacher_attendance_id')
            ->where('teacher_attendances.academic_year_id', $academicYearId)
            ->where('attendance_details.student_id', $studentId)
            ->selectRaw("SUM(CASE WHEN attendance_details.status IN ('Hadir','Terlambat','Dispen') THEN 1 ELSE 0 END) as hadir")
            ->selectRaw("SUM(CASE WHEN attendance_details.status = 'Sakit' THEN 1 ELSE 0 END) as sakit")
            ->selectRaw("SUM(CASE WHEN attendance_details.status = 'Izin' THEN 1 ELSE 0 END) as izin")
            ->selectRaw("SUM(CASE WHEN attendance_details.status IN ('Alpha','Alpa') THEN 1 ELSE 0 END) as alpa")
            ->selectRaw("SUM(CASE WHEN attendance_details.status = 'Bolos' THEN 1 ELSE 0 END) as bolos")
            ->first();

        return [
            'Hadir/Terlambat/Dispen' => (int) ($counts->hadir ?? 0),
            'Sakit' => (int) ($counts->sakit ?? 0),
            'Izin' => (int) ($counts->izin ?? 0),
            'Alpa' => (int) ($counts->alpa ?? 0),
            'Bolos' => (int) ($counts->bolos ?? 0),
        ];
    }
}
