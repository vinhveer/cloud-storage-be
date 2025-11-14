<?php

namespace App\Http\Controllers\Api\Share;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RemoveUserFromShareController extends BaseApiController
{
    /**
     * DELETE /api/shares/{id}/users/{userId} - remove a user's access from a share
     */
    public function destroy(Request $request, $id, $userId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $share = DB::table('shares')->where('id', $id)->first();
        if (! $share) {
            return response()->json(['success' => false, 'message' => 'Share not found.'], 404);
        }

        // Only owner may remove recipients
        if ((int) $share->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Share not found.'], 404);
        }

        // Check if the user is currently a recipient of this share
        $exists = DB::table('receives_shares')
            ->where('share_id', $id)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            return response()->json(['success' => false, 'message' => 'User not found on this share.'], 404);
        }

        DB::table('receives_shares')
            ->where('share_id', $id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'User removed from share.'
        ]);
    }
}
