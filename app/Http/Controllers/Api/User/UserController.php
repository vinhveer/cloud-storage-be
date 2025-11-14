<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseApiController
{
    public function show(Request $request)
    {
        $user = $request->user();
        return $this->ok([
            'user' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
                'storage_used' => $user->storage_used ?? 0,
                'storage_limit' => $user->storage_limit ?? 0,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
            ],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $name = $request->input('name');
        $email = $request->input('email');

        if ($name !== null) {
            $user->name = $name;
        }
        if ($email !== null) {
            $user->email = $email;
        }

        $user->save();

        return $this->ok([
            'message' => 'Profile updated successfully.',
            'user' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();

        $current = $request->string('current_password')->toString();
        if (! Hash::check($current, (string) $user->password)) {
            return $this->fail('Current password is incorrect.', 400, 'INVALID_CURRENT_PASSWORD');
        }

        $newPassword = $request->string('new_password')->toString();
        $user->password = Hash::make($newPassword);
        $user->save();

        return $this->ok([
            'message' => 'Password changed successfully.',
        ]);
    }
}
