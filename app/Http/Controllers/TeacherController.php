<?php

namespace App\Http\Controllers;

use App\Exports\TeachersExport;
use App\Exports\TemplateExport;
use App\Imports\TeachersImport;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::latest()->get();

        return view(
            'admin.teachers.index',
            compact('teachers')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([

            'nip' => 'nullable|unique:teachers',

            'nuptk' => 'nullable',

            'nama_lengkap' => 'required',

            'jenis_kelamin' => 'required|in:L,P',

            'no_hp' => 'nullable',

            'alamat' => 'nullable',

        ]);

        Teacher::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Guru berhasil ditambahkan'
        ]);
    }

    public function edit(Teacher $teacher)
    {
        return response()->json($teacher);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([

            'nip' => [
                'nullable',
                Rule::unique('teachers')
                    ->ignore($teacher->id)
            ],

            'nuptk' => 'nullable',

            'nama_lengkap' => 'required',

            'jenis_kelamin' => 'required|in:L,P',

            'no_hp' => 'nullable',

            'alamat' => 'nullable',

        ]);

        $teacher->update($validated);

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

        Excel::import(new TeachersImport(), $request->file('file'));

        return redirect()->route('teachers.index')->with('success', 'Import data guru berhasil.');
    }

    public function export()
    {
        return Excel::download(new TeachersExport(), 'master-guru.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['nip', 'nuptk', 'nama_lengkap', 'jenis_kelamin', 'no_hp', 'alamat'],
                [['198801012010011001', '1234567890123456', 'Andi Wijaya', 'L', '08123456789', 'Jl. Pendidikan']]
            ),
            'format-import-guru.xlsx'
        );
    }

    public function generateAccounts()
    {
        Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        Teacher::query()->orderBy('id')->chunk(200, function ($teachers) use (&$created, &$updated, &$skipped) {
            foreach ($teachers as $teacher) {
                $username = trim((string) $teacher->nip);

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

                $user->syncRoles(['guru']);
            }
        });

        return redirect()->route('teachers.index')->with(
            'success',
            "Generate akun guru selesai. Dibuat: {$created}, Diperbarui: {$updated}, Dilewati (NIP kosong): {$skipped}."
        );
    }
}