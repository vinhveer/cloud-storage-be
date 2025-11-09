<?php

namespace App\Http\Controllers\Api\Folder;

use App\Http\Controllers\Api\BaseApiController;
use App\Support\Traits\WithDevTrace;
use App\Http\Requests\Folders\StoreFolderRequest;
use App\Http\Requests\Folders\ListFoldersRequest;
use App\Http\Requests\Folders\UpdateFolderRequest;
use App\Http\Requests\Folders\CopyFolderRequest;
use App\Http\Requests\Folders\MoveFolderRequest;
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
        $user = Auth::user();
        $token = request()->query('token') ?? request()->input('token');
        if (! $user && $token === null) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $folder = $this->folderService->checkAccessForFolder($user, $id, 'view', $token);
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            if (str_contains($lower, 'not accessible') || str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        return $this->ok([
            'folder_id' => $folder->id,
            'folder_name' => $folder->folder_name,
            'fol_folder_id' => $folder->fol_folder_id,
            'user_id' => $folder->user_id,
            'created_at' => $folder->created_at?->toISOString(),
            'is_deleted' => (bool) $folder->is_deleted,
            'deleted_at' => $folder->deleted_at?->toISOString(),
        ]);
    }

    public function contents(int $id)
    {
        $user = Auth::user();
        $token = request()->query('token') ?? request()->input('token');
        if (! $user && $token === null) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            if ($user) {
                $result = $this->folderService->listContents($user, $id);
            } else {
                // validate token against folder/public links
                $this->folderService->checkAccessForFolder(null, $id, 'view', $token);
                $result = $this->folderService->listContentsPublic($id);
            }
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            if (str_contains($lower, 'not accessible') || str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        $folders = $result['folders'];
        $files = $result['files'];

        return $this->ok([
            'folders' => $folders->map(fn($f) => [
                'folder_id' => $f->id,
                'folder_name' => $f->folder_name,
                'created_at' => $f->created_at?->toISOString(),
            ])->all(),
            'files' => $files->map(fn($f) => [
                'file_id' => $f->id,
                'display_name' => $f->display_name,
                'file_size' => (int) $f->file_size,
                'mime_type' => $f->mime_type,
                'file_extension' => $f->file_extension,
                'last_opened_at' => $f->last_opened_at?->toISOString(),
            ])->all(),
        ]);
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

    public function update(UpdateFolderRequest $request, int $id)
    {
        $user = Auth::user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $folderName = (string) ($data['folder_name'] ?? '');

        try {
            $folder = $this->folderService->renameFolder($user, $id, $folderName);
        } catch (\App\Exceptions\DomainValidationException $e) {
            return $this->fail($e->getMessage(), 404, 'FOLDER_NOT_FOUND');
        }

        return $this->ok([
            'message' => 'Folder renamed successfully.',
            'folder' => [
                'folder_id' => $folder->id,
                'folder_name' => $folder->folder_name,
            ],
        ]);
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        // authorize: require edit permission on the folder
        try {
            $this->folderService->checkAccessForFolder($user, $id, 'edit');
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            return $this->fail($message, 403, 'FORBIDDEN');
        }

        try {
            $this->folderService->softDeleteFolder($user, $id);
        } catch (\App\Exceptions\DomainValidationException $e) {
            return $this->fail($e->getMessage(), 400, 'DELETE_FAILED');
        }

        return $this->ok([
            'message' => 'Folder moved to trash.',
        ]);
    }

    public function copy(CopyFolderRequest $request, int $id)
    {
        $user = Auth::user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $targetFolderId = array_key_exists('target_folder_id', $data) && $data['target_folder_id'] !== null
            ? (int) $data['target_folder_id']
            : null;
        // optional public-link token in query or body
        $token = request()->query('token') ?? request()->input('token');

        // authorize: require download permission on source (allow public-token check when provided)
        try {
            if ($token !== null) {
                $this->folderService->checkAccessForFolder(null, $id, 'download', $token);
            } else {
                $this->folderService->checkAccessForFolder($user, $id, 'download');
            }
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            return $this->fail($message, 403, 'FORBIDDEN');
        }

        try {
            $newFolder = $this->folderService->copyFolder($user, $id, $targetFolderId);
        } catch (\App\Exceptions\DomainValidationException $e) {
            return $this->fail($e->getMessage(), 400, 'COPY_FAILED');
        }

        return $this->ok([
            'message' => 'Folder copied successfully.',
            'new_folder_id' => $newFolder->id,
        ]);
    }

    public function move(MoveFolderRequest $request, int $id)
    {
        $user = Auth::user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $targetFolderId = array_key_exists('target_folder_id', $data) && $data['target_folder_id'] !== null
            ? (int) $data['target_folder_id']
            : null;

        // authorize: require edit permission on source folder
        try {
            $this->folderService->checkAccessForFolder($user, $id, 'edit');
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            return $this->fail($message, 403, 'FORBIDDEN');
        }

        try {
            $this->folderService->moveFolder($user, $id, $targetFolderId);
        } catch (\App\Exceptions\DomainValidationException $e) {
            return $this->fail($e->getMessage(), 400, 'MOVE_FAILED');
        }

        return $this->ok([
            'message' => 'Folder moved successfully.',
        ]);
    }

    public function tree()
    {
        $user = Auth::user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $result = $this->folderService->tree($user);
        } catch (\Exception $e) {
            return $this->fail('Failed to build folder tree', 500, 'TREE_ERROR');
        }

        return $this->ok($result);
    }

    public function breadcrumb(int $id)
    {
        $user = Auth::user();
        $token = request()->query('token') ?? request()->input('token');
        if (! $user && $token === null) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            // validate access; this will throw DomainValidationException on failure
            $folder = $this->folderService->checkAccessForFolder($user, $id, 'view', $token);
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            if (str_contains($lower, 'not accessible') || str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        // Build breadcrumb from root -> current
        $crumbs = [];
        $cursor = $folder;
        while ($cursor !== null) {
            $crumbs[] = [
                'folder_id' => $cursor->id,
                'folder_name' => $cursor->folder_name,
            ];
            $cursor = $cursor->parent;
        }

        // Currently collected from current -> root, reverse to root -> current
        $breadcrumb = array_reverse($crumbs);

        return $this->ok(['breadcrumb' => $breadcrumb]);
    }
}



