<?php

namespace App\Http\Controllers;

use App\Models\PjjAssignment;
use App\Models\PjjAttendance;
use App\Models\PjjSubmission;
use App\Models\PjjSubmissionFile;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\Subject;
use App\Models\Holiday;
use App\Services\PjjAttendanceSyncService;
use App\Support\PklMode;
use App\Support\SchoolCalendar;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PjjController extends Controller
{
    public function __construct(private readonly PjjAttendanceSyncService $attendanceSync)
    {
    }

    public function adminIndex(Request $request, string $section = 'dashboard')
    {
        abort_unless(in_array($section, ['dashboard', 'tasks', 'teacher-attendance', 'student-attendance', 'effectiveness'], true), 404);

        $pjjDates = $this->configuredPjjDates();
        $filters = $this->periodFilter($request, $pjjDates);
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
        $classroomQuery = Classroom::query()->orderBy('nama_kelas');
        if (PklMode::isActive()) {
            $classroomQuery->where('tingkat', '!=', 'XII');
        }
        $classrooms = $classroomQuery->get(['id', 'nama_kelas', 'major_id']);
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
            'pjjDates',
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

        // Pilihan kelas & mapel dibatasi hanya yang terjadwal pada hari ini (jadwal pembelajaran biasa).
        $teacherSubjectsQuery = TeacherSubject::query()
            ->where('teacher_id', $teacher->id)
            ->whereHas('schedules', fn($schedule) => $schedule->where('hari', $this->dayNameToday()))
            ->with(['subject', 'classroom'])
            ->orderBy('classroom_id');
        if (PklMode::isActive()) {
            $teacherSubjectsQuery->whereHas('classroom', fn($classroom) => $classroom->where('tingkat', '!=', 'XII'));
        }
        $teacherSubjects = $teacherSubjectsQuery->get();
        $isPjjActiveToday = SchoolCalendar::isPjj(Carbon::today());
        $pjjDates = $this->configuredPjjDates();

        return view('guru.pjj.index', compact('teacher', 'teacherSubjects', 'isPjjActiveToday', 'pjjDates'));
    }

    public function teacherStoreAssignment(Request $request)
    {
        $this->ensurePjjActive();
        $teacher = $this->teacherForCurrentUser();
        $data = $request->validate([
            'teacher_subject_id' => ['required', 'integer', 'exists:teacher_subjects,id'],
            'extra_teacher_subject_ids' => ['nullable', 'array'],
            'extra_teacher_subject_ids.*' => ['integer', 'distinct', 'exists:teacher_subjects,id'],
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'due_at' => ['nullable', 'date', 'after_or_equal:now'],
        ]);

        $primaryTeacherSubject = TeacherSubject::query()
            ->whereKey($data['teacher_subject_id'])
            ->where('teacher_id', $teacher->id)
            ->with('classroom')
            ->firstOrFail();

        if (!$primaryTeacherSubject->schedules()->where('hari', $this->dayNameToday())->exists()) {
            throw ValidationException::withMessages([
                'teacher_subject_id' => 'Kelas dan mata pelajaran ini tidak terjadwal hari ini. Tugas PJJ hanya dapat dipublikasikan sesuai jadwal pembelajaran biasa.',
            ]);
        }

        if (PklMode::excludesClassroomLevel($primaryTeacherSubject->classroom->tingkat ?? null)) {
            throw ValidationException::withMessages([
                'teacher_subject_id' => 'Mode PKL aktif. Tugas PJJ untuk kelas XII dinonaktifkan sementara.',
            ]);
        }

        $teacherSubjectIds = collect([$primaryTeacherSubject->id])
            ->merge($data['extra_teacher_subject_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $teacherSubjects = TeacherSubject::query()
            ->whereIn('id', $teacherSubjectIds)
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $primaryTeacherSubject->subject_id)
            ->whereHas('classroom', fn($classroom) => $classroom->where('tingkat', $primaryTeacherSubject->classroom->tingkat))
            ->whereHas('schedules', fn($schedule) => $schedule->where('hari', $this->dayNameToday()))
            ->with('classroom')
            ->get();

        if ($teacherSubjects->count() !== $teacherSubjectIds->count()) {
            throw ValidationException::withMessages([
                'extra_teacher_subject_ids' => 'Kelas tambahan harus kelas yang Anda ampu dengan mata pelajaran yang sama, jenjang yang sama, dan terjadwal pada hari ini.',
            ]);
        }

        $attachmentPath = $request->file('attachment')?->store('pjj/assignments', 'local');

        foreach ($teacherSubjects as $teacherSubject) {
            $assignment = PjjAssignment::query()->create([
                'teacher_id' => $teacher->id,
                'teacher_subject_id' => $teacherSubject->id,
                'title' => $data['title'],
                'instructions' => $data['instructions'],
                'attachment_path' => $attachmentPath,
                'due_at' => $data['due_at'] ?? null,
            ]);

            // Sinkronkan ke absensi reguler: upload tugas PJJ = guru hadir pada sesi KBM hari ini.
            $this->attendanceSync->syncTeacherAssignment($assignment);
        }

        $message = $teacherSubjects->count() > 1
            ? 'Tugas PJJ berhasil dipublikasikan ke ' . $teacherSubjects->count() . ' kelas: '
                . $teacherSubjects->map(fn(TeacherSubject $teacherSubject) => $teacherSubject->classroom->nama_kelas)->join(', ') . '.'
            : 'Tugas PJJ berhasil dipublikasikan.';

        return redirect()->route('guru.pjj.index')->with('success', $message);
    }

    public function teacherUpdateAssignment(Request $request, PjjAssignment $assignment)
    {
        $teacher = $this->teacherForCurrentUser();
        abort_unless($assignment->teacher_id === $teacher->id, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'due_at' => ['nullable', 'date'],
            'remove_attachment' => ['nullable', 'boolean'],
        ]);

        $oldAttachmentPath = $assignment->attachment_path;
        $attachmentPath = $oldAttachmentPath;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('pjj/assignments', 'local');
        } elseif ($request->boolean('remove_attachment')) {
            $attachmentPath = null;
        }

        $assignment->update([
            'title' => $data['title'],
            'instructions' => $data['instructions'],
            'attachment_path' => $attachmentPath,
            'due_at' => $data['due_at'] ?? null,
        ]);

        if ($oldAttachmentPath && $oldAttachmentPath !== $attachmentPath) {
            $this->deleteAssignmentAttachmentIfUnused($oldAttachmentPath);
        }

        return redirect()->route('guru.pjj.index')->with('success', 'Tugas PJJ berhasil diperbarui.');
    }

    public function teacherDestroyAssignment(PjjAssignment $assignment)
    {
        $teacher = $this->teacherForCurrentUser();
        abort_unless($assignment->teacher_id === $teacher->id, 403);

        $attachmentPaths = $assignment->submissions()
            ->whereNotNull('attachment_path')
            ->pluck('attachment_path')
            ->merge(
                PjjSubmissionFile::query()
                    ->whereIn('pjj_submission_id', $assignment->submissions()->select('id'))
                    ->pluck('path')
            )
            ->all();
        $assignmentAttachmentPath = $assignment->attachment_path;

        $assignment->delete();

        foreach ($attachmentPaths as $path) {
            Storage::disk('local')->delete($path);
        }

        if ($assignmentAttachmentPath) {
            $this->deleteAssignmentAttachmentIfUnused($assignmentAttachmentPath);
        }

        return redirect()->route('guru.pjj.index')->with('success', 'Tugas PJJ berhasil dihapus.');
    }

    public function teacherShowAssignment(Request $request, PjjAssignment $assignment)
    {
        $teacher = $this->teacherForCurrentUser();
        abort_unless($assignment->teacher_id === $teacher->id, 403);

        $assignment->load(['teacherSubject.subject', 'teacherSubject.classroom']);
        if ($request->ajax()) {
            return $this->teacherSubmissionsDataTable($request, $assignment);
        }

        $pjjDates = $this->configuredPjjDates();

        return view('guru.pjj.assignment', compact('assignment', 'pjjDates'));
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

    public function teacherBulkGradeSubmissions(Request $request, PjjAssignment $assignment)
    {
        $teacher = $this->teacherForCurrentUser();
        abort_unless($assignment->teacher_id === $teacher->id, 403);

        $data = $request->validate([
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.submission_id' => ['required', 'integer'],
            'grades.*.score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grades.*.feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $grades = collect($data['grades'])->keyBy('submission_id');
        $submissions = $assignment->submissions()->whereIn('id', $grades->keys())->get();

        if ($submissions->count() !== $grades->count()) {
            throw ValidationException::withMessages(['grades' => 'Beberapa jawaban siswa tidak ditemukan pada tugas ini.']);
        }

        DB::transaction(function () use ($submissions, $grades): void {
            foreach ($submissions as $submission) {
                $grade = $grades[$submission->id];
                $submission->update([
                    'score' => $grade['score'],
                    'feedback' => ($grade['feedback'] ?? '') !== '' ? $grade['feedback'] : null,
                    'graded_at' => now(),
                ]);
            }
        });

        $message = 'Nilai ' . $submissions->count() . ' jawaban siswa berhasil disimpan.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
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

        $pjjDates = $this->configuredPjjDates();

        // Rekap: gabungkan absensi tercatat dengan tanggal PJJ yang terlewat (Alpa).
        $recordedAttendances = PjjAttendance::query()
            ->where('student_id', $student->id)
            ->orderByDesc('attendance_date')
            ->limit(30)
            ->get()
            ->keyBy(fn(PjjAttendance $attendance) => $attendance->attendance_date->toDateString());
        $attendanceHistory = collect($pjjDates)
            ->filter(fn(string $pjjDate) => $pjjDate <= Carbon::today()->toDateString())
            ->sortDesc()
            ->take(30)
            ->map(fn(string $pjjDate) => [
                'date' => Carbon::parse($pjjDate),
                'attendance' => $recordedAttendances->get($pjjDate),
            ])
            ->values();

        $subjects = Subject::query()
            ->whereHas('teacherSubjects', fn($teacherSubject) => $teacherSubject->where('classroom_id', $student->classroom_id))
            ->orderBy('nama_mapel')
            ->get(['id', 'nama_mapel']);

        return view('siswa.pjj.index', compact('student', 'todayAttendance', 'attendanceHistory', 'subjects', 'pjjDates'));
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
            'answer' => ['nullable', 'required_without:attachments', 'string', 'max:10000'],
            'attachments' => ['nullable', 'required_without:answer', 'array', 'min:1', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $submission = PjjSubmission::query()->firstOrNew([
            'pjj_assignment_id' => $assignment->id,
            'student_id' => $student->id,
        ]);
        $submission->answer = $data['answer'] ?? null;
        $submission->submitted_at = now();
        $submission->score = null;
        $submission->feedback = null;
        $submission->graded_at = null;

        // Simpan file ke disk di luar transaksi agar lock DB sesingkat mungkin.
        $storedFiles = [];
        foreach ($request->file('attachments', []) as $file) {
            $storedFiles[] = [
                'path' => $file->store('pjj/submissions', 'local'),
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'size' => (int) $file->getSize(),
            ];
        }

        $oldPaths = [];
        DB::transaction(function () use ($submission, $storedFiles, &$oldPaths) {
            if ($storedFiles) {
                // File baru menggantikan seluruh file lama (termasuk kolom lama attachment_path).
                if ($submission->exists) {
                    $oldPaths = $submission->files()->pluck('path')->all();
                }
                if ($submission->attachment_path) {
                    $oldPaths[] = $submission->attachment_path;
                    $submission->attachment_path = null;
                }
            }

            $submission->save();

            if ($storedFiles) {
                $submission->files()->delete();
                $now = now();
                // Bulk insert: satu query untuk seluruh metadata file.
                PjjSubmissionFile::query()->insert(array_map(fn(array $file) => $file + [
                    'pjj_submission_id' => $submission->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $storedFiles));
            }
        });

        if ($oldPaths) {
            Storage::disk('local')->delete($oldPaths);
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

        $todayAttendance = PjjAttendance::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();

        if ($todayAttendance && $todayAttendance->check_out_at) {
            throw ValidationException::withMessages(['selfie' => 'Absen datang dan pulang hari ini sudah lengkap tercatat.']);
        }

        $now = Carbon::now();
        $isCheckOut = $todayAttendance !== null;

        if ($isCheckOut && $now->lt(Carbon::today()->setTime(15, 0))) {
            throw ValidationException::withMessages(['selfie' => 'Absen pulang baru dapat dilakukan mulai pukul 15.00. Saat ini pukul ' . $now->format('H:i') . '.']);
        }

        // Absen datang setelah 06.30 tetap diterima namun berstatus Terlambat.
        $isLate = !$isCheckOut && $now->gt(Carbon::today()->setTime(6, 30));

        $data = $request->validate([
            'selfie_data' => ['required', 'string', 'max:2200000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if (!preg_match('/\Adata:image\/jpeg;base64,([A-Za-z0-9+\/=]+)\z/', $data['selfie_data'], $matches)) {
            throw ValidationException::withMessages(['selfie_data' => 'Selfie harus diambil langsung menggunakan kamera.']);
        }

        $imageBytes = base64_decode($matches[1], true);
        if ($imageBytes === false || strlen($imageBytes) > 1536 * 1024) {
            throw ValidationException::withMessages(['selfie_data' => 'Ukuran selfie melebihi batas. Silakan ambil ulang.']);
        }

        $imageInfo = getimagesizefromstring($imageBytes);
        if ($imageInfo === false || $imageInfo[2] !== IMAGETYPE_JPEG) {
            throw ValidationException::withMessages(['selfie_data' => 'Selfie harus berupa foto JPEG yang diambil langsung dari kamera.']);
        }

        $image = @imagecreatefromstring($imageBytes);
        if (!$image) {
            throw ValidationException::withMessages(['selfie_data' => 'Foto selfie tidak dapat dibaca. Silakan ambil ulang.']);
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
            throw ValidationException::withMessages(['selfie_data' => 'Foto selfie gagal diproses. Silakan coba lagi.']);
        }

        $path = 'pjj/attendances/' . Carbon::today()->toDateString() . '/' . $student->id . '-' . bin2hex(random_bytes(8)) . '.jpg';
        $createdAttendance = DB::transaction(function () use ($student, $path, $jpeg, $data, $isCheckOut, $isLate) {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

            $attendance = PjjAttendance::query()
                ->where('student_id', $student->id)
                ->whereDate('attendance_date', Carbon::today())
                ->first();

            if ($isCheckOut) {
                if (!$attendance || $attendance->check_out_at) {
                    return null;
                }

                if (!Storage::disk('local')->put($path, $jpeg)) {
                    throw new \RuntimeException('Foto selfie gagal disimpan ke penyimpanan.');
                }

                $attendance->update([
                    'check_out_at' => now(),
                    'check_out_selfie_path' => $path,
                    'check_out_latitude' => $data['latitude'],
                    'check_out_longitude' => $data['longitude'],
                ]);

                return $attendance;
            }

            if ($attendance) {
                return null;
            }

            if (!Storage::disk('local')->put($path, $jpeg)) {
                throw new \RuntimeException('Foto selfie gagal disimpan ke penyimpanan.');
            }

            return PjjAttendance::query()->create([
                'student_id' => $student->id,
                'attendance_date' => Carbon::today()->toDateString(),
                'selfie_path' => $path,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'check_in_at' => now(),
                'status' => $isLate ? 'Terlambat' : 'Hadir',
            ]);
        });

        if (!$createdAttendance) {
            throw ValidationException::withMessages(['selfie' => 'Absensi selfie sudah tercatat. Muat ulang halaman untuk melihat status terbaru.']);
        }

        if (!$isCheckOut) {
            // Sinkronkan ke absensi reguler: selfie PJJ = Hadir/Terlambat pada semua jadwal kelas hari ini.
            $this->attendanceSync->syncStudentAttendance($createdAttendance);
        }

        if ($isCheckOut) {
            return back()->with('success', 'Absen pulang berhasil dicatat pukul ' . $now->format('H:i') . '.');
        }

        return back()->with('success', $isLate
            ? 'Absen datang dicatat pukul ' . $now->format('H:i') . ' dengan status Terlambat (melewati pukul 06.30).'
            : 'Absen datang berhasil dicatat pukul ' . $now->format('H:i') . '.');
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
        $this->authorizeSubmissionAccess($submission);

        return Storage::disk('local')->response($submission->attachment_path);
    }

    public function downloadSubmissionFile(PjjSubmission $submission, PjjSubmissionFile $file)
    {
        abort_unless($file->pjj_submission_id === $submission->id, 404);
        $this->authorizeSubmissionAccess($submission);

        return Storage::disk('local')->response($file->path, $file->original_name);
    }

    private function authorizeSubmissionAccess(PjjSubmission $submission): void
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return;
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
    }

    public function adminAttendancePhoto(Request $request, PjjAttendance $attendance)
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);

        if ($request->query('type') === 'out') {
            abort_unless($attendance->check_out_selfie_path, 404);
            return Storage::disk('local')->response($attendance->check_out_selfie_path);
        }

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
                'check_in' => (($attendance->check_in_at ?? $attendance->created_at)?->format('H:i') ?? '—')
                    . ($attendance->status === 'Terlambat' ? ' <span class="badge badge-warning">Terlambat</span>' : ''),
                'check_out' => $attendance->check_out_at
                    ? $attendance->check_out_at->format('H:i')
                    : '<span class="badge badge-warning">Belum absen pulang</span>',
                'photo' => '<a class="btn btn-xs btn-outline-primary" target="_blank" href="' . e(route('admin.pjj.attendance-photo', $attendance)) . '"><i class="fas fa-camera"></i> Datang</a>'
                    . ($attendance->check_out_selfie_path
                        ? ' <a class="btn btn-xs btn-outline-info" target="_blank" href="' . e(route('admin.pjj.attendance-photo', $attendance) . '?type=out') . '"><i class="fas fa-camera"></i> Pulang</a>'
                        : ''),
                'location' => $attendance->latitude !== null && $attendance->longitude !== null
                    ? e($attendance->latitude . ', ' . $attendance->longitude)
                        . ' <a class="btn btn-xs btn-outline-success" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps?q='
                        . e($attendance->latitude . ',' . $attendance->longitude)
                        . '"><i class="fas fa-map-marker-alt"></i> Peta</a>'
                    : '<span class="text-muted">Tidak tersedia</span>',
            ]
        );
    }

    private function teacherAssignmentsDataTable(Request $request, Teacher $teacher)
    {
        $pjjDates = $this->configuredPjjDates();
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d', Rule::in($pjjDates)],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::in($pjjDates)],
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
                'action' => '<a class="btn btn-xs btn-primary" href="' . e(route('guru.pjj.assignments.show', $assignment)) . '"><i class="fas fa-clipboard-check"></i> Lihat & nilai</a> '
                    . '<button type="button" class="btn btn-xs btn-warning pjj-edit-assignment"'
                    . ' data-url="' . e(route('guru.pjj.assignments.update', $assignment)) . '"'
                    . ' data-title="' . e($assignment->title) . '"'
                    . ' data-instructions="' . e($assignment->instructions) . '"'
                    . ' data-due-at="' . e($assignment->due_at?->format('Y-m-d\TH:i') ?? '') . '"'
                    . ' data-attachment="' . e($assignment->attachment_path ? route('pjj.assignment.download', $assignment) : '') . '"'
                    . '><i class="fas fa-edit"></i> Edit</button> '
                    . '<button type="button" class="btn btn-xs btn-danger pjj-delete-assignment"'
                    . ' data-url="' . e(route('guru.pjj.assignments.destroy', $assignment)) . '"'
                    . ' data-title="' . e($assignment->title) . '"'
                    . ' data-submissions="' . (int) $assignment->submissions_count . '"'
                    . '><i class="fas fa-trash"></i> Hapus</button>',
            ]
        );
    }

    private function teacherSubmissionsDataTable(Request $request, PjjAssignment $assignment)
    {
        $pjjDates = $this->configuredPjjDates();
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d', Rule::in($pjjDates)],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::in($pjjDates)],
            'grading_status' => ['nullable', 'in:graded,pending'],
        ]);
        $query = $assignment->submissions()->with(['student', 'files']);
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
                'attachment' => $this->submissionFilesHtml($submission),
                'grading' => '<form method="POST" action="' . e(route('guru.pjj.submissions.grade', $submission)) . '" class="pjj-grade-form" data-submission-id="' . (int) $submission->id . '">'
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
        $pjjDates = $this->configuredPjjDates();
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d', Rule::in($pjjDates)],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::in($pjjDates)],
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

                $instructions = (string) $assignment->instructions;
                $viewTextButton = mb_strlen($instructions) > 140
                    ? '<br><button type="button" class="btn btn-xs btn-outline-info mt-1 pjj-view-text"'
                        . ' data-title="' . e($assignment->title) . '"'
                        . ' data-text="' . e($instructions) . '"'
                        . '><i class="fas fa-eye"></i> Lihat</button>'
                    : '';

                return [
                    'date' => $assignment->created_at->format('d-m-Y'),
                    'subject' => e($assignment->teacherSubject->subject->nama_mapel),
                    'teacher' => e($assignment->teacher->nama_lengkap),
                    'title' => '<strong>' . e($assignment->title) . '</strong><br><small>' . e(\Illuminate\Support\Str::limit($instructions, 140)) . '</small>' . $viewTextButton,
                    'deadline' => $assignment->due_at?->format('d-m-Y H:i') ?? '—',
                    'status' => $status,
                    'material' => $material,
                    'action' => $action,
                ];
            }
        );
    }

    private function submissionFilesHtml(PjjSubmission $submission): string
    {
        $links = $submission->files
            ->map(fn(PjjSubmissionFile $file, int $index) => '<a class="d-block" href="'
                . e(route('pjj.submission.files.download', [$submission, $file]))
                . '">File ' . ($index + 1) . ': ' . e(\Illuminate\Support\Str::limit($file->original_name, 40)) . '</a>')
            ->all();

        // Dukungan data lama: kolom attachment_path tunggal.
        if (!$links && $submission->attachment_path) {
            $links[] = '<a href="' . e(route('pjj.submission.download', $submission)) . '">Lihat file jawaban</a>';
        }

        return $links ? implode('', $links) : '—';
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

        // Prioritaskan identitas unik (NIP/NUPTK); pencocokan nama hanya fallback
        // agar guru dengan nama kembar tidak ter-resolve ke data guru yang salah.
        $teacher = Teacher::query()
            ->where(function ($query) use ($user) {
                $query->where('nip', $user->email)
                    ->orWhere('nuptk', $user->email);
            })
            ->first()
            ?? Teacher::query()->where('nama_lengkap', $user->name)->first();

        abort_unless($teacher, 403, 'Akun guru tidak terhubung dengan data guru.');
        return $teacher;
    }

    private function studentForCurrentUser(): Student
    {
        $user = Auth::user();

        // Prioritaskan identitas unik (NISN/NIS); pencocokan nama hanya fallback
        // agar siswa dengan nama kembar tidak ter-resolve ke kelas yang salah.
        $student = Student::query()
            ->where(function ($query) use ($user) {
                $query->where('nisn', $user->email)
                    ->orWhere('nis', $user->email);
            })
            ->first()
            ?? Student::query()->where('nama_lengkap', $user->name)->first();

        abort_unless($student, 403, 'Akun siswa tidak terhubung dengan data siswa.');
        return $student;
    }

    private function deleteAssignmentAttachmentIfUnused(string $path): void
    {
        $stillUsed = PjjAssignment::query()
            ->where('attachment_path', $path)
            ->exists();

        if (!$stillUsed) {
            Storage::disk('local')->delete($path);
        }
    }

    private function ensurePjjActive(): void
    {
        abort_unless(SchoolCalendar::isPjj(Carbon::today()), 403, 'Fitur ini hanya aktif pada tanggal yang ditetapkan sebagai mode PJJ.');
    }

    private function dayNameToday(): string
    {
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return $dayNames[Carbon::today()->dayOfWeekIso];
    }

    private function periodFilter(Request $request, array $pjjDates): array
    {
        $data = $request->validate([
            'period' => ['nullable', 'in:day,week,month'],
            'date' => ['nullable', 'date_format:Y-m-d', Rule::in($pjjDates)],
        ]);
        $period = $data['period'] ?? 'day';
        $today = Carbon::today()->toDateString();
        $defaultDate = in_array($today, $pjjDates, true)
            ? $today
            : (collect($pjjDates)->filter(fn($pjjDate) => $pjjDate <= $today)->last()
                ?? ($pjjDates[0] ?? $today));
        $date = Carbon::parse($data['date'] ?? $defaultDate);

        [$start, $end] = match ($period) {
            'week' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default => [$date->copy(), $date->copy()],
        };

        return compact('period', 'date', 'start', 'end');
    }

    private function configuredPjjDates(): array
    {
        return Holiday::query()
            ->where('is_pjj', true)
            ->get(['tanggal', 'tanggal_akhir'])
            ->flatMap(function (Holiday $holiday): array {
                $start = Carbon::parse($holiday->tanggal)->startOfDay();
                $end = Carbon::parse($holiday->tanggal_akhir ?? $holiday->tanggal)->startOfDay();

                return collect(CarbonPeriod::create($start, $end))
                    ->map(fn(Carbon $date) => $date->toDateString())
                    ->all();
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
