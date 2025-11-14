<?php

namespace App\Http\Controllers\Api\Share;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivedSharesController extends BaseApiController
{
    /**
     * GET /api/shares/received - list shares that were shared with current user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $offset = ($page - 1) * $perPage;

        $baseQB = DB::table('receives_shares as rs')
            ->join('shares as sh', 'rs.share_id', '=', 'sh.id')
            ->leftJoin('files as f', 'sh.file_id', '=', 'f.id')
            ->leftJoin('folders as fo', 'sh.folder_id', '=', 'fo.id')
            ->join('users as u', 'sh.user_id', '=', 'u.id')
            ->where('rs.user_id', $user->id)
            ->selectRaw(
                'sh.id as share_id, sh.shareable_type, COALESCE(f.display_name, fo.folder_name) as shareable_name, sh.user_id as owner_user_id, u.name as owner_name, rs.permission as permission, sh.created_at as shared_at'
            );

        $total = (int) DB::table(DB::raw("({$baseQB->toSql()}) as t"))
            ->mergeBindings($baseQB)
            ->count();

        $rows = $baseQB->orderByDesc('sh.created_at')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $data = $rows->map(function ($r) {
            return [
                'share_id' => (int) $r->share_id,
                'shareable_type' => $r->shareable_type,
                'shareable_name' => $r->shareable_name,
                'owner' => [
                    'user_id' => (int) $r->owner_user_id,
                    'name' => $r->owner_name,
                ],
                'permission' => $r->permission,
                'shared_at' => $r->shared_at,
            ];
        })->values();

        $totalPages = (int) ceil($total / $perPage);

        return response()->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ]);
    }
}
