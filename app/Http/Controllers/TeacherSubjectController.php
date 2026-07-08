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
    public function index()
    {
        $teacherSubjects = TeacherSubject::with(['teacher', 'subject', 'classroom', 'academicYear'])
            ->latest()
            ->get();

        $teachers = Teacher::orderBy('nama_lengkap')->get();
        $subjects = Subject::orderBy('nama_mapel')->get();
        $classrooms = Classroom::orderBy('nama_kelas')->get();
        $academicYears = AcademicYear::orderByDesc('tahun_ajaran')->get();

        return view('admin.teacher_subjects.index', compact(
            'teacherSubjects',
            'teachers',
            'subjects',
            'classrooms',
            'academicYears'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => [
                'required',
                'exists:teachers,id',
                Rule::unique('teacher_subjects')->where(function ($query) use ($request) {
                    return $query
                        ->where('subject_id', $request->subject_id)
                        ->where('classroom_id', $request->classroom_id)
                        ->where('academic_year_id', $request->academic_year_id);
                }),
            ],
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        TeacherSubject::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data guru pengampu berhasil ditambahkan.'
        ]);
    }

    public function edit(TeacherSubject $teacherSubject)
    {
        return response()->json($teacherSubject);
    }

    public function update(Request $request, TeacherSubject $teacherSubject)
    {
        $validated = $request->validate([
            'teacher_id' => [
                'required',
                'exists:teachers,id',
                Rule::unique('teacher_subjects')->ignore($teacherSubject->id)->where(function ($query) use ($request) {
                    return $query
                        ->where('subject_id', $request->subject_id)
                        ->where('classroom_id', $request->classroom_id)
                        ->where('academic_year_id', $request->academic_year_id);
                }),
            ],
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $teacherSubject->update($validated);

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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new TeacherSubjectsImport(), $request->file('file'));

        return redirect()->route('teacher-subjects.index')->with('success', 'Import data guru pengampu berhasil.');
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
