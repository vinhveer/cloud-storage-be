<?php

namespace App\Http\Controllers\Api\Trash;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Folder;
use App\Models\File as FileModel;

class RestoreTrashController extends BaseApiController
{
    public function restore(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $type = $request->input('type');
        if (! in_array($type, ['file', 'folder'], true)) {
            return $this->fail('Invalid type. Must be "file" or "folder".', 400, 'INVALID_TYPE');
        }

        if ($type === 'file') {
            $file = FileModel::onlyTrashed()->where('id', $id)->where('user_id', $user->id)->first();
            if (! $file) {
                return $this->fail('File not found in trash', 404, 'FILE_NOT_FOUND');
            }

            // If file is inside a trashed folder, indicate to client that they should restore the top-level folder
            if ($file->folder_id !== null) {
                $parent = Folder::onlyTrashed()->where('id', $file->folder_id)->first();
                if ($parent) {
                    // Find top-most trashed ancestor
                    $top = $parent;
                    while ($top->parent && $top->parent->deleted_at !== null) {
                        $top = Folder::onlyTrashed()->where('id', $top->parent->id)->first() ?? $top;
                        if (! $top) { break; }
                    }

                    return $this->fail('Parent folder is trashed. Restore folder instead or confirm restoring file.', 409, 'PARENT_FOLDER_TRASHED', null, [
                        'suggested_action' => 'restore_folder',
                        'top_folder_id' => $top->id,
                        'top_folder_name' => $top->folder_name,
                    ]);
                }
            }

            // Restore the file
            try {
                $file->restore();
            } catch (\Throwable $e) {
                return $this->fail('Failed to restore file: ' . $e->getMessage(), 500, 'RESTORE_FAILED');
            }

            return $this->ok([
                'message' => 'Item restored successfully.',
                'restored_item' => [
                    'id' => $file->id,
                    'type' => 'file',
                    'display_name' => $file->display_name,
                ],
            ]);
        }

        // type === 'folder'
        $folder = Folder::onlyTrashed()->where('id', $id)->where('user_id', $user->id)->first();
        if (! $folder) {
            return $this->fail('Folder not found in trash', 404, 'FOLDER_NOT_FOUND');
        }

        // Cascade restore: restore folder, its trashed files, and all trashed descendant folders/files
        $restoreCount = 0;

        $cascade = function (Folder $f) use (&$cascade, &$restoreCount) {
            // Restore current folder
            try {
                $f->restore();
                $restoreCount++;
            } catch (\Throwable $e) {
                // continue
            }

            // Restore files in this folder
            try {
                FileModel::onlyTrashed()->where('folder_id', $f->id)->restore();
            } catch (\Throwable $e) {
                // continue
            }

            // Recurse into children
            $children = Folder::onlyTrashed()->where('fol_folder_id', $f->id)->get();
            foreach ($children as $child) {
                $cascade($child);
            }
        };

        try {
            $cascade($folder);
        } catch (\Throwable $e) {
            return $this->fail('Failed to restore folder: ' . $e->getMessage(), 500, 'RESTORE_FAILED');
        }

        return $this->ok([
            'message' => 'Item restored successfully.',
            'restored_item' => [
                'id' => $folder->id,
                'type' => 'folder',
                'display_name' => $folder->folder_name,
            ],
        ]);
    }
}
