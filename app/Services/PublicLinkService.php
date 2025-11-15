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
        $link = PublicLink::with(['user:id,name', 'file:id,display_name', 'folder:id,folder_name'])->where('token', $token)->first();
        if (! $link) {
            throw new DomainValidationException('Public link not found');
        }
        if ($link->revoked_at !== null) {
            throw new DomainValidationException('Public link revoked');
        }
        if ($link->expired_at !== null && Carbon::now()->greaterThan($link->expired_at)) {
            throw new DomainValidationException('Public link expired');
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
     * Only for file links.
     *
     * @throws DomainValidationException
     */
    public function buildPreviewUrl(string $token): array
    {
        $link = $this->getActiveByToken($token);
        if ($link->shareable_type !== 'file' || $link->file_id === null) {
            throw new DomainValidationException('Public link is not for a file');
        }
        // Only allow preview when permission is 'view'
        if ($link->permission !== 'view') {
            throw new DomainValidationException('Public link does not grant required permission');
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
     * Build the file download URL via token.
     * Only for file links with download permission.
     *
     * @throws DomainValidationException
     */
    public function buildDownloadUrl(string $token): string
    {
        $link = $this->getActiveByToken($token);
        if ($link->shareable_type !== 'file' || $link->file_id === null) {
            throw new DomainValidationException('Public link is not for a file');
        }
        if ($link->permission !== 'download') {
            throw new DomainValidationException('Public link does not grant required permission');
        }
        $base = rtrim(config('app.url', ''), '/');
        return $base . '/api/files/' . (int) $link->file_id . '/download?token=' . urlencode($token);
    }
}
