<?php

namespace App\Http\Controllers;

use App\Exports\TeachersExport;
use App\Exports\TemplateExport;
use App\Imports\TeachersImport;
use App\Models\Classroom;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $hasWaliKelasColumns = $this->hasWaliKelasColumns();
        $kurikulumFilter = $request->string('kurikulum_filter')->toString();

        $teacherQuery = Teacher::query();

        if ($hasWaliKelasColumns) {
            $teacherQuery->with('waliClassroom');
        }

        if ($kurikulumFilter === 'kurikulum') {
            $teacherQuery->where('is_kurikulum', true);
        } elseif ($kurikulumFilter === 'non_kurikulum') {
            $teacherQuery->where(function ($query) {
                $query->whereNull('is_kurikulum')->orWhere('is_kurikulum', false);
            });
        }

        $teachers = $teacherQuery->latest()->get();

        $accountIdentifiers = $teachers
            ->flatMap(fn(Teacher $teacher) => array_filter([
                trim((string) $teacher->nip),
                trim((string) $teacher->nuptk),
            ]))
            ->unique()
            ->values();

        $existingAccounts = User::query()
            ->whereIn('email', $accountIdentifiers)
            ->pluck('email')
            ->all();

        $existingAccountLookup = array_fill_keys($existingAccounts, true);

        $teachers->each(function (Teacher $teacher) use ($existingAccountLookup) {
            $teacher->has_account = isset($existingAccountLookup[trim((string) $teacher->nip)])
                || isset($existingAccountLookup[trim((string) $teacher->nuptk)]);
        });

        $classrooms = $hasWaliKelasColumns
            ? Classroom::orderBy('nama_kelas')->get()
            : collect();

        return view(
            'admin.teachers.index',
            compact('teachers', 'classrooms', 'kurikulumFilter')
        );
    }

    public function store(Request $request)
    {
        $hasWaliKelasColumns = $this->hasWaliKelasColumns();

        $rules = [

            'nip' => 'nullable|unique:teachers',

            'nuptk' => 'nullable',

            'nama_lengkap' => 'required',

            'jabatan' => 'required|in:guru,kepala_program,kepala_sekolah,bk',

            'jenis_kelamin' => 'required|in:L,P',

            'no_hp' => 'nullable',

            'alamat' => 'nullable',

            'is_kurikulum' => 'nullable|boolean',

        ];

        if ($hasWaliKelasColumns) {
            $rules['wali_classroom_id'] = [
                'nullable',
                'exists:classrooms,id',
                Rule::unique('teachers', 'wali_classroom_id'),
            ];
        }

        $validated = $request->validate($rules);

        if ($hasWaliKelasColumns) {
            $validated['wali_classroom_id'] = $validated['wali_classroom_id'] ?: null;
            $validated['is_wali_kelas'] = !is_null($validated['wali_classroom_id']);
        }

        $validated['is_kurikulum'] = $request->boolean('is_kurikulum');

        $teacher = Teacher::create($validated);

        $this->syncTeacherRoles($teacher);

        return response()->json([
            'success' => true,
            'message' => 'Guru berhasil ditambahkan'
        ]);
    }

    public function edit(Teacher $teacher)
    {
        return response()->json(
            $this->hasWaliKelasColumns()
                ? $teacher->load('waliClassroom')
                : $teacher
        );
    }

    public function update(Request $request, Teacher $teacher)
    {
        $hasWaliKelasColumns = $this->hasWaliKelasColumns();

        $rules = [

            'nip' => [
                'nullable',
                Rule::unique('teachers')
                    ->ignore($teacher->id)
            ],

            'nuptk' => 'nullable',

            'nama_lengkap' => 'required',

            'jabatan' => 'required|in:guru,kepala_program,kepala_sekolah,bk',

            'jenis_kelamin' => 'required|in:L,P',

            'no_hp' => 'nullable',

            'alamat' => 'nullable',

            'is_kurikulum' => 'nullable|boolean',

        ];

        if ($hasWaliKelasColumns) {
            $rules['wali_classroom_id'] = [
                'nullable',
                'exists:classrooms,id',
                Rule::unique('teachers', 'wali_classroom_id')->ignore($teacher->id),
            ];
        }

        $validated = $request->validate($rules);

        if ($hasWaliKelasColumns) {
            $validated['wali_classroom_id'] = $validated['wali_classroom_id'] ?: null;
            $validated['is_wali_kelas'] = !is_null($validated['wali_classroom_id']);
        }

        $validated['is_kurikulum'] = $request->boolean('is_kurikulum');

        $teacher->update($validated);

        $this->syncTeacherRoles($teacher->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Guru berhasil diperbarui'
        ]);
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guru berhasil dihapus'
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $importer = new TeachersImport();

        Excel::import($importer, $request->file('file'));

        return redirect()->route('teachers.index')->with('success', $importer->getSuccessMessage());
    }

    public function export()
    {
        return Excel::download(new TeachersExport(), 'master-guru.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['nip', 'nuptk', 'nama_lengkap', 'jabatan', 'jenis_kelamin', 'no_hp', 'alamat', 'is_wali_kelas', 'wali_kelas', 'is_kurikulum'],
                [['198801012010011001', '1234567890123456', 'Andi Wijaya', 'guru', 'L', '08123456789', 'Jl. Pendidikan', '0', '', '0']]
            ),
            'format-import-guru.xlsx'
        );
    }

    public function generateAccounts()
    {
        Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kurikulum', 'guard_name' => 'web']);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        Teacher::query()->orderBy('id')->chunk(200, function ($teachers) use (&$created, &$updated, &$skipped) {
            foreach ($teachers as $teacher) {
                $username = trim((string) $teacher->nip);

                if ($username === '') {
                    $username = trim((string) $teacher->nuptk);
                }

                if ($username === '') {
                    $skipped++;
                    continue;
                }

                $user = User::updateOrCreate(
                    ['email' => $username],
                    [
                        'name' => $teacher->nama_lengkap,
                        'password' => Hash::make('guru12345'),
                    ]
                );

                if ($user->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                if (!$user->hasRole('guru')) {
                    $user->assignRole('guru');
                }

                $this->syncTeacherRoles($teacher, $user);
            }
        });

        return redirect()->route('teachers.index')->with(
            'success',
            "Generate akun guru selesai. Dibuat: {$created}, Diperbarui: {$updated}, Dilewati (NIP/NUPTK kosong): {$skipped}."
        );
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:teachers,id',
        ]);

        Teacher::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' data guru berhasil dihapus',
        ]);
    }

    private function hasWaliKelasColumns(): bool
    {
        return Schema::hasColumns('teachers', ['is_wali_kelas', 'wali_classroom_id']);
    }

    private function syncTeacherRoles(Teacher $teacher, ?User $user = null): void
    {
        $username = trim((string) $teacher->nip);

        if ($username === '') {
            $username = trim((string) $teacher->nuptk);
        }

        if ($username === '') {
            return;
        }

        $user = $user ?? User::query()->where('email', $username)->first();

        if (!$user) {
            return;
        }

        Role::firstOrCreate(['name' => 'kurikulum', 'guard_name' => 'web']);

        if ($teacher->is_kurikulum) {
            if (!$user->hasRole('kurikulum')) {
                $user->assignRole('kurikulum');
            }

            return;
        }

        if ($user->hasRole('kurikulum')) {
            $user->removeRole('kurikulum');
        }
    }
}
