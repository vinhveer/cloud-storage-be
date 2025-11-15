<?php

namespace App\Http\Controllers\Api\Share;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GetShareController extends BaseApiController
{
    /**
     * GET /api/shares/{id} - Return share detail with recipients
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated.', 401, 'UNAUTHENTICATED');
        }

        $share = DB::table('shares as sh')
            ->leftJoin('files as f', 'sh.file_id', '=', 'f.id')
            ->leftJoin('folders as fo', 'sh.folder_id', '=', 'fo.id')
            ->where('sh.id', $id)
            ->selectRaw('sh.id as share_id, sh.shareable_type, COALESCE(f.display_name, fo.folder_name) as shareable_name, sh.created_at, sh.user_id as shared_by_user_id')
            ->first();

        if (! $share) {
            return response()->json(['message' => 'Share not found.'], 404);
        }

        // Only allow the owner (shared_by) to view details via this endpoint
        if ((int) $share->shared_by_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Share not found.'], 404);
        }

        $sharedBy = DB::table('users')->where('id', $share->shared_by_user_id)->select('id', 'name')->first();

        $sharedWithRows = DB::table('receives_shares as rs')
            ->join('users as u', 'rs.user_id', '=', 'u.id')
            ->where('rs.share_id', $id)
            ->select('u.id as user_id', 'u.name', 'rs.permission')
            ->get();

        $sharedWith = $sharedWithRows->map(function ($r) {
            return [
                'user_id' => (int) $r->user_id,
                'name' => $r->name,
                'permission' => $r->permission,
            ];
        })->values();

        $result = [
            'share_id' => (int) $share->share_id,
            'shareable_type' => $share->shareable_type,
            'shareable_name' => $share->shareable_name,
            // share.permission deprecated: per-recipient permissions are available in 'shared_with'
            'created_at' => $share->created_at,
            'shared_by' => [
                'user_id' => (int) $sharedBy->id,
                'name' => $sharedBy->name,
            ],
            'shared_with' => $sharedWith,
        ];

        return response()->json($result);
    }
}
