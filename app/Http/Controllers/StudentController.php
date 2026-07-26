<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Exports\TemplateExport;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class StudentController extends Controller
{
    private function resolveAuthenticatedStudent(): ?Student
    {
        return Student::query()
            ->with('classroom.major')
            ->where('nisn', auth()->user()->email)
            ->orWhere('nis', auth()->user()->email)
            ->first();
    }

    public function index(Request $request)
    {
        $majorFilter     = $request->input('major_id', '');
        $classroomFilter = $request->input('classroom_id', '');

        $query = Student::with('classroom.major')->latest();

        if ($majorFilter !== '') {
            $query->whereHas('classroom', fn($q) => $q->where('major_id', $majorFilter));
        }

        if ($classroomFilter !== '') {
            $query->where('classroom_id', $classroomFilter);
        }

        $students = $query->get();

        $accountIdentifiers = $students
            ->flatMap(fn(Student $student) => array_filter([
                trim((string) $student->nisn),
                trim((string) $student->nis),
            ]))
            ->unique()
            ->values();

        $existingAccounts = User::query()
            ->whereIn('email', $accountIdentifiers)
            ->pluck('email')
            ->all();

        $existingAccountLookup = array_fill_keys($existingAccounts, true);

        $students->each(function (Student $student) use ($existingAccountLookup) {
            $student->has_account = isset($existingAccountLookup[trim((string) $student->nisn)])
                || isset($existingAccountLookup[trim((string) $student->nis)]);
        });

        $majors = Major::orderBy('nama_jurusan')->get();

        $classrooms = Classroom::with('major')
            ->orderBy('tingkat')
            ->orderBy('rombel')
            ->get();

        return view(
            'admin.students.index',
            compact(
                'students',
                'classrooms',
                'majors',
                'majorFilter',
                'classroomFilter'
            )
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|unique:students',
            'nisn' => 'nullable',
            'nama_lengkap' => 'required',
            'nama_orang_tua_wali' => 'nullable|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'classroom_id' => 'required|exists:classrooms,id',
            'jabatan_kelas' => 'nullable|in:ketua_kelas,sekretaris,bendahara',
            'alamat' => 'nullable',
            'no_hp' => 'nullable',
            'no_hp_orang_tua' => 'nullable|string|max:255',
            'tinggi_badan' => 'nullable|numeric|min:0',
            'berat_badan' => 'nullable|numeric|min:0',

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

            'nama_orang_tua_wali' => 'nullable|string|max:255',

            'jenis_kelamin' => 'required|in:L,P',

            'classroom_id' => 'required|exists:classrooms,id',

            'jabatan_kelas' => 'nullable|in:ketua_kelas,sekretaris,bendahara',

            'alamat' => 'nullable',

            'no_hp' => 'nullable',

            'no_hp_orang_tua' => 'nullable|string|max:255',

            'tinggi_badan' => 'nullable|numeric|min:0',

            'berat_badan' => 'nullable|numeric|min:0',

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

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:students,id',
        ]);

        Student::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' data siswa berhasil dihapus',
        ]);
    }

    public function updateOwnIdentity(Request $request): RedirectResponse
    {
        $student = $this->resolveAuthenticatedStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa untuk akun ini tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_orang_tua_wali' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'no_hp' => 'nullable|string|max:255',
            'no_hp_orang_tua' => 'nullable|string|max:255',
            'tinggi_badan' => 'nullable|numeric|min:0',
            'berat_badan' => 'nullable|numeric|min:0',
        ]);

        $student->update($validated);

        return redirect()->route('siswa.identity.edit')->with('success', 'Identitas siswa berhasil diperbarui.');
    }

    public function editOwnIdentity()
    {
        $student = $this->resolveAuthenticatedStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa untuk akun ini tidak ditemukan.');
        }

        return view('siswa.identity.edit', compact('student'));
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
                ['nis', 'nisn', 'nama_lengkap', 'jenis_kelamin', 'classroom_kode_kelas', 'jabatan_kelas'],
                [[
                    '24001',
                    '9988776655',
                    'Budi Santoso',
                    'L',
                    'X-RPL-1',
                    'KM',
                ]]
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
