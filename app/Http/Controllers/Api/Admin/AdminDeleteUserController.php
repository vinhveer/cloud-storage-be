<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDeleteUserController extends BaseApiController
{
    /**
     * DELETE /api/admin/users/{id} - delete a user (admin only)
     */
    public function destroy(Request $request, $id)
    {
        $auth = $request->user();
        if (! $auth) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! isset($auth->role) || $auth->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Prevent admin from deleting themselves
        if ((int) $auth->id === (int) $id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete yourself.'], 422);
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        DB::transaction(function () use ($user) {
            // Hard delete by default; if soft deletes are enabled the model will handle that.
            $user->delete();
        });

        return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
    }
}
