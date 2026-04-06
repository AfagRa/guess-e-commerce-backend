<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return $this->success([
            'user' => $this->userPayload($user),
            'token' => $token,
        ], 'Registered', 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }

        $user = User::findOrFail(Auth::id());
        $token = $user->createToken('auth')->plainTextToken;

        return $this->success([
            'user' => $this->userPayload($user),
            'token' => $token,
        ], 'Logged in');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out');
    }

    public function me(Request $request)
    {
        return $this->success($this->userPayload($request->user()));
    }

    private function userPayload(User $user): array
    {
        return $user->only([
            'id',
            'name',
            'email',
            'role',
            'phone',
            'address',
            'avatar',
            'created_at',
            'updated_at',
        ]);
    }
}
