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

        // Use the raw request keys to detect presence of a field even when
        // validator might omit nullable fields in some cases. Prefer the
        // validated value when available.
        $raw = $request->all();

        if (array_key_exists('name', $raw) || array_key_exists('name', $data)) {
            $user->name = $data['name'] ?? $request->input('name');
        }

        // Only allow updating name and storage_limit via this admin endpoint.
        if (array_key_exists('storage_limit', $raw) || array_key_exists('storage_limit', $data)) {
            $user->storage_limit = $data['storage_limit'] ?? $request->input('storage_limit');
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
