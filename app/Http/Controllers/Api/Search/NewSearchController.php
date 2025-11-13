<?php

namespace App\Http\Controllers\Api\Search;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class NewSearchController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->query('q');
        $type = $request->query('type', 'all');
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

        // File query
        $fileQB = DB::table('files as f')
            ->leftJoin('users as u', 'f.user_id', '=', 'u.id')
            ->leftJoin('shares as s', function ($join) {
                $join->on('s.file_id', '=', 'f.id')->where('s.shareable_type', 'file');
            })
            ->leftJoin('public_links as pl', function ($join) {
                $join->on('pl.file_id', '=', 'f.id')->where('pl.shareable_type', 'file');
            })
            ->where('f.is_deleted', 0)
            ->selectRaw("
                'file' as type,
                f.id,
                f.display_name,
                f.file_size,
                f.mime_type,
                f.file_extension,
                u.id as user_id,
                u.name,
                u.email,
                CASE
                    WHEN pl.id IS NOT NULL THEN 'public'
                    WHEN s.id IS NOT NULL THEN 'shared'
                    ELSE 'private'
                END as shared_status,
                f.created_at
            ");

        // Folder query
        $folderQB = DB::table('folders as fo')
            ->leftJoin('users as u', 'fo.user_id', '=', 'u.id')
            ->leftJoin('shares as s', function ($join) {
                $join->on('s.folder_id', '=', 'fo.id')->where('s.shareable_type', 'folder');
            })
            ->leftJoin('public_links as pl', function ($join) {
                $join->on('pl.folder_id', '=', 'fo.id')->where('pl.shareable_type', 'folder');
            })
            ->where('fo.is_deleted', 0)
            ->selectRaw("
                'folder' as type,
                fo.id,
                fo.folder_name,
                NULL as file_size,
                NULL as mime_type,
                NULL as file_extension,
                u.id as user_id,
                u.name,
                u.email,
                CASE
                    WHEN pl.id IS NOT NULL THEN 'public'
                    WHEN s.id IS NOT NULL THEN 'shared'
                    ELSE 'private'
                END as shared_status,
                fo.created_at
            ");

        // Apply filters
        if ($q) {
            $like = '%' . strtolower($q) . '%';
            $fileQB->where(function ($w) use ($like) {
                $w->whereRaw('LOWER(f.display_name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(f.file_extension) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(f.mime_type) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(u.name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(u.email) LIKE ?', [$like]);
            });

            $folderQB->where(function ($w) use ($like) {
                $w->whereRaw('LOWER(fo.folder_name) LIKE ?', [$like])
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
            $folderQB->whereDate('fo.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $fileQB->whereDate('f.created_at', '<=', $dateTo);
            $folderQB->whereDate('fo.created_at', '<=', $dateTo);
        }
        if ($ownerId) {
            $fileQB->where('f.user_id', (int) $ownerId);
            $folderQB->where('fo.user_id', (int) $ownerId);
        }
        if ($sharedStatus) {
            if ($sharedStatus === 'public') {
                $fileQB->whereNotNull('pl.id');
                $folderQB->whereNotNull('pl.id');
            } elseif ($sharedStatus === 'shared') {
                $fileQB->whereNull('pl.id')->whereNotNull('s.id');
                $folderQB->whereNull('pl.id')->whereNotNull('s.id');
            } elseif ($sharedStatus === 'private') {
                $fileQB->whereNull('pl.id')->whereNull('s.id');
                $folderQB->whereNull('pl.id')->whereNull('s.id');
            }
        }

        // Fetch and merge
        $files = ($type === 'file' || $type === 'all') ? $fileQB->get() : collect();
        $folders = ($type === 'folder' || $type === 'all') ? $folderQB->get() : collect();
        $results = $files->merge($folders)->sortByDesc('created_at')->values();

        // Pagination
        $total = $results->count();
        $totalPages = (int) ceil($total / $perPage);
        $pageItems = $results->slice($offset, $perPage);

        // Format output
        $formatted = $pageItems->map(function ($item) {
            $owner = [
                'user_id' => (int) $item->user_id,
                'name' => $item->name,
                'email' => $item->email,
            ];

            if ($item->type === 'file') {
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
            }

            return [
                'type' => 'folder',
                'id' => (int) $item->id,
                'folder_name' => $item->folder_name,
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
            $foldersMatchingQ = null;
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

                $foldersMatchingQ = (int) DB::table('folders as fo')
                    ->leftJoin('users as u', 'fo.user_id', '=', 'u.id')
                    ->where('fo.is_deleted', 0)
                    ->where(function ($w) use ($like) {
                        $w->whereRaw('LOWER(fo.folder_name) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(u.name) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(u.email) LIKE ?', [$like]);
                    })->count();
            }

            $response['debug'] = [
                'user_id' => $userId,
                'total_files' => $totalFiles,
                'user_files' => $userFiles,
                'files_matching_q' => $filesMatchingQ,
                'folders_matching_q' => $foldersMatchingQ,
                'filters' => [
                    'q' => $q,
                    'type' => $type,
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