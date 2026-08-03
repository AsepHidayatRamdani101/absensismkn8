<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDetail;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentLeaveRequest;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StudentLeaveRequestController extends Controller
{
    public function siswaIndex()
    {
        if (!Schema::hasTable('student_leave_requests')) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $student = $this->resolveCurrentStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
        }

        if (!$student->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum mengakses pengajuan izin/sakit. Riwayat Absen tetap bisa diakses.');
        }

        $requests = StudentLeaveRequest::query()
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        return view('siswa.leave_requests.index', [
            'student' => $student,
            'requests' => $requests,
        ]);
    }

    public function siswaStore(Request $request)
    {
        if (!Schema::hasTable('student_leave_requests')) {
            return redirect()->route('siswa.leave-requests.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $student = $this->resolveCurrentStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa tidak ditemukan untuk akun ini.');
        }

        if (!$student->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum mengirim pengajuan izin/sakit. Riwayat Absen tetap bisa diakses.');
        }

        $validated = $request->validate([
            'jenis_pengajuan' => 'required|in:Izin,Sakit',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:2000',
            'foto_surat' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:min_width=600,min_height=600',
        ]);

        $fotoSuratPath = $request->file('foto_surat')->store('siswa-surat-izin', 'public');

        StudentLeaveRequest::create([
            'student_id' => $student->id,
            'jenis_pengajuan' => $validated['jenis_pengajuan'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'alasan' => $validated['alasan'],
            'foto_surat_path' => $fotoSuratPath,
            'status_pengajuan' => 'Menunggu',
            'catatan_wali' => null,
            'verified_by_teacher_id' => null,
            'verified_at' => null,
        ]);

        return redirect()->route('siswa.leave-requests.index')
            ->with('success', 'Pengajuan izin/sakit berhasil dikirim. Tunggu verifikasi wali kelas.');
    }

    public function waliIndex()
    {
        if (!Schema::hasTable('student_leave_requests')) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher || !$teacher->wali_classroom_id) {
            return redirect()->route('guru.dashboard')->with('error', 'Anda tidak terdaftar sebagai wali kelas.');
        }

        $students = Student::query()
            ->where('classroom_id', $teacher->wali_classroom_id)
            ->orderBy('nama_lengkap')
            ->get();

        $requests = StudentLeaveRequest::query()
            ->with(['student.classroom', 'verifier'])
            ->whereHas('student', function ($query) use ($teacher) {
                $query->where('classroom_id', $teacher->wali_classroom_id);
            })
            ->latest()
            ->get();

        $pendingCount = (int) $requests->where('status_pengajuan', 'Menunggu')->count();

        return view('guru.wali_kelas.leave_requests.index', [
            'teacher' => $teacher,
            'students' => $students,
            'requests' => $requests,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function waliApprove(Request $request, StudentLeaveRequest $leaveRequest)
    {
        if (!Schema::hasTable('student_leave_requests')) {
            return redirect()->route('guru.wali-kelas.leave-requests.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher || !$teacher->wali_classroom_id) {
            abort(403);
        }

        $leaveRequest->load('student.classroom');

        if ((int) ($leaveRequest->student->classroom_id ?? 0) !== (int) $teacher->wali_classroom_id) {
            abort(403);
        }

        $validated = $request->validate([
            'catatan_wali' => 'nullable|string|max:2000',
        ]);

        $leaveRequest->update([
            'status_pengajuan' => 'Disetujui',
            'catatan_wali' => $validated['catatan_wali'] ?? null,
            'verified_by_teacher_id' => $teacher->id,
            'verified_at' => now(),
        ]);

        $this->applyLeaveToAttendanceDetails($leaveRequest);

        return redirect()->route('guru.wali-kelas.leave-requests.index')
            ->with('success', 'Pengajuan disetujui dan absensi siswa telah diisikan sepanjang durasi pengajuan.');
    }

    public function waliReject(Request $request, StudentLeaveRequest $leaveRequest)
    {
        if (!Schema::hasTable('student_leave_requests')) {
            return redirect()->route('guru.wali-kelas.leave-requests.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher || !$teacher->wali_classroom_id) {
            abort(403);
        }

        $leaveRequest->load('student.classroom');

        if ((int) ($leaveRequest->student->classroom_id ?? 0) !== (int) $teacher->wali_classroom_id) {
            abort(403);
        }

        $validated = $request->validate([
            'catatan_wali' => 'nullable|string|max:2000',
        ]);

        $leaveRequest->update([
            'status_pengajuan' => 'Ditolak',
            'catatan_wali' => $validated['catatan_wali'] ?? null,
            'verified_by_teacher_id' => $teacher->id,
            'verified_at' => now(),
        ]);

        return redirect()->route('guru.wali-kelas.leave-requests.index')
            ->with('success', 'Pengajuan ditolak.');
    }

    public function waliStoreHardfile(Request $request)
    {
        if (!Schema::hasTable('student_leave_requests')) {
            return redirect()->route('guru.wali-kelas.leave-requests.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher || !$teacher->wali_classroom_id) {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'jenis_pengajuan' => 'required|in:Izin,Sakit',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:2000',
            'catatan_wali' => 'nullable|string|max:2000',
            'foto_surat' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:min_width=600,min_height=600',
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);

        if ((int) $student->classroom_id !== (int) $teacher->wali_classroom_id) {
            abort(403);
        }

        $fotoSuratPath = $request->file('foto_surat')->store('siswa-surat-izin', 'public');

        $leaveRequest = StudentLeaveRequest::create([
            'student_id' => $student->id,
            'jenis_pengajuan' => $validated['jenis_pengajuan'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'alasan' => $validated['alasan'],
            'foto_surat_path' => $fotoSuratPath,
            'status_pengajuan' => 'Disetujui',
            'catatan_wali' => $validated['catatan_wali'] ?? 'Input langsung wali kelas dengan bukti screenshot surat/chat orang tua.',
            'verified_by_teacher_id' => $teacher->id,
            'verified_at' => now(),
        ]);

        $this->applyLeaveToAttendanceDetails($leaveRequest);

        return redirect()->route('guru.wali-kelas.leave-requests.index')
            ->with('success', 'Input langsung izin/sakit oleh wali kelas berhasil diproses dan absensi siswa telah diisikan.');
    }

    public function officerStoreDirect(Request $request)
    {
        if (!Schema::hasTable('student_leave_requests')) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $officer = $this->resolveOfficerStudent();

        if (!$officer || !$officer->canSubmitTeacherAttendance()) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Hanya KM/Sekretaris/Bendahara yang dapat menginput izin/sakit siswa.');
        }

        if (!$officer->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum menginput izin/sakit siswa.');
        }

        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'classroom_id' => 'required|integer',
            'schedule_id' => 'required|integer|exists:schedules,id',
            'jenis_pengajuan' => 'required|in:Izin,Sakit',
            'alasan' => 'required|string|max:2000',
            'foto_surat' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:min_width=600,min_height=600',
        ]);

        $classroomId = (int) $validated['classroom_id'];

        if ((int) $officer->classroom_id !== $classroomId) {
            abort(403);
        }

        $student = Student::query()->findOrFail((int) $validated['student_id']);

        if ((int) $student->classroom_id !== $classroomId) {
            abort(403);
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

        $today = Carbon::today();
        $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;

        if ($todayDayName === null || in_array($todayDayName, ['Sabtu', 'Minggu'], true)) {
            return redirect()->route('siswa.attendance-details.index', ['schedule_id' => (int) $validated['schedule_id']])
                ->with('error', 'Absensi siswa otomatis libur pada hari Sabtu dan Minggu.');
        }

        $schedule = Schedule::query()
            ->with('teacherSubject')
            ->where('id', (int) $validated['schedule_id'])
            ->where('hari', $todayDayName)
            ->whereHas('teacherSubject', function ($query) use ($classroomId) {
                $query->where('classroom_id', $classroomId);
            })
            ->first();

        if (!$schedule || !$schedule->teacherSubject) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Jadwal tidak valid untuk kelas Anda hari ini.');
        }

        $approvedPermit = \App\Models\OfficerAttendancePermit::query()
            ->where('officer_student_id', $officer->id)
            ->where('schedule_id', $schedule->id)
            ->whereDate('request_date', $today->toDateString())
            ->where('status_pengajuan', 'Disetujui')
            ->exists();

        if (!$approvedPermit) {
            return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
                ->with('error', 'Ajukan izin absen kelas ke kurikulum terlebih dahulu untuk jadwal ini.');
        }

        $fotoSuratPath = $request->file('foto_surat')->store('siswa-surat-izin', 'public');

        $leaveRequest = StudentLeaveRequest::create([
            'student_id' => $student->id,
            'jenis_pengajuan' => $validated['jenis_pengajuan'],
            'tanggal_mulai' => $today->toDateString(),
            'tanggal_selesai' => $today->toDateString(),
            'alasan' => $validated['alasan'],
            'foto_surat_path' => $fotoSuratPath,
            'status_pengajuan' => 'Disetujui',
            'catatan_wali' => 'Input langsung pengurus kelas dengan bukti screenshot surat/chat orang tua.',
            'verified_by_teacher_id' => null,
            'verified_at' => now(),
        ]);

        $this->applyLeaveToAttendanceDetails($leaveRequest);

        return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
            ->with('success', 'Izin/sakit siswa berhasil diinput dengan bukti screenshot dan absensi telah diperbarui.');
    }

    public function officerLeaveIndex()
    {
        if (!Schema::hasTable('student_leave_requests')) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $officer = $this->resolveOfficerStudent();

        if (!$officer) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Hanya KM/Sekretaris/Bendahara yang dapat mengakses menu ini.');
        }

        if (!$officer->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum mengakses menu ini.');
        }

        $students = Student::query()
            ->where('classroom_id', $officer->classroom_id)
            ->where('id', '!=', $officer->id)
            ->orderBy('nama_lengkap')
            ->get();

        $requests = StudentLeaveRequest::query()
            ->with(['student', 'verifier'])
            ->whereHas('student', fn($q) => $q->where('classroom_id', $officer->classroom_id))
            ->latest()
            ->get();

        return view('siswa.officer_leave.index', [
            'officer' => $officer,
            'students' => $students,
            'requests' => $requests,
        ]);
    }

    public function officerLeaveStore(Request $request)
    {
        if (!Schema::hasTable('student_leave_requests')) {
            return redirect()->route('siswa.officer-leave.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $officer = $this->resolveOfficerStudent();

        if (!$officer) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Hanya KM/Sekretaris/Bendahara yang dapat mengakses menu ini.');
        }

        if (!$officer->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum menginput izin/sakit siswa.');
        }

        $validated = $request->validate([
            'student_id'       => 'required|integer|exists:students,id',
            'jenis_pengajuan'  => 'required|in:Izin,Sakit',
            'tanggal_mulai'    => 'required|date',
            'tanggal_selesai'  => 'required|date|after_or_equal:tanggal_mulai',
            'alasan'           => 'required|string|max:2000',
            'catatan'          => 'nullable|string|max:2000',
            'foto_surat'       => 'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:min_width=600,min_height=600',
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);

        if ((int) $student->classroom_id !== (int) $officer->classroom_id) {
            abort(403);
        }

        $fotoSuratPath = $request->file('foto_surat')->store('siswa-surat-izin', 'public');

        $leaveRequest = StudentLeaveRequest::create([
            'student_id'              => $student->id,
            'jenis_pengajuan'         => $validated['jenis_pengajuan'],
            'tanggal_mulai'           => $validated['tanggal_mulai'],
            'tanggal_selesai'         => $validated['tanggal_selesai'],
            'alasan'                  => $validated['alasan'],
            'foto_surat_path'         => $fotoSuratPath,
            'status_pengajuan'        => 'Disetujui',
            'catatan_wali'            => $validated['catatan'] ?? 'Input langsung pengurus kelas dengan bukti screenshot surat/chat orang tua.',
            'verified_by_teacher_id'  => null,
            'verified_at'             => now(),
        ]);

        $this->applyLeaveToAttendanceDetails($leaveRequest);

        return redirect()->route('siswa.officer-leave.index')
            ->with('success', 'Izin/sakit ' . $student->nama_lengkap . ' berhasil dicatat dan absensi telah diperbarui.');
    }

    private function applyLeaveToAttendanceDetails(StudentLeaveRequest $leaveRequest): void
    {
        $leaveRequest->loadMissing('student');

        $student = $leaveRequest->student;
        if (!$student) {
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

        $status = $leaveRequest->jenis_pengajuan === 'Sakit' ? 'Sakit' : 'Izin';
        $sourceNote = 'Verifikasi wali kelas';
        $catatanLower = strtolower((string) ($leaveRequest->catatan_wali ?? ''));

        if (str_contains($catatanLower, 'pengurus kelas')) {
            $sourceNote = 'Input pengurus kelas';
        } elseif (str_contains($catatanLower, 'input langsung wali kelas')) {
            $sourceNote = 'Input langsung wali kelas';
        }

        $keterangan = $sourceNote . ' (' . $leaveRequest->jenis_pengajuan . ')';

        $period = CarbonPeriod::create(
            Carbon::parse($leaveRequest->tanggal_mulai)->startOfDay(),
            Carbon::parse($leaveRequest->tanggal_selesai)->startOfDay()
        );

        foreach ($period as $date) {
            $dayName = $dayMap[$date->dayOfWeekIso] ?? null;

            if ($dayName === null || in_array($dayName, ['Sabtu', 'Minggu'], true)) {
                continue;
            }

            $schedules = Schedule::query()
                ->with('teacherSubject')
                ->where('hari', $dayName)
                ->whereHas('teacherSubject', function ($query) use ($student) {
                    $query->where('classroom_id', $student->classroom_id);
                })
                ->orderBy('jam_mulai')
                ->get();

            foreach ($schedules as $schedule) {
                if (!$schedule->teacherSubject) {
                    continue;
                }

                $teacherAttendance = TeacherAttendance::query()
                    ->where('schedule_id', $schedule->id)
                    ->whereDate('tanggal', $date->toDateString())
                    ->first();

                if (!$teacherAttendance) {
                    $lastPertemuan = (int) TeacherAttendance::query()
                        ->where('schedule_id', $schedule->id)
                        ->max('pertemuan');

                    $teacherAttendance = TeacherAttendance::create([
                        'teacher_id' => $schedule->teacherSubject->teacher_id,
                        'schedule_id' => $schedule->id,
                        'classroom_id' => $schedule->teacherSubject->classroom_id,
                        'subject_id' => $schedule->teacherSubject->subject_id,
                        'academic_year_id' => $schedule->teacherSubject->academic_year_id,
                        'tanggal' => $date->toDateString(),
                        'pertemuan' => max($lastPertemuan + 1, 1),
                        'materi_pembelajaran' => null,
                        'catatan_guru' => null,
                        'status' => 'Draft',
                    ]);
                }

                AttendanceDetail::updateOrCreate(
                    [
                        'teacher_attendance_id' => $teacherAttendance->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => $status,
                        'keterangan' => $keterangan,
                        'jam_absen' => now()->format('H:i:s'),
                    ]
                );
            }
        }
    }

    private function resolveCurrentStudent(): ?Student
    {
        $user = auth()->user();

        return Student::query()
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();
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
        $student = $this->resolveCurrentStudent();

        if (!$student) {
            return null;
        }

        if (!$student->canSubmitTeacherAttendance()) {
            return null;
        }

        return $student;
    }
}
