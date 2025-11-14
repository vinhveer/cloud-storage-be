<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AdminUpdateStorageLimitController extends BaseApiController
{
    /**
     * PUT /api/admin/users/{id}/storage-limit - update storage limit (admin only)
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
            'storage_limit' => 'required|integer|min:0',
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

        $user->storage_limit = $request->input('storage_limit');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Storage limit updated.',
            'user' => [
                'user_id' => (int) $user->id,
                'storage_limit' => $user->storage_limit !== null ? (int) $user->storage_limit : null,
            ],
        ]);
    }
}
