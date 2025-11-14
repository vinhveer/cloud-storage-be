<?php

namespace App\Http\Controllers\Api\Search;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchSuggestionsRequest;

class SearchSuggestionsController extends Controller
{
    /**
     * Return autocomplete suggestions for files and folders.
     * q is required.
     */
        public function suggestions(SearchSuggestionsRequest $request)
        {
            $data = $request->validated();
            $q = $data['q'];
            $type = $data['type'] ?? 'all';
            $limit = isset($data['limit']) ? (int) $data['limit'] : 10;

            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => 'Unauthenticated',
                        'code' => 'UNAUTHENTICATED',
                        'errors' => null,
                    ],
                    'meta' => null,
                ], 401);
            }

            $like = '%' . strtolower($q) . '%';

            // Build SQL depending on requested type. Include parent id so we can compute paths.
            if ($type === 'file') {
                $sql = "SELECT id, display_name as name, created_at, folder_id as parent_id, 'file' as type FROM files WHERE user_id = ? AND is_deleted = 0 AND LOWER(display_name) LIKE ? ORDER BY created_at DESC LIMIT ?";
                $bindings = [$user->id, $like, $limit];
            } elseif ($type === 'folder') {
                $sql = "SELECT id, folder_name as name, created_at, fol_folder_id as parent_id, 'folder' as type FROM folders WHERE user_id = ? AND is_deleted = 0 AND LOWER(folder_name) LIKE ? ORDER BY created_at DESC LIMIT ?";
                $bindings = [$user->id, $like, $limit];
            } else {
                $sql = "SELECT id, display_name as name, created_at, folder_id as parent_id, 'file' as type FROM files WHERE user_id = ? AND is_deleted = 0 AND LOWER(display_name) LIKE ? UNION ALL SELECT id, folder_name as name, created_at, fol_folder_id as parent_id, 'folder' as type FROM folders WHERE user_id = ? AND is_deleted = 0 AND LOWER(folder_name) LIKE ? ORDER BY created_at DESC LIMIT ?";
                $bindings = [$user->id, $like, $user->id, $like, $limit];
            }

            $rows = DB::select($sql, $bindings);

            $rowsCollect = collect($rows);

            // Cache for folder records fetched on demand: id => ['folder_name'=>..., 'parent_id'=>...]
            $folderCache = [];

            $getFolder = function ($folderId) use (&$folderCache) {
                if ($folderId === null) {
                    return null;
                }
                if (array_key_exists($folderId, $folderCache)) {
                    return $folderCache[$folderId];
                }
                $f = DB::table('folders')->where('id', $folderId)->where('is_deleted', 0)->first();
                if (! $f) {
                    $folderCache[$folderId] = null;
                    return null;
                }
                $folderCache[$folderId] = ['id' => $f->id, 'folder_name' => $f->folder_name, 'parent_id' => $f->fol_folder_id];
                return $folderCache[$folderId];
            };

            $buildPath = function ($startFolderId) use (&$getFolder) {
                if ($startFolderId === null) {
                    return '/';
                }
                $parts = [];
                $cur = $startFolderId;
                // walk up until root
                while ($cur !== null) {
                    $f = $getFolder($cur);
                    if (! $f) {
                        break;
                    }
                    array_unshift($parts, $f['folder_name']);
                    $cur = $f['parent_id'];
                }
                return '/' . implode('/', $parts);
            };

            $mapped = $rowsCollect->map(function ($item) use ($buildPath) {
                $path = $buildPath($item->parent_id ?? null);
                $fullPath = $item->type === 'file'
                    ? rtrim($path, '/') . '/' . $item->name
                    : rtrim($path, '/');

                return [
                    'type' => $item->type,
                    'id' => (int) $item->id,
                    'name' => $item->name,
                    'full_path' => $fullPath,
                ];
            });

            // unique by type+full_path so same-named items in different folders remain.
            $final = $mapped->unique(function ($item) {
                return $item['type'] . '|' . $item['full_path'];
            })->values()->slice(0, $limit)->values()->all();

            return response()->json([
                'success' => true,
                'data' => ['suggestions' => $final],
                'error' => null,
                'meta' => null,
            ], 200);
        }
}
