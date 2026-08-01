<?php

namespace App\Http\Controllers\Api;

use App\Models\Student;
use App\Models\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StudentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $query = Student::query()->with('classroom.major');

        if (method_exists($user, 'hasRole') && $user->hasRole('siswa')) {
            $query->where(function ($q) use ($user) {
                $q->where('nisn', $user->email)
                    ->orWhere('nis', $user->email);
            });
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('guru')) {
            $teacher = Teacher::query()
                ->where('nip', $user->email)
                ->orWhere('nama_lengkap', $user->name)
                ->first();

            if ($teacher?->wali_classroom_id) {
                $query->where('classroom_id', (int) $teacher->wali_classroom_id);
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
