<?php

namespace App\Http\Controllers\Api\Trash;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Http\Requests\Trash\EmptyTrashRequest;
use App\Models\Folder;
use App\Models\File as FileModel;
use Illuminate\Support\Facades\DB;
use App\Services\TrashService;
use App\Exceptions\DomainValidationException;

class EmptyTrashController extends BaseApiController
{
    public function emptyTrash(EmptyTrashRequest $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $trashService = app(TrashService::class);
        try {
            $counts = $trashService->emptyTrash($user);
            return $this->ok([
                'message' => 'Trash emptied successfully.',
                'deleted_count' => [
                    'files' => $counts['files'] ?? 0,
                    'folders' => $counts['folders'] ?? 0,
                ],
            ]);
        } catch (DomainValidationException $e) {
            return $this->fail('Failed to empty trash: ' . $e->getMessage(), 400, 'EMPTY_TRASH_FAILED');
        } catch (\Throwable $e) {
            return $this->fail('Failed to empty trash: ' . $e->getMessage(), 500, 'EMPTY_TRASH_FAILED');
        }
    }
}
