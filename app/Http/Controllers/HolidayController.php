<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

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
            'tanggal' => 'required|date',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal',
            'keterangan' => 'required|string|max:255',
            'is_national' => 'nullable|boolean',
            'is_pjj' => 'nullable|boolean',
        ]);

        $validated['tanggal_akhir'] = $validated['tanggal_akhir'] ?? $validated['tanggal'];
        if ($this->overlapsExistingHoliday($validated['tanggal'], $validated['tanggal_akhir'])) {
            return response()->json([
                'message' => 'Rentang tanggal bertabrakan dengan data hari libur lain.',
                'errors' => ['tanggal' => ['Pilih rentang tanggal yang tidak bertabrakan dengan data lain.']],
            ], 422);
        }

        $validated['is_national'] = $request->boolean('is_national');
        $validated['is_pjj'] = $request->boolean('is_pjj');

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
            'tanggal' => 'required|date',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal',
            'keterangan' => 'required|string|max:255',
            'is_national' => 'nullable|boolean',
            'is_pjj' => 'nullable|boolean',
        ]);

        $validated['tanggal_akhir'] = $validated['tanggal_akhir'] ?? $validated['tanggal'];
        if ($this->overlapsExistingHoliday($validated['tanggal'], $validated['tanggal_akhir'], $holiday->id)) {
            return response()->json([
                'message' => 'Rentang tanggal bertabrakan dengan data hari libur lain.',
                'errors' => ['tanggal' => ['Pilih rentang tanggal yang tidak bertabrakan dengan data lain.']],
            ], 422);
        }

        $validated['is_national'] = $request->boolean('is_national');
        $validated['is_pjj'] = $request->boolean('is_pjj');

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

    private function overlapsExistingHoliday(string $startDate, string $endDate, ?int $ignoreId = null): bool
    {
        return Holiday::query()
            ->when($ignoreId !== null, fn($query) => $query->where('id', '!=', $ignoreId))
            ->whereDate('tanggal', '<=', $endDate)
            ->where(function ($query) use ($startDate) {
                $query->where(function ($query) use ($startDate) {
                    $query->whereNull('tanggal_akhir')
                        ->whereDate('tanggal', '>=', $startDate);
                })->orWhereDate('tanggal_akhir', '>=', $startDate);
            })
            ->exists();
    }
}
