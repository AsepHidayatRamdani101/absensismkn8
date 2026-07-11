<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Exports\TemplateExport;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('classroom.major')
            ->latest()
            ->get();

        $classrooms = Classroom::with('major')
            ->orderBy('tingkat')
            ->orderBy('rombel')
            ->get();

        return view(
            'admin.students.index',
            compact(
                'students',
                'classrooms'
            )
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|unique:students',
            'nisn' => 'nullable',
            'nama_lengkap' => 'required',
            'jenis_kelamin' => 'required|in:L,P',
            'classroom_id' => 'required|exists:classrooms,id',
            'jabatan_kelas' => 'nullable|in:ketua_kelas,sekretaris,bendahara',
            'alamat' => 'nullable',
            'no_hp' => 'nullable',

        ]);

        Student::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil ditambahkan'
        ]);
    }

    public function edit(Student $student)
    {
        return response()->json($student);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([

            'nis' => [
                'required',
                Rule::unique('students')
                    ->ignore($student->id),
            ],

            'nisn' => 'nullable',

            'nama_lengkap' => 'required',

            'jenis_kelamin' => 'required|in:L,P',

            'classroom_id' => 'required|exists:classrooms,id',

            'jabatan_kelas' => 'nullable|in:ketua_kelas,sekretaris,bendahara',

            'alamat' => 'nullable',

            'no_hp' => 'nullable',

        ]);

        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil diperbarui'
        ]);
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil dihapus'
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new StudentsImport(), $request->file('file'));

        return redirect()->route('students.index')->with('success', 'Import data siswa berhasil.');
    }

    public function export()
    {
        return Excel::download(new StudentsExport(), 'master-siswa.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['nis', 'nisn', 'nama_lengkap', 'jenis_kelamin', 'classroom_kode_kelas', 'jabatan_kelas', 'alamat', 'no_hp'],
                [['24001', '9988776655', 'Budi Santoso', 'L', 'X-RPL-1', 'KM', 'Jl. Garut', '08123456789']]
            ),
            'format-import-siswa.xlsx'
        );
    }

    public function generateAccounts()
    {
        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        Student::query()->orderBy('id')->chunk(200, function ($students) use (&$created, &$updated, &$skipped) {
            foreach ($students as $student) {
                $username = trim((string) $student->nisn);

                if ($username === '') {
                    $username = trim((string) $student->nis);
                }

                if ($username === '') {
                    $skipped++;
                    continue;
                }

                $user = User::updateOrCreate(
                    ['email' => $username],
                    [
                        'name' => $student->nama_lengkap,
                        'password' => Hash::make('siswa12345'),
                    ]
                );

                if ($user->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                $user->syncRoles(['siswa']);
            }
        });

        return redirect()->route('students.index')->with(
            'success',
            "Generate akun siswa selesai. Dibuat: {$created}, Diperbarui: {$updated}, Dilewati (NISN/NIS kosong): {$skipped}."
        );
    }
}
