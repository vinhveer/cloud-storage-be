<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserDetailController extends BaseApiController
{
    /**
     * GET /api/admin/users/{id} - return user detail (admin only)
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! isset($user->role) || $user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $u = DB::table('users')->where('id', $id)->select('id', 'name', 'email', 'role', 'storage_limit', 'storage_used')->first();
        if (! $u) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json([
            'user' => [
                'user_id' => (int) $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'storage_limit' => $u->storage_limit !== null ? (int) $u->storage_limit : null,
                'storage_used' => $u->storage_used !== null ? (int) $u->storage_used : null,
            ],
        ]);
    }
}
