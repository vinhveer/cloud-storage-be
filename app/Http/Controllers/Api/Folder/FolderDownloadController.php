<?php

namespace App\Http\Controllers\Api\Folder;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\FolderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FolderDownloadController extends BaseApiController
{
    public function __construct(private readonly FolderService $folderService) {}

    /**
     * GET /api/folders/{id}/download
     * Download entire folder as a ZIP file.
     * Requires 'download' permission (owner, share with download/edit, or public link with download).
     */
    public function download(int $id)
    {
        $user = Auth::user();
        $token = request()->query('token') ?? request()->input('token');

        if (!$user && $token === null) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            // Check access with 'download' permission
            $folder = $this->folderService->checkAccessForFolder($user, $id, 'download', $token);
        } catch (\App\Exceptions\DomainValidationException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'not found')) {
                return $this->fail($message, 404, 'FOLDER_NOT_FOUND');
            }
            if (str_contains($lower, 'not accessible') || str_contains($lower, 'forbidden')) {
                return $this->fail($message, 403, 'FORBIDDEN');
            }
            return $this->fail($message, 400, 'BAD_REQUEST');
        }

        // Create temporary ZIP file
        $zipFileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $folder->folder_name) . '.zip';
        $tempZipPath = storage_path('app/tmp/downloads/' . uniqid('folder_', true) . '.zip');

        // Ensure temp directory exists
        $tempDir = dirname($tempZipPath);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return $this->fail('Failed to create ZIP file', 500, 'ZIP_ERROR');
        }

        try {
            // Recursively add folder contents to ZIP
            $this->addFolderToZip($zip, $folder, '');
            $zip->close();
        } catch (\Exception $e) {
            $zip->close();
            @unlink($tempZipPath);
            return $this->fail('Failed to build ZIP: ' . $e->getMessage(), 500, 'ZIP_ERROR');
        }

        // Check if ZIP has any content
        if (!file_exists($tempZipPath) || filesize($tempZipPath) === 0) {
            @unlink($tempZipPath);
            return $this->fail('Folder is empty or has no downloadable files', 400, 'EMPTY_FOLDER');
        }

        // Return ZIP file as download response
        return response()->download($tempZipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Recursively add folder contents to ZIP archive.
     */
    private function addFolderToZip(ZipArchive $zip, $folder, string $basePath): void
    {
        $disk = Storage::disk(config('filesystems.default', 'local'));
        $folderPath = $basePath === '' ? $folder->folder_name : $basePath . '/' . $folder->folder_name;

        // Add empty folder entry
        $zip->addEmptyDir($folderPath);

        // Add files in this folder
        $files = $folder->files()->where('is_deleted', false)->get();
        foreach ($files as $file) {
            // Get latest version
            $version = $file->versions()->orderByDesc('version_number')->first();
            if (!$version) {
                continue;
            }

            $ext = $version->file_extension;
            $storagePath = "files/{$file->id}/v{$version->version_number}/" . $version->uuid . ($ext ? ".{$ext}" : '');

            if (!$disk->exists($storagePath)) {
                continue;
            }

            // Read file content and add to ZIP
            $content = $disk->get($storagePath);
            $fileName = $file->display_name ?? ($version->uuid . ($ext ? ".{$ext}" : ''));
            $zip->addFromString($folderPath . '/' . $fileName, $content);
        }

        // Recursively add subfolders
        $subfolders = $folder->children()->where('is_deleted', false)->get();
        foreach ($subfolders as $subfolder) {
            $this->addFolderToZip($zip, $subfolder, $folderPath);
        }
    }
}
