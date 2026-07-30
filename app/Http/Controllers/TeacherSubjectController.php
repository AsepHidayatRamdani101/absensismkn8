<?php

namespace App\Http\Controllers;

use App\Exports\TeacherSubjectsExport;
use App\Exports\TemplateExport;
use App\Imports\TeacherSubjectsImport;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class TeacherSubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = TeacherSubject::with(['teacher', 'subject', 'classroom', 'academicYear'])->latest();

        $teacherSubjects = $query->get();

        $teachers = Teacher::orderBy('nama_lengkap')->get();
        $subjects = Subject::orderBy('nama_mapel')->get();
        $classrooms = Classroom::orderBy('nama_kelas')->get();
        $academicYears = AcademicYear::orderByDesc('tahun_ajaran')->get();
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        return view('admin.teacher_subjects.index', compact(
            'teacherSubjects',
            'teachers',
            'subjects',
            'classrooms',
            'academicYears',
            'activeAcademicYear'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id'      => 'required|exists:teachers,id',
            'subject_id'      => 'required|exists:subjects,id',
            'classroom_id'    => 'required|array',
            'classroom_id.*'  => 'integer|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $classroomIds = $validated['classroom_id'];
        $createdCount = 0;

        foreach ($classroomIds as $classroomId) {
            // Check if already exists
            $exists = TeacherSubject::where('teacher_id', $validated['teacher_id'])
                ->where('subject_id', $validated['subject_id'])
                ->where('classroom_id', $classroomId)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->exists();

            if (!$exists) {
                TeacherSubject::create([
                    'teacher_id'      => $validated['teacher_id'],
                    'subject_id'      => $validated['subject_id'],
                    'classroom_id'    => $classroomId,
                    'academic_year_id' => $validated['academic_year_id'],
                ]);
                $createdCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $createdCount > 0
                ? "{$createdCount} data guru pengampu berhasil ditambahkan."
                : 'Data guru pengampu sudah ada.'
        ]);
    }

    public function edit(TeacherSubject $teacherSubject)
    {
        return response()->json($teacherSubject);
    }

    public function update(Request $request, TeacherSubject $teacherSubject)
    {
        $validated = $request->validate([
            'teacher_id'      => 'required|exists:teachers,id',
            'subject_id'      => 'required|exists:subjects,id',
            'classroom_id'    => 'required|array',
            'classroom_id.*'  => 'integer|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        // Edit hanya record yang dipilih agar data lain tidak ikut terhapus.
        $classroomId = (int) ($validated['classroom_id'][0] ?? 0);

        $teacherSubject->update([
            'teacher_id'       => $validated['teacher_id'],
            'subject_id'       => $validated['subject_id'],
            'classroom_id'     => $classroomId,
            'academic_year_id' => $validated['academic_year_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data guru pengampu berhasil diperbarui.'
        ]);
    }

    public function destroy(TeacherSubject $teacherSubject)
    {
        $teacherSubject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data guru pengampu berhasil dihapus.'
        ]);
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:teacher_subjects,id',
        ]);

        TeacherSubject::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' data guru pengampu berhasil dihapus.'
        ]);
    }

    public function getAssignedClassrooms(Request $request)
    {
        $subjectId      = $request->input('subject_id');
        $academicYearId = $request->input('academic_year_id');
        $excludeTeacherSubjectId = $request->input('exclude_id'); // untuk mode edit

        $query = TeacherSubject::query();

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        if ($excludeTeacherSubjectId) {
            // Exclude semua record yang se-teacher+subject+academicYear dengan record ini
            $record = TeacherSubject::find($excludeTeacherSubjectId);
            if ($record) {
                $query->where(function ($q) use ($record) {
                    $q->where('teacher_id', '!=', $record->teacher_id)
                        ->orWhere('subject_id', '!=', $record->subject_id)
                        ->orWhere('academic_year_id', '!=', $record->academic_year_id);
                });
            }
        }

        $assignedClassroomIds = $query->pluck('classroom_id')->unique()->values();

        return response()->json($assignedClassroomIds);
    }

    public function getFullSubjects(Request $request)
    {
        $academicYearId = $request->input('academic_year_id');
        $totalClassrooms = Classroom::count();

        $query = TeacherSubject::query();
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        // Hitung jumlah kelas unik per subject
        $assignedCounts = $query->get(['subject_id', 'classroom_id'])
            ->groupBy('subject_id')
            ->map(function ($rows) {
                return $rows->pluck('classroom_id')->unique()->count();
            });

        // Subject yang sudah penuh (jumlah kelas assigned >= total kelas)
        $fullSubjectIds = $assignedCounts->filter(function ($count) use ($totalClassrooms) {
            return $count >= $totalClassrooms;
        })->keys()->values();

        return response()->json($fullSubjectIds);
    }

    public function getClassrooms($teacherId, $subjectId, $academicYearId)
    {
        $classroomIds = TeacherSubject::where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('classroom_id')
            ->toArray();

        return response()->json($classroomIds);
    }

    public function searchTeachers(Request $request)
    {
        $search = $request->input('q', '');

        $teachers = Teacher::where('nama_lengkap', 'like', '%' . $search . '%')
            ->orderBy('nama_lengkap')
            ->take(20)
            ->get(['id', 'nama_lengkap']);

        return response()->json([
            'results' => $teachers->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'text' => $teacher->nama_lengkap
                ];
            })
        ]);
    }

    public function searchSubjects(Request $request)
    {
        $search = $request->input('q', '');

        $subjects = Subject::where('nama_mapel', 'like', '%' . $search . '%')
            ->orderBy('nama_mapel')
            ->take(20)
            ->get(['id', 'nama_mapel']);

        return response()->json([
            'results' => $subjects->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'text' => $subject->nama_mapel
                ];
            })
        ]);
    }

    public function searchClassrooms(Request $request)
    {
        $search = $request->input('q', '');

        $classrooms = Classroom::where('nama_kelas', 'like', '%' . $search . '%')
            ->orderBy('nama_kelas')
            ->take(20)
            ->get(['id', 'nama_kelas']);

        return response()->json([
            'results' => $classrooms->map(function ($classroom) {
                return [
                    'id' => $classroom->id,
                    'text' => $classroom->nama_kelas
                ];
            })
        ]);
    }

    public function getTeacherById($id)
    {
        $teacher = Teacher::find($id);
        if (!$teacher) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json([
            'id' => $teacher->id,
            'text' => $teacher->nama_lengkap
        ]);
    }

    public function getSubjectById($id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json([
            'id' => $subject->id,
            'text' => $subject->nama_mapel
        ]);
    }

    public function getClassroomById($id)
    {
        $classroom = Classroom::find($id);
        if (!$classroom) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json([
            'id' => $classroom->id,
            'text' => $classroom->nama_kelas
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'import_mode' => 'nullable|in:auto_create,strict_existing',
        ]);

        $autoCreateMaster = $request->input('import_mode', 'auto_create') === 'auto_create';
        $importer = new TeacherSubjectsImport($autoCreateMaster);

        Excel::import($importer, $request->file('file'));

        return redirect()->route('teacher-subjects.index')->with('success', $importer->getSuccessMessage());
    }

    public function export()
    {
        return Excel::download(new TeacherSubjectsExport(), 'master-guru-pengampu.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['teacher_nip', 'subject_kode_mapel', 'classroom_kode_kelas', 'tahun_ajaran', 'semester'],
                [['198801012010011001', 'MTK01', 'X-RPL-1', '2026/2027', 'Ganjil']]
            ),
            'format-import-guru-pengampu.xlsx'
        );
    }
}
