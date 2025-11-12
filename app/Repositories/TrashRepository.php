<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Folder;
use App\Models\File as FileModel;

class TrashRepository
{
    /**
     * Return array of root trashed folder IDs for a user.
     * Root folders are trashed folders whose parent is null or whose parent is not trashed.
     *
     * @param int $userId
     * @return int[]
     */
    public function getRootTrashedFolderIdsByUser(int $userId): array
    {
        $ids = DB::table('folders as f')
            ->leftJoin('folders as parent', 'f.fol_folder_id', '=', 'parent.id')
            ->whereNotNull('f.deleted_at')
            ->where('f.user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('f.fol_folder_id')
                  ->orWhereNull('parent.deleted_at');
            })
            ->select('f.id')
            ->get()
            ->pluck('id')
            ->all();

        return $ids;
    }

    /**
     * Return array of root trashed file IDs for a user.
     * Root files are trashed files whose folder is null or whose parent folder is not trashed.
     *
     * @param int $userId
     * @return int[]
     */
    public function getRootTrashedFileIdsByUser(int $userId): array
    {
        $ids = DB::table('files as fi')
            ->leftJoin('folders as pf', 'fi.folder_id', '=', 'pf.id')
            ->whereNotNull('fi.deleted_at')
            ->where('fi.user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('fi.folder_id')
                  ->orWhereNull('pf.deleted_at');
            })
            ->select('fi.id')
            ->get()
            ->pluck('id')
            ->all();

        return $ids;
    }

    public function findTrashedFolderByIdAndUser(int $id, int $userId): ?Folder
    {
        return Folder::onlyTrashed()->where('id', $id)->where('user_id', $userId)->first();
    }

    public function findTrashedFileByIdAndUser(int $id, int $userId): ?FileModel
    {
        return FileModel::onlyTrashed()->where('id', $id)->where('user_id', $userId)->first();
    }

    public function getTrashedFilesInFolder(int $userId, int $folderId)
    {
        return FileModel::onlyTrashed()->where('user_id', $userId)->where('folder_id', $folderId)->get();
    }

    public function getTrashedChildrenFolders(int $userId, int $folderId)
    {
        return Folder::onlyTrashed()->where('user_id', $userId)->where('fol_folder_id', $folderId)->get();
    }
}
