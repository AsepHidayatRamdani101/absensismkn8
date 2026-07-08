<?php

namespace App\Http\Controllers;

use App\Exports\MajorsExport;
use App\Exports\TemplateExport;
use App\Imports\MajorsImport;
use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MajorController extends Controller
{
    /**
     * Menampilkan halaman data jurusan
     */
    public function index()
    {
        $majors = Major::orderBy('kode_jurusan')->get();

        return view('admin.majors.index', compact('majors'));
    }

    /**
     * Menyimpan data jurusan baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_jurusan' => 'required|max:10|unique:majors,kode_jurusan',
            'nama_jurusan' => 'required|max:255',
            'singkatan'    => 'required|max:10',
        ]);

        Major::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data jurusan berhasil ditambahkan.'
        ]);
    }

    /**
     * Mengambil data untuk modal edit
     */
    public function edit(Major $major)
    {
        return response()->json($major);
    }

    /**
     * Mengupdate data jurusan
     */
    public function update(Request $request, Major $major)
    {
        $validated = $request->validate([
            'kode_jurusan' => [
                'required',
                'max:10',
                Rule::unique('majors')->ignore($major->id),
            ],
            'nama_jurusan' => 'required|max:255',
            'singkatan'    => 'required|max:10',
        ]);

        $major->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data jurusan berhasil diperbarui.'
        ]);
    }

    /**
     * Menghapus data jurusan
     */
    public function destroy(Major $major)
    {
        // Opsional: cegah jika masih dipakai oleh kelas
        if ($major->classrooms()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Jurusan tidak dapat dihapus karena masih digunakan oleh data kelas.'
            ], 422);
        }

        $major->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data jurusan berhasil dihapus.'
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new MajorsImport(), $request->file('file'));

        return redirect()->route('majors.index')->with('success', 'Import data jurusan berhasil.');
    }

    public function export()
    {
        return Excel::download(new MajorsExport(), 'master-jurusan.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['kode_jurusan', 'nama_jurusan', 'singkatan'],
                [['RPL', 'Rekayasa Perangkat Lunak', 'RPL']]
            ),
            'format-import-jurusan.xlsx'
        );
    }
}