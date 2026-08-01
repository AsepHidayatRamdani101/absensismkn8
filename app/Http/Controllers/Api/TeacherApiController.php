<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $query = Teacher::query()->orderBy('nama_lengkap');

        if (method_exists($user, 'hasRole') && $user->hasRole('guru')) {
            $query->where(function ($q) use ($user) {
                $q->where('nip', $user->email)
                    ->orWhere('nama_lengkap', $user->name);
            });
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('siswa')) {
            $query->whereRaw('1 = 0');
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
