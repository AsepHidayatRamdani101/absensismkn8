<?php

namespace App\Http\Controllers;

use App\Exports\StaffTuExport;
use App\Exports\TemplateExport;
use App\Imports\StaffTuImport;
use App\Models\StaffTu;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class StaffTuController extends Controller
{
    public function index()
    {
        $staffList = StaffTu::query()->latest()->get();

        return view('admin.staff_tu.index', compact('staffList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip'           => 'nullable|string|max:30|unique:staff_tu,nip',
            'nama_lengkap'  => 'required|string|max:255',
            'jabatan'       => 'required|in:kepala_tu,staf_tu',
            'jenis_kelamin' => 'required|in:L,P',
            'no_hp'         => 'nullable|string|max:20',
            'alamat'        => 'nullable|string|max:1000',
        ]);

        StaffTu::create($validated);

        return response()->json(['success' => true, 'message' => 'Staf TU berhasil ditambahkan.']);
    }

    public function edit(StaffTu $staffTu)
    {
        return response()->json($staffTu);
    }

    public function update(Request $request, StaffTu $staffTu)
    {
        $validated = $request->validate([
            'nip'           => ['nullable', 'string', 'max:30', Rule::unique('staff_tu', 'nip')->ignore($staffTu->id)],
            'nama_lengkap'  => 'required|string|max:255',
            'jabatan'       => 'required|in:kepala_tu,staf_tu',
            'jenis_kelamin' => 'required|in:L,P',
            'no_hp'         => 'nullable|string|max:20',
            'alamat'        => 'nullable|string|max:1000',
        ]);

        $staffTu->update($validated);

        return response()->json(['success' => true, 'message' => 'Staf TU berhasil diperbarui.']);
    }

    public function destroy(StaffTu $staffTu)
    {
        $staffTu->delete();

        return response()->json(['success' => true, 'message' => 'Staf TU berhasil dihapus.']);
    }

    public function destroyMultiple(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:staff_tu,id',
        ]);

        StaffTu::whereIn('id', $validated['ids'])->delete();

        return response()->json(['success' => true, 'message' => count($validated['ids']) . ' staf TU berhasil dihapus.']);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);

        $importer = new StaffTuImport();
        Excel::import($importer, $request->file('file'));

        return redirect()->route('staff-tu.index')->with('success', $importer->getSuccessMessage());
    }

    public function export()
    {
        return Excel::download(new StaffTuExport(), 'master-staff-tu.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['nip', 'nama_lengkap', 'jabatan', 'jenis_kelamin', 'no_hp', 'alamat'],
                [['198801012010011001', 'Budi Santoso', 'staf_tu', 'L', '08123456789', 'Jl. Sekolah No. 1']]
            ),
            'format-import-staff-tu.xlsx'
        );
    }

    public function generateAccounts()
    {
        Role::firstOrCreate(['name' => 'tu', 'guard_name' => 'web']);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        StaffTu::query()->orderBy('id')->chunk(200, function ($staffList) use (&$created, &$updated, &$skipped) {
            foreach ($staffList as $staff) {
                $username = trim((string) $staff->nip);

                if ($username === '') {
                    $skipped++;
                    continue;
                }

                $user = User::updateOrCreate(
                    ['email' => $username],
                    [
                        'name'     => $staff->nama_lengkap,
                        'password' => Hash::make('tu12345'),
                    ]
                );

                if ($user->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                if (!$user->hasRole('tu')) {
                    $user->assignRole('tu');
                }
            }
        });

        return redirect()->route('staff-tu.index')->with(
            'success',
            "Generate akun TU selesai. Dibuat: {$created}, Diperbarui: {$updated}, Dilewati (NIP kosong): {$skipped}."
        );
    }
}
