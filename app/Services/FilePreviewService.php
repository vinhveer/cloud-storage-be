<?php

namespace App\Services;

use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\DomainValidationException;
use Carbon\Carbon;

class FilePreviewService
{
    protected FileRepository $fileRepository;
    protected FileService $fileService;

    public function __construct(FileRepository $fileRepository, FileService $fileService)
    {
        $this->fileRepository = $fileRepository;
        $this->fileService = $fileService;
    }

    /**
     * Generate a preview URL (or converted preview) for a file.
     * Returns array with keys: file, preview_url, expires_in
     * Throws DomainValidationException on unsupported or missing content.
     *
     * Note: For some office formats conversion requires an external service and
     * is not implemented here; the method will raise an informative exception
     * in that case. TXT files are converted into a simple HTML preview.
     *
     * @param mixed $user
     * @param int $fileId
     * @param string|null $publicToken
     * @return array
     * @throws DomainValidationException
     */
    public function generatePreview($user, int $fileId, ?string $publicToken = null): array
    {
        // Validate access (owner/share/public token) - will throw if not allowed
        $file = $this->fileService->checkAccessForFile($user, $fileId, 'view', $publicToken);

        // get latest version
        $version = $file->versions()->orderByDesc('version_number')->first();
        if (! $version) {
            throw new DomainValidationException('Version not found for file');
        }

        $ext = strtolower((string) ($version->file_extension ?? ''));
        $mime = $version->mime_type ?: ($file->mime_type ?? 'application/octet-stream');
        $versionNumber = $version->version_number;

        $path = "files/{$file->id}/v{$versionNumber}/" . $version->uuid . ($ext ? ".{$ext}" : '');
        $diskName = config('filesystems.default', 'local');
        $disk = Storage::disk($diskName);

        // Helper to build the file summary returned to client
        $fileSummary = [
            'file_id' => $file->id,
            'display_name' => $file->display_name ?? ($version->uuid . ($ext ? ".{$ext}" : '')),
            'mime_type' => $mime,
            'file_size' => (int) ($version->file_size ?? $file->file_size ?? 0),
        ];

        // Direct previewable types: pdf, images, video, audio
        if ($ext === 'pdf' || str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/') || str_starts_with($mime, 'audio/')) {
            // prefer a temporary signed URL when supported
            $expires = 3600; // 1 hour
            if (method_exists($disk, 'temporaryUrl')) {
                $url = $disk->temporaryUrl($path, Carbon::now()->addSeconds($expires));
            } else {
                // fallback to public URL or Storage::url
                if (method_exists($disk, 'url')) {
                    $url = $disk->url($path);
                } else {
                    throw new DomainValidationException('Storage driver does not support URL generation');
                }
                $expires = 0; // unknown
            }

            return [
                'file' => $fileSummary,
                'preview_url' => $url,
                'expires_in' => $expires,
            ];
        }

        // TXT files: convert to simple HTML preview on the fly
        if ($ext === 'txt') {
            if (! $disk->exists($path)) {
                throw new DomainValidationException('File content not found');
            }

            $content = $disk->get($path);

            // build a previews path and store a simple HTML file
            $previewPath = "previews/{$file->id}/v{$versionNumber}/preview.html";
            $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($fileSummary['display_name']) . '</title></head><body><pre>' . e($content) . '</pre></body></html>';

            $disk->put($previewPath, $html);

            $expires = 3600;
            if (method_exists($disk, 'temporaryUrl')) {
                $url = $disk->temporaryUrl($previewPath, Carbon::now()->addSeconds($expires));
            } elseif (method_exists($disk, 'url')) {
                $url = $disk->url($previewPath);
                $expires = 0;
            } else {
                throw new DomainValidationException('Storage driver does not support URL generation');
            }

            return [
                'file' => $fileSummary,
                'preview_url' => $url,
                'expires_in' => $expires,
            ];
        }

        // Office documents (doc, docx, xls, xlsx, ppt, pptx) typically require conversion
        $officeExts = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        if (in_array($ext, $officeExts, true)) {
            // Conversion to PDF/HTML requires an external converter (LibreOffice, unoconv, cloud service, etc.)
            throw new DomainValidationException('Preview conversion for this file type requires an external converter and is not available');
        }

        // Other types: not supported
        throw new DomainValidationException('Preview not supported for this file type.');
    }
}
