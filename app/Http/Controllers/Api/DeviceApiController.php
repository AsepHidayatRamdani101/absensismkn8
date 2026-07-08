<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeviceApiController extends Controller
{
    public function rfid(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'RFID API berjalan'
        ]);
    }

    public function face(Request $request)
    {
        $validated = $request->validate([
            'student_id'  => 'required|integer',
            'device_code' => 'required|string',
            'confidence'  => 'nullable|numeric',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Face recognition berhasil',
            'data' => [
                'student_id'  => $validated['student_id'],
                'device_code' => $validated['device_code'],
                'confidence'  => $validated['confidence'] ?? null,
                'timestamp'   => now(),
            ]
        ]);
    }
}