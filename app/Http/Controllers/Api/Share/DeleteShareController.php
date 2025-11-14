<?php

namespace App\Http\Controllers\Api\Share;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeleteShareController extends BaseApiController
{
    /**
     * DELETE /api/shares/{id} - Revoke (delete) a share and remove access for all recipients
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $share = DB::table('shares')->where('id', $id)->first();
        if (! $share) {
            return response()->json(['success' => false, 'message' => 'Share not found.'], 404);
        }

        // Only owner may revoke
        if ((int) $share->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Share not found.'], 404);
        }

        DB::transaction(function () use ($id) {
            // delete receives_shares rows (cascade in DB may handle this, but be explicit)
            DB::table('receives_shares')->where('share_id', $id)->delete();

            // delete the share itself
            DB::table('shares')->where('id', $id)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Share revoked successfully.'
        ]);
    }
}
