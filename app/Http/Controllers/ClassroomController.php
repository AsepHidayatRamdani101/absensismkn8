<?php

namespace App\Http\Controllers;

use App\Exports\ClassroomsExport;
use App\Exports\TemplateExport;
use App\Imports\ClassroomsImport;
use App\Models\Classroom;
use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ClassroomController extends Controller
{
    /**
     * Menampilkan halaman data kelas.
     */
    public function index()
    {
        $classrooms = Classroom::with('major')
            ->orderBy('tingkat')
            ->orderBy('rombel')
            ->get();

        $majors = Major::orderBy('nama_jurusan')->get();

        return view(
            'admin.classrooms.index',
            compact('classrooms', 'majors')
        );
    }

    /**
     * Menyimpan data kelas baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'major_id'    => 'required|exists:majors,id',
            'kode_kelas'  => 'required|max:20|unique:classrooms,kode_kelas',
            'nama_kelas'  => 'required|max:100',
            'tingkat'     => 'required|in:X,XI,XII',
            'rombel'      => 'required|max:10',
        ]);

        Classroom::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data kelas berhasil ditambahkan.'
        ]);
    }

    /**
     * Mengambil data untuk modal edit.
     */
    public function edit(Classroom $classroom)
    {
        return response()->json($classroom);
    }

    /**
     * Memperbarui data kelas.
     */
    public function update(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'major_id' => 'required|exists:majors,id',

            'kode_kelas' => [
                'required',
                'max:20',
                Rule::unique('classrooms')
                    ->ignore($classroom->id),
            ],

            'nama_kelas' => 'required|max:100',

            'tingkat' => 'required|in:X,XI,XII',

            'rombel' => 'required|max:10',
        ]);

        $classroom->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data kelas berhasil diperbarui.'
        ]);
    }

    /**
     * Menghapus data kelas.
     */
    public function destroy(Classroom $classroom)
    {
        /*
        // Aktifkan nanti jika tabel siswa sudah dibuat
        if ($classroom->students()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa.'
            ], 422);
        }
        */

        $classroom->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data kelas berhasil dihapus.'
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new ClassroomsImport(), $request->file('file'));

        return redirect()->route('classrooms.index')->with('success', 'Import data kelas berhasil.');
    }

    public function export()
    {
        return Excel::download(new ClassroomsExport(), 'master-kelas.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['major_kode_jurusan', 'kode_kelas', 'nama_kelas', 'tingkat', 'rombel'],
                [['RPL', 'X-RPL-1', 'X RPL 1', 'X', '1']]
            ),
            'format-import-kelas.xlsx'
        );
    }
}