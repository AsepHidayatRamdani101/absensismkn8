<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::query()
            ->orderByDesc('tanggal')
            ->get();

        return view('admin.holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date|unique:holidays,tanggal',
            'keterangan' => 'required|string|max:255',
            'is_national' => 'nullable|boolean',
        ]);

        $validated['is_national'] = $request->boolean('is_national');

        Holiday::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Hari libur berhasil ditambahkan.'
        ]);
    }

    public function edit(Holiday $holiday)
    {
        return response()->json($holiday);
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'tanggal' => [
                'required',
                'date',
                Rule::unique('holidays', 'tanggal')->ignore($holiday->id),
            ],
            'keterangan' => 'required|string|max:255',
            'is_national' => 'nullable|boolean',
        ]);

        $validated['is_national'] = $request->boolean('is_national');

        $holiday->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Hari libur berhasil diperbarui.'
        ]);
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hari libur berhasil dihapus.'
        ]);
    }
}
