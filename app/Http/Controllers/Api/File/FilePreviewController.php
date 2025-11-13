<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Files\PreviewFileRequest;
use App\Services\FilePreviewService;
use App\Exceptions\DomainValidationException;

class FilePreviewController extends BaseApiController
{
    public function __construct(private readonly FilePreviewService $service) {}

    /**
     * GET /api/files/{id}/preview
     */
    public function show(PreviewFileRequest $request, int $id)
    {
        $user = $request->user();
        $token = $request->query('token') ?? $request->input('token');

        if (! $user && $token === null) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            $result = $this->service->generatePreview($user, $id, $token);
        } catch (DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);

            if (str_contains($lower, 'not found') && str_contains($lower, 'file')) {
                return $this->fail($message, 404, 'FILE_NOT_FOUND');
            }
            if (str_contains($lower, 'not found') && str_contains($lower, 'content')) {
                return $this->fail($message, 404, 'FILE_CONTENT_NOT_FOUND');
            }
            if (str_contains($lower, 'requires an external converter')) {
                return $this->fail($message, 501, 'PREVIEW_CONVERSION_UNAVAILABLE');
            }
            if (str_contains($lower, 'not supported')) {
                return $this->fail($message, 400, 'PREVIEW_NOT_SUPPORTED');
            }
            if (str_contains($lower, 'not accessible') || str_contains($lower, 'not owned') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }

            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        return $this->ok([
            'message' => 'Preview URL generated successfully.',
            'file' => $result['file'],
            'preview_url' => $result['preview_url'],
            'expires_in' => $result['expires_in'],
        ]);
    }
}
