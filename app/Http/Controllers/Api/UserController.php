<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'avatar' => 'nullable|string|max:2048',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->fill($validated);
        $user->save();

        return $this->success($user->only([
            'id',
            'name',
            'email',
            'role',
            'phone',
            'address',
            'avatar',
            'created_at',
            'updated_at',
        ]), 'Updated');
    }
}
