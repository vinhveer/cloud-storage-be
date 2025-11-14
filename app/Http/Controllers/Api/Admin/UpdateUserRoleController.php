<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class UpdateUserRoleController extends BaseApiController
{
    /**
     * PUT /api/admin/users/{id}/role - change a user's role (admin only)
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
            'role' => 'required|string|in:admin,user',
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

        // Optional: prevent admins from demoting themselves. Not required but safer.
        if ((int) $auth->id === (int) $user->id && $request->input('role') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'You cannot change your own role to a lower privilege.'], 422);
        }

        $user->role = $request->input('role');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'user' => [
                'user_id' => (int) $user->id,
                'role' => $user->role,
            ],
        ]);
    }
}
