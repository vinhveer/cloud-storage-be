<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\FileService;
use App\Services\FileVersionService;
use App\Http\Requests\Files\UploadFileVersionRequest;
use App\Exceptions\DomainValidationException;

class FileVersionController extends BaseApiController
{
    public function __construct(private readonly FileService $files, private readonly FileVersionService $versions) {}

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

    public function index(\App\Http\Requests\Files\ListFileVersionsRequest $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);
        // enforce reasonable bounds
        $perPage = max(1, min(100, $perPage));

        try {
            $file = $this->files->getFileForUser($user, $id, 'view');
        } catch (DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned') || str_contains($lower, 'not accessible') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }

            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        $paginator = $file->versions()->orderByDesc('version_number')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = array_map(function ($v) {
            return [
                'version_id' => $v->id,
                'version_number' => (int) $v->version_number,
                'action' => $v->action,
                'notes' => $v->notes,
                'file_size' => (int) $v->file_size,
                'created_at' => $v->created_at ? $v->created_at->toIso8601String() : null,
            ];
        }, $paginator->items());

        $payload = [
            'data' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'total_items' => $paginator->total(),
            ],
        ];

        return $this->ok($payload);
    }
    public function show(Request $request, int $id, int $versionId)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $file = $this->files->getFileForUser($user, $id, 'view');
        } catch (DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned') || str_contains($lower, 'not accessible') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }

            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        $version = $file->versions()->where('id', $versionId)->first();
        if (! $version) {
            return $this->fail('File version not found', 404, 'FILE_VERSION_NOT_FOUND');
        }

        $uploader = $version->user;

        $payload = [
            'version_id' => $version->id,
            'file_id' => $version->file_id,
            'version_number' => (int) $version->version_number,
            'uuid' => $version->uuid,
            'file_extension' => $version->file_extension,
            'mime_type' => $version->mime_type,
            'file_size' => (int) $version->file_size,
            'action' => $version->action,
            'notes' => $version->notes,
            'created_at' => $version->created_at ? $version->created_at->toIso8601String() : null,
            'uploaded_by' => $uploader ? [
                'user_id' => $uploader->id,
                'name' => $uploader->name,
            ] : null,
        ];

        return $this->ok($payload);
    }
    public function download(Request $request, int $id, int $versionId)
    {
        $user = $request->user();
        $token = $request->query('token') ?? $request->input('token');
        if (! $user && $token === null) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            // Validate access via owner/share/public link (checkAccessForFile will throw if not allowed)
            $this->files->checkAccessForFile($user, $id, 'download', $token);

            if ($user) {
                $info = $this->versions->prepareVersionDownloadForUser($user, $id, $versionId);
            } else {
                $info = $this->versions->prepareVersionDownloadForPublic($id, $versionId);
            }
        } catch (DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned') || str_contains($lower, 'not accessible') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            if (str_contains($lower, 'content not found') || str_contains($lower, 'version not found')) {
                return $this->fail($message, 404, 'FILE_CONTENT_NOT_FOUND');
            }

            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        $disk = Storage::disk($info['disk']);
        $path = $info['path'];
        $downloadName = $info['download_name'];
        $mime = $info['mime'] ?? 'application/octet-stream';

        // Mark parent file as opened (throttled inside service). We update before returning the stream.
        try {
            $this->files->markOpened($id);
        } catch (\Exception $_) {
            // ignore non-fatal errors
        }

        return $disk->download($path, $downloadName, ['Content-Type' => $mime]);
    }
    public function restore(Request $request, int $id, int $versionId)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $version = $this->versions->restoreVersionForUser($user, $id, $versionId);
        } catch (DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                // differentiate file vs content/version not found
                if (str_contains($lower, 'version')) {
                    return $this->fail($message, 404, 'FILE_VERSION_NOT_FOUND');
                }
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not owned') || str_contains($lower, 'not accessible') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            if (str_contains($lower, 'storage limit')) {
                return $this->fail($message, 409, 'STORAGE_LIMIT_EXCEEDED');
            }
            if (str_contains($lower, 'content not found')) {
                return $this->fail($message, 404, 'FILE_CONTENT_NOT_FOUND');
            }

            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        $payload = [
            'message' => 'Version restored successfully.',
            'restored_to_version' => [
                'version_id' => $version->id,
                'version_number' => (int) $version->version_number,
                'action' => $version->action,
                'restored_at' => $version->created_at ? $version->created_at->toIso8601String() : null,
            ],
        ];

        return $this->created($payload);
    }
    public function destroy(Request $request, int $id, int $versionId)
    {
        $user = $request->user();
        if (! $user) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        // Only admin may delete versions
        // Assumption: User model has a `role` attribute and admin role is the string 'admin'
        // Eloquent model attributes are not real class properties, so avoid property_exists().
        if (($user->role ?? null) !== 'admin') {
            return $this->fail('Forbidden', 403, 'FORBIDDEN');
        }

        try {
            $this->versions->deleteVersionForAdmin($user, $id, $versionId);
        } catch (DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                if (str_contains($lower, 'version')) {
                    return $this->fail($message, 404, 'FILE_VERSION_NOT_FOUND');
                }
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'forbidden') || str_contains($lower, 'not owned') || str_contains($lower, 'not accessible')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            if (str_contains($lower, 'content not found') || str_contains($lower, 'file content not found')) {
                return $this->fail($message, 404, 'FILE_CONTENT_NOT_FOUND');
            }

            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        $payload = [
            'message' => 'Version deleted successfully.',
        ];

        return $this->ok($payload);
    }
}
