<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolSettingController extends Controller
{
    public function index()
    {
        $setting = SchoolSetting::first();

        return view('admin.school_settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama_sekolah' => 'required|string|max:255',
            'npsn' => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'jam_masuk' => 'required|date_format:H:i',
            'batas_terlambat' => 'required|integer|min:0|max:240',
        ]);

        $setting = SchoolSetting::firstOrCreate([], [
            'nama_sekolah' => $validated['nama_sekolah'],
            'jam_masuk' => $validated['jam_masuk'],
            'batas_terlambat' => $validated['batas_terlambat'],
        ]);

        if ($request->hasFile('logo')) {
            if (!empty($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }

            $validated['logo'] = $request->file('logo')->store('school-settings', 'public');
        }

        $setting->update($validated);

        return redirect()
            ->route('school-settings.index')
            ->with('success', 'Profile sekolah berhasil diperbarui.');
    }
}
