<?php

namespace App\Http\Controllers\Api\Trash;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Trash\DeleteTrashRequest;
use App\Exceptions\DomainValidationException;
use App\Services\TrashService;

class DeleteTrashController extends BaseApiController
{
    private TrashService $trashService;

    public function __construct(TrashService $trashService)
    {
        $this->trashService = $trashService;
    }

    /**
     * Permanently delete a trashed file or folder.
     * Expects validated `type` via DeleteTrashRequest (file|folder) and an id param.
     */
    public function destroy(DeleteTrashRequest $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $type = $request->input('type');

        try {
            if ($type === 'file') {
                $this->trashService->permanentlyDeleteFile($user, (int) $id);
            } else {
                $this->trashService->permanentlyDeleteFolder($user, (int) $id);
            }

            return $this->ok(['message' => 'Item permanently deleted.']);
        } catch (DomainValidationException $e) {
            // service already rolled back where necessary
            return $this->fail('Failed to delete item: ' . $e->getMessage(), 400, 'DELETE_FAILED');
        } catch (\Throwable $e) {
            return $this->fail('Failed to delete item: ' . $e->getMessage(), 500, 'DELETE_FAILED');
        }
    }
}
