<?php

namespace App\Services\Dashboard;

use Illuminate\Http\Request;

class DashboardFilterService
{
    public function normalize(Request $request): array
    {
        return [
            'academic_year_id' => $request->string('academic_year_id')->toString(),
            'semester' => $request->string('semester')->toString(),
            'major_id' => $request->string('major_id')->toString(),
            'classroom_id' => $request->string('classroom_id')->toString(),
            'student_id' => $request->string('student_id')->toString(),
            'teacher_id' => $request->string('teacher_id')->toString(),
            'source' => $request->string('source')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'gender' => $request->string('gender')->toString(),
            'grade_level' => $request->string('grade_level')->toString(),
            'top_limit' => (string) $request->integer('top_limit', 10),
            'compare_mode' => $request->string('compare_mode')->toString(),
        ];
    }
}
