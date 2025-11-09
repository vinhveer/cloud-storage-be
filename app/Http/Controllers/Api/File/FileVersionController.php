<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Services\FileService;
use App\Http\Requests\Files\UploadFileVersionRequest;
use App\Exceptions\DomainValidationException;

class FileVersionController extends BaseApiController
{
    public function __construct(private readonly FileService $files) {}

    public function store(UploadFileVersionRequest $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $uploaded = $request->file('file');
        $action = $request->input('action');
        $notes = $request->input('notes');

        try {
            $version = $this->files->createVersion($user, $id, $uploaded, $action, $notes);
        } catch (DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned') || str_contains($lower, 'not accessible') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            if (str_contains($lower, 'storage limit')) {
                return $this->fail($message, 409, 'STORAGE_LIMIT_EXCEEDED');
            }
            if (str_contains($lower, 'max_upload_size') || str_contains($lower, 'file size')) {
                return $this->fail($message, 422, 'FILE_TOO_LARGE');
            }

            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        $payload = [
            'message' => 'New version uploaded successfully.',
            'version' => [
                'version_id' => $version->id,
                'file_id' => $version->file_id,
                'user_id' => $version->user_id,
                'version_number' => $version->version_number,
                'uuid' => $version->uuid,
                'file_extension' => $version->file_extension,
                'mime_type' => $version->mime_type,
                'file_size' => (int) $version->file_size,
                'action' => $version->action,
                'notes' => $version->notes,
                'created_at' => $version->created_at ? $version->created_at->toIso8601String() : null,
            ],
        ];

        return $this->created($payload);
    }

    public function index(int $id) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function show(int $id, int $versionId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function download(int $id, int $versionId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function restore(int $id, int $versionId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
    public function destroy(int $id, int $versionId) { return $this->fail('Not implemented', 501, 'NOT_IMPLEMENTED'); }
}
