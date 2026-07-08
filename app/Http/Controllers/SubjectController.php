<?php

namespace App\Http\Controllers;

use App\Exports\SubjectsExport;
use App\Exports\TemplateExport;
use App\Imports\SubjectsImport;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('kode_mapel')->get();

        return view('admin.subjects.index', compact('subjects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_mapel' => 'required|max:20|unique:subjects,kode_mapel',
            'nama_mapel' => 'required|max:255',
            'kategori' => 'required|in:Umum,Kejuruan,Muatan Lokal',
            'jam_per_minggu' => 'required|integer|min:0',
        ]);

        Subject::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data mata pelajaran berhasil ditambahkan.'
        ]);
    }

    public function edit(Subject $subject)
    {
        return response()->json($subject);
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'kode_mapel' => [
                'required',
                'max:20',
                Rule::unique('subjects')->ignore($subject->id),
            ],
            'nama_mapel' => 'required|max:255',
            'kategori' => 'required|in:Umum,Kejuruan,Muatan Lokal',
            'jam_per_minggu' => 'required|integer|min:0',
        ]);

        $subject->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data mata pelajaran berhasil diperbarui.'
        ]);
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data mata pelajaran berhasil dihapus.'
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new SubjectsImport(), $request->file('file'));

        return redirect()->route('subjects.index')->with('success', 'Import data mata pelajaran berhasil.');
    }

    public function export()
    {
        return Excel::download(new SubjectsExport(), 'master-mata-pelajaran.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['kode_mapel', 'nama_mapel', 'kategori', 'jam_per_minggu'],
                [['MTK01', 'Matematika', 'Umum', '4']]
            ),
            'format-import-mata-pelajaran.xlsx'
        );
    }
}
