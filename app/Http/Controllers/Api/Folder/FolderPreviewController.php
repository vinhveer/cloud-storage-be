<?php

namespace App\Http\Controllers\Api\Folder;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\FolderService;
use Illuminate\Support\Facades\Auth;

class FolderPreviewController extends BaseApiController
{
    public function __construct(private readonly FolderService $folderService) {}

    /**
     * GET /api/folders/{id}/preview
     * Preview folder contents: list all files and subfolders with metadata.
     * Requires 'view' permission (owner, share with view/download/edit, or public link).
     */
    public function preview(int $id)
    {
        $user = Auth::user();
        $token = request()->query('token') ?? request()->input('token');

        if (!$user && $token === null) {
            return $this->fail('Unauthenticated', 401, 'UNAUTHENTICATED');
        }

        try {
            // Check access with 'view' permission
            $folder = $this->folderService->checkAccessForFolder($user, $id, 'view', $token);
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

        // Get folder statistics
        $stats = $this->calculateFolderStats($folder);

        // Get immediate children (files and subfolders)
        $subfolders = $folder->children()
            ->where('is_deleted', false)
            ->orderBy('folder_name')
            ->get(['id', 'folder_name', 'created_at', 'updated_at']);

        $files = $folder->files()
            ->where('is_deleted', false)
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'file_size', 'mime_type', 'file_extension', 'created_at', 'updated_at', 'last_opened_at']);

        return $this->ok([
            'folder' => [
                'folder_id' => $folder->id,
                'folder_name' => $folder->folder_name,
                'created_at' => $folder->created_at?->toISOString(),
                'updated_at' => $folder->updated_at?->toISOString(),
            ],
            'stats' => $stats,
            'contents' => [
                'folders' => $subfolders->map(fn($f) => [
                    'folder_id' => $f->id,
                    'folder_name' => $f->folder_name,
                    'created_at' => $f->created_at?->toISOString(),
                    'updated_at' => $f->updated_at?->toISOString(),
                ])->all(),
                'files' => $files->map(fn($f) => [
                    'file_id' => $f->id,
                    'display_name' => $f->display_name,
                    'file_size' => (int) $f->file_size,
                    'mime_type' => $f->mime_type,
                    'file_extension' => $f->file_extension,
                    'created_at' => $f->created_at?->toISOString(),
                    'updated_at' => $f->updated_at?->toISOString(),
                    'last_opened_at' => $f->last_opened_at?->toISOString(),
                ])->all(),
            ],
        ]);
    }

    /**
     * Calculate folder statistics recursively.
     */
    private function calculateFolderStats($folder): array
    {
        $totalFiles = 0;
        $totalFolders = 0;
        $totalSize = 0;

        $this->countRecursive($folder, $totalFiles, $totalFolders, $totalSize);

        return [
            'total_files' => $totalFiles,
            'total_folders' => $totalFolders,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
        ];
    }

    /**
     * Recursively count files, folders and total size.
     */
    private function countRecursive($folder, int &$totalFiles, int &$totalFolders, int &$totalSize): void
    {
        // Count files in this folder
        $files = $folder->files()->where('is_deleted', false)->get(['file_size']);
        $totalFiles += $files->count();
        $totalSize += $files->sum('file_size');

        // Count and recurse into subfolders
        $subfolders = $folder->children()->where('is_deleted', false)->get();
        $totalFolders += $subfolders->count();

        foreach ($subfolders as $subfolder) {
            $this->countRecursive($subfolder, $totalFiles, $totalFolders, $totalSize);
        }
    }

    /**
     * Format bytes to human readable string.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
