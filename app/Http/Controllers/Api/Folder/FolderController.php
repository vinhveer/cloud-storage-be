<?php

namespace App\Http\Controllers\Api\Folder;

use App\Http\Controllers\Api\BaseApiController;
use App\Support\Traits\WithDevTrace;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\ListFoldersRequest;
use App\Services\FolderService;
use Illuminate\Support\Facades\Auth;

class FolderController extends BaseApiController
{
    use WithDevTrace;
    public function __construct(private readonly FolderService $folderService)
    {
    }

    public function index(ListFoldersRequest $request)
    {
        $user = Auth::user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $parentId = array_key_exists('parent_id', $data) && $data['parent_id'] !== null ? (int) $data['parent_id'] : null;
        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 15);

        try {
            $result = $this->folderService->listChildren($user, $parentId, $page, $perPage);
        } catch (\App\Exceptions\DomainValidationException $e) {
            return $this->fail($e->getMessage(), 404, 'PARENT_NOT_FOUND');
        }

        $items = $result['items'];
        $total = (int) $result['total'];
        $totalPages = (int) ceil($total / max($perPage, 1));

        return $this->ok([
            'data' => $items->map(fn($f) => [
                'folder_id' => $f->id,
                'folder_name' => $f->folder_name,
                'fol_folder_id' => $f->fol_folder_id,
                'created_at' => $f->created_at?->toISOString(),
            ])->all(),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ]);
    }

    public function show(int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function contents(int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function store(StoreFolderRequest $request)
    {
        $user = Auth::user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }
        $data = $request->validated();
        $folderName = (string) ($data['folder_name'] ?? '');
        $parentFolderId = array_key_exists('parent_folder_id', $data) && $data['parent_folder_id'] !== null
            ? (int) $data['parent_folder_id']
            : null;
        $folder = $this->folderService->createFolder(
            $user,
            $folderName,
            $parentFolderId
        );

        return $this->ok([
            'message' => 'Folder created successfully.',
            'folder' => [
                'folder_id' => $folder->id,
                'folder_name' => $folder->folder_name,
                'fol_folder_id' => $folder->fol_folder_id,
                'user_id' => $folder->user_id,
                'created_at' => $folder->created_at?->toISOString(),
            ],
        ]);
    }

    public function update(int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function destroy(int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function restore(int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function forceDelete(int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function copy(int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function move(int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function tree()
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }

    public function breadcrumb(int $id)
    {
        return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED');
    }
}



