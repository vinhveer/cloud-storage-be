<?php

namespace App\Http\Controllers\Api\Trash;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Folder;
use App\Models\File as FileModel;

class CombinedTrashController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $search = $request->query('search');
        $page = (int) ($request->query('page', 1));
        $perPage = (int) ($request->query('per_page', 15));
        $page = max($page, 1);
        $perPage = max($perPage, 1);

        // Folders: only top-level deleted folders (root in trash). Root means parent is null or parent is NOT trashed.
        $foldersQuery = Folder::onlyTrashed()
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('fol_folder_id')
                  ->orWhereHas('parent', fn($p) => $p->whereNull('deleted_at'));
            });

        if ($search) {
            $foldersQuery->where('folder_name', 'like', "%{$search}%");
        }

        $totalFolders = (int) $foldersQuery->count();
        $folders = $foldersQuery->orderBy('deleted_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Files: deleted files whose parent folder is not deleted (or has no folder)
        $filesQuery = FileModel::onlyTrashed()->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('folder_id')
                  ->orWhereHas('folder', fn($p) => $p->whereNull('deleted_at'));
            });

        if ($search) {
            $filesQuery->where('display_name', 'like', "%{$search}%");
        }

        $totalFiles = (int) $filesQuery->count();
        $files = $filesQuery->orderBy('deleted_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // For the main trash listing we only return top-level trashed folders (folder parents)
        // so the frontend can request the contents of a folder when the user clicks it.
        $folderData = $folders->map(function ($folder) {
            $hasChildren = Folder::onlyTrashed()->where('fol_folder_id', $folder->id)->exists();
            $filesCount = FileModel::onlyTrashed()->where('folder_id', $folder->id)->count();

            return [
                'folder_id' => $folder->id,
                'folder_name' => $folder->folder_name,
                'deleted_at' => $folder->deleted_at ? $folder->deleted_at->toIso8601String() : null,
                'has_children' => (bool) $hasChildren,
                'trashed_files_count' => (int) $filesCount,
            ];
        })->all();

        $fileData = $files->map(fn($f) => [
            'file_id' => $f->id,
            'display_name' => $f->display_name,
            'file_size' => (int) $f->file_size,
            'mime_type' => $f->mime_type,
            'file_extension' => $f->file_extension,
            'deleted_at' => $f->deleted_at ? $f->deleted_at->toIso8601String() : null,
        ])->all();

        $response = [
            'folders' => $folderData,
            'folders_pagination' => [
                'current_page' => $page,
                'total_pages' => (int) ceil($totalFolders / max($perPage, 1)),
                'total_items' => $totalFolders,
            ],
            'files' => $fileData,
            'files_pagination' => [
                'current_page' => $page,
                'total_pages' => (int) ceil($totalFiles / max($perPage, 1)),
                'total_items' => $totalFiles,
            ],
        ];

        return $this->ok($response);
    }

    /**
     * Return immediate trashed children folders and trashed files inside a trashed folder.
     * Frontend will call this when user clicks a trashed folder to drill into it.
     */
    public function folderContents(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        // Validate folder exists and is trashed and belongs to user
        $folder = Folder::onlyTrashed()->where('user_id', $user->id)->where('id', $id)->first();
        if (! $folder) {
            return $this->fail('Folder not found in trash', 404, 'FOLDER_NOT_FOUND');
        }

        $search = $request->query('search');
        $page = (int) ($request->query('page', 1));
        $perPage = (int) ($request->query('per_page', 15));
        $page = max($page, 1);
        $perPage = max($perPage, 1);

        $childrenQuery = Folder::onlyTrashed()->where('user_id', $user->id)->where('fol_folder_id', $folder->id);
        if ($search) {
            $childrenQuery->where('folder_name', 'like', "%{$search}%");
        }
        $totalChildren = (int) $childrenQuery->count();
        $children = $childrenQuery->orderBy('deleted_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()->map(fn($f) => [
                'folder_id' => $f->id,
                'folder_name' => $f->folder_name,
                'deleted_at' => $f->deleted_at ? $f->deleted_at->toIso8601String() : null,
            ])->all();

        $filesQuery = FileModel::onlyTrashed()->where('user_id', $user->id)->where('folder_id', $folder->id);
        if ($search) {
            $filesQuery->where('display_name', 'like', "%{$search}%");
        }
        $totalFiles = (int) $filesQuery->count();
        $files = $filesQuery->orderBy('deleted_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()->map(fn($f) => [
                'file_id' => $f->id,
                'display_name' => $f->display_name,
                'file_size' => (int) $f->file_size,
                'mime_type' => $f->mime_type,
                'file_extension' => $f->file_extension,
                'deleted_at' => $f->deleted_at ? $f->deleted_at->toIso8601String() : null,
            ])->all();

        return $this->ok([
            'folder_id' => $folder->id,
            'folder_name' => $folder->folder_name,
            'deleted_at' => $folder->deleted_at ? $folder->deleted_at->toIso8601String() : null,
            'folders' => $children,
            'folders_pagination' => [
                'current_page' => $page,
                'total_pages' => (int) ceil($totalChildren / max($perPage, 1)),
                'total_items' => $totalChildren,
            ],
            'files' => $files,
            'files_pagination' => [
                'current_page' => $page,
                'total_pages' => (int) ceil($totalFiles / max($perPage, 1)),
                'total_items' => $totalFiles,
            ],
        ]);
    }
}
