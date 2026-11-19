<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TeacherResource;
use App\Models\Teacher;
use App\Support\SchoolIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherApiController extends Controller
{
    private const FULL_ACCESS_ROLES = ['admin', 'kurikulum', 'kesiswaan', 'bk', 'tu'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $query = Teacher::query()->orderBy('nama_lengkap');

        if (!$user->hasAnyRole(self::FULL_ACCESS_ROLES)) {
            $teacher = $user->hasRole('guru') ? SchoolIdentity::teacherFor($user) : null;
            $query->whereKey($teacher?->id ?? 0);
        }

        $perPage = max(min((int) $request->integer('per_page', 20), 100), 10);
        $teachers = $query->paginate($perPage);

        return response()->json([

            'success' => true,

            'data' => TeacherResource::collection($teachers->getCollection()),
            'meta' => [
                'current_page' => $teachers->currentPage(),
                'last_page' => $teachers->lastPage(),
                'per_page' => $teachers->perPage(),
                'total' => $teachers->total(),
            ],

        ]);
    }
}
