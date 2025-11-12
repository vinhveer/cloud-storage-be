<?php

namespace App\Services;

use App\Repositories\BulkRepository;
use App\Models\User;
use App\Models\Folder;
use App\Exceptions\DomainValidationException;

class BulkService
{
    public function __construct(private readonly BulkRepository $bulkRepo)
    {
    }

    /**
     * Bulk delete (move to trash) files and folders for a user.
     * Returns an array with keys 'files' and 'folders' listing affected ids.
     *
     * @param \App\Models\User $user
     * @param array<int> $fileIds
     * @param array<int> $folderIds
     * @return array{files: array<int>, folders: array<int>} 
     * @throws DomainValidationException when no items were moved
     */
    public function bulkDelete(User $user, array $fileIds = [], array $folderIds = []): array
    {
        $fileIds = array_values(array_filter($fileIds, fn($v) => is_numeric($v) && $v > 0));
        $folderIds = array_values(array_filter($folderIds, fn($v) => is_numeric($v) && $v > 0));

        $fileResult = [
            'requested' => [],
            'found' => [],
            'not_found' => [],
            'not_owned' => [],
            'already_deleted' => [],
            'deleted' => [],
        ];

        $folderResult = [
            'requested' => [],
            'found' => [],
            'not_found' => [],
            'not_owned' => [],
            'already_deleted' => [],
            'deleted_folders' => [],
            'deleted_files' => [],
        ];

        // Use DB transaction to ensure consistency
        \DB::beginTransaction();
        try {
            if (count($fileIds) > 0) {
                $fileResult = $this->bulkRepo->moveFilesToTrash($fileIds, $user);
            }

            if (count($folderIds) > 0) {
                $folderResult = $this->bulkRepo->moveFoldersToTrash($folderIds, $user);
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw new DomainValidationException('Failed to delete selected items.');
        }

        $deletedFiles = $fileResult['deleted'] ?? [];
        // Merge files deleted by folder operation
        if (! empty($folderResult['deleted_files'])) {
            $deletedFiles = array_values(array_unique(array_merge($deletedFiles, $folderResult['deleted_files'])));
        }

        $deletedFolders = $folderResult['deleted_folders'] ?? [];

        if (empty($deletedFiles) && empty($deletedFolders)) {
            // No items actually moved to trash
            throw new DomainValidationException('No valid files or folders found to delete.');
        }

        return [
            'files' => $deletedFiles,
            'folders' => $deletedFolders,
            'file_result' => $fileResult,
            'folder_result' => $folderResult,
        ];
    }

    /**
     * Move files and folders to a destination folder for the given user.
     * Ensures ownership and that destination folder belongs to the same user.
     *
     * @param User $user
     * @param array<int> $fileIds
     * @param array<int> $folderIds
     * @param int $destinationFolderId
     * @return array{files: array<int>, folders: array<int>}
     */
    public function bulkMove(User $user, array $fileIds = [], array $folderIds = [], ?int $destinationFolderId = null): array
    {
        $fileIds = array_values(array_filter($fileIds, fn($v) => is_numeric($v) && $v > 0));
        $folderIds = array_values(array_filter($folderIds, fn($v) => is_numeric($v) && $v > 0));

        // Normalize: treat 0 as null (root) in case earlier caller cast to int(0)
        if ($destinationFolderId === 0) {
            $destinationFolderId = null;
        }

        // If destination is provided (not null), ensure it exists and belongs to the user.
        if ($destinationFolderId !== null) {
            $dest = Folder::where('id', $destinationFolderId)->first();
            if (! $dest || $dest->user_id !== $user->id) {
                throw new DomainValidationException('Destination folder not found or permission denied.');
            }
        }

        $movedFiles = [];
        $movedFolders = [];

        \DB::beginTransaction();
        try {
            if (! empty($fileIds)) {
                $res = $this->bulkRepo->moveFilesToFolder($fileIds, $user, $destinationFolderId);
                $movedFiles = $res['moved'] ?? [];
            }

            if (! empty($folderIds)) {
                $resf = $this->bulkRepo->moveFoldersToFolder($folderIds, $user, $destinationFolderId);
                $movedFolders = $resf['moved'] ?? [];
            }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw new DomainValidationException('Failed to move selected items.');
        }

        if (empty($movedFiles) && empty($movedFolders)) {
            throw new DomainValidationException('No valid files or folders moved.');
        }

        return [
            'files' => $movedFiles,
            'folders' => $movedFolders,
        ];
    }

    /**
     * Bulk copy files and folders into a destination folder.
     * Returns arrays of copied file and folder mappings.
     *
     * @param User $user
     * @param array<int> $fileIds
     * @param array<int> $folderIds
     * @param int|null $destinationFolderId
     * @return array{files: array<array{original:int,new:int}>, folders: array<array{original:int,new:int}>}
     * @throws DomainValidationException
     */
    public function bulkCopy(User $user, array $fileIds = [], array $folderIds = [], ?int $destinationFolderId = null): array
    {
        $fileIds = array_values(array_filter($fileIds, fn($v) => is_numeric($v) && $v > 0));
        $folderIds = array_values(array_filter($folderIds, fn($v) => is_numeric($v) && $v > 0));

        // Normalize: treat 0 as null (root) in case earlier caller cast to int(0)
        if ($destinationFolderId === 0) {
            $destinationFolderId = null;
        }

        // If destination is provided (not null), ensure it exists and belongs to the user.
        if ($destinationFolderId !== null) {
            $dest = Folder::where('id', $destinationFolderId)->first();
            if (! $dest || $dest->user_id !== $user->id) {
                throw new DomainValidationException('Destination folder not found or permission denied.');
            }
        }

        $copiedFiles = [];
        $copiedFolders = [];

        \DB::beginTransaction();
        try {
            if (! empty($fileIds)) {
                $res = $this->bulkRepo->copyFilesToFolder($fileIds, $user, $destinationFolderId);
                $copiedFiles = $res['copied'] ?? [];
            }

            if (! empty($folderIds)) {
                $resf = $this->bulkRepo->copyFoldersToFolder($folderIds, $user, $destinationFolderId);
                $copiedFolders = $resf['copied_folders'] ?? [];
                // also include files copied due to folders
                if (! empty($resf['copied_files'])) {
                    $copiedFiles = array_values(array_merge($copiedFiles, $resf['copied_files']));
                }
            }

            \DB::commit();
        } catch (DomainValidationException $e) {
            \DB::rollBack();
            // preserve domain-level message
            throw $e;
        } catch (\Exception $e) {
            \DB::rollBack();
            // wrap unknown exceptions but include underlying message for diagnostics
            throw new DomainValidationException('Failed to copy selected items: ' . $e->getMessage());
        }

        if (empty($copiedFiles) && empty($copiedFolders)) {
            throw new DomainValidationException('No valid files or folders copied.');
        }

        return [
            'files' => $copiedFiles,
            'folders' => $copiedFolders,
        ];
    }
}
