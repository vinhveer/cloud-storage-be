<?php

namespace App\Services;

use App\Exceptions\DomainValidationException;
use App\Models\File;
use App\Models\Folder;
use App\Models\PublicLink;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PublicLinkService
{
    /**
     * Create a new public link for a file or folder.
     *
     * @param User $user
     * @param string $shareableType 'file'|'folder'
     * @param int $shareableId
     * @param string $permission 'view'|'download'
     * @param string|null $expiredAt
     * @return PublicLink
     * @throws DomainValidationException
     */
    public function create(User $user, string $shareableType, int $shareableId, string $permission, ?string $expiredAt): PublicLink
    {
        if (! in_array($shareableType, ['file', 'folder'], true)) {
            throw new DomainValidationException('Invalid shareable_type');
        }
        if (! in_array($permission, ['view', 'download'], true)) {
            throw new DomainValidationException('Invalid permission');
        }

        $fileId = null;
        $folderId = null;

        if ($shareableType === 'file') {
            $file = File::find($shareableId);
            if (! $file) {
                throw new DomainValidationException('File not found');
            }
            if ((int) $file->user_id !== (int) $user->id) {
                throw new DomainValidationException('File not owned by user');
            }
            if ((bool) $file->is_deleted || $file->deleted_at !== null) {
                throw new DomainValidationException('File is deleted');
            }
            $fileId = $file->id;
        } else {
            $folder = Folder::find($shareableId);
            if (! $folder) {
                throw new DomainValidationException('Folder not found');
            }
            if ((int) $folder->user_id !== (int) $user->id) {
                throw new DomainValidationException('Folder not owned by user');
            }
            if ((bool) $folder->is_deleted || $folder->deleted_at !== null) {
                throw new DomainValidationException('Folder is deleted');
            }
            $folderId = $folder->id;
        }

        $token = Str::random(40);
        while (PublicLink::where('token', $token)->exists()) {
            $token = Str::random(40);
        }

        $expiredAtDt = null;
        if ($expiredAt !== null && $expiredAt !== '') {
            try {
                $expiredAtDt = Carbon::parse($expiredAt);
            } catch (\Throwable $e) {
                throw new DomainValidationException('expired_at is not a valid date');
            }
        }

        $link = PublicLink::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'file_id' => $fileId,
            'shareable_type' => $shareableType,
            'permission' => $permission,
            'token' => $token,
            'expired_at' => $expiredAtDt,
            'revoked_at' => null,
        ]);

        return $link;
    }

    /**
     * List public links created by a user with pagination.
     *
     * @return array{items: \Illuminate\Support\Collection, total: int}
     */
    public function listByUser(User $user, int $page = 1, int $perPage = 15, bool $includeRevoked = false): array
    {
        $query = PublicLink::query()
            ->where('user_id', $user->id)
            ->when(!$includeRevoked, fn($q) => $q->whereNull('revoked_at'))
            ->orderByDesc('id')
            ->with(['file:id,display_name', 'folder:id,folder_name']);

        $total = (clone $query)->count();
        $items = $query->forPage($page, $perPage)->get();

        return [
            'items' => $items,
            'total' => (int) $total,
        ];
    }

    /**
     * Resolve a token and ensure it is valid and active.
     *
     * @throws DomainValidationException
     */
    public function getActiveByToken(string $token): PublicLink
    {
        $link = PublicLink::with([
            'user:id,name',
            'file:id,display_name,is_deleted,deleted_at',
            'folder:id,folder_name,is_deleted,deleted_at',
        ])->where('token', $token)->first();
        if (! $link) {
            throw new DomainValidationException('Public link not found');
        }
        if ($link->revoked_at !== null) {
            throw new DomainValidationException('Public link revoked');
        }
        if ($link->expired_at !== null && Carbon::now()->greaterThan($link->expired_at)) {
            throw new DomainValidationException('Public link expired');
        }

        // If the target file/folder has been soft-deleted, consider the link invalid.
        if ($link->file_id !== null) {
            if (! $link->file) {
                throw new DomainValidationException('File not found');
            }
            if ((bool) $link->file->is_deleted || $link->file->deleted_at !== null) {
                throw new DomainValidationException('File not found');
            }
        }

        if ($link->folder_id !== null) {
            if (! $link->folder) {
                throw new DomainValidationException('Folder not found');
            }
            if ((bool) $link->folder->is_deleted || $link->folder->deleted_at !== null) {
                throw new DomainValidationException('Folder not found');
            }
        }
        return $link;
    }

    /**
     * Revoke a public link owned by user.
     *
     * @throws DomainValidationException
     */
    public function revoke(User $user, int $id): PublicLink
    {
        $link = PublicLink::where('id', $id)->where('user_id', $user->id)->first();
        if (! $link) {
            throw new DomainValidationException('Public link not found');
        }
        if ($link->revoked_at !== null) {
            return $link;
        }
        $link->revoked_at = Carbon::now();
        $link->save();
        return $link;
    }

    /**
     * Update a public link (permission/expired_at) owned by user.
     *
     * @throws DomainValidationException
     */
    public function update(User $user, int $id, ?string $permission, $expiredAt): PublicLink
    {
        $link = PublicLink::where('id', $id)->where('user_id', $user->id)->first();
        if (! $link) {
            throw new DomainValidationException('Public link not found');
        }

        if ($permission !== null) {
            if (! in_array($permission, ['view', 'download'], true)) {
                throw new DomainValidationException('Invalid permission');
            }
            $link->permission = $permission;
        }

        if ($expiredAt !== null) {
            if ($expiredAt === '') {
                $expiredAt = null;
            }
            if ($expiredAt !== null) {
                try {
                    $link->expired_at = Carbon::parse($expiredAt);
                } catch (\Throwable $e) {
                    throw new DomainValidationException('expired_at is not a valid date');
                }
            } else {
                $link->expired_at = null;
            }
        }

        $link->save();
        return $link;
    }

    /**
     * List all public links for a specific file owned by user.
     *
     * @return array{file:\App\Models\File, links:\Illuminate\Support\Collection}
     * @throws DomainValidationException
     */
    public function listForFile(User $user, int $fileId): array
    {
        $file = File::find($fileId);
        if (! $file) {
            throw new DomainValidationException('File not found');
        }
        if ((int) $file->user_id !== (int) $user->id) {
            throw new DomainValidationException('File not owned by user');
        }

        $links = PublicLink::where('user_id', $user->id)
            ->where('file_id', $fileId)
            ->orderByDesc('id')
            ->get();

        return ['file' => $file, 'links' => $links];
    }

    /**
     * Build the public URL for a token (API endpoint).
     */
    public function buildPublicUrl(string $token): string
    {
        $base = rtrim(config('app.url', ''), '/');
        if ($base === '') {
            $base = '';
        }
        return $base . '/api/public-links/' . $token;
    }

    /**
     * Build the file preview URL via token.
     * Supports both file and folder links.
     *
     * @throws DomainValidationException
     */
    public function buildPreviewUrl(string $token): array
    {
        $link = $this->getActiveByToken($token);
        
        // Allow preview when permission is 'view' or 'download'
        if (! in_array($link->permission, ['view', 'download'], true)) {
            throw new DomainValidationException('Public link does not grant required permission');
        }

        // Handle folder preview
        if ($link->shareable_type === 'folder' && $link->folder_id !== null) {
            return $this->buildFolderPreview($link);
        }

        // Handle file preview
        if ($link->shareable_type !== 'file' || $link->file_id === null) {
            throw new DomainValidationException('Public link is not for a file or folder');
        }

        $service = app(FilePreviewService::class);
        $result = $service->generatePreview(null, (int) $link->file_id, $token);

        return [
            'shareable_type' => 'file',
            'file' => $result['file'],
            'preview_url' => $result['preview_url'],
            'expires_in' => $result['expires_in'],
        ];
    }

    /**
     * Build folder preview data.
     */
    private function buildFolderPreview(PublicLink $link): array
    {
        $folder = $link->folder;
        
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

        return [
            'shareable_type' => 'folder',
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
            'token' => $link->token,
        ];
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
        $files = $folder->files()->where('is_deleted', false)->get(['file_size']);
        $totalFiles += $files->count();
        $totalSize += $files->sum('file_size');

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

    /**
     * Build the download URL via token.
     * Supports both file and folder links with download permission.
     *
     * @throws DomainValidationException
     */
    public function buildDownloadUrl(string $token): array
    {
        $link = $this->getActiveByToken($token);
        
        if ($link->permission !== 'download') {
            throw new DomainValidationException('Public link does not grant required permission');
        }

        $base = rtrim(config('app.url', ''), '/');

        // Handle folder download
        if ($link->shareable_type === 'folder' && $link->folder_id !== null) {
            return [
                'shareable_type' => 'folder',
                'folder_id' => (int) $link->folder_id,
                'folder_name' => $link->folder?->folder_name,
                'download_url' => $base . '/api/folders/' . (int) $link->folder_id . '/download?token=' . urlencode($token),
            ];
        }

        // Handle file download
        if ($link->shareable_type !== 'file' || $link->file_id === null) {
            throw new DomainValidationException('Public link is not for a file or folder');
        }
        return [
            'shareable_type' => 'file',
            'file_id' => (int) $link->file_id,
            'file_name' => $link->file?->display_name,
            'download_url' => $base . '/api/files/' . (int) $link->file_id . '/download?token=' . urlencode($token),
        ];
    }
}
