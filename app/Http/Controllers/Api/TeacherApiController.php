<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;

class TeacherApiController extends Controller
{
    public function index()
    {
        return response()->json([

            'success' => true,

            'data' => Teacher::orderBy('nama_lengkap')
                ->get()

        ]);
    }
}