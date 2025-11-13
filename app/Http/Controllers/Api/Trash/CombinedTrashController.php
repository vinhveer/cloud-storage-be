<?php

namespace App\Http\Controllers\Api\Trash;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Http\Requests\Trash\CombinedTrashIndexRequest;
use App\Http\Requests\Trash\CombinedTrashFolderContentsRequest;
use App\Models\Folder;
use App\Models\File as FileModel;
use Illuminate\Support\Facades\DB;
use App\Services\TrashService;

class CombinedTrashController extends BaseApiController
{
    /**
     * Inject TrashService via the container.
     *
     * @param \App\Services\TrashService $trashService
     */
    public function __construct(private readonly TrashService $trashService)
    {
    }
    public function index(CombinedTrashIndexRequest $request)
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

        $result = $this->trashService->getCombinedTrash($user, $search, $page, $perPage);

        return $this->ok($result);
    }

    /**
     * Return immediate trashed children folders and trashed files inside a trashed folder.
     * Frontend will call this when user clicks a trashed folder to drill into it.
     */
    public function folderContents(CombinedTrashFolderContentsRequest $request, int $id)
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

    $result = $this->trashService->getFolderContents($user, $folder->id, $search, $page, $perPage);

        return $this->ok($result);
    }
}
