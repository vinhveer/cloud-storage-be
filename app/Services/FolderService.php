<?php

namespace App\Services;

use App\Exceptions\DomainValidationException;
use App\Models\Folder;
use App\Models\User;
use App\Repositories\FolderRepository;
use Illuminate\Support\Facades\DB;
use App\Models\File;
use App\Models\Folder as FolderModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\FileVersion;
use App\Models\SystemConfig;

class FolderService
{
    public function __construct(
        private readonly FolderRepository $folders,
        private readonly FileService $fileService
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
     * Move a folder under a new parent (or root when null).
     *
     * @throws DomainValidationException
     */
    public function moveFolder(User $user, int $sourceFolderId, ?int $targetFolderId = null): void
    {
        $source = $this->folders->findByIdAndUser($sourceFolderId, $user->id);
        if (! $source) {
            throw new DomainValidationException('Source folder not found or not owned by user');
        }

        $targetParent = null;
        if ($targetFolderId !== null) {
            $targetParent = $this->folders->findByIdAndUser($targetFolderId, $user->id);
            if (! $targetParent) {
                throw new DomainValidationException('Target folder not found or not owned by user');
            }
        }

        // Prevent moving into itself or its descendant
        if ($targetParent !== null) {
            $cursor = $targetParent;
            while ($cursor !== null) {
                if ($cursor->id === $source->id) {
                    throw new DomainValidationException('Cannot move a folder into its own descendant');
                }
                $cursor = $cursor->parent()->first();
            }
        }

        // Perform the move
        DB::transaction(function () use ($source, $targetParent) {
            $source->fol_folder_id = $targetParent?->id;
            $source->save();
        });
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


    /**
     * Copy a folder (including all nested subfolders and files with all versions) into
     * the specified target folder (or root if null).
     *
     * Returns the newly created root folder.
     *
     * @param User $user
     * @param int $sourceFolderId
     * @param int|null $targetFolderId
     * @return Folder
     * @throws DomainValidationException
     */
    public function copyFolder(User $user, int $sourceFolderId, ?int $targetFolderId = null): Folder
    {
        $source = $this->folders->findByIdAndUser($sourceFolderId, $user->id);
        if (! $source) {
            throw new DomainValidationException('Source folder not found or not owned by user');
        }

        $targetParent = null;
        if ($targetFolderId !== null) {
            $targetParent = $this->folders->findByIdAndUser($targetFolderId, $user->id);
            if (! $targetParent) {
                throw new DomainValidationException('Target folder not found or not owned by user');
            }
        }

        // Prevent copying into itself or its descendant
        if ($targetParent !== null) {
            $cursor = $targetParent;
            while ($cursor !== null) {
                if ($cursor->id === $source->id) {
                    throw new DomainValidationException('Cannot copy a folder into its own descendant');
                }
                $cursor = $cursor->parent()->first();
            }
        }

        // Two-phase approach:
        // 1) Scan entire folder tree, collect all files and their versions, compute total bytes, and copy
        //    all version objects into a temporary batch directory on disk.
        // 2) Within a DB transaction, create the folder tree and file/version rows, moving temp files to
        //    their final locations. On failure attempt cleanup of created DB rows and physical files.

        $disk = Storage::disk(config('filesystems.default', 'local'));
        $batchId = (string) Str::uuid();
        $tempBase = "tmp/copies/{$batchId}";

        // Step A: Traverse tree and collect files
        $stack = [$source];
        $visitedFolders = [];
        $filesByFolder = []; // folderId => [File, ...]
        $allFiles = [];
        while (! empty($stack)) {
            $node = array_pop($stack);
            $visitedFolders[$node->id] = $node;
            $files = $node->files()->get();
            if ($files->isNotEmpty()) {
                $filesByFolder[$node->id] = $files;
                foreach ($files as $f) {
                    $allFiles[$f->id] = $f;
                }
            }
            foreach ($node->children()->get() as $child) {
                if (! array_key_exists($child->id, $visitedFolders)) {
                    $stack[] = $child;
                }
            }
        }

        // Collect versions and compute total size
        $versionMap = []; // fileId => [versions ordered]
        $totalSize = 0;
        foreach ($allFiles as $file) {
            $versions = $file->versions()->orderBy('version_number')->get();
            if ($versions->isEmpty()) {
                continue;
            }
            $versionMap[$file->id] = $versions;
            $sum = $versions->sum(fn($v) => (int) ($v->file_size ?? 0));
            $totalSize += $sum > 0 ? $sum : ((int) ($file->file_size ?? 0));
        }

        // Check user quota before copying files
        $systemDefaultLimit = (int) SystemConfig::getBytes('default_storage_limit', 0);
        $limit = (int) ($user->storage_limit ?: $systemDefaultLimit);
        $used = (int) ($user->storage_used ?? 0);
        if ($limit > 0 && ($used + $totalSize) > $limit) {
            throw new DomainValidationException('Storage limit exceeded');
        }

        // Step B: copy objects to temp
        $tempPathsByFile = []; // fileId => [ ['temp'=>path, 'srcVersion'=>Model, 'ext'=>string, 'size'=>int, 'mime'=>string], ... ]
        try {
            foreach ($versionMap as $fileId => $versions) {
                $index = 1;
                foreach ($versions as $v) {
                    $ext = $v->file_extension;
                    $srcPath = "files/{$fileId}/v{$v->version_number}/" . $v->uuid . ($ext ? ".{$ext}" : '');
                    if (! $disk->exists($srcPath)) {
                        throw new DomainValidationException('File content not found for file '.$fileId.' version '.$v->version_number);
                    }
                    $tempPath = "{$tempBase}/files/{$fileId}/v{$v->version_number}/" . $v->uuid . ($ext ? ".{$ext}" : '');
                    $copied = $disk->copy($srcPath, $tempPath);
                    if (! $copied) {
                        throw new DomainValidationException('Failed to copy to temp for file '.$fileId.' version '.$v->version_number);
                    }
                    $tempPathsByFile[$fileId][] = ['temp' => $tempPath, 'srcVersion' => $v, 'ext' => $ext, 'size' => (int) ($v->file_size ?? 0), 'mime' => $v->mime_type ?? null];
                    $index++;
                }
            }
        } catch (\Exception $e) {
            // cleanup temp
            try {
                if (! empty($tempPathsByFile)) {
                    $paths = [];
                    foreach ($tempPathsByFile as $arr) {
                        foreach ($arr as $p) {
                            $paths[] = $p['temp'];
                        }
                    }
                    if (! empty($paths)) {
                        $disk->delete($paths);
                    }
                }
                $disk->deleteDirectory($tempBase);
            } catch (\Exception $inner) {
                // ignore
            }
            throw $e;
        }

        // Step C: commit DB - create folders/files/versions and move temp files into final locations
        $createdTargetPaths = [];
        $createdFileIds = [];
        try {
            $rootNewFolder = null;
            DB::beginTransaction();

            // recursive create based on source tree
            $createRecursive = function (FolderModel $node, ?FolderModel $newParent) use (&$createRecursive, $user, &$tempPathsByFile, &$createdTargetPaths, &$createdFileIds, $disk) {
                // create folder record
                $new = $this->folders->create($user, $newParent, $node->folder_name);

                // create files for this folder
                foreach ($node->files()->get() as $file) {
                    $srcFileId = $file->id;
                    $tempList = $tempPathsByFile[$srcFileId] ?? [];
                    if (empty($tempList)) {
                        // skip files without versions
                        continue;
                    }

                    // determine latest version to set file_size and extension
                    $latest = end($tempList);
                    $latestSize = (int) ($latest['size'] ?? 0);
                    $latestExt = $latest['ext'] ?? null;

                    // build display name with deduplication (avoid appending _copy)
                    $origDisplay = $file->display_name ?? (($tempList[0]['srcVersion']->uuid ?? '') . ($latestExt ? ".{$latestExt}" : ''));
                    $candidate = $origDisplay;
                    $j = 0;
                    while (\App\Models\File::where('folder_id', $new->id)->where('display_name', $candidate)->exists()) {
                        $j++;
                        $suffix = $j === 1 ? ' (copy)' : " (copy {$j})";
                        if ($latestExt && pathinfo($origDisplay, PATHINFO_EXTENSION) === $latestExt) {
                            $base = pathinfo($origDisplay, PATHINFO_FILENAME);
                            $candidate = $base . $suffix . ($latestExt ? ".{$latestExt}" : '');
                        } else {
                            $candidate = $origDisplay . $suffix . ($latestExt ? ".{$latestExt}" : '');
                        }
                    }
                    $newDisplay = $candidate;

                    // create File record
                    $newFile = File::create([
                        'folder_id' => $new->id,
                        'user_id' => $user->id,
                        'display_name' => $newDisplay,
                        'file_size' => $latestSize,
                        'mime_type' => $file->mime_type,
                        'file_extension' => $latestExt,
                        'is_deleted' => false,
                    ]);
                    $createdFileIds[] = $newFile->id;

                    // move temp files into final locations and create versions
                    $newVersionNumber = 1;
                    foreach ($tempList as $p) {
                        $srcExt = $p['ext'];
                        $srcMime = $p['mime'];
                        $srcSize = $p['size'];
                        $tempPath = $p['temp'];

                        $newUuid = (string) Str::uuid();
                        $version = FileVersion::create([
                            'file_id' => $newFile->id,
                            'user_id' => $user->id,
                            'version_number' => $newVersionNumber,
                            'uuid' => $newUuid,
                            'file_extension' => $srcExt,
                            'mime_type' => $srcMime,
                            'file_size' => $srcSize,
                            'action' => 'upload',
                            'notes' => 'Copied from file ' . $file->id . ' version ' . ($p['srcVersion']->version_number ?? '?'),
                        ]);

                        $targetPath = "files/{$newFile->id}/v{$newVersionNumber}/" . $newUuid . ($srcExt ? ".{$srcExt}" : '');
                        // move temp -> target
                        $moved = false;
                        try {
                            $moved = $disk->move($tempPath, $targetPath);
                        } catch (\Throwable $moveEx) {
                            $moved = $disk->copy($tempPath, $targetPath) && $disk->delete($tempPath);
                        }
                        if (! $moved) {
                            throw new DomainValidationException('Failed to move temp file to final location for file ' . $file->id);
                        }
                        $createdTargetPaths[] = $targetPath;
                        $newVersionNumber++;
                    }
                }

                // recurse children
                foreach ($node->children()->get() as $child) {
                    $createRecursive($child, $new);
                }

                return $new;
            };

            $rootNewFolder = $createRecursive($source, $targetParent);

            // Update user's storage usage
            if ($totalSize > 0) {
                $user->increment('storage_used', $totalSize);
            }

            DB::commit();

            // Cleanup temp dir
            try {
                $disk->deleteDirectory($tempBase);
            } catch (\Exception $inner) {
                // ignore
            }

            return $rootNewFolder;
        } catch (\Exception $e) {
            DB::rollBack();
            // best-effort cleanup: delete created files on disk and DB records
            try {
                if (! empty($createdTargetPaths)) {
                    $disk->delete($createdTargetPaths);
                }
                if (! empty($createdFileIds)) {
                    FileVersion::whereIn('file_id', $createdFileIds)->delete();
                    File::whereIn('id', $createdFileIds)->delete();
                }
                // cleanup temp
                if (! empty($tempPathsByFile)) {
                    $paths = [];
                    foreach ($tempPathsByFile as $arr) {
                        foreach ($arr as $p) {
                            $paths[] = $p['temp'];
                        }
                    }
                    if (! empty($paths)) {
                        $disk->delete($paths);
                    }
                }
                $disk->deleteDirectory($tempBase);
            } catch (\Exception $inner) {
                // ignore
            }

            throw new DomainValidationException($e->getMessage());
        }
    }
}


