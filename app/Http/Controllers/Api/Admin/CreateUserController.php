<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SystemConfig;

class CreateUserController extends BaseApiController
{
    /**
     * POST /api/admin/users - create a new user (admin only)
     */
    public function store(Request $request)
    {
        $auth = $request->user();
        if (! $auth) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! isset($auth->role) || $auth->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user',
            'storage_limit' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $newUserData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'storage_used' => 0,
        ];

        if (array_key_exists('storage_limit', $data)) {
            $newUserData['storage_limit'] = $data['storage_limit'];
        } else {
            // Use the system default storage limit (in bytes)
            $newUserData['storage_limit'] = SystemConfig::getBytes('default_storage_limit', 0);
        }

        $newUser = User::create($newUserData);

        return response()->json([
            'success' => true,
            'user' => [
                'user_id' => (int) $newUser->id,
                'name' => $newUser->name,
                'email' => $newUser->email,
                'role' => $newUser->role,
                'storage_limit' => $newUser->storage_limit !== null ? (int) $newUser->storage_limit : 0,
                'storage_used' => $newUser->storage_used !== null ? (int) $newUser->storage_used : 0,
            ],
        ], 201);
    }
}
