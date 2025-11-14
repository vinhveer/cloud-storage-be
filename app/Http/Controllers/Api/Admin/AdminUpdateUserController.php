<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AdminUpdateUserController extends BaseApiController
{
    /**
     * PUT /api/admin/users/{id} - update user info (admin only)
     */
    public function update(Request $request, $id)
    {
        $auth = $request->user();
        if (! $auth) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! isset($auth->role) || $auth->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,'.$id,
            'storage_limit' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $data = $validator->validated();

        if (array_key_exists('name', $data)) {
            $user->name = $data['name'];
        }

        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }

        if (array_key_exists('storage_limit', $data)) {
            $user->storage_limit = $data['storage_limit'];
        }

        $user->save();

        return response()->json([
            'success' => true,
            'user' => [
                'user_id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'storage_limit' => $user->storage_limit !== null ? (int) $user->storage_limit : null,
                'storage_used' => $user->storage_used !== null ? (int) $user->storage_used : null,
            ],
        ]);
    }
}
