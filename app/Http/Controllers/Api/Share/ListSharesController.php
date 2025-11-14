<?php

namespace App\Http\Controllers\Api\Share;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListSharesController extends BaseApiController
{
    /**
     * GET /api/shares - list shares created by current user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated.', 401, 'UNAUTHENTICATED');
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $offset = ($page - 1) * $perPage;

        // Base query: joins to resolve shareable name and count receivers
        $baseQB = DB::table('shares as sh')
            ->leftJoin('files as f', 'sh.file_id', '=', 'f.id')
            ->leftJoin('folders as fo', 'sh.folder_id', '=', 'fo.id')
            ->leftJoin('receives_shares as rs', 'rs.share_id', '=', 'sh.id')
            ->where('sh.user_id', $user->id)
            ->selectRaw(
                'sh.id as share_id, sh.shareable_type, COALESCE(f.display_name, fo.folder_name) as shareable_name, sh.permission, sh.created_at, COUNT(DISTINCT rs.user_id) as shared_with_count'
            )
            ->groupBy('sh.id', 'sh.shareable_type', 'shareable_name', 'sh.permission', 'sh.created_at');

        $total = (int) DB::table(DB::raw("({$baseQB->toSql()}) as t"))
            ->mergeBindings($baseQB)
            ->count();

        $rows = $baseQB->orderByDesc('sh.created_at')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $formatted = $rows->map(function ($r) {
            return [
                'share_id' => (int) $r->share_id,
                'shareable_type' => $r->shareable_type,
                'shareable_name' => $r->shareable_name,
                'permission' => $r->permission,
                'shared_with_count' => (int) $r->shared_with_count,
                'created_at' => $r->created_at,
            ];
        });

        $totalPages = (int) ceil($total / $perPage);

        return $this->ok([
            'data' => $formatted,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ]);
    }
}
