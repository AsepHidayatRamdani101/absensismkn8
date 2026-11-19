<?php

namespace App\Http\Controllers\Api;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\TeacherSubject;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StudentResource;
use App\Support\SchoolIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentApiController extends Controller
{
    private const FULL_ACCESS_ROLES = ['admin', 'kurikulum', 'kesiswaan', 'bk', 'tu'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $query = Student::query()->with('classroom.major');

        if (!$user->hasAnyRole(self::FULL_ACCESS_ROLES)) {
            if ($user->hasRole('guru')) {
                $teacher = SchoolIdentity::teacherFor($user);
                abort_if($teacher === null, 403, 'Data guru tidak ditemukan untuk akun ini.');

                $classroomIds = TeacherSubject::query()
                    ->where('teacher_id', $teacher->id)
                    ->where('academic_year_id', AcademicYear::query()->where('is_active', true)->value('id'))
                    ->pluck('classroom_id')
                    ->push($teacher->is_wali_kelas ? $teacher->wali_classroom_id : null)
                    ->filter()
                    ->unique()
                    ->values();

                $query->whereIn('classroom_id', $classroomIds);
            } elseif ($user->hasRole('siswa')) {
                $student = SchoolIdentity::studentFor($user);
                $query->whereKey($student?->id ?? 0);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $perPage = max(min((int) $request->integer('per_page', 20), 100), 10);
        $students = $query->orderBy('nama_lengkap')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => StudentResource::collection($students->getCollection()),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }
}
