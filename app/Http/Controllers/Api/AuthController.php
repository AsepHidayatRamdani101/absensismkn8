<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([

            'email' => 'required|email',

            'password' => 'required'

        ]);

        if (!Auth::attempt($credentials)) {

            return response()->json([

                'success' => false,
                'message' => 'Email atau password salah',
                'data' => null,

            ], 401);
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        /** @var User $user */

        $token = $user->createToken('mobile-app')
            ->plainTextToken;

        return response()->json([

            'success' => true,
            'message' => 'Login berhasil',

            // Keep legacy keys for mobile clients while providing standard envelope.
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
            ],
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                ],
            ],

        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([

            'success' => true,
            'message' => 'Logout berhasil',
            'data' => null,

        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $role = is_object($user) && method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->first()
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil dimuat',
            'data' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'email' => $user?->email,
                'role' => $role,
            ],
        ]);
    }
}
