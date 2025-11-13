<?php

namespace App\Http\Controllers\Api\Trash;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Trash\RestoreTrashRequest;
use App\Models\Folder;
use App\Models\File as FileModel;
use App\Services\TrashService;
use App\Exceptions\DomainValidationException;

class RestoreTrashController extends BaseApiController
{
    private TrashService $trashService;

    public function __construct(TrashService $trashService)
    {
        $this->trashService = $trashService;
    }
    public function restore(RestoreTrashRequest $request, int $id)
    {
        $data = $request->validated();

        $type = $data['type'];
        $user = $request->user();

        try {
            if ($type === 'file') {
                $file = $this->trashService->restoreFile($user, $id);

                return $this->ok([
                    'message' => 'Item restored successfully.',
                    'restored_item' => [
                        'id' => $file->id,
                        'type' => 'file',
                        'display_name' => $file->display_name,
                    ],
                ]);
            }

            // folder
            $folder = $this->trashService->restoreFolder($user, $id);

            return $this->ok([
                'message' => 'Item restored successfully.',
                'restored_item' => [
                    'id' => $folder->id,
                    'type' => 'folder',
                    'display_name' => $folder->folder_name,
                ],
            ]);
        } catch (DomainValidationException $e) {
            return $this->fail($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->fail('Failed to restore item: ' . $e->getMessage(), 500);
        }
    }
}
