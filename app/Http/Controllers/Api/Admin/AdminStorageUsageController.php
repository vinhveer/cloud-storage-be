<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Models\User;

class AdminStorageUsageController extends BaseApiController
{
    /**
     * GET /api/admin/users/{id}/storage-usage - return storage usage for a user (admin only)
     */
    public function show(Request $request, $id)
    {
        $auth = $request->user();
        if (! $auth) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! isset($auth->role) || $auth->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $u = User::find($id);
        if (! $u) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $used = $u->storage_used !== null ? (int) $u->storage_used : 0;
        $limit = $u->storage_limit !== null ? (int) $u->storage_limit : null;

        $usagePercent = null;
        if ($limit !== null && $limit > 0) {
            $usagePercent = round(($used / $limit) * 100, 2);
        } else {
            $usagePercent = 0.0;
        }

        return response()->json([
            'user_id' => (int) $u->id,
            'storage_used' => $used,
            'storage_limit' => $limit,
            'usage_percent' => $usagePercent,
        ]);
    }
}
