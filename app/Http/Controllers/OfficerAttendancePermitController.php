<?php

namespace App\Http\Controllers;

use App\Models\OfficerAttendancePermit;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\TeacherLeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OfficerAttendancePermitController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = (string) $request->query('status_pengajuan', '');

        $query = OfficerAttendancePermit::query()
            ->with([
                'officer.classroom',
                'teacher',
                'schedule.teacherSubject.subject',
                'approver',
            ])
            ->orderByDesc('request_date')
            ->orderByDesc('id');

        if ($statusFilter !== '') {
            $query->where('status_pengajuan', $statusFilter);
        }

        $requests = $query->get();

        $pendingCount = (int) OfficerAttendancePermit::query()
            ->where('status_pengajuan', 'Menunggu')
            ->count();

        return view('kurikulum.officer_attendance_permits.index', [
            'requests' => $requests,
            'pendingCount' => $pendingCount,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function store(Request $request)
    {
        $officer = $this->resolveOfficerStudent();

        if (!$officer || !$officer->canSubmitTeacherAttendance()) {
            return redirect()->route('siswa.dashboard')
                ->with('error', 'Hanya KM/Sekretaris/Bendahara yang dapat mengajukan izin absen kelas.');
        }

        if (!$officer->hasMinimumIdentityForProtectedMenus()) {
            return redirect()->route('siswa.identity.edit')
                ->with('error', 'Lengkapi minimal No HP Orang Tua pada menu Identitas Siswa sebelum mengajukan izin absen kelas.');
        }

        $validated = $request->validate([
            'schedule_id' => 'required|integer|exists:schedules,id',
            'alasan' => 'required|string|max:2000',
        ]);

        $today = Carbon::today();

        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $todayDayName = $dayMap[$today->dayOfWeekIso] ?? null;

        if ($todayDayName === null || in_array($todayDayName, ['Sabtu', 'Minggu'], true)) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Pengajuan izin absen kelas hanya bisa dilakukan pada hari sekolah.');
        }

        $schedule = Schedule::query()
            ->with('teacherSubject')
            ->where('id', (int) $validated['schedule_id'])
            ->where('hari', $todayDayName)
            ->whereHas('teacherSubject', function ($query) use ($officer) {
                $query->where('classroom_id', $officer->classroom_id);
            })
            ->first();

        if (!$schedule || !$schedule->teacherSubject) {
            return redirect()->route('siswa.attendance-details.index')
                ->with('error', 'Jadwal tidak valid untuk kelas Anda hari ini.');
        }

        $approvedLeave = TeacherLeaveRequest::query()
            ->where('teacher_id', $schedule->teacherSubject->teacher_id)
            ->where('status_pengajuan', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $today->toDateString())
            ->whereDate('tanggal_selesai', '>=', $today->toDateString())
            ->latest('id')
            ->first();

        if (!$approvedLeave) {
            return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
                ->with('error', 'Pengajuan izin absen kelas hanya dapat dilakukan saat guru pada jadwal tersebut sudah izin dan disetujui kurikulum.');
        }

        $permit = OfficerAttendancePermit::query()->firstOrNew([
            'officer_student_id' => $officer->id,
            'schedule_id' => $schedule->id,
            'request_date' => $today->toDateString(),
        ]);

        if ($permit->exists && $permit->status_pengajuan === 'Disetujui') {
            return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
                ->with('success', 'Izin absen kelas untuk jadwal ini sudah disetujui kurikulum.');
        }

        $permit->fill([
            'classroom_id' => (int) $officer->classroom_id,
            'teacher_id' => (int) $schedule->teacherSubject->teacher_id,
            'alasan' => $validated['alasan'],
            'status_pengajuan' => 'Menunggu',
            'catatan_kurikulum' => null,
            'approved_by_user_id' => null,
            'approved_at' => null,
        ]);

        $permit->save();

        $message = $permit->wasRecentlyCreated
            ? 'Pengajuan izin absen kelas berhasil dikirim ke kurikulum.'
            : 'Pengajuan izin absen kelas diperbarui dan dikirim ulang ke kurikulum.';

        return redirect()->route('siswa.attendance-details.index', ['schedule_id' => $schedule->id])
            ->with('success', $message);
    }

    public function approve(Request $request, OfficerAttendancePermit $permit)
    {
        if ($permit->status_pengajuan !== 'Menunggu') {
            return redirect()->route('kurikulum.officer-attendance-permits.index')
                ->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'catatan_kurikulum' => 'nullable|string|max:2000',
        ]);

        $permit->update([
            'status_pengajuan' => 'Disetujui',
            'catatan_kurikulum' => $validated['catatan_kurikulum'] ?? null,
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('kurikulum.officer-attendance-permits.index')
            ->with('success', 'Pengajuan izin absen kelas berhasil disetujui.');
    }

    public function reject(Request $request, OfficerAttendancePermit $permit)
    {
        if ($permit->status_pengajuan !== 'Menunggu') {
            return redirect()->route('kurikulum.officer-attendance-permits.index')
                ->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'catatan_kurikulum' => 'nullable|string|max:2000',
        ]);

        $permit->update([
            'status_pengajuan' => 'Ditolak',
            'catatan_kurikulum' => $validated['catatan_kurikulum'] ?? null,
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('kurikulum.officer-attendance-permits.index')
            ->with('success', 'Pengajuan izin absen kelas berhasil ditolak.');
    }

    private function resolveOfficerStudent(): ?Student
    {
        $user = auth()->user();

        return Student::query()
            ->where('nisn', $user->email)
            ->orWhere('nis', $user->email)
            ->first();
    }
}
