<?php

namespace App\Http\Controllers;

use App\Models\StaffTu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

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
            'nip'          => 'nullable|string|max:30|unique:staff_tu,nip',
            'nama_lengkap' => 'required|string|max:255',
            'jabatan'      => 'required|in:kepala_tu,staf_tu',
            'jenis_kelamin' => 'required|in:L,P',
            'no_hp'        => 'nullable|string|max:20',
            'alamat'       => 'nullable|string|max:1000',
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
            'nip'          => ['nullable', 'string', 'max:30', Rule::unique('staff_tu', 'nip')->ignore($staffTu->id)],
            'nama_lengkap' => 'required|string|max:255',
            'jabatan'      => 'required|in:kepala_tu,staf_tu',
            'jenis_kelamin' => 'required|in:L,P',
            'no_hp'        => 'nullable|string|max:20',
            'alamat'       => 'nullable|string|max:1000',
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
}
