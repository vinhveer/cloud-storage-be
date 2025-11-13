<?php

namespace App\Http\Controllers\Api\Search;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SearchFoldersController extends Controller
{
    /**
     * Search folders (folders only) and return the specified JSON shape.
     */
    public function folders(Request $request)
    {
        $q = $request->query('q');
        $ownerId = $request->query('owner_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $sharedStatus = $request->query('shared_status');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $offset = ($page - 1) * $perPage;

        $folderQB = DB::table('folders as fo')
            ->leftJoin('users as u', 'fo.user_id', '=', 'u.id')
            ->leftJoin('shares as s', function ($join) {
                $join->on('s.folder_id', '=', 'fo.id')->where('s.shareable_type', 'folder');
            })
            ->leftJoin('public_links as pl', function ($join) {
                $join->on('pl.folder_id', '=', 'fo.id')->where('pl.shareable_type', 'folder');
            })
            ->where('fo.is_deleted', 0)
            ->selectRaw("fo.id, fo.folder_name, u.id as user_id, u.name, CASE WHEN pl.id IS NOT NULL THEN 'public' WHEN s.id IS NOT NULL THEN 'shared' ELSE 'private' END as shared_status, fo.created_at");

        if ($q) {
            $like = '%' . strtolower($q) . '%';
            $folderQB->where(function ($w) use ($like) {
                $w->whereRaw('LOWER(fo.folder_name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(u.name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(u.email) LIKE ?', [$like]);
            });
        }

        if ($ownerId) {
            $folderQB->where('fo.user_id', (int) $ownerId);
        }
        if ($dateFrom) {
            $folderQB->whereDate('fo.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $folderQB->whereDate('fo.created_at', '<=', $dateTo);
        }
        if ($sharedStatus) {
            if ($sharedStatus === 'public') {
                $folderQB->whereNotNull('pl.id');
            } elseif ($sharedStatus === 'shared') {
                $folderQB->whereNull('pl.id')->whereNotNull('s.id');
            } elseif ($sharedStatus === 'private') {
                $folderQB->whereNull('pl.id')->whereNull('s.id');
            }
        }

        $folders = $folderQB->orderByDesc('fo.created_at')->get();

        $total = $folders->count();
        $totalPages = (int) ceil($total / $perPage);
        $pageItems = $folders->slice($offset, $perPage);

        $formatted = $pageItems->map(function ($item) {
            return [
                'folder_id' => (int) $item->id,
                'folder_name' => $item->folder_name,
                'owner' => [
                    'user_id' => (int) $item->user_id,
                    'name' => $item->name,
                ],
                'shared_status' => $item->shared_status,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json([
            'data' => $formatted,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ]);
    }
}
