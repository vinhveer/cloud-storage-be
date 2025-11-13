<?php

namespace App\Http\Controllers\Api\Search;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SearchController extends Controller
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

        // Only search the authenticated user's files and folders
        $authUser = $request->user();
        if (! $authUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $userId = $authUser->id;

        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $offset = ($page - 1) * $perPage;

        // File query - apply user filter early to avoid scanning others' rows
        $fileQB = DB::table('files as f')
            ->where('f.is_deleted', 0)
            ->where('f.user_id', $userId)
            ->selectRaw("
                'file' as type,
                f.id,
                f.display_name,
                f.file_size,
                f.mime_type,
                f.file_extension,
                f.user_id as user_id,
                f.created_at
            ");

        // Folder query - apply user filter early
        $folderQB = DB::table('folders as fo')
            ->where('fo.is_deleted', 0)
            ->where('fo.user_id', $userId)
            ->selectRaw("
                'folder' as type,
                fo.id,
                fo.folder_name,
                NULL as file_size,
                NULL as mime_type,
                NULL as file_extension,
                fo.user_id as user_id,
                fo.created_at
            ");

        // Apply filters
        if ($q) {
            $like = '%' . strtolower($q) . '%';
                        $fileQB->where(function ($w) use ($like) {
                                $w->whereRaw('LOWER(f.display_name) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(f.file_extension) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(f.mime_type) LIKE ?', [$like]);
                        });

                        $folderQB->where(function ($w) use ($like) {
                                $w->whereRaw('LOWER(fo.folder_name) LIKE ?', [$like]);
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
        // the authenticated user's own files and folders

        // Build SQL for DB-level pagination using UNION ALL to combine files + folders
        $fileWhere = ["f.is_deleted = 0", "f.user_id = ?"];
        $fileBindings = [$userId];
        if ($extension) {
            $fileWhere[] = 'LOWER(f.file_extension) = ?';
            $fileBindings[] = strtolower($extension);
        }
        if ($sizeMin !== null) {
            $fileWhere[] = 'f.file_size >= ?';
            $fileBindings[] = (int) $sizeMin;
        }
        if ($sizeMax !== null) {
            $fileWhere[] = 'f.file_size <= ?';
            $fileBindings[] = (int) $sizeMax;
        }
        if ($dateFrom) {
            $fileWhere[] = "DATE(f.created_at) >= ?";
            $fileBindings[] = $dateFrom;
        }
        if ($dateTo) {
            $fileWhere[] = "DATE(f.created_at) <= ?";
            $fileBindings[] = $dateTo;
        }
        if ($q) {
            $like = '%' . strtolower($q) . '%';
            $fileWhere[] = "(LOWER(f.display_name) LIKE ? OR LOWER(f.file_extension) LIKE ? OR LOWER(f.mime_type) LIKE ? )";
            $fileBindings[] = $like;
            $fileBindings[] = $like;
            $fileBindings[] = $like;
        }

        $fileSql = "SELECT 'file' as type, f.id, f.display_name, NULL as folder_name, f.file_size, f.mime_type, f.file_extension, f.user_id, f.created_at FROM files f WHERE " . implode(' AND ', $fileWhere);

        // Folder
        $folderWhere = ["fo.is_deleted = 0", "fo.user_id = ?"];
        $folderBindings = [$userId];
        if ($dateFrom) {
            $folderWhere[] = "DATE(fo.created_at) >= ?";
            $folderBindings[] = $dateFrom;
        }
        if ($dateTo) {
            $folderWhere[] = "DATE(fo.created_at) <= ?";
            $folderBindings[] = $dateTo;
        }
        if ($q) {
            $like = '%' . strtolower($q) . '%';
            $folderWhere[] = "(LOWER(fo.folder_name) LIKE ?)";
            $folderBindings[] = $like;
        }

        $folderSql = "SELECT 'folder' as type, fo.id, NULL as display_name, fo.folder_name, NULL as file_size, NULL as mime_type, NULL as file_extension, fo.user_id, fo.created_at FROM folders fo WHERE " . implode(' AND ', $folderWhere);

        // Compose final SQL depending on type
        if ($type === 'file') {
            $innerSql = $fileSql;
            $innerBindings = $fileBindings;
        } elseif ($type === 'folder') {
            $innerSql = $folderSql;
            $innerBindings = $folderBindings;
        } else {
            $innerSql = $fileSql . ' UNION ALL ' . $folderSql;
            $innerBindings = array_merge($fileBindings, $folderBindings);
        }

        // Total count
        $countSql = "SELECT COUNT(*) as total FROM (" . $innerSql . ") as combined_count";
        $countRes = DB::select($countSql, $innerBindings);
        $total = isset($countRes[0]) ? (int) $countRes[0]->total : 0;
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 0;

        // Apply ordering + limit/offset
        $finalSql = $innerSql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $finalBindings = array_merge($innerBindings, [$perPage, $offset]);
        $rows = DB::select($finalSql, $finalBindings);
        $results = collect($rows);

        // Map results
        $formatted = $results->map(function ($item) use ($authUser) {
            $owner = [
                'user_id' => (int) $item->user_id,
                'name' => $authUser->name,
                'email' => $authUser->email,
            ];

            if ($item->type === 'file') {
                return [
                    'type' => 'file',
                    'id' => (int) $item->id,
                    'display_name' => $item->display_name,
                    'file_size' => $item->file_size !== null ? (int) $item->file_size : null,
                    'mime_type' => $item->mime_type,
                    'file_extension' => $item->file_extension,
                    'owner' => $owner,
                    'created_at' => $item->created_at,
                ];
            }

            return [
                'type' => 'folder',
                'id' => (int) $item->id,
                'folder_name' => $item->folder_name,
                'owner' => $owner,
                'created_at' => $item->created_at,
            ];
        })->values();

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
                    ->where('f.is_deleted', 0)
                    ->where('f.user_id', $userId)
                    ->where(function ($w) use ($like) {
                        $w->whereRaw('LOWER(f.display_name) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(f.file_extension) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(f.mime_type) LIKE ?', [$like]);
                    })->count();

                $foldersMatchingQ = (int) DB::table('folders as fo')
                    ->where('fo.is_deleted', 0)
                    ->where('fo.user_id', $userId)
                    ->where(function ($w) use ($like) {
                        $w->whereRaw('LOWER(fo.folder_name) LIKE ?', [$like]);
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
                    // owner_id and shared_status not applicable (search limited to current user)
                ],
            ];
        }

        return response()->json($response);
    }
}