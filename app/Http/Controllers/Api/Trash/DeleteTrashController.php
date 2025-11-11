<?php

namespace App\Http\Controllers\Api\Trash;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Folder;
use App\Models\File as FileModel;
use Illuminate\Support\Facades\DB;

class DeleteTrashController extends BaseApiController
{
    public function destroy(Request $request, $id) // bỏ kiểu int ở đây
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $type = $request->input('type');
        if (! in_array($type, ['file', 'folder'], true)) {
            return $this->fail('Invalid type. Must be "file" or "folder".', 400, 'INVALID_TYPE');
        }

        $id = (int) $id; // ép kiểu an toàn

        try {
            DB::beginTransaction();

            if ($type === 'file') {
                $file = FileModel::onlyTrashed()
                    ->where('id', $id)
                    ->where('user_id', $user->id)
                    ->first();

                if (! $file) {
                    DB::rollBack();
                    return $this->fail('File not found in trash', 404, 'FILE_NOT_FOUND');
                }

                // Nếu file chưa nằm trong trash (chưa xóa mềm)
                if (! $file->trashed()) {
                    DB::rollBack();
                    return $this->fail('File is not in trash', 409, 'NOT_IN_TRASH');
                }

                $file->forceDelete();

                DB::commit();
                return $this->ok([
                    'success' => true,
                    'message' => 'File permanently deleted.',
                    'deleted_item' => [
                        'id' => $id,
                        'type' => 'file',
                        'display_name' => $file->display_name ?? $file->name,
                    ]
                ]);
            }

            // --- Folder ---
            $folder = Folder::onlyTrashed()
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (! $folder) {
                DB::rollBack();
                return $this->fail('Folder not found in trash', 404, 'FOLDER_NOT_FOUND');
            }

            if (! $folder->trashed()) {
                DB::rollBack();
                return $this->fail('Folder is not in trash', 409, 'NOT_IN_TRASH');
            }

            // Recursive cascade delete
            $cascade = function (Folder $f) use (&$cascade) {
                // Delete files
                FileModel::onlyTrashed()->where('folder_id', $f->id)->forceDelete();

                // Delete children
                $children = Folder::onlyTrashed()->where('fol_folder_id', $f->id)->get();
                foreach ($children as $child) {
                    $cascade($child);
                }

                // Delete this folder
                $f->forceDelete();
            };

            $cascade($folder);

            DB::commit();
            return $this->ok([
                'success' => true,
                'message' => 'Folder permanently deleted.',
                'deleted_item' => [
                    'id' => $id,
                    'type' => 'folder',
                    'display_name' => $folder->display_name ?? $folder->name,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->fail('Internal server error: ' . $e->getMessage(), 500, 'DELETE_FAILED');
        }
    }
}
