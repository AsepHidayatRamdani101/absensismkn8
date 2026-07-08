<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

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
                'message' => 'Email atau password salah'

            ], 401);
        }

        $user = Auth::user();

        $token = $user->createToken('mobile-app')
                      ->plainTextToken;

        return response()->json([

            'success' => true,

            'token' => $token,

            'user' => [

                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),

            ]

        ]);
    }

    public function logout(Request $request)
    {
        $request->user()
                ->currentAccessToken()
                ->delete();

        return response()->json([

            'success' => true,
            'message' => 'Logout berhasil'

        ]);
    }

    public function profile(Request $request)
    {
        return response()->json(

            $request->user()

        );
    }
}