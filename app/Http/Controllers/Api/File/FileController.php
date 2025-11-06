<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Http\Requests\Files\UploadFileRequest;
use App\Http\Requests\Files\ListFilesRequest;
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
            if (str_contains(strtolower($message), 'storage limit')) {
                return $this->fail($message, 409, 'STORAGE_LIMIT_EXCEEDED');
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
    public function show(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function download(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function update(Request $request, int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function destroy(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function restore(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function forceDelete(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function copy(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function move(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function recent() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function sharedWithMe() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function sharedByMe() { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
