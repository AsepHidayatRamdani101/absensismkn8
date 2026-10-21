<?php

namespace App\Http\Controllers;

use App\Models\PjjAssignment;
use App\Models\PjjAttendance;
use App\Models\PjjSubmission;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\Subject;
use App\Support\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PjjController extends Controller
{
    public function adminIndex(Request $request, string $section = 'dashboard')
    {
        abort_unless(in_array($section, ['dashboard', 'tasks', 'teacher-attendance', 'student-attendance', 'effectiveness'], true), 404);

        $filters = $this->periodFilter($request);
        $assignmentQuery = PjjAssignment::query()
            ->whereBetween('created_at', [$filters['start']->copy()->startOfDay(), $filters['end']->copy()->endOfDay()]);
        $this->applyAssignmentFilters($assignmentQuery, $request);
        $assignmentIds = (clone $assignmentQuery)->select('id');

        if ($request->ajax() && $section !== 'dashboard' && $section !== 'effectiveness') {
            return $this->adminDataTable($request, $section, $assignmentQuery, $filters);
        }

        $assignmentsCount = (clone $assignmentQuery)->count();
        $teacherAttendanceCount = (clone $assignmentQuery)->distinct()->count('teacher_id');
        $studentAttendanceCount = PjjAttendance::query()
            ->whereBetween('attendance_date', [$filters['start']->toDateString(), $filters['end']->toDateString()])
            ->distinct()
            ->count('student_id');

        $studentTaskCount = DB::table('pjj_assignments')
            ->join('teacher_subjects', 'teacher_subjects.id', '=', 'pjj_assignments.teacher_subject_id')
            ->join('students', 'students.classroom_id', '=', 'teacher_subjects.classroom_id')
            ->whereIn('pjj_assignments.id', clone $assignmentIds)
            ->count();
        $submittedTaskCount = PjjSubmission::query()
            ->whereIn('pjj_assignment_id', clone $assignmentIds)
            ->count();
        $effectiveness = $studentTaskCount > 0
            ? round(($submittedTaskCount / $studentTaskCount) * 100, 2)
            : 0;

        $isPjjActiveToday = SchoolCalendar::isPjj(Carbon::today());
        $teachers = Teacher::query()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']);
        $classrooms = Classroom::query()->orderBy('nama_kelas')->get(['id', 'nama_kelas', 'major_id']);
        $subjects = Subject::query()->orderBy('nama_mapel')->get(['id', 'nama_mapel']);

        return view('admin.pjj.index', compact(
            'section',
            'filters',
            'assignmentsCount',
            'teacherAttendanceCount',
            'studentAttendanceCount',
            'studentTaskCount',
            'submittedTaskCount',
            'effectiveness',
            'isPjjActiveToday',
            'teachers',
            'classrooms',
            'subjects'
        ));
    }

    public function teacherIndex(Request $request)
    {
        $teacher = $this->teacherForCurrentUser();
        if ($request->ajax()) {
            return $this->teacherAssignmentsDataTable($request, $teacher);
        }

        $teacherSubjects = TeacherSubject::query()
            ->where('teacher_id', $teacher->id)
            ->with(['subject', 'classroom'])
            ->orderBy('classroom_id')
            ->get();
        $isPjjActiveToday = SchoolCalendar::isPjj(Carbon::today());

        return view('guru.pjj.index', compact('teacher', 'teacherSubjects', 'isPjjActiveToday'));
    }

    public function teacherStoreAssignment(Request $request)
    {
        $this->ensurePjjActive();
        $teacher = $this->teacherForCurrentUser();
        $data = $request->validate([
            'teacher_subject_id' => ['required', 'integer', 'exists:teacher_subjects,id'],
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'due_at' => ['nullable', 'date', 'after_or_equal:now'],
        ]);

        $teacherSubject = TeacherSubject::query()
            ->whereKey($data['teacher_subject_id'])
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $attachmentPath = $request->file('attachment')?->store('pjj/assignments', 'local');

        PjjAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'teacher_subject_id' => $teacherSubject->id,
            'title' => $data['title'],
            'instructions' => $data['instructions'],
            'attachment_path' => $attachmentPath,
            'due_at' => $data['due_at'] ?? null,
        ]);

        return redirect()->route('guru.pjj.index')->with('success', 'Tugas PJJ berhasil dipublikasikan.');
    }

    public function teacherShowAssignment(Request $request, PjjAssignment $assignment)
    {
        $teacher = $this->teacherForCurrentUser();
        abort_unless($assignment->teacher_id === $teacher->id, 403);

        $assignment->load(['teacherSubject.subject', 'teacherSubject.classroom']);
        if ($request->ajax()) {
            return $this->teacherSubmissionsDataTable($request, $assignment);
        }

        return view('guru.pjj.assignment', compact('assignment'));
    }

    public function teacherGradeSubmission(Request $request, PjjSubmission $submission)
    {
        $teacher = $this->teacherForCurrentUser();
        $submission->load('assignment');
        abort_unless($submission->assignment->teacher_id === $teacher->id, 403);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update([
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'graded_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Nilai tugas siswa berhasil disimpan.']);
        }

        return back()->with('success', 'Nilai tugas siswa berhasil disimpan.');
    }

    public function studentIndex(Request $request)
    {
        $student = $this->studentForCurrentUser();
        if ($request->ajax()) {
            return $this->studentAssignmentsDataTable($request, $student);
        }

        $todayAttendance = PjjAttendance::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();
        $subjects = Subject::query()
            ->whereHas('teacherSubjects', fn($teacherSubject) => $teacherSubject->where('classroom_id', $student->classroom_id))
            ->orderBy('nama_mapel')
            ->get(['id', 'nama_mapel']);

        return view('siswa.pjj.index', compact('student', 'todayAttendance', 'subjects'));
    }

    public function studentSubmitAssignment(Request $request, PjjAssignment $assignment)
    {
        $this->ensurePjjActive();
        $student = $this->studentForCurrentUser();
        abort_unless($assignment->teacherSubject()->where('classroom_id', $student->classroom_id)->exists(), 403);

        if ($assignment->due_at && $assignment->due_at->isPast()) {
            throw ValidationException::withMessages(['submission' => 'Batas pengumpulan tugas ini sudah lewat.']);
        }

        $data = $request->validate([
            'answer' => ['nullable', 'required_without:attachment', 'string', 'max:10000'],
            'attachment' => ['nullable', 'required_without:answer', 'file', 'mimes:pdf,txt,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $submission = PjjSubmission::query()->firstOrNew([
            'pjj_assignment_id' => $assignment->id,
            'student_id' => $student->id,
        ]);
        $oldAttachmentPath = $submission->attachment_path;
        $submission->answer = $data['answer'] ?? null;
        $submission->submitted_at = now();
        $submission->score = null;
        $submission->feedback = null;
        $submission->graded_at = null;

        if ($request->hasFile('attachment')) {
            $submission->attachment_path = $request->file('attachment')->store('pjj/submissions', 'local');
        }

        $submission->save();

        if ($request->hasFile('attachment') && $oldAttachmentPath) {
            Storage::disk('local')->delete($oldAttachmentPath);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tugas berhasil dikirim.']);
        }

        return back()->with('success', 'Tugas berhasil dikirim.');
    }

    public function studentSubmitAttendance(Request $request)
    {
        $this->ensurePjjActive();
        $student = $this->studentForCurrentUser();

        if (PjjAttendance::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', Carbon::today())
            ->exists()) {
            throw ValidationException::withMessages(['selfie' => 'Absensi selfie hari ini sudah tercatat.']);
        }

        $data = $request->validate([
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg', 'max:1536'],
        ]);

        $image = @imagecreatefromstring(file_get_contents($data['selfie']->getRealPath()));
        if (!$image) {
            throw ValidationException::withMessages(['selfie' => 'Foto selfie tidak dapat dibaca. Silakan ambil ulang.']);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, 1280 / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $compressed = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($compressed, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($image);

        ob_start();
        imagejpeg($compressed, null, 72);
        $jpeg = ob_get_clean();
        imagedestroy($compressed);

        if ($jpeg === false || $jpeg === '') {
            throw ValidationException::withMessages(['selfie' => 'Foto selfie gagal diproses. Silakan coba lagi.']);
        }

        $path = 'pjj/attendances/' . Carbon::today()->toDateString() . '/' . $student->id . '-' . bin2hex(random_bytes(8)) . '.jpg';
        $attendanceCreated = DB::transaction(function () use ($student, $path, $jpeg) {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

            if (PjjAttendance::query()
                ->where('student_id', $student->id)
                ->whereDate('attendance_date', Carbon::today())
                ->exists()) {
                return false;
            }

            if (!Storage::disk('local')->put($path, $jpeg)) {
                throw new \RuntimeException('Foto selfie gagal disimpan ke penyimpanan.');
            }

            PjjAttendance::query()->create([
                'student_id' => $student->id,
                'attendance_date' => Carbon::today()->toDateString(),
                'selfie_path' => $path,
            ]);

            return true;
        });

        if (!$attendanceCreated) {
            throw ValidationException::withMessages(['selfie' => 'Absensi selfie hari ini sudah tercatat.']);
        }

        return back()->with('success', 'Absensi selfie berhasil dicatat untuk hari ini.');
    }

    public function downloadAssignment(PjjAssignment $assignment)
    {
        abort_unless($assignment->attachment_path, 404);
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return Storage::disk('local')->response($assignment->attachment_path);
        }

        if ($user->hasRole('guru')) {
            $teacher = $this->teacherForCurrentUser();
            abort_unless($assignment->teacher_id === $teacher->id, 403);
        } elseif ($user->hasRole('siswa')) {
            $student = $this->studentForCurrentUser();
            abort_unless($assignment->teacherSubject()->where('classroom_id', $student->classroom_id)->exists(), 403);
        } else {
            abort(403);
        }

        return Storage::disk('local')->response($assignment->attachment_path);
    }

    public function downloadSubmission(PjjSubmission $submission)
    {
        abort_unless($submission->attachment_path, 404);
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return Storage::disk('local')->response($submission->attachment_path);
        }

        if ($user->hasRole('guru')) {
            $teacher = $this->teacherForCurrentUser();
            abort_unless($submission->assignment()->where('teacher_id', $teacher->id)->exists(), 403);
        } elseif ($user->hasRole('siswa')) {
            $student = $this->studentForCurrentUser();
            abort_unless($submission->student_id === $student->id, 403);
        } else {
            abort(403);
        }

        return Storage::disk('local')->response($submission->attachment_path);
    }

    public function adminAttendancePhoto(PjjAttendance $attendance)
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);
        return Storage::disk('local')->response($attendance->selfie_path);
    }

    private function adminDataTable(Request $request, string $section, $assignmentQuery, array $filters)
    {
        if ($section === 'tasks') {
            $query = (clone $assignmentQuery)
                ->with(['teacher', 'teacherSubject.subject', 'teacherSubject.classroom'])
                ->withCount('submissions');

            return $this->dataTableResponse(
                $request,
                $query,
                function ($query, string $search): void {
                    $query->where(function ($query) use ($search) {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('instructions', 'like', "%{$search}%")
                            ->orWhereHas('teacher', fn($teacher) => $teacher->where('nama_lengkap', 'like', "%{$search}%"))
                            ->orWhereHas('teacherSubject.classroom', fn($classroom) => $classroom->where('nama_kelas', 'like', "%{$search}%"))
                            ->orWhereHas('teacherSubject.subject', fn($subject) => $subject->where('nama_mapel', 'like', "%{$search}%"));
                    });
                },
                ['title' => 'title', 'teacher' => 'teacher_id', 'date' => 'created_at', 'deadline' => 'due_at'],
                fn(PjjAssignment $assignment) => [
                    'date' => $assignment->created_at?->format('d-m-Y H:i'),
                    'teacher' => e($assignment->teacher->nama_lengkap),
                    'classroom' => e($assignment->teacherSubject->classroom->nama_kelas),
                    'subject' => e($assignment->teacherSubject->subject->nama_mapel),
                    'title' => '<strong>' . e($assignment->title) . '</strong><br><span class="text-muted">' . e(\Illuminate\Support\Str::limit($assignment->instructions, 120)) . '</span>',
                    'deadline' => $assignment->due_at?->format('d-m-Y H:i') ?? '—',
                    'submissions' => (int) $assignment->submissions_count,
                    'attachment' => $assignment->attachment_path
                        ? '<a class="btn btn-xs btn-outline-primary" href="' . e(route('pjj.assignment.download', $assignment)) . '">Lihat file</a>'
                        : '—',
                ]
            );
        }

        if ($section === 'teacher-attendance') {
            $query = Teacher::query()
                ->whereIn('id', (clone $assignmentQuery)->select('teacher_id'))
                ->addSelect([
                    'tasks_count' => (clone $assignmentQuery)
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('teacher_id', 'teachers.id'),
                ]);

            return $this->dataTableResponse(
                $request,
                $query,
                fn($query, string $search) => $query->where(function ($query) use ($search) {
                    $query->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                }),
                ['teacher' => 'nama_lengkap', 'nip' => 'nip', 'tasks' => 'tasks_count'],
                fn(Teacher $teacher) => [
                    'teacher' => e($teacher->nama_lengkap),
                    'nip' => e($teacher->nip ?: '—'),
                    'tasks' => (int) $teacher->tasks_count,
                    'status' => '<span class="badge badge-success">Hadir (mengunggah tugas)</span>',
                ]
            );
        }

        $query = PjjAttendance::query()
            ->with(['student.classroom'])
            ->whereBetween('attendance_date', [$filters['start']->toDateString(), $filters['end']->toDateString()]);
        $classroomId = $request->input('classroom_id');
        if ($classroomId) {
            $query->whereHas('student', fn($student) => $student->where('classroom_id', $classroomId));
        }

        return $this->dataTableResponse(
            $request,
            $query,
            function ($query, string $search): void {
                $query->whereHas('student', fn($student) => $student->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhereHas('classroom', fn($classroom) => $classroom->where('nama_kelas', 'like', "%{$search}%")));
            },
            ['date' => 'attendance_date', 'student' => 'student_id'],
            fn(PjjAttendance $attendance) => [
                'date' => $attendance->attendance_date->format('d-m-Y'),
                'student' => e($attendance->student->nama_lengkap),
                'nis' => e($attendance->student->nis),
                'classroom' => e($attendance->student->classroom->nama_kelas),
                'photo' => '<a class="btn btn-xs btn-outline-primary" target="_blank" href="' . e(route('admin.pjj.attendance-photo', $attendance)) . '"><i class="fas fa-camera"></i> Lihat foto</a>',
            ]
        );
    }

    private function teacherAssignmentsDataTable(Request $request, Teacher $teacher)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'teacher_subject_id' => ['nullable', 'integer'],
            'submission_status' => ['nullable', 'in:submitted,pending'],
        ]);
        $query = PjjAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->with(['teacherSubject.subject', 'teacherSubject.classroom'])
            ->withCount('submissions');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('teacher_subject_id')) {
            $query->where('teacher_subject_id', $request->input('teacher_subject_id'));
        }
        if (in_array($request->input('submission_status'), ['submitted', 'pending'], true)) {
            $operator = $request->input('submission_status') === 'submitted' ? '>' : '=';
            $query->has('submissions', $operator, 0);
        }

        return $this->dataTableResponse(
            $request,
            $query,
            function ($query, string $search): void {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('instructions', 'like', "%{$search}%")
                        ->orWhereHas('teacherSubject.classroom', fn($classroom) => $classroom->where('nama_kelas', 'like', "%{$search}%"))
                        ->orWhereHas('teacherSubject.subject', fn($subject) => $subject->where('nama_mapel', 'like', "%{$search}%"));
                });
            },
            ['date' => 'created_at', 'title' => 'title', 'deadline' => 'due_at'],
            fn(PjjAssignment $assignment) => [
                'date' => $assignment->created_at?->format('d-m-Y H:i'),
                'classroom' => e($assignment->teacherSubject->classroom->nama_kelas),
                'subject' => e($assignment->teacherSubject->subject->nama_mapel),
                'title' => '<strong>' . e($assignment->title) . '</strong>',
                'deadline' => $assignment->due_at?->format('d-m-Y H:i') ?? '—',
                'submissions' => (int) $assignment->submissions_count,
                'action' => '<a class="btn btn-xs btn-primary" href="' . e(route('guru.pjj.assignments.show', $assignment)) . '"><i class="fas fa-clipboard-check"></i> Lihat & nilai</a>',
            ]
        );
    }

    private function teacherSubmissionsDataTable(Request $request, PjjAssignment $assignment)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'grading_status' => ['nullable', 'in:graded,pending'],
        ]);
        $query = $assignment->submissions()->with('student');
        if ($request->input('grading_status') === 'graded') {
            $query->whereNotNull('graded_at');
        } elseif ($request->input('grading_status') === 'pending') {
            $query->whereNull('graded_at');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->input('date_to'));
        }

        return $this->dataTableResponse(
            $request,
            $query,
            function ($query, string $search): void {
                $query->where(function ($query) use ($search) {
                    $query->where('answer', 'like', "%{$search}%")
                        ->orWhereHas('student', fn($student) => $student->where('nama_lengkap', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%"));
                });
            },
            ['date' => 'submitted_at', 'student' => 'student_id', 'score' => 'score'],
            fn(PjjSubmission $submission) => [
                'student' => e($submission->student->nama_lengkap) . '<br><small class="text-muted">' . e($submission->student->nis) . '</small>',
                'date' => $submission->submitted_at->format('d-m-Y H:i'),
                'answer' => $submission->answer
                    ? '<span class="submission-answer">' . nl2br(e(\Illuminate\Support\Str::limit($submission->answer, 400))) . '</span>'
                    : '<span class="text-muted">Jawaban berupa file</span>',
                'attachment' => $submission->attachment_path
                    ? '<a href="' . e(route('pjj.submission.download', $submission)) . '">Lihat file jawaban</a>'
                    : '—',
                'grading' => '<form method="POST" action="' . e(route('guru.pjj.submissions.grade', $submission)) . '" class="pjj-grade-form">'
                    . csrf_field()
                    . '<div class="input-group input-group-sm"><input aria-label="Nilai" type="number" step="0.01" min="0" max="100" name="score" value="' . e((string) $submission->score) . '" class="form-control" placeholder="Nilai" required>'
                    . '<input aria-label="Catatan" name="feedback" value="' . e((string) $submission->feedback) . '" class="form-control" placeholder="Catatan">'
                    . '<div class="input-group-append"><button class="btn btn-success">Simpan</button></div></div></form>',
                'status' => $submission->graded_at
                    ? '<span class="badge badge-success">Dinilai</span>'
                    : '<span class="badge badge-warning">Belum dinilai</span>',
            ]
        );
    }

    private function studentAssignmentsDataTable(Request $request, Student $student)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'subject_id' => ['nullable', 'integer'],
            'submission_status' => ['nullable', 'in:pending,submitted,graded'],
        ]);
        $query = PjjAssignment::query()
            ->whereHas('teacherSubject', fn($teacherSubject) => $teacherSubject->where('classroom_id', $student->classroom_id))
            ->with(['teacher', 'teacherSubject.subject', 'submissions' => fn($submission) => $submission->where('student_id', $student->id)]);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('subject_id')) {
            $query->whereHas('teacherSubject', fn($teacherSubject) => $teacherSubject->where('subject_id', $request->input('subject_id')));
        }
        $status = $request->input('submission_status');
        if ($status === 'submitted') {
            $query->whereHas('submissions', fn($submission) => $submission->where('student_id', $student->id));
        } elseif ($status === 'pending') {
            $query->whereDoesntHave('submissions', fn($submission) => $submission->where('student_id', $student->id));
        } elseif ($status === 'graded') {
            $query->whereHas('submissions', fn($submission) => $submission->where('student_id', $student->id)->whereNotNull('graded_at'));
        }

        return $this->dataTableResponse(
            $request,
            $query,
            function ($query, string $search): void {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('instructions', 'like', "%{$search}%")
                        ->orWhereHas('teacher', fn($teacher) => $teacher->where('nama_lengkap', 'like', "%{$search}%"))
                        ->orWhereHas('teacherSubject.subject', fn($subject) => $subject->where('nama_mapel', 'like', "%{$search}%"));
                });
            },
            ['date' => 'created_at', 'title' => 'title', 'deadline' => 'due_at'],
            function (PjjAssignment $assignment): array {
                $submission = $assignment->submissions->first();
                $status = $submission?->graded_at
                    ? '<span class="badge badge-success">Dinilai: ' . e((string) $submission->score) . '</span>'
                    : ($submission
                        ? '<span class="badge badge-info">Sudah dikumpulkan</span>'
                        : '<span class="badge badge-warning">Belum dikumpulkan</span>');
                $canSubmit = !$assignment->due_at || !$assignment->due_at->isPast();
                $action = $canSubmit
                    ? '<button type="button" class="btn btn-xs btn-primary pjj-open-submission"'
                        . ' data-url="' . e(route('siswa.pjj.assignments.submit', $assignment)) . '"'
                        . ' data-title="' . e($assignment->title) . '"'
                        . ' data-answer="' . e((string) $submission?->answer) . '"'
                        . ' data-attachment="' . e($assignment->attachment_path ? route('pjj.assignment.download', $assignment) : '') . '"'
                        . '><i class="fas fa-upload"></i> ' . ($submission ? 'Ubah jawaban' : 'Kumpulkan') . '</button>'
                    : '<span class="text-muted">Deadline lewat</span>';

                $material = $assignment->attachment_path
                    ? '<a class="btn btn-xs btn-outline-secondary" href="' . e(route('pjj.assignment.download', $assignment)) . '">Materi</a>'
                    : '—';

                return [
                    'date' => $assignment->created_at->format('d-m-Y'),
                    'subject' => e($assignment->teacherSubject->subject->nama_mapel),
                    'teacher' => e($assignment->teacher->nama_lengkap),
                    'title' => '<strong>' . e($assignment->title) . '</strong><br><small>' . e(\Illuminate\Support\Str::limit($assignment->instructions, 140)) . '</small>',
                    'deadline' => $assignment->due_at?->format('d-m-Y H:i') ?? '—',
                    'status' => $status,
                    'material' => $material,
                    'action' => $action,
                ];
            }
        );
    }

    private function applyAssignmentFilters($query, Request $request): void
    {
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }
        if ($request->filled('classroom_id')) {
            $query->whereHas('teacherSubject', fn($teacherSubject) => $teacherSubject->where('classroom_id', $request->input('classroom_id')));
        }
        if ($request->filled('subject_id')) {
            $query->whereHas('teacherSubject', fn($teacherSubject) => $teacherSubject->where('subject_id', $request->input('subject_id')));
        }
    }

    private function dataTableResponse(
        Request $request,
        $query,
        callable $search,
        array $orderMap,
        callable $mapRow
    ) {
        $total = (clone $query)->count();
        $searchValue = trim((string) $request->input('search.value', ''));
        if ($searchValue !== '') {
            $search($query, $searchValue);
        }
        $filtered = (clone $query)->count();

        $orderIndex = $request->input('order.0.column');
        $columnKey = is_numeric($orderIndex)
            ? $request->input("columns.{$orderIndex}.data")
            : null;
        if ($columnKey && isset($orderMap[$columnKey])) {
            $direction = strtolower((string) $request->input('order.0.dir')) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($orderMap[$columnKey], $direction);
        } else {
            $query->orderByDesc($query->getModel()->getQualifiedKeyName());
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length < 1 ? 100 : min($length, 100);
        $rows = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map($mapRow)->values(),
        ]);
    }

    private function teacherForCurrentUser(): Teacher
    {
        $user = Auth::user();
        $teacher = Teacher::query()
            ->where(function ($query) use ($user) {
                $query->where('nip', $user->email)
                    ->orWhere('nuptk', $user->email)
                    ->orWhere('nama_lengkap', $user->name);
            })
            ->first();

        abort_unless($teacher, 403, 'Akun guru tidak terhubung dengan data guru.');
        return $teacher;
    }

    private function studentForCurrentUser(): Student
    {
        $user = Auth::user();
        $student = Student::query()
            ->where(function ($query) use ($user) {
                $query->where('nisn', $user->email)
                    ->orWhere('nis', $user->email)
                    ->orWhere('nama_lengkap', $user->name);
            })
            ->first();

        abort_unless($student, 403, 'Akun siswa tidak terhubung dengan data siswa.');
        return $student;
    }

    private function ensurePjjActive(): void
    {
        abort_unless(SchoolCalendar::isPjj(Carbon::today()), 403, 'Fitur ini hanya aktif pada tanggal yang ditetapkan sebagai mode PJJ.');
    }

    private function periodFilter(Request $request): array
    {
        $data = $request->validate([
            'period' => ['nullable', 'in:day,week,month'],
            'date' => ['nullable', 'date'],
        ]);
        $period = $data['period'] ?? 'day';
        $date = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());

        [$start, $end] = match ($period) {
            'week' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default => [$date->copy(), $date->copy()],
        };

        return compact('period', 'date', 'start', 'end');
    }
}
