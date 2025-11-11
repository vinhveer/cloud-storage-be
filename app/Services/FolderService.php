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
use App\Models\PublicLink;
use Carbon\Carbon;

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
    /**
     * Get a folder by id that belongs to the given user or is shared to the user with sufficient permission.
     *
     * @param User $user
     * @param int $folderId
     * @param string|null $requiredPermission 'view'|'download'|'edit' (defaults to 'view')
     * @throws DomainValidationException when not found or not owned/granted
     */
    public function getByIdForUser(User $user, int $folderId, ?string $requiredPermission = 'view')
    {
        // Quick owner lookup
        $folder = $this->folders->findByIdAndUser($folderId, $user->id);
        if ($folder) {
            return $folder;
        }

        // Load the folder model; if it doesn't exist, fail fast
        $folderModel = Folder::find($folderId);
        if (! $folderModel) {
            throw new DomainValidationException('Folder not found');
        }

        // Traverse up the ancestor chain and check for a share that grants the required permission
        $cursor = $folderModel;
        while ($cursor !== null) {
            $found = \DB::table('shares')
                ->join('receives_shares', 'shares.id', '=', 'receives_shares.share_id')
                ->where('receives_shares.user_id', $user->id)
                ->where('shares.shareable_type', 'folder')
                ->where('shares.folder_id', $cursor->id)
                ->whereIn('receives_shares.permission', $this->allowedPermissionsFor($requiredPermission))
                ->exists();

            if ($found) {
                return $folderModel;
            }

            $cursor = $cursor->parent()->first();
        }

        throw new DomainValidationException('Folder not found or not owned by user');
    }

    /**
     * Public variant to get a folder by id without ownership checks.
     * Intended to be used by public-link flows which authenticate via token elsewhere.
     *
     * @param int $folderId
     * @return FolderModel
     * @throws DomainValidationException
     */
    public function getByIdPublic(int $folderId)
    {
        $folder = Folder::find($folderId);
        if (! $folder) {
            throw new DomainValidationException('Folder not found');
        }
        return $folder;
    }

    /**
     * Helper to map required permission to accepted granted permissions.
     * Kept in FolderService to avoid cross-service dependency.
     */
    private function allowedPermissionsFor(string $required): array
    {
        return match ($required) {
            'edit' => ['edit'],
            'download' => ['download', 'edit'],
            default => ['view', 'download', 'edit'],
        };
    }

    /**
     * List folders and files contained in a folder for a given user.
     * Verifies the user has access to the folder (ownership or shared with sufficient permission).
     *
     * @param User $user
     * @param int $folderId
     * @return array{folders:\Illuminate\Support\Collection, files:\Illuminate\Support\Collection}
     * @throws DomainValidationException
     */
    public function listContents(User $user, int $folderId): array
    {
        // will throw DomainValidationException if not accessible
        $folder = $this->getByIdForUser($user, $folderId, 'view');

        $folders = $folder->children()
            ->where('is_deleted', false)
            ->orderByDesc('id')
            ->get(['id', 'folder_name', 'created_at']);

        $files = $folder->files()
            ->where('is_deleted', false)
            ->orderByDesc('id')
            ->get(['id', 'display_name', 'file_size', 'mime_type', 'file_extension', 'last_opened_at']);

        return [
            'folders' => $folders,
            'files' => $files,
        ];
    }

    /**
     * Public variant of listContents which does not require an authenticated user.
     * Intended for public-link flows which validate access via token elsewhere.
     *
     * @param int $folderId
     * @return array{folders:\Illuminate\Support\Collection, files:\Illuminate\Support\Collection}
     * @throws DomainValidationException
     */
    public function listContentsPublic(int $folderId): array
    {
        $folder = $this->getByIdPublic($folderId);

        $folders = $folder->children()
            ->where('is_deleted', false)
            ->orderByDesc('id')
            ->get(['id', 'folder_name', 'created_at']);

        $files = $folder->files()
            ->where('is_deleted', false)
            ->orderByDesc('id')
            ->get(['id', 'display_name', 'file_size', 'mime_type', 'file_extension', 'last_opened_at']);

        return [
            'folders' => $folders,
            'files' => $files,
        ];
    }

    /**
     * Check access for a folder by owner, share (ancestor chain) or public link token.
     * Returns the Folder model when access is granted or throws DomainValidationException.
     *
     * @param User|null $user
     * @param int $folderId
     * @param string|null $requiredPermission
     * @param string|null $publicToken
     * @return FolderModel
     * @throws DomainValidationException
     */
    public function checkAccessForFolder(?User $user, int $folderId, ?string $requiredPermission = 'view', ?string $publicToken = null)
    {
        $folder = Folder::find($folderId);
        if (! $folder) {
            throw new DomainValidationException('Folder not found');
        }

        // Owner
        if ($user && $folder->user_id === $user->id) {
            return $folder;
        }

        // Check shares up the ancestor chain for authenticated user
        if ($user) {
            $cursor = $folder;
            while ($cursor !== null) {
                $found = \DB::table('shares')
                    ->join('receives_shares', 'shares.id', '=', 'receives_shares.share_id')
                    ->where('receives_shares.user_id', $user->id)
                    ->where('shares.shareable_type', 'folder')
                    ->where('shares.folder_id', $cursor->id)
                    ->whereIn('receives_shares.permission', $this->allowedPermissionsFor($requiredPermission))
                    ->exists();

                if ($found) {
                    return $folder;
                }

                $cursor = $cursor->parent()->first();
            }
        }

        // Public token based access: check public link on this folder or any ancestor
        if ($publicToken !== null) {
            $now = Carbon::now();
            $plQuery = PublicLink::where('token', $publicToken)
                ->whereNull('revoked_at')
                ->where(function ($q) use ($now) {
                    $q->whereNull('expired_at')->orWhere('expired_at', '>', $now);
                });

            $cursor = $folder;
            while ($cursor !== null) {
                $pl = (clone $plQuery)->where('folder_id', $cursor->id)->first();
                if ($pl && in_array($pl->permission, $this->allowedPermissionsFor($requiredPermission), true)) {
                    return $folder;
                }
                $cursor = $cursor->parent()->first();
            }
        }

        throw new DomainValidationException('Folder not accessible');
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
        // Allow copying when user owns the source or when the source is shared to the user
        try {
            // require download permission to perform a copy (copying implies downloading content)
            $source = $this->checkAccessForFolder($user, $sourceFolderId, 'download');
        } catch (DomainValidationException $e) {
            throw new DomainValidationException('Source folder not found or not accessible');
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

    /**
     * Build and return the folder tree for a given user as nested arrays.
     *
     * @param User $user
     * @return array{folders: array}
     */
    public function tree(User $user): array
    {
        // 1) collect owned folders
        $owned = $this->folders->getAllNonDeletedByUser($user->id);

        // map id => model placeholder
        $collected = [];
        foreach ($owned as $f) {
            $collected[$f->id] = $f;
        }

        // 2) collect folders reachable via shares granted to this user (view permission)
        $perms = $this->allowedPermissionsFor('view');
        $sharedRootIds = DB::table('shares')
            ->join('receives_shares', 'shares.id', '=', 'receives_shares.share_id')
            ->where('receives_shares.user_id', $user->id)
            ->where('shares.shareable_type', 'folder')
            ->whereIn('receives_shares.permission', $perms)
            ->pluck('shares.folder_id')
            ->unique()
            ->values()
            ->all();

        foreach ($sharedRootIds as $rootId) {
            $root = Folder::where('id', $rootId)->where('is_deleted', false)->first();
            if (! $root) {
                continue;
            }
            // traverse subtree of this shared root and add to collected
            $stack = [$root];
            while (! empty($stack)) {
                $node = array_pop($stack);
                if (! isset($collected[$node->id])) {
                    $collected[$node->id] = $node;
                }
                $children = $node->children()->where('is_deleted', false)->get();
                foreach ($children as $c) {
                    if (! isset($collected[$c->id])) {
                        $stack[] = $c;
                    }
                }
            }
        }

        // 3) build nodes map (folder_id => node array with children)
        $map = [];
        foreach ($collected as $f) {
            $map[$f->id] = [
                'folder_id' => $f->id,
                'folder_name' => $f->folder_name,
                'children' => [],
                'fol_folder_id' => $f->fol_folder_id,
            ];
        }

        // 4) attach children to parents when available; missing parents => treat as root
        $roots = [];
        foreach ($map as $id => $node) {
            $parentId = $node['fol_folder_id'];
            if ($parentId === null || ! isset($map[$parentId])) {
                $roots[] = &$map[$id];
            } else {
                $map[$parentId]['children'][] = &$map[$id];
            }
        }

        // 5) cleanup internal keys
        $clean = function (&$nodes) use (&$clean) {
            foreach ($nodes as &$n) {
                if (array_key_exists('fol_folder_id', $n)) {
                    unset($n['fol_folder_id']);
                }
                if (! empty($n['children'])) {
                    $clean($n['children']);
                }
            }
        };

        $clean($roots);

        return ['folders' => $roots];
    }
}


