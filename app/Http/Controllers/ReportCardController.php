<?php

namespace App\Http\Controllers;

use App\Exports\ReportCardLegerExport;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\Assessment\ReportCardService;
use App\Support\SchoolIdentity;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReportCardController extends Controller
{
    private const MAX_STUDENTS_PER_PDF = 60;

    public function __construct(private readonly ReportCardService $service)
    {
    }

    public function homeroom(Request $request)
    {
        $classroom = $this->homeroomClassroom();
        $year = $this->resolveYear($request);

        return view('report-cards.index', [
            'mode' => 'wali',
            'years' => $this->years(),
            'year' => $year,
            'classrooms' => collect([$classroom]),
            'classroom' => $classroom,
            'report' => $year ? $this->service->forClassroom($classroom, $year) : null,
            'canEdit' => true,
        ]);
    }

    public function admin(Request $request)
    {
        $year = $this->resolveYear($request);
        $classroomId = $request->integer('classroom_id');
        $classroom = $classroomId ? Classroom::query()->findOrFail($classroomId) : null;

        return view('report-cards.index', [
            'mode' => 'admin',
            'years' => $this->years(),
            'year' => $year,
            'classrooms' => Classroom::query()->with('major:id,nama_jurusan')->orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'classroom' => $classroom,
            'report' => $year && $classroom ? $this->service->forClassroom($classroom, $year) : null,
            'canEdit' => auth()->user()->hasRole('admin'),
        ]);
    }

    public function saveHomeroomNote(Request $request, Student $student)
    {
        $this->homeroomClassroom();

        return $this->saveNote($request, $student, 'wali');
    }

    public function saveAdminNote(Request $request, Student $student)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, 'Hanya admin atau wali kelas yang dapat mengubah catatan rapor.');

        return $this->saveNote($request, $student, 'admin');
    }

    private function saveNote(Request $request, Student $student, string $mode)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'homeroom_note' => ['nullable', 'string', 'max:1000'],
            'sick_days' => ['nullable', 'integer', 'min:0', 'max:200'],
            'permit_days' => ['nullable', 'integer', 'min:0', 'max:200'],
            'absent_days' => ['nullable', 'integer', 'min:0', 'max:200'],
        ], [
            'homeroom_note.max' => 'Catatan wali kelas maksimal 1000 karakter.',
            '*.integer' => 'Jumlah hari harus berupa bilangan bulat.',
            '*.min' => 'Jumlah hari tidak boleh negatif.',
        ]);

        $classroom = Classroom::query()->findOrFail($validated['classroom_id']);

        if ($mode === 'wali') {
            abort_unless((int) $classroom->id === (int) $this->homeroomClassroom()->id, 403);
        }

        $year = AcademicYear::query()->findOrFail($validated['academic_year_id']);
        $report = $this->service->forClassroom($classroom, $year, [$student->id]);
        abort_if($report['entries']->isEmpty(), 404, 'Siswa tidak termasuk kelas ini pada semester tersebut.');

        ReportCard::query()->updateOrCreate(
            ['student_id' => $student->id, 'academic_year_id' => $year->id],
            [
                'classroom_id' => $classroom->id,
                'homeroom_note' => trim((string) ($validated['homeroom_note'] ?? '')) ?: null,
                'sick_days' => $validated['sick_days'] ?? null,
                'permit_days' => $validated['permit_days'] ?? null,
                'absent_days' => $validated['absent_days'] ?? null,
                'updated_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Catatan rapor ' . $student->nama_lengkap . ' berhasil disimpan.');
    }

    public function printHomeroom(Request $request)
    {
        return $this->printPdf($request, $this->homeroomClassroom());
    }

    public function printAdmin(Request $request)
    {
        $request->validate(['classroom_id' => ['required', 'integer', 'exists:classrooms,id']]);

        return $this->printPdf($request, Classroom::query()->findOrFail($request->integer('classroom_id')));
    }

    public function legerHomeroom(Request $request)
    {
        return $this->downloadLeger($request, $this->homeroomClassroom());
    }

    public function legerAdmin(Request $request)
    {
        $request->validate(['classroom_id' => ['required', 'integer', 'exists:classrooms,id']]);

        return $this->downloadLeger($request, Classroom::query()->findOrFail($request->integer('classroom_id')));
    }

    private function printPdf(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'tanggal' => ['nullable', 'date'],
        ]);

        $year = AcademicYear::query()->findOrFail($validated['academic_year_id']);
        $studentIds = isset($validated['student_id']) ? [(int) $validated['student_id']] : null;
        $report = $this->service->forClassroom($classroom, $year, $studentIds);

        abort_if($report['entries']->isEmpty(), 404, 'Tidak ada siswa untuk dicetak.');
        abort_if(
            $report['entries']->count() > self::MAX_STUDENTS_PER_PDF,
            422,
            'Jumlah siswa terlalu banyak untuk satu PDF (maksimal ' . self::MAX_STUDENTS_PER_PDF . ').'
        );

        @set_time_limit(300);

        $school = SchoolSetting::query()->first();
        $printDate = Carbon::parse($validated['tanggal'] ?? now())->locale('id');

        $pdf = Pdf::loadView('report-cards.pdf', [
            'report' => $report,
            'school' => $school,
            'logo' => $this->logoDataUri($school),
            'printDate' => $printDate,
        ])
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4', 'portrait');

        $suffix = $studentIds
            ? Str::slug((string) $report['entries']->first()['student']->nis . '-' . $report['entries']->first()['student']->nama_lengkap)
            : Str::slug((string) $classroom->nama_kelas);
        $fileName = 'rapor-' . $suffix . '-' . Str::slug($year->tahun_ajaran . '-' . $year->semester) . '.pdf';

        return $pdf->stream($fileName);
    }

    private function downloadLeger(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ]);

        $year = AcademicYear::query()->findOrFail($validated['academic_year_id']);
        $report = $this->service->forClassroom($classroom, $year);
        $fileName = 'leger-' . Str::slug($classroom->nama_kelas . '-' . $year->tahun_ajaran . '-' . $year->semester) . '.xlsx';

        return Excel::download(new ReportCardLegerExport($report), $fileName);
    }

    private function homeroomClassroom(): Classroom
    {
        $teacher = SchoolIdentity::waliTeacherFor(auth()->user());
        abort_unless($teacher, 403, 'Anda tidak terdaftar sebagai wali kelas.');

        return Classroom::query()->with('major')->findOrFail($teacher->wali_classroom_id);
    }

    private function resolveYear(Request $request): ?AcademicYear
    {
        $yearId = $request->integer('academic_year_id');

        return $yearId
            ? AcademicYear::query()->findOrFail($yearId)
            : (AcademicYear::query()->where('is_active', true)->first() ?? AcademicYear::query()->orderByDesc('id')->first());
    }

    private function years()
    {
        return AcademicYear::query()->orderByDesc('tahun_ajaran')->orderByDesc('semester')->get();
    }

    private function logoDataUri(?SchoolSetting $school): ?string
    {
        $path = $school?->logo;

        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    }
}
