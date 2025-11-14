<?php

namespace App\Http\Controllers\Api\Share;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdateSharePermissionController extends BaseApiController
{
    /**
     * PUT /api/shares/{id} - Update default permission for a share (applies to all recipients)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'permission' => 'required|string|in:view,download,edit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission = $request->input('permission');

        $share = DB::table('shares')->where('id', $id)->first();
        if (! $share) {
            return response()->json(['success' => false, 'message' => 'Share not found.'], 404);
        }

        // Only owner may change the default permission
        if ((int) $share->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Share not found.'], 404);
        }

        DB::transaction(function () use ($id, $permission) {
            DB::table('shares')->where('id', $id)->update([
                'permission' => $permission,
                'updated_at' => now(),
            ]);

            // Apply to all receives_shares rows for this share
            DB::table('receives_shares')->where('share_id', $id)->update([
                'permission' => $permission,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Share permission updated successfully.',
            'share' => [
                'share_id' => (int) $id,
                'permission' => $permission,
            ],
        ]);
    }
}
