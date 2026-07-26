<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\TeacherLeaveRequest;
use App\Models\TeacherAttendance;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TeacherLeaveRequestController extends Controller
{
    public function approvalIndex()
    {
        if (!$this->hasLeaveRequestColumns()) {
            return redirect()->route('kurikulum.dashboard')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $requests = TeacherLeaveRequest::query()
            ->with('teacher')
            ->latest()
            ->get();

        return view('kurikulum.teacher_leave_requests.index', [
            'requests' => $requests,
        ]);
    }

    public function index()
    {
        if (!$this->hasLeaveRequestColumns()) {
            return redirect()->route('guru.dashboard')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        $requests = TeacherLeaveRequest::query()
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->get();

        return view('guru.leave_requests.index', [
            'teacher' => $teacher,
            'requests' => $requests,
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->hasLeaveRequestColumns()) {
            return redirect()->route('guru.leave-requests.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        $validated = $request->validate([
            'jenis_pengajuan' => ['required', Rule::in(['Izin', 'Sakit', 'Cuti', 'Dinas Luar', 'Home Visit'])],
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:2000',
            'lampiran_tugas' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'deskripsi_tugas' => 'nullable|string|max:3000',
        ]);

        $deskripsiTugas = trim((string) ($validated['deskripsi_tugas'] ?? ''));

        if ($validated['jenis_pengajuan'] !== 'Cuti' && !$request->hasFile('lampiran_tugas') && $deskripsiTugas === '') {
            return redirect()->route('guru.leave-requests.index')
            ->withErrors(['deskripsi_tugas' => 'Isi tugas wajib (file atau deskripsi) untuk semua pengajuan selain Cuti.'])
                ->withInput();
        }

        $attachmentPath = $request->hasFile('lampiran_tugas')
            ? $request->file('lampiran_tugas')->store('guru-pengajuan', 'public')
            : null;

        TeacherLeaveRequest::create([
            'teacher_id' => $teacher->id,
            'jenis_pengajuan' => $validated['jenis_pengajuan'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'alasan' => $validated['alasan'],
            'lampiran_tugas_path' => $attachmentPath,
            'deskripsi_tugas' => $deskripsiTugas !== '' ? $deskripsiTugas : null,
            'status_pengajuan' => 'Menunggu',
        ]);

        return redirect()->route('guru.leave-requests.index')
            ->with('success', 'Pengajuan berhasil dikirim.');
    }

    public function update(Request $request, TeacherLeaveRequest $teacherLeaveRequest)
    {
        if (!$this->hasLeaveRequestColumns()) {
            return redirect()->route('guru.leave-requests.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        if ((int) $teacherLeaveRequest->teacher_id !== (int) $teacher->id) {
            abort(403);
        }

        if ($teacherLeaveRequest->status_pengajuan !== 'Menunggu') {
            return redirect()->route('guru.leave-requests.index')
                ->with('error', 'Pengajuan yang sudah diproses tidak dapat diedit.');
        }

        $validated = $request->validate([
            'jenis_pengajuan' => ['required', Rule::in(['Izin', 'Sakit', 'Cuti', 'Dinas Luar', 'Home Visit'])],
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:2000',
            'lampiran_tugas' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'deskripsi_tugas' => 'nullable|string|max:3000',
        ]);

        $deskripsiTugas = trim((string) ($validated['deskripsi_tugas'] ?? ''));
        $hasExistingFile = !empty($teacherLeaveRequest->lampiran_tugas_path);

        if ($validated['jenis_pengajuan'] !== 'Cuti' && !$request->hasFile('lampiran_tugas') && !$hasExistingFile && $deskripsiTugas === '') {
            return redirect()->route('guru.leave-requests.index')
                ->withErrors(['deskripsi_tugas' => 'Isi tugas wajib (file atau deskripsi) untuk semua pengajuan selain Cuti.'])
                ->withInput();
        }

        $attachmentPath = $teacherLeaveRequest->lampiran_tugas_path;

        if ($request->hasFile('lampiran_tugas')) {
            if (!empty($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }

            $attachmentPath = $request->file('lampiran_tugas')->store('guru-pengajuan', 'public');
        }

        $teacherLeaveRequest->update([
            'jenis_pengajuan' => $validated['jenis_pengajuan'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'alasan' => $validated['alasan'],
            'lampiran_tugas_path' => $attachmentPath,
            'deskripsi_tugas' => $deskripsiTugas !== '' ? $deskripsiTugas : null,
        ]);

        return redirect()->route('guru.leave-requests.index')
            ->with('success', 'Pengajuan berhasil diperbarui.');
    }

    public function destroy(TeacherLeaveRequest $teacherLeaveRequest)
    {
        if (!$this->hasLeaveRequestColumns()) {
            return redirect()->route('guru.leave-requests.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacher = $this->resolveCurrentTeacher();

        if (!$teacher) {
            return redirect()->route('guru.dashboard')->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        if ((int) $teacherLeaveRequest->teacher_id !== (int) $teacher->id) {
            abort(403);
        }

        if ($teacherLeaveRequest->status_pengajuan !== 'Menunggu') {
            return redirect()->route('guru.leave-requests.index')
                ->with('error', 'Pengajuan yang sudah diproses tidak dapat dihapus.');
        }

        if (!empty($teacherLeaveRequest->lampiran_tugas_path)) {
            Storage::disk('public')->delete($teacherLeaveRequest->lampiran_tugas_path);
        }

        $teacherLeaveRequest->delete();

        return redirect()->route('guru.leave-requests.index')
            ->with('success', 'Pengajuan berhasil dihapus.');
    }

    public function approve(TeacherLeaveRequest $teacherLeaveRequest)
    {
        if (!$this->hasLeaveRequestColumns()) {
            return redirect()->route('kurikulum.teacher-leave-requests.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacherLeaveRequest->update([
            'status_pengajuan' => 'Disetujui',
        ]);

        $this->syncApprovedLeaveToTeacherAttendances($teacherLeaveRequest->fresh());

        return redirect()->route('kurikulum.teacher-leave-requests.index')
            ->with('success', 'Pengajuan izin guru disetujui.');
    }

    public function reject(TeacherLeaveRequest $teacherLeaveRequest)
    {
        if (!$this->hasLeaveRequestColumns()) {
            return redirect()->route('kurikulum.teacher-leave-requests.index')
                ->with('error', 'Menu pengajuan belum aktif. Jalankan migrasi database terlebih dahulu.');
        }

        $teacherLeaveRequest->update([
            'status_pengajuan' => 'Ditolak',
        ]);

        return redirect()->route('kurikulum.teacher-leave-requests.index')
            ->with('success', 'Pengajuan izin guru ditolak.');
    }

    private function resolveCurrentTeacher(): ?Teacher
    {
        $user = auth()->user();

        return Teacher::query()
            ->where('nip', $user->email)
            ->orWhere('nama_lengkap', $user->name)
            ->first();
    }

    private function hasLeaveRequestColumns(): bool
    {
        return Schema::hasTable('teacher_leave_requests')
            && Schema::hasColumns('teacher_leave_requests', ['lampiran_tugas_path', 'deskripsi_tugas']);
    }

    private function syncApprovedLeaveToTeacherAttendances(TeacherLeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status_pengajuan !== 'Disetujui') {
            return;
        }

        $startDate = Carbon::parse($leaveRequest->tanggal_mulai)->startOfDay();
        $endDate = Carbon::parse($leaveRequest->tanggal_selesai)->startOfDay();

        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayName = $dayMap[$date->dayOfWeekIso] ?? null;

            if (!$dayName) {
                continue;
            }

            $schedules = Schedule::query()
                ->with('teacherSubject')
                ->where('hari', $dayName)
                ->whereHas('teacherSubject', function ($query) use ($leaveRequest) {
                    $query->where('teacher_id', $leaveRequest->teacher_id);
                })
                ->get();

            foreach ($schedules as $schedule) {
                $existing = TeacherAttendance::query()
                    ->where('schedule_id', $schedule->id)
                    ->whereDate('tanggal', $date->toDateString())
                    ->first();

                if ($existing) {
                    $existing->update([
                        'kehadiran_guru' => $leaveRequest->jenis_pengajuan,
                        'tugas_file_path' => $leaveRequest->lampiran_tugas_path,
                        'tugas_deskripsi' => $leaveRequest->deskripsi_tugas,
                    ]);

                    continue;
                }

                $lastPertemuan = (int) TeacherAttendance::query()
                    ->where('schedule_id', $schedule->id)
                    ->max('pertemuan');

                TeacherAttendance::create([
                    'teacher_id' => $schedule->teacherSubject->teacher_id,
                    'schedule_id' => $schedule->id,
                    'classroom_id' => $schedule->teacherSubject->classroom_id,
                    'subject_id' => $schedule->teacherSubject->subject_id,
                    'academic_year_id' => $schedule->teacherSubject->academic_year_id,
                    'tanggal' => $date->toDateString(),
                    'pertemuan' => max($lastPertemuan + 1, 1),
                    'materi_pembelajaran' => null,
                    'catatan_guru' => null,
                    'kehadiran_guru' => $leaveRequest->jenis_pengajuan,
                    'tugas_file_path' => $leaveRequest->lampiran_tugas_path,
                    'tugas_deskripsi' => $leaveRequest->deskripsi_tugas,
                    'status' => 'Draft',
                ]);
            }
        }
    }
}
