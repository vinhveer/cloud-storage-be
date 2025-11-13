<?php

namespace App\Http\Controllers\Api\Search;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SearchFilesController extends Controller
{
    /**
     * Search files only and return the specified JSON shape.
     */
    public function files(Request $request)
    {
        $q = $request->query('q');
        $extension = $request->query('extension');
        $sizeMin = $request->query('size_min');
        $sizeMax = $request->query('size_max');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $ownerId = $request->query('owner_id');
        $sharedStatus = $request->query('shared_status');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $offset = ($page - 1) * $perPage;

        $fileQB = DB::table('files as f')
            ->leftJoin('users as u', 'f.user_id', '=', 'u.id')
            ->leftJoin('shares as s', function ($join) {
                $join->on('s.file_id', '=', 'f.id')->where('s.shareable_type', 'file');
            })
            ->leftJoin('public_links as pl', function ($join) {
                $join->on('pl.file_id', '=', 'f.id')->where('pl.shareable_type', 'file');
            })
            ->where('f.is_deleted', 0)
            ->selectRaw("f.id, f.display_name, f.file_size, f.mime_type, f.file_extension, u.id as user_id, u.name, u.email, CASE WHEN pl.id IS NOT NULL THEN 'public' WHEN s.id IS NOT NULL THEN 'shared' ELSE 'private' END as shared_status, f.created_at");

        if ($q) {
            $like = '%' . strtolower($q) . '%';
            $fileQB->where(function ($w) use ($like) {
                $w->whereRaw('LOWER(f.display_name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(f.file_extension) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(f.mime_type) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(u.name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(u.email) LIKE ?', [$like]);
            });
        }

        if ($extension) {
            $fileQB->whereRaw('LOWER(f.file_extension) = ?', [strtolower($extension)]);
        }
        if ($sizeMin !== null) {
            $fileQB->where('f.file_size', '>=', (int) $sizeMin);
        }
        if ($sizeMax !== null) {
            $fileQB->where('f.file_size', '<=', (int) $sizeMax);
        }
        if ($dateFrom) {
            $fileQB->whereDate('f.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $fileQB->whereDate('f.created_at', '<=', $dateTo);
        }
        if ($ownerId) {
            $fileQB->where('f.user_id', (int) $ownerId);
        }
        if ($sharedStatus) {
            if ($sharedStatus === 'public') {
                $fileQB->whereNotNull('pl.id');
            } elseif ($sharedStatus === 'shared') {
                $fileQB->whereNull('pl.id')->whereNotNull('s.id');
            } elseif ($sharedStatus === 'private') {
                $fileQB->whereNull('pl.id')->whereNull('s.id');
            }
        }

        $files = $fileQB->orderByDesc('f.created_at')->get();

        $total = $files->count();
        $totalPages = (int) ceil($total / $perPage);
        $pageItems = $files->slice($offset, $perPage);

        $formatted = $pageItems->map(function ($item) {
            $owner = [
                'user_id' => (int) $item->user_id,
                'name' => $item->name,
                'email' => $item->email,
            ];

            return [
                'type' => 'file',
                'id' => (int) $item->id,
                'display_name' => $item->display_name,
                'file_size' => (int) $item->file_size,
                'mime_type' => $item->mime_type,
                'file_extension' => $item->file_extension,
                'owner' => $owner,
                'shared_status' => $item->shared_status,
                'created_at' => $item->created_at,
            ];
        });

        $response = [
            'data' => $formatted,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ];

        // debug diagnostics when ?debug=1
        if ($request->query('debug') == '1') {
            $authUser = $request->user();
            $userId = $authUser ? $authUser->id : null;

            $totalFiles = (int) DB::table('files')->where('is_deleted', 0)->count();
            $userFiles = $userId ? (int) DB::table('files')->where('is_deleted', 0)->where('user_id', $userId)->count() : null;

            $filesMatchingQ = null;
            if ($q) {
                $like = '%' . strtolower($q) . '%';
                $filesMatchingQ = (int) DB::table('files as f')
                    ->leftJoin('users as u', 'f.user_id', '=', 'u.id')
                    ->where('f.is_deleted', 0)
                    ->where(function ($w) use ($like) {
                        $w->whereRaw('LOWER(f.display_name) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(f.file_extension) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(f.mime_type) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(u.name) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(u.email) LIKE ?', [$like]);
                    })->count();
            }

            $response['debug'] = [
                'user_id' => $userId,
                'total_files' => $totalFiles,
                'user_files' => $userFiles,
                'files_matching_q' => $filesMatchingQ,
                'filters' => [
                    'q' => $q,
                    'extension' => $extension,
                    'size_min' => $sizeMin,
                    'size_max' => $sizeMax,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'owner_id' => $ownerId,
                    'shared_status' => $sharedStatus,
                ],
            ];
        }

        return response()->json($response);
    }
}
