<?php

namespace App\Http\Controllers\Api\Trash;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Folder;
use App\Models\File as FileModel;
use Illuminate\Support\Facades\DB;

class EmptyTrashController extends BaseApiController
{
    public function emptyTrash(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        // Count trashed items first
        $filesCount = (int) FileModel::onlyTrashed()->where('user_id', $user->id)->count();
        $folders = Folder::onlyTrashed()->where('user_id', $user->id)->get();
        $foldersCount = (int) $folders->count();

        // Delete in transaction and ensure proper order: files first, then folders (children before parents)
        try {
            DB::beginTransaction();

            // Delete all trashed files (including those in folders)
            FileModel::onlyTrashed()->where('user_id', $user->id)->forceDelete();

            if ($foldersCount > 0) {
                // Build set of trashed folder ids for quick lookup
                $trashedIds = $folders->pluck('id')->all();

                // Root trashed folders = trashed folders whose parent is null OR parent is not trashed
                $rootFolders = $folders->filter(function ($f) use ($trashedIds) {
                    return $f->fol_folder_id === null || ! in_array($f->fol_folder_id, $trashedIds);
                });

                $cascadeDelete = function (Folder $f) use (&$cascadeDelete) {
                    // delete children first
                    $children = Folder::onlyTrashed()->where('fol_folder_id', $f->id)->get();
                    foreach ($children as $child) {
                        $cascadeDelete($child);
                    }

                    // finally delete this folder
                    if ($f->exists) {
                        $f->forceDelete();
                    }
                };

                foreach ($rootFolders as $rf) {
                    $cascadeDelete($rf);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail('Failed to empty trash: ' . $e->getMessage(), 500, 'EMPTY_TRASH_FAILED');
        }

        return $this->ok([
            'message' => 'Trash emptied successfully.',
            'deleted_count' => [
                'files' => $filesCount,
                'folders' => $foldersCount,
            ],
        ]);
    }
}
