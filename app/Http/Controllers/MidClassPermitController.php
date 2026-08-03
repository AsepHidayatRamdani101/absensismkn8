<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDetail;
use App\Models\MidClassPermit;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MidClassPermitController extends Controller
{
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
            if (!$schedule->teacherSubject) {
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
