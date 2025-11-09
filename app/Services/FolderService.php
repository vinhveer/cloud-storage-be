<?php

namespace App\Services;

use App\Exceptions\DomainValidationException;
use App\Models\Folder;
use App\Models\User;
use App\Repositories\FolderRepository;
use Illuminate\Support\Facades\DB;
use App\Models\File;
use App\Models\Folder as FolderModel;

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

    /**
     * Get a folder by id that belongs to the given user.
     *
     * @throws DomainValidationException when not found or not owned by user
     */
    public function getByIdForUser(User $user, int $folderId)
    {
        $folder = $this->folders->findByIdAndUser($folderId, $user->id);
        if (! $folder) {
            throw new DomainValidationException('Folder not found or not owned by user');
        }

        return $folder;
    }

    /**
     * Rename a folder that belongs to the user.
     *
     * @throws DomainValidationException when not found or not owned by user
     */
    public function renameFolder(User $user, int $folderId, string $newName): Folder
    {
        $folder = $this->folders->findByIdAndUser($folderId, $user->id);
        if (! $folder) {
            throw new DomainValidationException('Folder not found or not owned by user');
        }

        $folder->folder_name = $newName;
        $folder->save();

        return $folder;
    }

    /**
     * Soft delete a folder and all its child folders/files (move to Trash).
     *
     * @throws DomainValidationException when folder not found or not owned by user
     */
    public function softDeleteFolder(User $user, int $folderId): void
    {
        $folder = $this->folders->findByIdAndUser($folderId, $user->id);
        if (! $folder) {
            throw new DomainValidationException('Folder not found or not owned by user');
        }

        // Wrap cascade delete in transaction
        DB::transaction(function () use ($folder) {
            $this->cascadeSoftDelete($folder);
        });
    }


    /**
     * Recursively mark folder/files as deleted and perform Eloquent soft deletes.
     */
    private function cascadeSoftDelete(FolderModel $folder): void
    {
        // Soft delete all files in this folder
        foreach ($folder->files()->get() as $file) {
            $file->is_deleted = true;
            $file->save();
            $file->delete();
        }

        // Recurse into children
        foreach ($folder->children()->get() as $child) {
            $this->cascadeSoftDelete($child);
        }

        // Mark folder as deleted and soft delete
        $folder->is_deleted = true;
        $folder->save();
        $folder->delete();
    }
}


