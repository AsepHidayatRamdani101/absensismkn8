<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use App\Support\ReferenceCache;
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
            'monthly_achievement_duration_days' => 'required|integer|min:0|max:31',
            'report_daily_weight' => 'required|integer|min:0|max:100',
            'report_uts_weight' => 'required|integer|min:0|max:100',
            'report_uas_weight' => 'required|integer|min:0|max:100',
            'report_kkm' => 'required|integer|min:40|max:95',
            'report_city' => 'nullable|string|max:100',
            'principal_name' => 'nullable|string|max:255',
            'principal_nip' => 'nullable|string|max:50',
        ], [
            'report_kkm.min' => 'KKM minimal 40.',
            'report_kkm.max' => 'KKM maksimal 95.',
        ]);

        $weightTotal = $validated['report_daily_weight'] + $validated['report_uts_weight'] + $validated['report_uas_weight'];

        if ($weightTotal !== 100) {
            return back()->withInput()->withErrors([
                'report_daily_weight' => "Jumlah bobot Harian + UTS + UAS harus 100% (saat ini {$weightTotal}%).",
            ]);
        }

        $setting = SchoolSetting::firstOrCreate([], [
            'nama_sekolah' => $validated['nama_sekolah'],
            'jam_masuk' => $validated['jam_masuk'],
            'batas_terlambat' => $validated['batas_terlambat'],
            'monthly_achievement_duration_days' => $validated['monthly_achievement_duration_days'],
        ]);

        if ($request->hasFile('logo')) {
            if (!empty($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }

            $validated['logo'] = $request->file('logo')->store('school-settings', 'public');
        }

        $setting->update($validated);
        ReferenceCache::forgetSchoolSettings();

        return redirect()
            ->route('school-settings.index')
            ->with('success', 'Profile sekolah berhasil diperbarui.');
    }
}
