<?php

namespace App\Http\Controllers;

use App\Exports\StudentAttendanceReportExport;
use App\Exports\TeacherAttendanceReportExport;
use App\Models\AttendanceDetail;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function teacherAttendance(Request $request)
    {
        $rows = $this->buildTeacherAttendanceQuery($request)->get();

        $teachers = Teacher::orderBy('nama_lengkap')->get();
        $majors = Major::orderBy('nama_jurusan')->get();
        $classrooms = Classroom::with('major')->orderBy('nama_kelas')->get();

        return view('admin.reports.teacher-attendance', [
            'rows' => $rows,
            'teachers' => $teachers,
            'majors' => $majors,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function studentAttendance(Request $request)
    {
        $rows = $this->buildStudentAttendanceQuery($request)->get();

        $teachers = Teacher::orderBy('nama_lengkap')->get();
        $students = Student::with('classroom')->orderBy('nama_lengkap')->get();
        $majors = Major::orderBy('nama_jurusan')->get();
        $classrooms = Classroom::with('major')->orderBy('nama_kelas')->get();

        return view('admin.reports.student-attendance', [
            'rows' => $rows,
            'teachers' => $teachers,
            'students' => $students,
            'majors' => $majors,
            'classrooms' => $classrooms,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ]);
    }

    public function teacherAttendancePdf(Request $request)
    {
        $rows = $this->buildTeacherAttendanceQuery($request)->get();

        $pdf = Pdf::loadView('admin.reports.pdf.teacher-attendance', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-absensi-guru.pdf');
    }

    public function teacherAttendanceExcel(Request $request)
    {
        $rows = $this->buildTeacherAttendanceQuery($request)->get();

        return Excel::download(
            new TeacherAttendanceReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-absensi-guru.xlsx'
        );
    }

    public function studentAttendancePdf(Request $request)
    {
        $rows = $this->buildStudentAttendanceQuery($request)->get();

        $pdf = Pdf::loadView('admin.reports.pdf.student-attendance', [
            'rows' => $rows,
            'filters' => $request->all(),
            'periodLabel' => $this->buildPeriodLabel($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-absensi-siswa.pdf');
    }

    public function studentAttendanceExcel(Request $request)
    {
        $rows = $this->buildStudentAttendanceQuery($request)->get();

        return Excel::download(
            new StudentAttendanceReportExport($rows, $request->all(), $this->buildPeriodLabel($request)),
            'laporan-absensi-siswa.xlsx'
        );
    }

    private function buildTeacherAttendanceQuery(Request $request)
    {
        $query = TeacherAttendance::with([
            'teacher',
            'subject',
            'classroom.major',
            'attendanceDetails',
        ])->orderByDesc('tanggal')->orderByDesc('id');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('major_id')) {
            $query->whereHas('classroom', function ($classroomQuery) use ($request) {
                $classroomQuery->where('major_id', $request->major_id);
            });
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        return $query;
    }

    private function buildStudentAttendanceQuery(Request $request)
    {
        $query = AttendanceDetail::with([
            'student.classroom.major',
            'teacherAttendance.teacher',
            'teacherAttendance.subject',
            'teacherAttendance.classroom.major',
        ])->orderByDesc('id');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        if ($startDate && $endDate) {
            $query->whereHas('teacherAttendance', function ($teacherAttendanceQuery) use ($startDate, $endDate) {
                $teacherAttendanceQuery->whereBetween('tanggal', [$startDate, $endDate]);
            });
        }

        if ($request->filled('teacher_id')) {
            $query->whereHas('teacherAttendance', function ($teacherAttendanceQuery) use ($request) {
                $teacherAttendanceQuery->where('teacher_id', $request->teacher_id);
            });
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('major_id')) {
            $query->whereHas('student.classroom', function ($classroomQuery) use ($request) {
                $classroomQuery->where('major_id', $request->major_id);
            });
        }

        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function ($studentQuery) use ($request) {
                $studentQuery->where('classroom_id', $request->classroom_id);
            });
        }

        return $query;
    }

    private function resolveDateRange(Request $request): array
    {
        $periodType = $request->input('period_type');

        if ($periodType === 'tanggal' && $request->filled('tanggal')) {
            $date = Carbon::parse($request->tanggal)->toDateString();

            return [$date, $date];
        }

        if ($periodType === 'mingguan' && $request->filled('minggu')) {
            [$year, $week] = explode('-W', $request->minggu);
            $startDate = Carbon::now()->setISODate((int) $year, (int) $week)->startOfWeek();
            $endDate = Carbon::now()->setISODate((int) $year, (int) $week)->endOfWeek();

            return [$startDate->toDateString(), $endDate->toDateString()];
        }

        if ($periodType === 'bulanan' && $request->filled('bulan')) {
            $date = Carbon::createFromFormat('Y-m', $request->bulan);

            return [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()];
        }

        if ($periodType === 'tahunan' && $request->filled('tahun')) {
            $startDate = Carbon::createFromDate((int) $request->tahun, 1, 1)->startOfDay();
            $endDate = Carbon::createFromDate((int) $request->tahun, 12, 31)->endOfDay();

            return [$startDate->toDateString(), $endDate->toDateString()];
        }

        return [null, null];
    }

    private function buildPeriodLabel(Request $request): string
    {
        $periodType = $request->input('period_type');

        if ($periodType === 'tanggal' && $request->filled('tanggal')) {
            return 'Tanggal: ' . $request->tanggal;
        }

        if ($periodType === 'mingguan' && $request->filled('minggu')) {
            return 'Mingguan: ' . $request->minggu;
        }

        if ($periodType === 'bulanan' && $request->filled('bulan')) {
            return 'Bulanan: ' . $request->bulan;
        }

        if ($periodType === 'tahunan' && $request->filled('tahun')) {
            return 'Tahunan: ' . $request->tahun;
        }

        return 'Semua Periode';
    }
}
