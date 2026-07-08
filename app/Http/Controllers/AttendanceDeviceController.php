<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDevice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceDeviceController extends Controller
{
    public function index()
    {
        $attendanceDevices = AttendanceDevice::orderBy('nama_device')->get();

        return view('admin.attendance_devices.index', compact('attendanceDevices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_device' => 'required|max:255',
            'device_code' => 'required|max:100|unique:attendance_devices,device_code',
            'jenis' => 'required|in:RFID,FACE,QR',
            'lokasi' => 'nullable|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        AttendanceDevice::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data perangkat IoT berhasil ditambahkan.'
        ]);
    }

    public function edit(AttendanceDevice $attendanceDevice)
    {
        return response()->json($attendanceDevice);
    }

    public function update(Request $request, AttendanceDevice $attendanceDevice)
    {
        $validated = $request->validate([
            'nama_device' => 'required|max:255',
            'device_code' => [
                'required',
                'max:100',
                Rule::unique('attendance_devices', 'device_code')->ignore($attendanceDevice->id),
            ],
            'jenis' => 'required|in:RFID,FACE,QR',
            'lokasi' => 'nullable|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $attendanceDevice->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data perangkat IoT berhasil diperbarui.'
        ]);
    }

    public function destroy(AttendanceDevice $attendanceDevice)
    {
        $attendanceDevice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data perangkat IoT berhasil dihapus.'
        ]);
    }
}
