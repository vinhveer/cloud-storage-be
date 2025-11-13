<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Files\UploadFileRequest;
use App\Http\Requests\Files\ListFilesRequest;
use App\Http\Requests\Files\UpdateFileRequest;
use App\Http\Requests\Files\CopyFileRequest;
use App\Services\FileService;

class FileController extends BaseApiController
{
    public function __construct(private readonly FileService $files) {}

    public function store(UploadFileRequest $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $fileModel = $this->files->upload(
                $user,
                $request->file('file'),
                $request->input('folder_id'),
                $request->input('display_name')
            );
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'storage limit')) {
                return $this->fail($message, 409, 'STORAGE_LIMIT_EXCEEDED');
            }
            if (str_contains($lower, 'max_upload_size') || str_contains($lower, 'file size')) {
                return $this->fail($message, 422, 'FILE_TOO_LARGE');
            }
            return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
        }

        // Prepare response payload per requirement
        $payload = [
            'message' => 'File uploaded successfully.',
            'file' => [
                'file_id' => $fileModel->id,
                'display_name' => $fileModel->display_name,
                'file_size' => $fileModel->file_size,
                'mime_type' => $fileModel->mime_type,
                'file_extension' => $fileModel->file_extension,
                'folder_id' => $fileModel->folder_id,
                'user_id' => $fileModel->user_id,
                'created_at' => $fileModel->created_at,
            ],
        ];

        return $this->created($payload);
    }
    public function index(ListFilesRequest $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $folderId = array_key_exists('folder_id', $data) && $data['folder_id'] !== null ? (int) $data['folder_id'] : null;
        $search = $data['search'] ?? null;
        $extension = $data['extension'] ?? null;
        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 15);

        try {
            $result = $this->files->listFiles($user, $folderId, $search, $extension, $page, $perPage);
        } catch (\App\Exceptions\DomainValidationException $e) {
            return $this->fail($e->getMessage(), 404, 'FOLDER_NOT_FOUND');
        }

        $items = $result['items'];
        $total = (int) $result['total'];
        $totalPages = (int) ceil($total / max($perPage, 1));

        return $this->ok([
            'data' => $items->map(fn($f) => [
                'file_id' => $f->id,
                'display_name' => $f->display_name,
                'file_size' => (int) $f->file_size,
                'mime_type' => $f->mime_type,
                'file_extension' => $f->file_extension,
                'folder_id' => $f->folder_id,
                'user_id' => $f->user_id,
                'is_deleted' => (bool) $f->is_deleted,
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
        $user = request()->user();
        $token = request()->query('token') ?? request()->input('token');
        if (! $user && $token === null) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $file = $this->files->checkAccessForFile($user, $id, 'view', $token);
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not accessible') || str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        // Prepare response payload per project convention
        $payload = [
            'file_id' => $file->id,
            'display_name' => $file->display_name,
            'file_size' => (int) $file->file_size,
            'mime_type' => $file->mime_type,
            'file_extension' => $file->file_extension,
            'folder_id' => $file->folder_id,
            'user_id' => $file->user_id,
            'is_deleted' => (bool) $file->is_deleted,
            'created_at' => $file->created_at ? $file->created_at->toIso8601String() : null,
            'last_opened_at' => $file->last_opened_at ? $file->last_opened_at->toIso8601String() : null,
        ];

        return $this->ok($payload);
    }
    public function download(int $id)
    {
        $user = request()->user();
        $token = request()->query('token') ?? request()->input('token');
        if (! $user && $token === null) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            // Validate access via owner/share/public link
            $this->files->checkAccessForFile($user, $id, 'download', $token);

            // Prepare download info based on context
            if ($user) {
                $info = $this->files->prepareDownloadForUser($user, $id);
            } else {
                $info = $this->files->prepareDownloadForPublic($id);
            }
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not accessible') || str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            if (str_contains($lower, 'content not found') || str_contains($lower, 'version not found')) {
                return $this->fail($message, 404, 'FILE_CONTENT_NOT_FOUND');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        // Use Storage download to return a BinaryFileResponse with proper headers
        $disk = Storage::disk($info['disk']);
        $path = $info['path'];
        $downloadName = $info['download_name'];
        $mime = $info['mime'] ?? 'application/octet-stream';

        // The Storage::download will set Content-Disposition: attachment; filename="..."
        // Mark file as opened (throttled inside service) since we are delivering content.
        try {
            $this->files->markOpened($id);
        } catch (\Exception $_) {
            // Non-fatal: don't prevent download if marking fails.
        }

        return $disk->download($path, $downloadName, ['Content-Type' => $mime]);
    }
    public function update(UpdateFileRequest $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $data = $request->validated();
        $displayName = array_key_exists('display_name', $data) ? $data['display_name'] : null;
        $folderId = array_key_exists('folder_id', $data) && $data['folder_id'] !== null ? (int) $data['folder_id'] : null;

        // authorize: require edit permission on the file
        try {
            $this->files->checkAccessForFile($user, $id, 'edit');
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            return $this->fail($message, 403, 'FORBIDDEN');
        }

        try {
            $file = $this->files->update($user, $id, $displayName, $folderId);
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'parent folder') || str_contains($lower, 'folder not')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            if (str_contains($lower, 'not found') && str_contains($lower, 'file')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            if (str_contains($lower, 'no data') || str_contains($lower, 'at least one')) {
                return $this->fail($message, 400, 'BAD_REQUEST');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        $payload = [
            'message' => 'File updated successfully.',
            'file' => [
                'file_id' => $file->id,
                'display_name' => $file->display_name,
                'folder_id' => $file->folder_id,
            ],
        ];

        return $this->ok($payload);
    }
    public function destroy(int $id)
    {
        $user = request()->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        // authorize: require edit permission
        try {
            $this->files->checkAccessForFile($user, $id, 'edit');
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            return $this->fail($message, 403, 'FORBIDDEN');
        }

        try {
            $this->files->moveToTrash($user, $id);
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            if (str_contains($lower, 'already')) {
                return $this->fail($message, 400, 'BAD_REQUEST');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        return $this->ok([
            'success' => true,
            'message' => 'File moved to trash.',
        ]);
    }
    public function copy(CopyFileRequest $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

    $data = $request->validated();
    // allow null/omitted to represent root
    $destinationFolderId = array_key_exists('destination_folder_id', $data) ? ($data['destination_folder_id'] === null ? null : (int) $data['destination_folder_id']) : null;
        // allow flag via query or body; use Request::boolean which checks input and query string
        $onlyLatest = $request->boolean('only_latest');
        // optional public-link token
        $token = $request->query('token') ?? $request->input('token');

        // authorize: require download permission on source (allow public-token check when provided)
        try {
            if ($token !== null) {
                $this->files->checkAccessForFile(null, $id, 'download', $token);
            } else {
                $this->files->checkAccessForFile($user, $id, 'download');
            }
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            return $this->fail($message, 403, 'FORBIDDEN');
        }

        try {
            $newFile = $this->files->copy($user, $id, $destinationFolderId, $onlyLatest);
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'storage limit')) {
                return $this->fail($message, 409, 'STORAGE_LIMIT_EXCEEDED');
            }
            if (str_contains($lower, 'not found') && str_contains($lower, 'version')) {
                return $this->fail($message, 404, 'FILE_VERSION_NOT_FOUND');
            }
            if (str_contains($lower, 'content not found')) {
                return $this->fail($message, 404, 'FILE_CONTENT_NOT_FOUND');
            }
            if (str_contains($lower, 'not found') && str_contains($lower, 'destination')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            if (str_contains($lower, 'not found') && str_contains($lower, 'file')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        return $this->ok([
            'success' => true,
            'message' => 'File copied successfully.',
            'new_file' => [
                'file_id' => $newFile->id,
                'display_name' => $newFile->display_name,
                'folder_id' => $newFile->folder_id,
            ],
        ]);
    }
    public function move(\App\Http\Requests\Files\MoveFileRequest $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

            $data = $request->validated();
            // allow null/omitted to represent root
            $destinationFolderId = array_key_exists('destination_folder_id', $data) ? ($data['destination_folder_id'] === null ? null : (int) $data['destination_folder_id']) : null;

        // authorize: require edit permission on the file
        try {
            $this->files->checkAccessForFile($user, $id, 'edit');
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            return $this->fail($message, 403, 'FORBIDDEN');
        }

        try {
            $file = $this->files->move($user, $id, $destinationFolderId);
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found') && str_contains($lower, 'destination')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            if (str_contains($lower, 'not found') && str_contains($lower, 'file')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        return $this->ok([
            'success' => true,
            'message' => 'File moved successfully.',
            'file' => [
                'file_id' => $file->id,
                'folder_id' => $file->folder_id,
            ],
        ]);
    }
    public function recent()
    {
        $user = request()->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $limit = (int) max(1, min(100, request()->query('limit', 20)));
        $includeShared = request()->boolean('include_shared', true);

        $items = $this->files->recent($user, $limit, $includeShared);

        return $this->ok([
            'data' => $items->all(),
        ]);
    }
    public function sharedWithMe() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function sharedByMe() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
