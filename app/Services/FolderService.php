<?php

namespace App\Services;

use App\Exceptions\DomainValidationException;
use App\Models\Folder;
use App\Models\User;
use App\Repositories\FolderRepository;

class FolderService
{
    public function __construct(
        private readonly FolderRepository $folders
    ) {}

    public function createFolder(User $user, string $folderName, ?int $parentFolderId = null): Folder
    {
        $parent = null;
        if ($parentFolderId !== null) {
            $parent = $this->folders->findByIdAndUser($parentFolderId, $user->id);
            if (! $parent) {
                throw new DomainValidationException('Parent folder not found or not owned by user');
            }
        }

        return $this->folders->create($user, $parent, $folderName);
    }

    /**
     * List child folders for a given parent (null = root) belonging to the user, with pagination.
     */
    public function listChildren(User $user, ?int $parentId, int $page, int $perPage): array
    {
        // Validate parent ownership if provided
        if ($parentId !== null) {
            $parent = $this->folders->findByIdAndUser($parentId, $user->id);
            if (! $parent) {
                throw new DomainValidationException('Parent folder not found or not owned by user');
            }
        }

        return $this->folders->paginateChildrenByUser($parentId, $user->id, $page, $perPage);
    }
}


