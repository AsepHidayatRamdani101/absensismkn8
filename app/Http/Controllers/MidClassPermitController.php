<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDetail;
use App\Models\MidClassPermit;
use App\Models\StaffTu;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Support\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MidClassPermitController extends Controller
{
    public function tuIndex()
    {
        abort_unless(auth()->user()->hasRole('tu'), 403);
        abort_unless(Schema::hasTable('mid_class_permits'), 422, 'Menu izin pulang belum aktif.');
        $students = Student::query()->with('classroom.major')->orderBy('nama_lengkap')->get();
        return view('tu.mid_class_permits.index', compact('students'));
    }

    public function tuData(Request $request)
    {
        abort_unless(auth()->user()->hasRole('tu'), 403);
        $query = MidClassPermit::query()->with(['student.classroom.major'])->latest();
        $total = (clone $query)->count();
        $search = trim((string) $request->input('search.value'));
        if ($search !== '') {
            $query->whereHas('student', fn ($q) => $q->where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('nis', 'like', "%{$search}%"));
        }
        if ($request->filled('student_id')) $query->where('student_id', $request->integer('student_id'));
        if ($request->filled('classroom_id')) $query->whereHas('student', fn ($q) => $q->where('classroom_id', $request->integer('classroom_id')));
        if ($request->filled('major_id')) $query->whereHas('student.classroom', fn ($q) => $q->where('major_id', $request->integer('major_id')));
        $filtered = (clone $query)->count();
        $start = max(0, $request->integer('start'));
        $length = $request->integer('length', 10);
        $items = $length === -1 ? $query->get() : $query->skip($start)->take(max(1, $length))->get();
        return response()->json(['draw' => $request->integer('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered,
            'data' => $items->map(fn ($permit) => ['id' => $permit->id, 'student_id' => $permit->student_id,
                'student' => $permit->student?->nama_lengkap, 'nis' => $permit->student?->nis, 'classroom_id' => $permit->student?->classroom_id,
                'classroom' => $permit->student?->classroom?->nama_kelas, 'major' => $permit->student?->classroom?->major?->nama_jurusan,
                'parent' => $permit->student?->nama_orang_tua_wali, 'phone' => $permit->student?->no_hp_orang_tua,
                'tanggal' => $permit->tanggal?->format('Y-m-d'), 'tipe_izin' => $permit->tipe_izin,
                'jam_keluar' => substr((string) $permit->jam_keluar, 0, 5), 'jam_kembali' => $permit->jam_kembali ? substr($permit->jam_kembali, 0, 5) : '',
                'alasan' => $permit->alasan, 'catatan' => $permit->catatan, 'print_url' => route('tu.mid-class-permits.print', $permit)])->values()]);
    }

    public function tuStore(Request $request)
    {
        abort_unless(auth()->user()->hasRole('tu'), 403);
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'tanggal' => 'required|date|before_or_equal:today',
            'tipe_izin' => 'required|in:sementara,penuh',
            'jam_keluar' => 'required|date_format:H:i',
            'jam_kembali' => 'nullable|date_format:H:i|after:jam_keluar',
            'alasan' => 'required|string|max:2000',
            'catatan' => 'nullable|string|max:500',
            'nama_orang_tua_wali' => 'required|string|max:255',
            'no_hp_orang_tua' => 'required|string|max:30',
        ]);
        $student = Student::query()->findOrFail((int) $validated['student_id']);
        $student->update([
            'nama_orang_tua_wali' => $validated['nama_orang_tua_wali'],
            'no_hp_orang_tua' => $validated['no_hp_orang_tua'],
        ]);
        $permit = MidClassPermit::create([
            'student_id' => $validated['student_id'], 'tanggal' => $validated['tanggal'],
            'tipe_izin' => $validated['tipe_izin'], 'jam_keluar' => $validated['jam_keluar'] . ':00',
            'jam_kembali' => $validated['tipe_izin'] === 'sementara' && !empty($validated['jam_kembali']) ? $validated['jam_kembali'] . ':00' : null,
            'alasan' => $validated['alasan'], 'catatan' => $validated['catatan'] ?? null,
            'submitted_by_type' => 'tu', 'submitted_by_teacher_id' => null, 'submitted_by_student_id' => null,
        ]);
        $this->applyMidClassPermitToAttendance($permit);
        $this->notifyPermitRecipients($student->fresh(), $permit);
        return back()->with('success', 'Izin pulang berhasil dibuat.');
    }

    private function notifyPermitRecipients(Student $student, MidClassPermit $permit): void
    {
        if (!(bool) config('services.fonnte.enabled') || trim((string) config('services.fonnte.token')) === '') {
            return;
        }

        $teacher = Teacher::query()->where('wali_classroom_id', $student->classroom_id)->first();
        $targets = collect([$student->no_hp_orang_tua, $teacher?->no_hp])
            ->map(fn ($phone) => $this->normalizePhone($phone))
            ->filter()->unique();
        $studentName = $student->nama_lengkap;
        $time = substr((string) $permit->jam_keluar, 0, 5);
        $message = "Informasi izin pulang siswa\nNama: {$studentName}\nTanggal: {$permit->tanggal->format('d/m/Y')}\nWaktu keluar: {$time}\nJenis: "
            . ($permit->tipe_izin === 'penuh' ? 'Pulang penuh' : 'Sementara')
            . "\nAlasan: {$permit->alasan}\n\nMohon perhatian dan tindak lanjutnya.";
        foreach ($targets as $target) {
            Http::timeout(20)->withHeaders(['Authorization' => (string) config('services.fonnte.token')])
                ->asForm()->post((string) config('services.fonnte.api_url'), [
                    'target' => $target, 'message' => $message,
                    'countryCode' => (string) config('services.fonnte.default_country_code', '62'),
                ]);
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '' || strlen($digits) < 8) return null;
        return str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;
    }

    public function tuPrint(MidClassPermit $permit)
    {
        abort_unless(auth()->user()->hasRole('tu'), 403);
        $permit->load('student.classroom');
        $user = auth()->user();
        $tuStaff = StaffTu::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();
        // Satu halaman A4 portrait dibagi 6 slot; hanya slot pertama yang diisi.
        return Pdf::loadView('tu.mid_class_permits.print', compact('permit', 'tuStaff'))->setPaper('a4', 'portrait')
            ->stream('izin-pulang-' . $permit->student->nis . '.pdf');
    }

    public function publicScan(string $token)
    {
        $permit = MidClassPermit::query()->with('student.classroom')->where('scan_token', $token)->firstOrFail();
        return view('public.mid_class_permit_scan', compact('permit'));
    }

    public function tuUpdate(Request $request, MidClassPermit $permit)
    {
        abort_unless(auth()->user()->hasRole('tu'), 403);
        $validated = $request->validate(['student_id' => 'required|exists:students,id', 'tanggal' => 'required|date|before_or_equal:today', 'tipe_izin' => 'required|in:sementara,penuh', 'jam_keluar' => 'required|date_format:H:i', 'jam_kembali' => 'nullable|date_format:H:i|after:jam_keluar', 'alasan' => 'required|string|max:2000', 'catatan' => 'nullable|string|max:500', 'nama_orang_tua_wali' => 'required|string|max:255', 'no_hp_orang_tua' => 'required|string|max:30']);
        $student = Student::query()->findOrFail($validated['student_id']);
        $student->update(['nama_orang_tua_wali' => $validated['nama_orang_tua_wali'], 'no_hp_orang_tua' => $validated['no_hp_orang_tua']]);
        $this->revertMidClassPermitFromAttendance($permit);
        $permit->update(['student_id' => $student->id, 'tanggal' => $validated['tanggal'], 'tipe_izin' => $validated['tipe_izin'], 'jam_keluar' => $validated['jam_keluar'] . ':00', 'jam_kembali' => $validated['tipe_izin'] === 'sementara' && $validated['jam_kembali'] ? $validated['jam_kembali'] . ':00' : null, 'alasan' => $validated['alasan'], 'catatan' => $validated['catatan'] ?? null]);
        $this->applyMidClassPermitToAttendance($permit->fresh());
        return response()->json(['message' => 'Izin pulang berhasil diperbarui.']);
    }

    public function tuDestroy(MidClassPermit $permit)
    {
        abort_unless(auth()->user()->hasRole('tu'), 403);
        $this->revertMidClassPermitFromAttendance($permit);
        $permit->delete();
        return response()->json(['message' => 'Izin pulang berhasil dihapus.']);
    }

    // ─── Wali Kelas ──────────────────────────────────────────────────────────

    public function waliIndex()
    {
        if (!Schema::hasTable('mid_class_permits')) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Menu izin pulang belum aktif. Jalankan migrasi terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher || !$teacher->wali_classroom_id) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Anda tidak terdaftar sebagai wali kelas.');
        }

        $students = Student::query()
            ->where('classroom_id', $teacher->wali_classroom_id)
            ->orderBy('nama_lengkap')
            ->get();

        $permits = MidClassPermit::query()
            ->with(['student', 'submittedByTeacher', 'submittedByStudent'])
            ->whereHas('student', fn($q) => $q->where('classroom_id', $teacher->wali_classroom_id))
            ->latest()
            ->get();

        return view('guru.wali_kelas.mid_class_permits.index', [
            'teacher'  => $teacher,
            'students' => $students,
            'permits'  => $permits,
        ]);
    }

    public function waliStore(Request $request)
    {
        if (!Schema::hasTable('mid_class_permits')) {
            return redirect()->route('guru.wali-kelas.mid-class-permits.index')
                ->with('error', 'Menu izin pulang belum aktif. Jalankan migrasi terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher || !$teacher->wali_classroom_id) {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'tanggal'    => 'required|date|before_or_equal:today',
            'tipe_izin'  => 'required|in:sementara,penuh',
            'jam_keluar' => 'required|date_format:H:i',
            'jam_kembali' => 'nullable|date_format:H:i|after:jam_keluar',
            'alasan'     => 'required|string|max:2000',
            'catatan'    => 'nullable|string|max:500',
            'foto_izin'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);

        if ((int) $student->classroom_id !== (int) $teacher->wali_classroom_id) {
            abort(403);
        }

        $fotoPath = $request->hasFile('foto_izin')
            ? $request->file('foto_izin')->store('mid-class-permits', 'public')
            : null;

        $jamKembali = ($validated['tipe_izin'] === 'sementara' && !empty($validated['jam_kembali']))
            ? $validated['jam_kembali'] . ':00'
            : null;

        $permit = MidClassPermit::create([
            'student_id'               => $student->id,
            'tanggal'                  => $validated['tanggal'],
            'tipe_izin'                => $validated['tipe_izin'],
            'jam_keluar'               => $validated['jam_keluar'] . ':00',
            'jam_kembali'              => $jamKembali,
            'alasan'                   => $validated['alasan'],
            'foto_izin_path'           => $fotoPath,
            'submitted_by_type'        => 'wali_kelas',
            'submitted_by_teacher_id'  => $teacher->id,
            'submitted_by_student_id'  => null,
            'catatan'                  => $validated['catatan'] ?? null,
        ]);

        $this->applyMidClassPermitToAttendance($permit);

        return redirect()->route('guru.wali-kelas.mid-class-permits.index')
            ->with('success', 'Izin pulang ' . $student->nama_lengkap . ' berhasil dicatat dan absensi otomatis diisikan.');
    }

    // ─── Pengurus Kelas ───────────────────────────────────────────────────────

    public function officerIndex()
    {
        if (!Schema::hasTable('mid_class_permits')) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Menu izin pulang belum aktif. Jalankan migrasi terlebih dahulu.');
        }

        $officer = $this->resolveOfficerStudent();

        if (!$officer) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Hanya KM/Sekretaris/Bendahara yang dapat mengakses menu ini.');
        }

        if (!$officer->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua sebelum mengakses menu ini.');
        }

        $students = Student::query()
            ->where('classroom_id', $officer->classroom_id)
            ->where('id', '!=', $officer->id)
            ->orderBy('nama_lengkap')
            ->get();

        $permits = MidClassPermit::query()
            ->with(['student', 'submittedByTeacher', 'submittedByStudent'])
            ->whereHas('student', fn($q) => $q->where('classroom_id', $officer->classroom_id))
            ->latest()
            ->get();

        return view('siswa.mid_class_permits.index', [
            'officer'  => $officer,
            'students' => $students,
            'permits'  => $permits,
        ]);
    }

    public function officerStore(Request $request)
    {
        if (!Schema::hasTable('mid_class_permits')) {
            return redirect()->route('siswa.mid-class-permits.index')
                ->with('error', 'Menu izin pulang belum aktif. Jalankan migrasi terlebih dahulu.');
        }

        $officer = $this->resolveOfficerStudent();

        if (!$officer) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Hanya KM/Sekretaris/Bendahara yang dapat mengakses menu ini.');
        }

        if (!$officer->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua sebelum menginput izin pulang siswa.');
        }

        $validated = $request->validate([
            'student_id'  => 'required|integer|exists:students,id',
            'tanggal'     => 'required|date|before_or_equal:today',
            'tipe_izin'   => 'required|in:sementara,penuh',
            'jam_keluar'  => 'required|date_format:H:i',
            'jam_kembali' => 'nullable|date_format:H:i|after:jam_keluar',
            'alasan'      => 'required|string|max:2000',
            'catatan'     => 'nullable|string|max:500',
            'foto_izin'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);

        if ((int) $student->classroom_id !== (int) $officer->classroom_id) {
            abort(403);
        }

        $fotoPath = $request->hasFile('foto_izin')
            ? $request->file('foto_izin')->store('mid-class-permits', 'public')
            : null;

        $jamKembali = ($validated['tipe_izin'] === 'sementara' && !empty($validated['jam_kembali']))
            ? $validated['jam_kembali'] . ':00'
            : null;

        $permit = MidClassPermit::create([
            'student_id'               => $student->id,
            'tanggal'                  => $validated['tanggal'],
            'tipe_izin'                => $validated['tipe_izin'],
            'jam_keluar'               => $validated['jam_keluar'] . ':00',
            'jam_kembali'              => $jamKembali,
            'alasan'                   => $validated['alasan'],
            'foto_izin_path'           => $fotoPath,
            'submitted_by_type'        => 'pengurus_kelas',
            'submitted_by_teacher_id'  => null,
            'submitted_by_student_id'  => $officer->id,
            'catatan'                  => $validated['catatan'] ?? null,
        ]);

        $this->applyMidClassPermitToAttendance($permit);

        return redirect()->route('siswa.mid-class-permits.index')
            ->with('success', 'Izin pulang ' . $student->nama_lengkap . ' berhasil dicatat dan absensi otomatis diisikan.');
    }

    // ─── Shared Logic ─────────────────────────────────────────────────────────

    private function applyMidClassPermitToAttendance(MidClassPermit $permit): void
    {
        $permit->loadMissing('student');
        $student = $permit->student;

        if (!$student) {
            return;
        }

        $tanggal = Carbon::parse($permit->tanggal);
        if (SchoolCalendar::isHoliday($tanggal)) {
            return;
        }

        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $dayName = $dayMap[$tanggal->dayOfWeekIso] ?? null;

        if (!$dayName || in_array($dayName, ['Sabtu', 'Minggu'], true)) {
            return;
        }

        // Sementara: hanya tandai jam di antara keluar & kembali; Penuh: semua jam setelah keluar
        $schedulesQuery = Schedule::query()
            ->with('teacherSubject')
            ->where('hari', $dayName)
            ->whereHas('teacherSubject', fn($q) => $q->where('classroom_id', $student->classroom_id))
            ->where('jam_selesai', '>', $permit->jam_keluar)
            ->orderBy('jam_mulai');

        if ($permit->tipe_izin === 'sementara' && $permit->jam_kembali) {
            $schedulesQuery->where('jam_mulai', '<', $permit->jam_kembali);
        }

        $schedules = $schedulesQuery->get();

        $keterangan = $permit->tipe_izin === 'sementara' && $permit->jam_kembali
            ? 'Izin sementara jam ' . substr($permit->jam_keluar, 0, 5) . '–' . substr($permit->jam_kembali, 0, 5)
            : 'Izin pulang jam ' . substr($permit->jam_keluar, 0, 5);

        foreach ($schedules as $schedule) {
            if (
                !$schedule->teacherSubject
                || SchoolCalendar::isHolidayAt($tanggal, $schedule->jam_mulai)
            ) {
                continue;
            }

            $teacherAttendance = TeacherAttendance::query()
                ->where('schedule_id', $schedule->id)
                ->whereDate('tanggal', $tanggal->toDateString())
                ->first();

            if (!$teacherAttendance) {
                $lastPertemuan = (int) TeacherAttendance::query()
                    ->where('schedule_id', $schedule->id)
                    ->max('pertemuan');

                $teacherAttendance = TeacherAttendance::create([
                    'teacher_id'           => $schedule->teacherSubject->teacher_id,
                    'schedule_id'          => $schedule->id,
                    'classroom_id'         => $schedule->teacherSubject->classroom_id,
                    'subject_id'           => $schedule->teacherSubject->subject_id,
                    'academic_year_id'     => $schedule->teacherSubject->academic_year_id,
                    'tanggal'              => $tanggal->toDateString(),
                    'pertemuan'            => max($lastPertemuan + 1, 1),
                    'materi_pembelajaran'  => null,
                    'catatan_guru'         => null,
                    'status'               => 'Draft',
                ]);
            }

            AttendanceDetail::updateOrCreate(
                [
                    'teacher_attendance_id' => $teacherAttendance->id,
                    'student_id'            => $student->id,
                ],
                [
                    'status'    => 'Izin',
                    'keterangan' => $keterangan,
                    'jam_absen' => $permit->jam_keluar,
                ]
            );
        }
    }

    private function revertMidClassPermitFromAttendance(MidClassPermit $permit): void
    {
        $permit->loadMissing('student');
        if (!$permit->student) return;
        AttendanceDetail::query()->where('student_id', $permit->student_id)
            ->whereHas('teacherAttendance', fn ($q) => $q->whereDate('tanggal', Carbon::parse($permit->tanggal)->toDateString()))
            ->where('keterangan', 'like', 'Izin%')->delete();
    }

    private function resolveCurrentTeacher(): ?Teacher
    {
        $user = auth()->user();

        return Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();
    }

    private function resolveOfficerStudent(): ?Student
    {
        $user  = auth()->user();

        $student = Student::query()
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();

        if (!$student || !$student->canSubmitTeacherAttendance()) {
            return null;
        }

        return $student;
    }
}
