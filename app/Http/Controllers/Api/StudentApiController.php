<?php

namespace App\Http\Controllers\Api;

use App\Models\Student;

use App\Http\Controllers\Controller;

class StudentApiController extends Controller
{
    public function index()
    {
        return response()->json(

            Student::with('classroom.major')->get()

        );
    }
}