<?php

namespace App\Http\Controllers;

use App\Exports\SchedulesExport;
use App\Exports\TemplateExport;
use App\Imports\SchedulesImport;
use App\Models\Schedule;
use App\Models\TeacherSubject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::with([
            'teacherSubject.teacher',
            'teacherSubject.subject',
            'teacherSubject.classroom.major',
            'teacherSubject.academicYear'
        ])->latest()->get();

        $teacherSubjects = TeacherSubject::with(['teacher', 'subject', 'classroom', 'academicYear'])
            ->latest()
            ->get();

        $filterTingkats = $schedules
            ->pluck('teacherSubject.classroom.tingkat')
            ->filter()
            ->unique()
            ->values();

        $filterJurusans = $schedules
            ->pluck('teacherSubject.classroom.major.nama_jurusan')
            ->filter()
            ->unique()
            ->values();

        $filterKelas = $schedules
            ->map(function ($schedule) {
                return [
                    'nama_kelas' => $schedule->teacherSubject->classroom->nama_kelas ?? null,
                    'tingkat' => $schedule->teacherSubject->classroom->tingkat ?? null,
                    'jurusan' => $schedule->teacherSubject->classroom->major->nama_jurusan ?? null,
                ];
            })
            ->filter(function ($item) {
                return !empty($item['nama_kelas']);
            })
            ->unique(function ($item) {
                return ($item['nama_kelas'] ?? '') . '|' . ($item['tingkat'] ?? '') . '|' . ($item['jurusan'] ?? '');
            })
            ->values();

        return view('admin.schedules.index', compact(
            'schedules',
            'teacherSubjects',
            'filterTingkats',
            'filterJurusans',
            'filterKelas'
        ));
    }

    public function store(Request $request)
    {
        $this->normalizeTimeInput($request, 'jam_mulai');
        $this->normalizeTimeInput($request, 'jam_selesai');

        $validated = $request->validate([
            'teacher_subject_id' => [
                'required',
                'exists:teacher_subjects,id',
                Rule::unique('schedules')->where(function ($query) use ($request) {
                    return $query
                        ->where('hari', $request->hari)
                        ->where('jam_mulai', $request->jam_mulai)
                        ->where('jam_selesai', $request->jam_selesai);
                }),
            ],
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'ruangan' => 'nullable|max:100',
        ]);

        $validated['ruangan'] = isset($validated['ruangan']) ? trim($validated['ruangan']) : null;
        if ($validated['ruangan'] === '') {
            $validated['ruangan'] = null;
        }

        $teacherSubject = TeacherSubject::findOrFail($validated['teacher_subject_id']);

        $conflictMessage = $this->checkScheduleConflict(
            null,
            $teacherSubject->teacher_id,
            $teacherSubject->classroom_id,
            $validated['hari'],
            $validated['jam_mulai'],
            $validated['jam_selesai'],
            $validated['ruangan']
        );

        if ($conflictMessage !== null) {
            return response()->json([
                'success' => false,
                'message' => $conflictMessage,
            ], 422);
        }

        Schedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data jadwal berhasil ditambahkan.'
        ]);
    }

    public function edit(Schedule $schedule)
    {
        return response()->json($schedule);
    }

    public function update(Request $request, Schedule $schedule)
    {
        $this->normalizeTimeInput($request, 'jam_mulai');
        $this->normalizeTimeInput($request, 'jam_selesai');

        $validated = $request->validate([
            'teacher_subject_id' => [
                'required',
                'exists:teacher_subjects,id',
                Rule::unique('schedules')->ignore($schedule->id)->where(function ($query) use ($request) {
                    return $query
                        ->where('hari', $request->hari)
                        ->where('jam_mulai', $request->jam_mulai)
                        ->where('jam_selesai', $request->jam_selesai);
                }),
            ],
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'ruangan' => 'nullable|max:100',
        ]);

        $validated['ruangan'] = isset($validated['ruangan']) ? trim($validated['ruangan']) : null;
        if ($validated['ruangan'] === '') {
            $validated['ruangan'] = null;
        }

        $teacherSubject = TeacherSubject::findOrFail($validated['teacher_subject_id']);

        $conflictMessage = $this->checkScheduleConflict(
            $schedule->id,
            $teacherSubject->teacher_id,
            $teacherSubject->classroom_id,
            $validated['hari'],
            $validated['jam_mulai'],
            $validated['jam_selesai'],
            $validated['ruangan']
        );

        if ($conflictMessage !== null) {
            return response()->json([
                'success' => false,
                'message' => $conflictMessage,
            ], 422);
        }

        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data jadwal berhasil diperbarui.'
        ]);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data jadwal berhasil dihapus.'
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new SchedulesImport(), $request->file('file'));

        return redirect()->route('schedules.index')->with('success', 'Import data jadwal berhasil.');
    }

    public function export()
    {
        return Excel::download(new SchedulesExport(), 'master-jadwal.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['teacher_subject_id', 'hari', 'jam_mulai', 'jam_selesai', 'ruangan'],
                [['1', 'Senin', '07:00', '08:40', 'Lab RPL 1']]
            ),
            'format-import-jadwal.xlsx'
        );
    }

    private function checkScheduleConflict(
        ?int $ignoreScheduleId,
        int $teacherId,
        int $classroomId,
        string $hari,
        string $jamMulai,
        string $jamSelesai,
        ?string $ruangan
    ): ?string {
        $baseQuery = Schedule::query()
            ->where('hari', $hari)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai);

        if ($ignoreScheduleId !== null) {
            $baseQuery->where('id', '!=', $ignoreScheduleId);
        }

        $teacherConflict = (clone $baseQuery)
            ->whereHas('teacherSubject', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->exists();

        if ($teacherConflict) {
            return 'Jadwal bentrok: guru sudah mengajar pada jam tersebut.';
        }

        $classroomConflict = (clone $baseQuery)
            ->whereHas('teacherSubject', function ($query) use ($classroomId) {
                $query->where('classroom_id', $classroomId);
            })
            ->exists();

        if ($classroomConflict) {
            return 'Jadwal bentrok: kelas sudah memiliki jadwal pada jam tersebut.';
        }

        if ($ruangan !== null) {
            $roomConflict = (clone $baseQuery)
                ->where('ruangan', $ruangan)
                ->exists();

            if ($roomConflict) {
                return 'Jadwal bentrok: ruangan sudah dipakai pada jam tersebut.';
            }
        }

        return null;
    }

    private function normalizeTimeInput(Request $request, string $field): void
    {
        $value = $request->input($field);

        if (!is_string($value) || trim($value) === '') {
            return;
        }

        // Normalize HH:MM:SS into HH:MM to satisfy date_format:H:i validation.
        $normalized = substr(trim($value), 0, 5);
        $request->merge([$field => $normalized]);
    }
}
